<?php
/**
 * One-time repair tool — patches posts with missing affiliate_html from seed.
 * Protected by CRON_TOKEN. Hit this URL once on Railway, then delete the file.
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

$dbPath   = getenv('DB_PATH') ?: __DIR__ . '/../data/blog.db';
$seedFile = __DIR__ . '/../seed/posts.json';

echo "<pre>\n";
echo "DB path:   $dbPath\n";
echo "Seed file: $seedFile\n\n";

if (!file_exists($seedFile)) {
    die("ERROR: seed/posts.json not found.\n</pre>");
}

$posts = json_decode(file_get_contents($seedFile), true);
if (!$posts) {
    die("ERROR: Could not parse seed/posts.json.\n</pre>");
}

echo "Seed contains " . count($posts) . " post(s).\n\n";

$pdo = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Show current state
$current = $pdo->query("SELECT slug, length(affiliate_html) as aff_len FROM posts")->fetchAll(PDO::FETCH_ASSOC);
echo "Current DB posts:\n";
foreach ($current as $row) {
    echo "  [{$row['slug']}] affiliate_html length: {$row['aff_len']}\n";
}
echo "\n";

// Apply patch
$updated = 0;
$stmt = $pdo->prepare("UPDATE posts SET affiliate_html = :html WHERE slug = :slug");

foreach ($posts as $post) {
    if (empty($post['affiliate_html'])) continue;
    $stmt->execute([':html' => $post['affiliate_html'], ':slug' => $post['slug']]);
    $rows = $stmt->rowCount();
    echo "  Patched slug [{$post['slug']}]: {$rows} row(s) updated.\n";
    $updated += $rows;
}

echo "\nDone. $updated post(s) updated.\n";

// Verify
$verify = $pdo->query("SELECT slug, length(affiliate_html) as aff_len FROM posts")->fetchAll(PDO::FETCH_ASSOC);
echo "\nVerification:\n";
foreach ($verify as $row) {
    $status = $row['aff_len'] > 0 ? "OK ({$row['aff_len']} chars)" : "STILL EMPTY";
    echo "  [{$row['slug']}] $status\n";
}

echo "\nALL DONE. You can now delete this file.\n</pre>";
