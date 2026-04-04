<?php
/**
 * Web-facing cron endpoint for cron-job.org (or any HTTP-based scheduler).
 * Returns 200 immediately, then generates the post after the connection closes.
 *
 * Call as: GET /cron.php?token=<CRON_TOKEN>
 *      or: Header X-Cron-Token: <CRON_TOKEN>
 */

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
$providedToken = $_GET['token'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? '';

if ($expectedToken !== '' && !hash_equals($expectedToken, $providedToken)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ── Send 200 immediately so the scheduler never times out ──────────────────
http_response_code(200);
header('Content-Type: application/json');
header('Connection: close');

$responseBody = json_encode(['status' => 'accepted', 'time' => date('Y-m-d H:i:s')]);
header('Content-Length: ' . strlen($responseBody));
echo $responseBody;

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    if (ob_get_level() > 0) ob_end_flush();
    flush();
}

// ── Generate post after connection is closed ───────────────────────────────
ignore_user_abort(true);
set_time_limit(300);

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/BlogGenerator.php';
require_once __DIR__ . '/../src/TopicSelector.php';

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

    log_web('Starting post generation...');

    $recentPosts  = $db->getRecentSlugsAndTitles(60);
    $recentTitles = array_column($recentPosts, 'title');
    $recentSlugs  = array_column($recentPosts, 'slug');
    $recentTopics = $db->getRecentTopicSlugs(20);
    $recentBooks  = $db->getRecentlyUsedBooks(20);

    $topic     = TopicSelector::selectTopic($recentTopics);
    $generator = new BlogGenerator();

    log_web("Selected topic: [{$topic['slug']}] {$topic['subtopic']}");

    $attempts = 0;
    $maxTries = 3;

    while ($attempts < $maxTries) {
        $attempts++;
        log_web("Attempt {$attempts}/{$maxTries} — calling Claude API...");

        try {
            $post = $generator->generatePost($recentTitles, $recentBooks, $topic);
        } catch (RuntimeException $e) {
            log_web("Claude error on attempt {$attempts}: " . $e->getMessage());
            if ($attempts >= $maxTries) throw $e;
            sleep(5);
            continue;
        }

        if (in_array($post['slug'], $recentSlugs, true) || $db->slugExists($post['slug'])) {
            $post['slug'] .= '-' . date('YmdHis');
            log_web("Slug collision — appended timestamp: {$post['slug']}");
        }

        $db->insertPost($post);
        log_web("Post saved: \"{$post['title']}\" (slug: {$post['slug']})");
        log_web('Books used: ' . implode(', ', $post['used_books']));
        break;
    }

} catch (Throwable $e) {
    log_web('FATAL: ' . $e->getMessage());
}
