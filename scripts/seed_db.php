<?php
/**
 * Database seed script — runs as Railway preDeployCommand.
 *
 * Copies seed/blog.db to the Volume path ONLY if the Volume DB does not yet
 * exist or contains no posts. This ensures existing Railway data is never
 * overwritten on redeploy, while a fresh deployment gets the committed posts.
 */

$seedPath   = __DIR__ . '/../seed/blog.db';
$volumePath = getenv('DB_PATH') ?: '/app/data/blog.db';

echo "[seed_db] Seed path:   {$seedPath}\n";
echo "[seed_db] Volume path: {$volumePath}\n";

if (!file_exists($seedPath)) {
    echo "[seed_db] No seed file found — skipping.\n";
    exit(0);
}

// Ensure the target directory exists
$targetDir = dirname($volumePath);
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
    echo "[seed_db] Created directory: {$targetDir}\n";
}

// Check if volume DB already has posts
if (file_exists($volumePath) && filesize($volumePath) > 4096) {
    try {
        $pdo   = new PDO('sqlite:' . $volumePath);
        $count = (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
        if ($count > 0) {
            echo "[seed_db] Volume DB already has {$count} post(s) — skipping seed.\n";
            exit(0);
        }
    } catch (Throwable $e) {
        echo "[seed_db] Could not read volume DB ({$e->getMessage()}) — will seed.\n";
    }
}

// Copy seed DB to the volume path
if (copy($seedPath, $volumePath)) {
    $pdo   = new PDO('sqlite:' . $volumePath);
    $count = (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    echo "[seed_db] Seeded successfully — {$count} post(s) copied to volume.\n";
} else {
    echo "[seed_db] ERROR: Failed to copy seed DB.\n";
    exit(1);
}
