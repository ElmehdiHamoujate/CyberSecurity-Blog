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
        // Base table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS posts (
                id             INTEGER  PRIMARY KEY AUTOINCREMENT,
                title          TEXT     NOT NULL,
                slug           TEXT     UNIQUE NOT NULL,
                excerpt        TEXT     NOT NULL,
                content_html   TEXT     NOT NULL,
                affiliate_html TEXT     NOT NULL DEFAULT '',
                created_at     DATETIME DEFAULT (datetime('now'))
            );

            CREATE INDEX IF NOT EXISTS idx_posts_created ON posts(created_at DESC);
            CREATE INDEX IF NOT EXISTS idx_posts_slug    ON posts(slug);
        ");

        // Additive migrations — safe to run on existing DBs
        foreach ([
            "ALTER TABLE posts ADD COLUMN topic_slug  TEXT NOT NULL DEFAULT ''",
            "ALTER TABLE posts ADD COLUMN used_books  TEXT NOT NULL DEFAULT '[]'",
        ] as $sql) {
            try {
                $this->pdo->exec($sql);
            } catch (PDOException) {
                // Column already exists — ignore
            }
        }

        $this->seedIfEmpty();
    }

    /**
     * If the posts table is empty and a seed/posts.json file exists in the
     * project root, import those posts. Runs on every startup but only
     * inserts when the table is truly empty — safe to leave in production.
     */
    private function seedIfEmpty(): void
    {
        $seedFile = __DIR__ . '/../seed/posts.json';
        if (!file_exists($seedFile)) {
            return;
        }

        $posts = json_decode(file_get_contents($seedFile), true);
        if (empty($posts) || !is_array($posts)) {
            return;
        }

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();

        if ($count === 0) {
            // Table is empty — do a full seed
            $insert = $this->pdo->prepare('
                INSERT OR IGNORE INTO posts
                    (title, slug, excerpt, content_html, affiliate_html, topic_slug, used_books, created_at)
                VALUES
                    (:title, :slug, :excerpt, :content_html, :affiliate_html, :topic_slug, :used_books, :created_at)
            ');

            foreach ($posts as $post) {
                $insert->execute([
                    ':title'          => $post['title']          ?? '',
                    ':slug'           => $post['slug']           ?? '',
                    ':excerpt'        => $post['excerpt']        ?? '',
                    ':content_html'   => $post['content_html']   ?? '',
                    ':affiliate_html' => $post['affiliate_html'] ?? '',
                    ':topic_slug'     => $post['topic_slug']     ?? '',
                    ':used_books'     => $post['used_books']     ?? '[]',
                    ':created_at'     => $post['created_at']     ?? date('Y-m-d H:i:s'),
                ]);
            }
        } else {
            // Table has posts — patch any rows with missing affiliate_html from seed
            $update = $this->pdo->prepare('
                UPDATE posts
                SET affiliate_html = :affiliate_html,
                    topic_slug     = CASE WHEN topic_slug = "" THEN :topic_slug ELSE topic_slug END,
                    used_books     = CASE WHEN used_books = "[]" THEN :used_books ELSE used_books END
                WHERE slug = :slug
                  AND (affiliate_html = "" OR affiliate_html IS NULL)
            ');

            foreach ($posts as $post) {
                if (empty($post['affiliate_html'])) continue;
                $update->execute([
                    ':slug'          => $post['slug'],
                    ':affiliate_html'=> $post['affiliate_html'],
                    ':topic_slug'    => $post['topic_slug'] ?? '',
                    ':used_books'    => $post['used_books'] ?? '[]',
                ]);
            }
        }
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
            INSERT INTO posts (title, slug, excerpt, content_html, affiliate_html, topic_slug, used_books)
            VALUES (:title, :slug, :excerpt, :content_html, :affiliate_html, :topic_slug, :used_books)
        ');
        return $stmt->execute([
            ':title'          => $data['title'],
            ':slug'           => $data['slug'],
            ':excerpt'        => $data['excerpt'],
            ':content_html'   => $data['content_html'],
            ':affiliate_html' => $data['affiliate_html'],
            ':topic_slug'     => $data['topic_slug']  ?? '',
            ':used_books'     => json_encode($data['used_books'] ?? []),
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

    public function getRecentTopicSlugs(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT topic_slug FROM posts WHERE topic_slug != '' ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$limit]);
        return array_column($stmt->fetchAll(), 'topic_slug');
    }

    /**
     * Returns a flat list of book titles used in the most recent $limit posts.
     */
    public function getRecentlyUsedBooks(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT used_books FROM posts WHERE used_books != '[]' ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$limit]);
        $rows  = $stmt->fetchAll();
        $books = [];
        foreach ($rows as $row) {
            $decoded = json_decode($row['used_books'], true);
            if (is_array($decoded)) {
                $books = array_merge($books, $decoded);
            }
        }
        return array_values(array_unique($books));
    }

    public function getLastPostTime(): ?string
    {
        $result = $this->pdo->query(
            "SELECT created_at FROM posts ORDER BY created_at DESC LIMIT 1"
        )->fetchColumn();

        return $result ?: null;
    }
}
