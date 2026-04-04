<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../data/blog.db', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$posts = $pdo->query('SELECT slug, affiliate_html FROM posts')->fetchAll();
foreach ($posts as $p) {
    echo '=== ' . $p['slug'] . PHP_EOL;
    echo $p['affiliate_html'] . PHP_EOL . PHP_EOL;
}
