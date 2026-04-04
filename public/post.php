<?php
require_once __DIR__ . '/../src/Database.php';

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($val));
    }
}

$db   = new Database();
$slug = trim($_GET['slug'] ?? '');

if ($slug === '') { header('Location: /'); exit; }

$post = $db->getPost($slug);
if (!$post) { http_response_code(404); }

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function formatDate(string $dt): string { return date('F j, Y', strtotime($dt)); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php if ($post): ?>
    <title><?= h($post['title']) ?> &mdash; SMB CyberSecurity</title>
    <meta name="description" content="<?= h($post['excerpt']) ?>">
  <?php else: ?>
    <title>Post Not Found &mdash; SMB CyberSecurity</title>
  <?php endif; ?>
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>

  <header class="site-header">
    <div class="container">
      <a href="/" class="site-logo">&#128274; SMB CyberSecurity</a>
      <nav class="site-nav">
        <a href="/">&#128221; Blog</a>
      </nav>
    </div>
  </header>

  <main class="container">

    <?php if (!$post): ?>

      <div class="error-state">
        <h1>Post not found</h1>
        <p>This post doesn't exist or may have been removed.</p>
        <a href="/">&larr; Back to Blog</a>
      </div>

    <?php else: ?>

      <article class="single-post">

        <a href="/" class="post-back">&larr; Blog</a>

        <h1 class="post-title-single"><?= h($post['title']) ?></h1>
        <div class="post-date-single">
          <time datetime="<?= h($post['created_at']) ?>"><?= formatDate($post['created_at']) ?></time>
        </div>

        <div class="post-content">
          <?= $post['content_html'] ?>
        </div>

        <?php if (!empty($post['affiliate_html'])): ?>
          <aside class="affiliate-section">
            <h2 class="affiliate-section-title">&#128218; Recommended Resources</h2>
            <div class="affiliate-list">
              <?= $post['affiliate_html'] ?>
            </div>
          </aside>
        <?php endif; ?>

        <nav class="post-nav">
          <a href="/">&larr; Back to all posts</a>
        </nav>

      </article>

    <?php endif; ?>
  </main>

  <footer class="site-footer">
    <div class="container">
      <div class="footer-inner">
        <span class="footer-brand">SMB CyberSecurity</span>
        <div class="footer-links">
          <a href="/">Blog Home</a>
        </div>
        <span class="footer-copy">&copy; <?= date('Y') ?> SMB CyberSecurity. Posts generated with AI. Some links are affiliate links.</span>
      </div>
    </div>
  </footer>

</body>
</html>
