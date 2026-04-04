<?php
/**
 * One-time repair — patches posts with missing affiliate_html from seed.
 * Uses the same Database class the blog uses, so the DB path is always correct.
 *
 * Usage: /fix_affiliates.php?token=<CRON_TOKEN>
 */

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($val));
    }
}

$expectedToken = getenv('CRON_TOKEN') ?: '';
$providedToken = $_GET['token'] ?? '';
if ($expectedToken !== '' && !hash_equals($expectedToken, $providedToken)) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../src/Database.php';

$seedFile = __DIR__ . '/../seed/posts.json';

echo "<pre>\n";
echo "DB_PATH env:  " . (getenv('DB_PATH') ?: '(not set, using fallback)') . "\n";
echo "Seed file:    $seedFile\n\n";

if (!file_exists($seedFile)) {
    die("ERROR: seed/posts.json not found at $seedFile\n</pre>");
}

$posts = json_decode(file_get_contents($seedFile), true);
if (!$posts) {
    die("ERROR: Could not parse seed/posts.json\n</pre>");
}
echo "Seed contains " . count($posts) . " post(s).\n\n";

// Use the same DB connection the blog uses
$db  = new Database();
$pdo = (function() use ($db) {
    $r = new ReflectionProperty($db, 'pdo');
    $r->setAccessible(true);
    return $r->getValue($db);
})();

// Show current state
$current = $pdo->query("SELECT slug, length(affiliate_html) as aff_len FROM posts")->fetchAll(PDO::FETCH_ASSOC);
echo "Current DB posts:\n";
foreach ($current as $row) {
    echo "  [{$row['slug']}] affiliate_html length: {$row['aff_len']}\n";
}
echo "\n";

// Force-update affiliate_html for every post in the seed
$stmt    = $pdo->prepare("UPDATE posts SET affiliate_html = :html WHERE slug = :slug");
$updated = 0;

foreach ($posts as $post) {
    if (empty($post['affiliate_html'])) continue;
    $stmt->execute([':html' => $post['affiliate_html'], ':slug' => $post['slug']]);
    $rows = $stmt->rowCount();
    echo "  [{$post['slug']}]: {$rows} row(s) updated.\n";
    $updated += $rows;
}

echo "\nTotal updated: $updated\n\n";

// Verify
$verify = $pdo->query("SELECT slug, length(affiliate_html) as aff_len FROM posts")->fetchAll(PDO::FETCH_ASSOC);
echo "Verification:\n";
foreach ($verify as $row) {
    $ok = $row['aff_len'] > 0 ? "OK ({$row['aff_len']} chars)" : "STILL EMPTY";
    echo "  [{$row['slug']}] $ok\n";
}

echo "\nDone. Reload any post page to see the Amazon buttons.\n</pre>";
