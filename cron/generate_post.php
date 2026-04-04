<?php
/**
 * CLI post generation script.
 * Usage: php cron/generate_post.php
 */

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
require_once __DIR__ . '/../src/TopicSelector.php';

$interval = (int)(getenv('POST_INTERVAL') ?: 300);

function log_msg(string $msg): void
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
}

try {
    $db       = new Database();
    $lastPost = $db->getLastPostTime();

    if ($lastPost !== null) {
        $secondsSince = time() - strtotime($lastPost);
        if ($secondsSince < $interval) {
            log_msg("Skipping — last post was {$secondsSince}s ago. Next in " . ($interval - $secondsSince) . 's.');
            exit(0);
        }
    }

    log_msg('Starting post generation...');

    $recentPosts      = $db->getRecentSlugsAndTitles(60);
    $recentTitles     = array_column($recentPosts, 'title');
    $recentSlugs      = array_column($recentPosts, 'slug');
    $recentTopics     = $db->getRecentTopicSlugs(20);
    $recentBooks      = $db->getRecentlyUsedBooks(20);

    $topic     = TopicSelector::selectTopic($recentTopics);
    $generator = new BlogGenerator();

    log_msg("Selected topic: [{$topic['slug']}] {$topic['subtopic']}");

    $attempts = 0;
    $maxTries = 3;

    while ($attempts < $maxTries) {
        $attempts++;
        log_msg("Attempt {$attempts}/{$maxTries} — calling Claude API...");

        try {
            $post = $generator->generatePost($recentTitles, $recentBooks, $topic);
        } catch (RuntimeException $e) {
            log_msg("Claude error on attempt {$attempts}: " . $e->getMessage());
            if ($attempts >= $maxTries) throw $e;
            sleep(5);
            continue;
        }

        if (in_array($post['slug'], $recentSlugs, true) || $db->slugExists($post['slug'])) {
            $post['slug'] .= '-' . date('YmdHis');
            log_msg("Slug collision — appended timestamp: {$post['slug']}");
        }

        $db->insertPost($post);
        log_msg("Post saved: \"{$post['title']}\" (slug: {$post['slug']})");
        log_msg("Books used: " . implode(', ', $post['used_books']));
        break;
    }

} catch (Throwable $e) {
    log_msg('FATAL: ' . $e->getMessage());
    log_msg($e->getTraceAsString());
    exit(1);
}

exit(0);
