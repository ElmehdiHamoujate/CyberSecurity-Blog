<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../data/blog.db');
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$posts = $pdo->query('SELECT * FROM posts ORDER BY created_at ASC')->fetchAll();
file_put_contents(
    __DIR__ . '/../seed/posts.json',
    json_encode($posts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);
echo count($posts) . ' posts exported to seed/posts.json' . PHP_EOL;
