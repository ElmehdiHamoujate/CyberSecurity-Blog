<?php

class Database
{
    private PDO $pdo;

    public function __construct()
    {
        $dbPath = getenv('DB_PATH') ?: __DIR__ . '/../data/blog.db';

        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec('PRAGMA journal_mode=WAL;');
        $this->migrate();
    }

    private function migrate(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS posts (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                title         TEXT    NOT NULL,
                slug          TEXT    UNIQUE NOT NULL,
                excerpt       TEXT    NOT NULL,
                content_html  TEXT    NOT NULL,
                affiliate_html TEXT   NOT NULL DEFAULT '',
                created_at    DATETIME DEFAULT (datetime('now'))
            );

            CREATE INDEX IF NOT EXISTS idx_posts_created ON posts(created_at DESC);
            CREATE INDEX IF NOT EXISTS idx_posts_slug    ON posts(slug);
        ");
    }

    public function getPost(string $slug): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM posts WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    public function getPosts(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $stmt   = $this->pdo->prepare(
            'SELECT id, title, slug, excerpt, created_at FROM posts ORDER BY created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$perPage, $offset]);
        return $stmt->fetchAll();
    }

    public function countPosts(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    }

    public function insertPost(array $data): bool
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO posts (title, slug, excerpt, content_html, affiliate_html)
            VALUES (:title, :slug, :excerpt, :content_html, :affiliate_html)
        ');
        return $stmt->execute([
            ':title'         => $data['title'],
            ':slug'          => $data['slug'],
            ':excerpt'       => $data['excerpt'],
            ':content_html'  => $data['content_html'],
            ':affiliate_html'=> $data['affiliate_html'],
        ]);
    }

    public function slugExists(string $slug): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM posts WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        return (bool) $stmt->fetchColumn();
    }

    public function getRecentSlugsAndTitles(int $limit = 60): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT slug, title FROM posts ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function getLastPostTime(): ?string
    {
        $result = $this->pdo->query(
            "SELECT created_at FROM posts ORDER BY created_at DESC LIMIT 1"
        )->fetchColumn();

        return $result ?: null;
    }
}
