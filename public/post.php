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

/* ── Affiliate fallback: load from seed if DB column is empty ────────────── */
if ($post && empty($post['affiliate_html'])) {
    $seedFile = __DIR__ . '/../seed/posts.json';
    if (file_exists($seedFile)) {
        $seedPosts = json_decode(file_get_contents($seedFile), true) ?: [];
        foreach ($seedPosts as $sp) {
            if (($sp['slug'] ?? '') === $slug && !empty($sp['affiliate_html'])) {
                $post['affiliate_html'] = $sp['affiliate_html'];
                break;
            }
        }
    }
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function formatDate(string $dt): string { return date('F j, Y', strtotime($dt)); }
function topicLabel(string $slug): string {
    $map = [
        'scanner-noise-false-positives'                => 'Scanner Noise & False Positives',
        'unreachable-theoretical-vulnerabilities'      => 'Attack Surface Analysis',
        'enterprise-frameworks-too-complex'            => 'Security Frameworks',
        'merged-it-security-roles'                     => 'Governance & Roles',
        'translating-risk-to-financial-terms'          => 'Risk Quantification',
        'overpriced-automated-scans-as-pentests'       => 'Penetration Testing',
        'vanity-metrics-checkbox-compliance'           => 'Compliance & Metrics',
        'tool-overlap-security-as-product'             => 'Security Architecture',
        'asset-visibility-configuration-awareness'     => 'Asset Visibility',
        'self-resolving-non-exploited-vulnerabilities' => 'Patch Management',
    ];
    return $map[$slug] ?? 'Cybersecurity';
}

/* gradient per topic for the hero */
function topicGradient(string $slug): string {
    $map = [
        'scanner-noise-false-positives'                => 'linear-gradient(135deg,#b91c1c,#ea580c)',
        'unreachable-theoretical-vulnerabilities'      => 'linear-gradient(135deg,#6d28d9,#a855f7)',
        'enterprise-frameworks-too-complex'            => 'linear-gradient(135deg,#0369a1,#0891b2)',
        'merged-it-security-roles'                     => 'linear-gradient(135deg,#0891b2,#0d9488)',
        'translating-risk-to-financial-terms'          => 'linear-gradient(135deg,#059669,#16a34a)',
        'overpriced-automated-scans-as-pentests'       => 'linear-gradient(135deg,#991b1b,#dc2626)',
        'vanity-metrics-checkbox-compliance'           => 'linear-gradient(135deg,#b45309,#d97706)',
        'tool-overlap-security-as-product'             => 'linear-gradient(135deg,#4338ca,#7c3aed)',
        'asset-visibility-configuration-awareness'     => 'linear-gradient(135deg,#0c4a6e,#0369a1)',
        'self-resolving-non-exploited-vulnerabilities' => 'linear-gradient(135deg,#064e3b,#059669)',
    ];
    return $map[$slug] ?? 'linear-gradient(135deg,#1e3a5f,#1d4ed8)';
}

$topicSlug     = $post['topic_slug'] ?? '';
$heroGradient  = topicGradient($topicSlug);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php if ($post): ?>
    <title><?= h($post['title']) ?> — SMB CyberSecurity</title>
    <meta name="description" content="<?= h($post['excerpt']) ?>">
  <?php else: ?>
    <title>Post Not Found — SMB CyberSecurity</title>
  <?php endif; ?>
  <link rel="stylesheet" href="/assets/style.css">
  <?php if ($post): ?>
  <style>
    .post-hero { background: <?= $heroGradient ?>; }
    .post-hero::before { display: none; }
    .post-hero-accent { background: rgba(0,0,0,.25); }
  </style>
  <?php endif; ?>
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
        <a href="/">Blog</a>
        <span class="header-byline">by Ramzi Naouali &amp; Elmehdi Hamoujate</span>
      </nav>
    </div>
  </header>

  <?php if (!$post): ?>

    <!-- ── 404 ───────────────────────────────────────────────────────────── -->
    <main>
      <div class="container">
        <div class="error-state">
          <h1>Post not found</h1>
          <p>This post doesn't exist or may have been removed.</p>
          <a href="/" class="post-back">← Back to Blog</a>
        </div>
      </div>
    </main>

  <?php else: ?>

    <!-- ── Post hero ──────────────────────────────────────────────────────── -->
    <section class="post-hero">
      <div class="post-hero-accent"></div>
      <div class="container">
        <div class="post-hero-inner">
          <nav class="post-breadcrumb">
            <a href="/">Home</a>
            <span class="post-breadcrumb-sep">/</span>
            <a href="/">Blog</a>
            <span class="post-breadcrumb-sep">/</span>
            <span><?= h(mb_strimwidth($post['title'], 0, 40, '…')) ?></span>
          </nav>
          <div class="post-hero-meta">
            <time class="post-hero-date" datetime="<?= h($post['created_at']) ?>">
              <?= formatDate($post['created_at']) ?>
            </time>
            <?php if ($topicSlug): ?>
              <span class="post-hero-tag"><?= h(topicLabel($topicSlug)) ?></span>
            <?php endif; ?>
          </div>
          <h1 class="post-hero-title"><?= h($post['title']) ?></h1>
          <?php if (!empty($post['excerpt'])): ?>
            <p class="post-hero-excerpt"><?= h($post['excerpt']) ?></p>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- ── Post body ──────────────────────────────────────────────────────── -->
    <main>
      <div class="container">
        <div class="post-back-bar">
          <a href="/" class="post-back">← All Articles</a>
        </div>

        <div class="post-layout">

          <div class="post-content">
            <?= $post['content_html'] ?>
          </div>

          <?php if (!empty($post['affiliate_html'])): ?>
            <aside class="affiliate-section">
              <div class="affiliate-heading">
                📚 Recommended Resources
              </div>
              <p class="affiliate-subtext">Curated books and tools related to this topic — selected by our AI.</p>
              <div class="affiliate-list">
                <?= $post['affiliate_html'] ?>
              </div>
            </aside>
          <?php endif; ?>

        </div>
      </div>
    </main>

  <?php endif; ?>

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
