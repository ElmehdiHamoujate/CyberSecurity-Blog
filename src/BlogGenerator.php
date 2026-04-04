<?php

class BlogGenerator
{
    private string $apiKey;
    private string $associateId;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private string $model  = 'claude-opus-4-5';

    public function __construct()
    {
        $this->apiKey      = getenv('CLAUDE_API_KEY')       ?: '';
        $this->associateId = getenv('AMAZON_ASSOCIATE_ID')  ?: '270cc89-20';

        if (empty($this->apiKey)) {
            throw new RuntimeException('CLAUDE_API_KEY environment variable is not set.');
        }
    }

    /**
     * Generate a full blog post with affiliate links.
     *
     * @param array $coveredTitles Titles already in the DB to avoid repeats.
     * @return array{title, slug, excerpt, content_html, affiliate_html}
     */
    public function generatePost(array $coveredTitles = []): array
    {
        $topicList = !empty($coveredTitles)
            ? 'Topics already covered (do NOT repeat these):\n- ' . implode("\n- ", array_slice($coveredTitles, 0, 40))
            : 'This is the first post — pick any strong topic.';

        $prompt = <<<PROMPT
You write for a cybersecurity blog aimed at small and medium businesses (SMBs) with limited technical resources.
Your audience is non-technical business owners and office managers who need practical, actionable advice.

{$topicList}

Write a NEW blog post on a cybersecurity topic relevant to SMBs. Return ONLY valid JSON — no markdown fences, no extra text — matching this exact shape:

{
  "title": "...",
  "slug": "...",
  "excerpt": "Two concise sentences that summarise the post.",
  "content_html": "<p>Full post in HTML, ~650 words. Use <h2> for section headings, <p> for paragraphs, <ul>/<li> for lists. No <html>, <head>, or <body> tags.</p>",
  "affiliates": [
    {
      "name": "Product or Book Name",
      "author": "Author name if a book, otherwise empty string",
      "description": "One sentence on why this helps SMBs with the topic.",
      "amazon_search_query": "exact search query to find this on Amazon"
    }
  ]
}

Rules:
- slug: lowercase, hyphens only, no special chars, max 60 chars.
- content_html: write in plain English, avoid jargon. Include a brief intro, 3-4 actionable sections, and a closing paragraph.
- affiliates: exactly 3 items — a mix of a book, a software/hardware tool, and one other resource relevant to the post topic.
- amazon_search_query: specific enough to surface the right product (e.g. "Cybersecurity Essentials SMB book Charles Brooks").
PROMPT;

        $response = $this->callClaude($prompt);
        $data     = $this->parseJson($response);

        $this->validateFields($data, ['title', 'slug', 'excerpt', 'content_html', 'affiliates']);

        $affiliateHtml = $this->buildAffiliateHtml($data['affiliates']);

        return [
            'title'          => trim($data['title']),
            'slug'           => $this->sanitizeSlug($data['slug']),
            'excerpt'        => trim($data['excerpt']),
            'content_html'   => $data['content_html'],
            'affiliate_html' => $affiliateHtml,
        ];
    }

    private function callClaude(string $userMessage): string
    {
        $payload = json_encode([
            'model'      => $this->model,
            'max_tokens' => 2048,
            'system'     => 'You are an expert cybersecurity writer specialising in practical guidance for small and medium businesses. Always respond with valid JSON only.',
            'messages'   => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ]);

        $ch = curl_init($this->apiUrl);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT        => 120,
        ];
        // Use local CA bundle on Windows dev where system certs may be missing
        $localCert = __DIR__ . '/../cacert.pem';
        if (file_exists($localCert)) {
            $opts[CURLOPT_CAINFO] = realpath($localCert);
        }
        curl_setopt_array($ch, $opts);

        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException("cURL error: {$curlError}");
        }

        if ($httpCode !== 200) {
            throw new RuntimeException("Claude API returned HTTP {$httpCode}: {$body}");
        }

        $decoded = json_decode($body, true);

        if (empty($decoded['content'][0]['text'])) {
            throw new RuntimeException('Unexpected Claude API response shape: ' . $body);
        }

        return $decoded['content'][0]['text'];
    }

    private function parseJson(string $raw): array
    {
        // Strip possible markdown code fences Claude might add despite instructions
        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $clean = preg_replace('/\s*```$/', '', $clean);

        $data = json_decode(trim($clean), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to parse Claude JSON response: ' . json_last_error_msg() . "\nRaw: " . substr($raw, 0, 300));
        }

        return $data;
    }

    private function validateFields(array $data, array $required): void
    {
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new RuntimeException("Missing required field '{$field}' in Claude response.");
            }
        }
    }

    private function sanitizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return substr($slug, 0, 60);
    }

    private function buildAffiliateHtml(array $affiliates): string
    {
        $html = '';
        foreach ($affiliates as $item) {
            $name   = htmlspecialchars($item['name']        ?? 'Resource', ENT_QUOTES);
            $author = htmlspecialchars($item['author']       ?? '',         ENT_QUOTES);
            $desc   = htmlspecialchars($item['description']  ?? '',         ENT_QUOTES);
            $query  = urlencode($item['amazon_search_query'] ?? $item['name']);
            $url    = "https://www.amazon.com/s?k={$query}&tag={$this->associateId}";

            $byline = $author ? "<p class=\"affiliate-byline\">by {$author}</p>" : '';

            $html .= <<<HTML
<div class="affiliate-item">
  <h3>{$name}</h3>
  {$byline}
  <p class="affiliate-desc">{$desc}</p>
  <a href="{$url}" target="_blank" rel="noopener sponsored" class="affiliate-link">View on Amazon &rarr;</a>
</div>
HTML;
        }
        return $html;
    }
}
