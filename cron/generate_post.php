<?php
/**
 * Post generation cron script.
 *
 * Railway runs this on a schedule (e.g. every 5 min for testing).
 * The script itself enforces the interval guard so duplicate runs are harmless.
 *
 * Usage: php cron/generate_post.php
 */

// Load .env if present (for local dev — on Railway, vars come from the dashboard)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($val));
    }
}

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/BlogGenerator.php';

// How many seconds must pass before generating the next post
$interval = (int)(getenv('POST_INTERVAL') ?: 300); // default: 5 minutes

function log_msg(string $msg): void
{
    $ts = date('Y-m-d H:i:s');
    echo "[{$ts}] {$msg}" . PHP_EOL;
}

try {
    $db        = new Database();
    $lastPost  = $db->getLastPostTime();

    if ($lastPost !== null) {
        $secondsSince = time() - strtotime($lastPost);
        if ($secondsSince < $interval) {
            $remaining = $interval - $secondsSince;
            log_msg("Skipping — last post was {$secondsSince}s ago. Next post in {$remaining}s.");
            exit(0);
        }
    }

    log_msg("Starting post generation...");

    $generator   = new BlogGenerator();
    $recentPosts = $db->getRecentSlugsAndTitles(60);
    $titles      = array_column($recentPosts, 'title');
    $slugs       = array_column($recentPosts, 'slug');

    $attempts = 0;
    $maxTries = 3;

    while ($attempts < $maxTries) {
        $attempts++;
        log_msg("Attempt {$attempts}/{$maxTries} — calling Claude API...");

        try {
            $post = $generator->generatePost($titles);
        } catch (RuntimeException $e) {
            log_msg("Claude error on attempt {$attempts}: " . $e->getMessage());
            if ($attempts >= $maxTries) throw $e;
            sleep(5);
            continue;
        }

        // Ensure unique slug
        if (in_array($post['slug'], $slugs, true) || $db->slugExists($post['slug'])) {
            $post['slug'] = $post['slug'] . '-' . date('YmdHis');
            log_msg("Slug collision detected — appended timestamp: {$post['slug']}");
        }

        $db->insertPost($post);
        log_msg("Post saved: \"{$post['title']}\" (slug: {$post['slug']})");
        break;
    }

} catch (Throwable $e) {
    log_msg("FATAL: " . $e->getMessage());
    log_msg($e->getTraceAsString());
    exit(1);
}

exit(0);
