<?php
/**
 * Web-facing cron endpoint for cron-job.org (or any HTTP-based scheduler).
 *
 * Returns 200 immediately so the scheduler never times out, then generates
 * the post in the same process after the connection is closed.
 *
 * Secure with CRON_TOKEN env var. Call as:
 *   GET /cron.php?token=<CRON_TOKEN>
 *   or Header:  X-Cron-Token: <CRON_TOKEN>
 */

// ── Load .env (local dev only; Railway injects vars directly) ──────────────
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($val));
    }
}

// ── Token auth ─────────────────────────────────────────────────────────────
$expectedToken = getenv('CRON_TOKEN') ?: '';
$providedToken = $_GET['token']
    ?? $_SERVER['HTTP_X_CRON_TOKEN']
    ?? '';

if ($expectedToken !== '' && !hash_equals($expectedToken, $providedToken)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ── Send 200 immediately so cron-job.org doesn't time out ──────────────────
http_response_code(200);
header('Content-Type: application/json');
header('Connection: close');

$responseBody = json_encode(['status' => 'accepted', 'time' => date('Y-m-d H:i:s')]);
header('Content-Length: ' . strlen($responseBody));

echo $responseBody;

// Flush response to client before continuing
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    if (ob_get_level() > 0) ob_end_flush();
    flush();
}

// ── Generate post in background (client already received 200) ──────────────
ignore_user_abort(true);
set_time_limit(300);

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/BlogGenerator.php';

$interval = (int)(getenv('POST_INTERVAL') ?: 300);

function log_web(string $msg): void
{
    error_log('[cron.php] ' . $msg);
}

try {
    $db       = new Database();
    $lastPost = $db->getLastPostTime();

    if ($lastPost !== null) {
        $secondsSince = time() - strtotime($lastPost);
        if ($secondsSince < $interval) {
            log_web("Skipping — last post was {$secondsSince}s ago.");
            exit(0);
        }
    }

    log_web("Starting post generation...");

    $generator   = new BlogGenerator();
    $recentPosts = $db->getRecentSlugsAndTitles(60);
    $titles      = array_column($recentPosts, 'title');
    $slugs       = array_column($recentPosts, 'slug');

    $attempts = 0;
    $maxTries = 3;

    while ($attempts < $maxTries) {
        $attempts++;
        log_web("Attempt {$attempts}/{$maxTries} — calling Claude API...");

        try {
            $post = $generator->generatePost($titles);
        } catch (RuntimeException $e) {
            log_web("Claude error on attempt {$attempts}: " . $e->getMessage());
            if ($attempts >= $maxTries) throw $e;
            sleep(5);
            continue;
        }

        if (in_array($post['slug'], $slugs, true) || $db->slugExists($post['slug'])) {
            $post['slug'] .= '-' . date('YmdHis');
            log_web("Slug collision — appended timestamp: {$post['slug']}");
        }

        $db->insertPost($post);
        log_web("Post saved: \"{$post['title']}\" (slug: {$post['slug']})");
        break;
    }

} catch (Throwable $e) {
    log_web("FATAL: " . $e->getMessage());
}
