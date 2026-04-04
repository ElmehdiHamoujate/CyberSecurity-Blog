<?php

class BlogGenerator
{
    private string $apiKey;
    private string $associateId;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private string $model  = 'claude-opus-4-5';

    /**
     * 28-book curated pool.
     * Each entry: [name, author, description, amazon_search_query, topics[]]
     * topics[] references TopicSelector slugs — books can belong to multiple topics.
     * weight: base selection weight (1–3); higher = more likely when not recently used.
     */
    private static array $bookPool = [
        [
            'name'   => 'Cybersecurity for Small Networks',
            'author' => 'Seth Enoka',
            'desc'   => 'A practical, jargon-free guide to protecting small business networks without enterprise budgets or dedicated security staff.',
            'query'  => 'Cybersecurity for Small Networks Seth Enoka',
            'topics' => ['enterprise-frameworks-too-complex', 'merged-it-security-roles', 'asset-visibility-configuration-awareness'],
            'weight' => 3,
        ],
        [
            'name'   => 'The CISO Desk Reference Guide',
            'author' => 'Bill Bonney, Gary Hayslip, Matt Stamper',
            'desc'   => 'A field manual for security leaders covering risk management, governance, and building a security program that aligns with business goals.',
            'query'  => 'CISO Desk Reference Guide Bonney Hayslip Stamper',
            'topics' => ['merged-it-security-roles', 'translating-risk-to-financial-terms', 'vanity-metrics-checkbox-compliance'],
            'weight' => 2,
        ],
        [
            'name'   => 'Security Metrics: Replacing Fear, Uncertainty, and Doubt',
            'author' => 'Andrew Jaquith',
            'desc'   => 'The definitive guide to measuring security outcomes rather than activity counts — teaches you how to build metrics that actually reflect risk reduction.',
            'query'  => 'Security Metrics Replacing Fear Uncertainty Doubt Andrew Jaquith',
            'topics' => ['vanity-metrics-checkbox-compliance', 'translating-risk-to-financial-terms'],
            'weight' => 3,
        ],
        [
            'name'   => 'Measuring and Managing Information Risk: A FAIR Approach',
            'author' => 'Jack Freund and Jack Jones',
            'desc'   => 'The authoritative book on the FAIR methodology — shows how to quantify cyber risk in financial terms that resonate with CFOs and boards.',
            'query'  => 'Measuring Managing Information Risk FAIR Approach Freund Jones',
            'topics' => ['translating-risk-to-financial-terms', 'vanity-metrics-checkbox-compliance'],
            'weight' => 3,
        ],
        [
            'name'   => 'Zero Trust Networks: Building Secure Systems in Untrusted Networks',
            'author' => 'Evan Gilman and Doug Barth',
            'desc'   => 'A practical introduction to Zero Trust architecture that explains how to build a coherent security strategy rather than layering disconnected tools.',
            'query'  => 'Zero Trust Networks Building Secure Systems Gilman Barth',
            'topics' => ['tool-overlap-security-as-product', 'unreachable-theoretical-vulnerabilities', 'asset-visibility-configuration-awareness'],
            'weight' => 2,
        ],
        [
            'name'   => 'The Practice of Network Security Monitoring',
            'author' => 'Richard Bejtlich',
            'desc'   => 'Teaches network defenders how to collect, analyze, and act on security data — essential reading for anyone running detection on a lean team.',
            'query'  => 'Practice of Network Security Monitoring Richard Bejtlich',
            'topics' => ['asset-visibility-configuration-awareness', 'scanner-noise-false-positives', 'tool-overlap-security-as-product'],
            'weight' => 2,
        ],
        [
            'name'   => 'Defensive Security Handbook',
            'author' => 'Lee Brotherston and Amanda Berlin',
            'desc'   => 'A hands-on playbook for building defensive security capabilities covering asset management, vulnerability management, and incident response for resource-constrained teams.',
            'query'  => 'Defensive Security Handbook Brotherston Berlin',
            'topics' => ['asset-visibility-configuration-awareness', 'scanner-noise-false-positives', 'self-resolving-non-exploited-vulnerabilities'],
            'weight' => 3,
        ],
        [
            'name'   => 'Social Engineering: The Science of Human Hacking',
            'author' => 'Christopher Hadnagy',
            'desc'   => 'Explains the psychological techniques attackers use against employees — and how to build human-layer defenses that tools alone cannot provide.',
            'query'  => 'Social Engineering Science Human Hacking Christopher Hadnagy',
            'topics' => ['tool-overlap-security-as-product', 'enterprise-frameworks-too-complex'],
            'weight' => 2,
        ],
        [
            'name'   => 'Building Secure and Reliable Systems',
            'author' => 'Heather Adkins et al. (Google)',
            'desc'   => 'Google\'s SRE team shares how to design systems that are both reliable and secure by default — directly applicable to cloud-heavy SMB environments.',
            'query'  => 'Building Secure Reliable Systems Google SRE Heather Adkins',
            'topics' => ['asset-visibility-configuration-awareness', 'tool-overlap-security-as-product', 'enterprise-frameworks-too-complex'],
            'weight' => 2,
        ],
        [
            'name'   => 'Click Here to Kill Everybody',
            'author' => 'Bruce Schneier',
            'desc'   => 'Schneier makes the case for treating security as a societal and business problem — ideal for helping non-technical executives understand why cybersecurity investment matters.',
            'query'  => 'Click Here to Kill Everybody Bruce Schneier',
            'topics' => ['translating-risk-to-financial-terms', 'vanity-metrics-checkbox-compliance', 'enterprise-frameworks-too-complex'],
            'weight' => 2,
        ],
        [
            'name'   => 'Data and Goliath',
            'author' => 'Bruce Schneier',
            'desc'   => 'Explores the hidden world of data collection and surveillance, helping business owners understand the privacy and security risks they face from third parties.',
            'query'  => 'Data and Goliath Bruce Schneier',
            'topics' => ['translating-risk-to-financial-terms', 'vanity-metrics-checkbox-compliance'],
            'weight' => 1,
        ],
        [
            'name'   => 'Tribe of Hackers: Security Leaders',
            'author' => 'Marcus J. Carey and Jennifer Jin',
            'desc'   => 'Interviews with 70 CISOs and security leaders on how they built programs, justified budgets, and navigated the challenges of leading security in organizations of every size.',
            'query'  => 'Tribe of Hackers Security Leaders Marcus Carey Jennifer Jin',
            'topics' => ['merged-it-security-roles', 'translating-risk-to-financial-terms'],
            'weight' => 2,
        ],
        [
            'name'   => 'Incident Response & Computer Forensics',
            'author' => 'Jason Luttgens, Matthew Pepe, Kevin Mandia',
            'desc'   => 'The hands-on guide to detecting, containing, and recovering from security incidents — critical reading for teams who need to respond without a dedicated SOC.',
            'query'  => 'Incident Response Computer Forensics Luttgens Pepe Mandia',
            'topics' => ['merged-it-security-roles', 'tool-overlap-security-as-product'],
            'weight' => 2,
        ],
        [
            'name'   => 'Cybersecurity and Cyberwar: What Everyone Needs to Know',
            'author' => 'P.W. Singer and Allan Friedman',
            'desc'   => 'An accessible overview of the cyber threat landscape that helps business owners understand what they are actually up against — written for a non-technical audience.',
            'query'  => 'Cybersecurity and Cyberwar P.W. Singer Allan Friedman',
            'topics' => ['enterprise-frameworks-too-complex', 'translating-risk-to-financial-terms'],
            'weight' => 2,
        ],
        [
            'name'   => 'IT Governance: An International Guide to Data Security and ISO 27001',
            'author' => 'Alan Calder and Steve Watkins',
            'desc'   => 'A comprehensive guide to implementing ISO 27001 that bridges the gap between technical security and management governance for small and mid-size organizations.',
            'query'  => 'IT Governance International Guide ISO 27001 Alan Calder Steve Watkins',
            'topics' => ['enterprise-frameworks-too-complex', 'vanity-metrics-checkbox-compliance', 'unreachable-theoretical-vulnerabilities'],
            'weight' => 2,
        ],
        [
            'name'   => 'NIST Cybersecurity Framework: A Pocket Guide',
            'author' => 'Alan Calder',
            'desc'   => 'A concise, actionable interpretation of the NIST CSF that makes the framework accessible to small organizations without a dedicated security team.',
            'query'  => 'NIST Cybersecurity Framework Pocket Guide Alan Calder',
            'topics' => ['enterprise-frameworks-too-complex', 'vanity-metrics-checkbox-compliance'],
            'weight' => 3,
        ],
        [
            'name'   => 'Penetration Testing: A Hands-On Introduction to Hacking',
            'author' => 'Georgia Weidman',
            'desc'   => 'Teaches the fundamentals of real penetration testing — invaluable for buyers who need to understand what a legitimate test involves versus an automated scan.',
            'query'  => 'Penetration Testing Hands-On Introduction Hacking Georgia Weidman',
            'topics' => ['overpriced-automated-scans-as-pentests', 'scanner-noise-false-positives'],
            'weight' => 3,
        ],
        [
            'name'   => 'Blue Team Handbook: Incident Response Edition',
            'author' => 'Don Murdoch',
            'desc'   => 'A condensed field reference for defenders covering detection, triage, and response procedures — designed for practitioners operating without large team support.',
            'query'  => 'Blue Team Handbook Incident Response Don Murdoch',
            'topics' => ['merged-it-security-roles', 'tool-overlap-security-as-product', 'scanner-noise-false-positives'],
            'weight' => 2,
        ],
        [
            'name'   => 'The Phoenix Project',
            'author' => 'Gene Kim, Kevin Behr, and George Spafford',
            'desc'   => 'A business novel that makes the case for treating security as a shared responsibility across IT and operations — helps non-technical managers understand DevSecOps principles.',
            'query'  => 'The Phoenix Project Gene Kim Kevin Behr George Spafford',
            'topics' => ['merged-it-security-roles', 'vanity-metrics-checkbox-compliance', 'tool-overlap-security-as-product'],
            'weight' => 2,
        ],
        [
            'name'   => 'Cybersecurity Risk Management',
            'author' => 'Cynthia Brumfield and Brian Haugli',
            'desc'   => 'A practical guide to implementing a risk management program that maps directly to NIST CSF — written specifically for organizations without large security teams.',
            'query'  => 'Cybersecurity Risk Management Cynthia Brumfield Brian Haugli',
            'topics' => ['translating-risk-to-financial-terms', 'enterprise-frameworks-too-complex', 'unreachable-theoretical-vulnerabilities'],
            'weight' => 3,
        ],
        [
            'name'   => 'CompTIA Security+ Study Guide',
            'author' => 'Mike Chapple and David Seidl',
            'desc'   => 'The leading certification study guide that serves as a practical reference for core security concepts — useful for IT generalists stepping into security responsibilities.',
            'query'  => 'CompTIA Security Plus Study Guide Mike Chapple David Seidl',
            'topics' => ['merged-it-security-roles', 'enterprise-frameworks-too-complex'],
            'weight' => 2,
        ],
        [
            'name'   => 'CISO Compass: Navigating Cybersecurity Leadership Challenges',
            'author' => 'Todd Fitzgerald',
            'desc'   => 'Combines CISO interviews with practical frameworks for building security programs, communicating risk to leadership, and making the most of limited resources.',
            'query'  => 'CISO Compass Navigating Cybersecurity Leadership Todd Fitzgerald',
            'topics' => ['merged-it-security-roles', 'translating-risk-to-financial-terms', 'vanity-metrics-checkbox-compliance'],
            'weight' => 2,
        ],
        [
            'name'   => 'The Art of Deception',
            'author' => 'Kevin Mitnick',
            'desc'   => 'Mitnick\'s classic on social engineering attacks illustrates how human vulnerabilities are exploited — essential context for SMBs designing security awareness programs.',
            'query'  => 'The Art of Deception Kevin Mitnick',
            'topics' => ['tool-overlap-security-as-product', 'merged-it-security-roles'],
            'weight' => 1,
        ],
        [
            'name'   => 'Network Security Assessment',
            'author' => 'Chris McNab',
            'desc'   => 'A methodical guide to assessing network security that gives buyers the vocabulary to evaluate vendor testing deliverables and identify scan-as-pentest fraud.',
            'query'  => 'Network Security Assessment Chris McNab OReilly',
            'topics' => ['overpriced-automated-scans-as-pentests', 'asset-visibility-configuration-awareness', 'scanner-noise-false-positives'],
            'weight' => 2,
        ],
        [
            'name'   => 'Practical Vulnerability Management',
            'author' => 'Andrew Magnusson',
            'desc'   => 'A no-nonsense playbook for building a vulnerability management program that reduces real risk instead of generating compliance paperwork.',
            'query'  => 'Practical Vulnerability Management Andrew Magnusson',
            'topics' => ['scanner-noise-false-positives', 'self-resolving-non-exploited-vulnerabilities', 'unreachable-theoretical-vulnerabilities'],
            'weight' => 3,
        ],
        [
            'name'   => 'The Hacker Playbook 3: Practical Guide to Penetration Testing',
            'author' => 'Peter Kim',
            'desc'   => 'Shows how real attackers chain vulnerabilities together — critical reading for understanding why unreachable CVEs and theoretical risks must be evaluated in context.',
            'query'  => 'Hacker Playbook 3 Practical Guide Penetration Testing Peter Kim',
            'topics' => ['overpriced-automated-scans-as-pentests', 'unreachable-theoretical-vulnerabilities'],
            'weight' => 2,
        ],
        [
            'name'   => 'Enterprise Cybersecurity Study Guide',
            'author' => 'Scott E. Donaldson et al.',
            'desc'   => 'Maps enterprise security practices to SMB-applicable controls, showing how large-organization frameworks can be right-sized for teams with limited resources.',
            'query'  => 'Enterprise Cybersecurity Study Guide Scott Donaldson',
            'topics' => ['enterprise-frameworks-too-complex', 'tool-overlap-security-as-product'],
            'weight' => 2,
        ],
        [
            'name'   => 'Asset Attack Vectors: Building Effective Vulnerability Management Strategies',
            'author' => 'Morey Haber and Brad Hibbert',
            'desc'   => 'A comprehensive guide to asset-centric vulnerability management — connects asset visibility to risk prioritization in a way that directly addresses scanner noise.',
            'query'  => 'Asset Attack Vectors Vulnerability Management Morey Haber Brad Hibbert',
            'topics' => ['asset-visibility-configuration-awareness', 'scanner-noise-false-positives', 'self-resolving-non-exploited-vulnerabilities'],
            'weight' => 3,
        ],
    ];

