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
function formatDate(string $dt): string { return date('M j, Y', strtotime($dt)); }
function topicLabel(string $slug): string {
    $map = [
        'scanner-noise-false-positives'              => 'Scanner Noise',
        'unreachable-theoretical-vulnerabilities'    => 'Attack Surface',
        'enterprise-frameworks-too-complex'          => 'Frameworks',
        'merged-it-security-roles'                   => 'Governance',
        'translating-risk-to-financial-terms'        => 'Risk Quantification',
        'overpriced-automated-scans-as-pentests'     => 'Pentesting',
        'vanity-metrics-checkbox-compliance'         => 'Compliance',
        'tool-overlap-security-as-product'           => 'Security Stack',
        'asset-visibility-configuration-awareness'   => 'Asset Visibility',
        'self-resolving-non-exploited-vulnerabilities' => 'Patch Management',
    ];
    return $map[$slug] ?? 'Cybersecurity';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SMB CyberSecurity — Practical Security for Growing Businesses</title>
  <meta name="description" content="Practical cybersecurity strategies for small and medium businesses. Vulnerability detection, risk assessment, and real-world security guidance.">
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>

  <!-- ── Header ─────────────────────────────────────────────────────────── -->
  <header class="site-header">
    <div class="container">
      <a href="/" class="site-logo">
        <span class="logo-shield">🛡️</span>
        SMB CyberSecurity
      </a>
      <nav class="site-nav">
        <a href="/" class="active">Blog</a>
        <span class="header-byline">by Ramzi Naouali &amp; Elmehdi Hamoujate</span>
      </nav>
    </div>
  </header>

  <!-- ── Hero ───────────────────────────────────────────────────────────── -->
  <section class="hero">
    <div class="container">
      <div class="hero-inner">
        <span class="hero-badge">🔐 Security Intelligence</span>
        <h1>Stop <span>Hackers</span> Before<br>They Stop You</h1>
        <p>Practical, no-nonsense cybersecurity strategies for small and medium businesses. Real threats, real fixes — no enterprise budget required.</p>
        <div class="hero-stats">
          <div class="hero-stat">
            <div class="num"><?= $total ?>+</div>
            <div class="label">Articles</div>
          </div>
          <div class="hero-stat">
            <div class="num">10</div>
            <div class="label">Topic Areas</div>
          </div>
          <div class="hero-stat">
            <div class="num">Free</div>
            <div class="label">Always</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Posts ──────────────────────────────────────────────────────────── -->
  <main>
    <div class="container">
      <section class="posts-section">

        <?php if (empty($posts)): ?>
          <div class="empty-state">
            <div class="empty-icon">⚙️</div>
            <h2>First post generating…</h2>
            <p>Our AI is writing the first article. Check back in a couple of minutes.</p>
          </div>

        <?php else: ?>
          <h2 class="section-heading">
            <?php if ($page > 1): ?>
              Page <?= $page ?> of <?= $pages ?>
            <?php else: ?>
              Latest Articles
            <?php endif; ?>
          </h2>

          <div class="post-grid">
            <?php foreach ($posts as $post): ?>
              <?php
                $slug  = h($post['slug']);
                $topic = $post['topic_slug'] ?? '';
              ?>
              <article class="post-card" <?= $topic ? 'data-topic="' . h($topic) . '"' : '' ?>>
                <div class="card-accent"></div>
                <div class="card-body">
                  <div class="card-meta">
                    <time class="card-date" datetime="<?= h($post['created_at']) ?>">
                      <?= formatDate($post['created_at']) ?>
                    </time>
                    <span class="card-tag"><?= h(topicLabel($topic)) ?></span>
                  </div>
                  <a href="/post.php?slug=<?= $slug ?>" class="card-title">
                    <?= h($post['title']) ?>
                  </a>
                  <p class="card-excerpt"><?= h(mb_strimwidth($post['excerpt'], 0, 140, '…')) ?></p>
                  <div class="card-footer">
                    <a href="/post.php?slug=<?= $slug ?>" class="card-cta">
                      Read article <span>→</span>
                    </a>
                    <span class="card-shield">🔒</span>
                  </div>
                </div>
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
                <a href="/?page=<?= $page + 1 ?>" class="next-link">Next »</a>
              <?php endif; ?>
            </nav>
          <?php endif; ?>

        <?php endif; ?>
      </section>
    </div>
  </main>

  <!-- ── Footer ─────────────────────────────────────────────────────────── -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-inner">
        <div class="footer-logo">🛡️ SMB CyberSecurity</div>
        <div class="footer-links">
          <a href="/">Blog</a>
        </div>
        <p class="footer-copy">© <?= date('Y') ?> SMB CyberSecurity · Founded by Ramzi Naouali &amp; Elmehdi Hamoujate · Some links are affiliate links.</p>
      </div>
    </div>
  </footer>

</body>
</html>
