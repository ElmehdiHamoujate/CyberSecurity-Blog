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

$db      = new Database();
$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$total   = $db->countPosts();
$posts   = $db->getPosts($page, $perPage);
$pages   = (int)ceil($total / $perPage);

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function formatDate(string $dt): string { return date('F j, Y', strtotime($dt)); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SMB CyberSecurity Blog</title>
  <meta name="description" content="Practical cybersecurity strategies for small and medium businesses. Vulnerability detection, risk assessment, and real-world security guidance.">
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>

  <header class="site-header">
    <div class="container">
      <a href="/" class="site-logo">&#128274; SMB CyberSecurity</a>
      <nav class="site-nav">
        <a href="/" class="active">&#128221; Blog</a>
      </nav>
    </div>
  </header>

  <main class="container">

    <h1 class="page-heading">Blog</h1>

    <?php if (empty($posts)): ?>
      <div class="empty-state">
        <p>The first post is being generated. Check back in a few minutes.</p>
      </div>
    <?php else: ?>

      <div class="post-list">
        <?php foreach ($posts as $post): ?>
          <article class="post-item">
            <h2><a href="/post.php?slug=<?= h($post['slug']) ?>"><?= h($post['title']) ?></a></h2>
            <div class="post-date"><time datetime="<?= h($post['created_at']) ?>"><?= formatDate($post['created_at']) ?></time></div>
            <p class="post-excerpt"><?= h($post['excerpt']) ?></p>
            <a href="/post.php?slug=<?= h($post['slug']) ?>" class="read-more">Read More &rarr;</a>
          </article>
        <?php endforeach; ?>
      </div>

      <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Pagination">
          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <?php if ($i === $page): ?>
              <span class="current"><?= $i ?></span>
            <?php else: ?>
              <a href="/?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          <?php if ($page < $pages): ?>
            <a href="/?page=<?= $page + 1 ?>" class="next-link">Next &raquo;</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>

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