    public function __construct()
    {
        $this->apiKey      = getenv('CLAUDE_API_KEY')      ?: '';
        $this->associateId = getenv('AMAZON_ASSOCIATE_ID') ?: '270cc89-20';

        if (empty($this->apiKey)) {
            throw new RuntimeException('CLAUDE_API_KEY environment variable is not set.');
        }
    }

    /**
     * Generate a full blog post with affiliate links.
     *
     * @param array  $coveredTitles    Titles already in DB to avoid repetition.
     * @param array  $recentBookTitles Book names used in recent posts (for weighted avoidance).
     * @param array  $topic            Topic data from TopicSelector::selectTopic().
     * @return array{title, slug, excerpt, content_html, affiliate_html, topic_slug, used_books}
     */
    public function generatePost(
        array $coveredTitles    = [],
        array $recentBookTitles = [],
        array $topic            = []
    ): array {
        // Select 3 affiliate books (weighted, topic-aware)
        $selectedBooks = $this->selectWeightedBooks($recentBookTitles, $topic['slug'] ?? '', 3);

        // Build the content prompt
        $post = $this->generateContent($coveredTitles, $topic, $selectedBooks);

        // Build affiliate HTML from pre-selected books
        $affiliateHtml = $this->buildAffiliateHtml($selectedBooks);
        $usedBookNames = array_column($selectedBooks, 'name');

        return array_merge($post, [
            'affiliate_html' => $affiliateHtml,
            'topic_slug'     => $topic['slug'] ?? '',
            'used_books'     => $usedBookNames,
        ]);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function generateContent(array $coveredTitles, array $topic, array $books): array
    {
        $titleList = !empty($coveredTitles)
            ? implode("\n- ", array_slice($coveredTitles, 0, 40))
            : '(none yet)';

        $bookContext = implode(', ', array_column($books, 'name'));

        $topicBlock = !empty($topic)
            ? <<<TOPIC

## Your assigned topic area
Topic: {$topic['title']}
Specific angle for this post: {$topic['subtopic']}
Core problem you are solving for the reader: {$topic['problem']}
Authoritative frameworks and standards to reference naturally in the post: {$topic['frameworks']}
TOPIC
            : '';

        $prompt = <<<PROMPT
You write for a cybersecurity blog aimed at small and medium businesses (SMBs) with limited technical resources.
Your audience is non-technical business owners and office managers who need practical, actionable advice.
Write in plain English. Avoid jargon. When you must use a technical term, define it in one sentence.
{$topicBlock}

## Posts already published (do NOT repeat these topics or titles)
- {$titleList}

## Books already selected for affiliate links in this post
{$bookContext}
(Do NOT reference these books by name in the post body — they appear separately as recommendations.)

## Your task
Write a NEW blog post matching the topic angle above. Return ONLY valid JSON — no markdown fences, no extra text — matching this exact shape:

{
  "title": "...",
  "slug": "...",
  "excerpt": "Two concise sentences that summarise the post and its value to an SMB owner.",
  "content_html": "<p>Full post in HTML, ~650 words. Use <h2> for section headings, <p> for paragraphs, <ul>/<li> for lists. No <html>, <head>, or <body> tags. Reference the frameworks listed above naturally where relevant.</p>"
}

## Rules
- title: compelling, specific, and benefit-focused. Under 80 characters.
- slug: lowercase, hyphens only, no special chars, max 60 chars.
- excerpt: two sentences, no more.
- content_html: include a brief intro paragraph, 3–4 actionable sections with <h2> headings, and a closing paragraph. Write as if explaining to a smart but non-technical business owner.
PROMPT;

        $response = $this->callClaude($prompt);
        $data     = $this->parseJson($response);
        $this->validateFields($data, ['title', 'slug', 'excerpt', 'content_html']);

        return [
            'title'        => trim($data['title']),
            'slug'         => $this->sanitizeSlug($data['slug']),
            'excerpt'      => trim($data['excerpt']),
            'content_html' => $data['content_html'],
        ];
    }

    /**
     * Pick $count books from the pool, preferring topic-matched and non-recently-used books.
     */
    private function selectWeightedBooks(
        array  $recentTitles,
        string $topicSlug,
        int    $count
    ): array {
        $pool = self::$bookPool;

        // Assign dynamic weights
        $weighted = [];
        foreach ($pool as $book) {
            $w = $book['weight'];

            // Boost books matching the current topic
            if (!empty($topicSlug) && in_array($topicSlug, $book['topics'], true)) {
                $w += 3;
            }

            // Penalise recently used books (last 20 posts)
            $recentPosition = array_search($book['name'], $recentTitles);
            if ($recentPosition !== false) {
                if ($recentPosition < 5)  $w = max(0, $w - 6); // used in last 5 posts
                elseif ($recentPosition < 10) $w = max(0, $w - 3); // used in last 10
                else $w = max(1, $w - 1);
            }

            $weighted[] = ['book' => $book, 'weight' => max(1, $w)];
        }

        // Weighted random selection without replacement
        $selected = [];
        for ($i = 0; $i < $count; $i++) {
            if (empty($weighted)) break;

            $totalWeight = array_sum(array_column($weighted, 'weight'));
            $rand        = mt_rand(1, $totalWeight);
            $cumulative  = 0;
            $pickedIdx   = 0;

            foreach ($weighted as $idx => $entry) {
                $cumulative += $entry['weight'];
                if ($rand <= $cumulative) {
                    $pickedIdx = $idx;
                    break;
                }
            }

            $selected[] = $weighted[$pickedIdx]['book'];
            array_splice($weighted, $pickedIdx, 1); // remove so it can't be picked again
        }

        return $selected;
    }

    private function buildAffiliateHtml(array $books): string
    {
        $html = '';
        foreach ($books as $book) {
            $name   = htmlspecialchars($book['name'],   ENT_QUOTES);
            $author = htmlspecialchars($book['author'], ENT_QUOTES);
            $desc   = htmlspecialchars($book['desc'],   ENT_QUOTES);
            $query  = urlencode($book['query']);
            $url    = "https://www.amazon.com/s?k={$query}&tag={$this->associateId}";

            $html .= <<<HTML
<div class="affiliate-item">
  <h3>{$name}</h3>
  <p class="affiliate-byline">by {$author}</p>
  <p class="affiliate-desc">{$desc}</p>
  <a href="{$url}" target="_blank" rel="noopener sponsored" class="affiliate-link">View on Amazon &rarr;</a>
</div>
HTML;
        }
        return $html;
    }

    private function callClaude(string $userMessage): string
    {
        $payload = json_encode([
            'model'      => $this->model,
            'max_tokens' => 2048,
            'system'     => 'You are an expert cybersecurity writer specialising in practical guidance for small and medium businesses. Always respond with valid JSON only — no markdown fences, no extra prose.',
            'messages'   => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ]);

        $ch   = curl_init($this->apiUrl);
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

        $localCert = __DIR__ . '/../cacert.pem';
        if (file_exists($localCert)) {
            $opts[CURLOPT_CAINFO] = realpath($localCert);
        }

        curl_setopt_array($ch, $opts);
        $body      = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $clean = preg_replace('/\s*```$/', '', $clean);
        $data  = json_decode(trim($clean), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'Failed to parse Claude JSON: ' . json_last_error_msg() .
                "\nRaw: " . substr($raw, 0, 300)
            );
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
}
