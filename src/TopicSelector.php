<?php

class TopicSelector
{
    /**
     * 10 core topic areas, each with 5 subtopic variations.
     * 'frameworks' feeds directly into the Claude prompt as authoritative references.
     */
    private static array $topics = [

        'scanner-noise-false-positives' => [
            'title'   => 'Scanner Noise & False Positives',
            'problem' => 'vulnerability scanners that flood teams with thousands of alerts, the majority of which are low-risk or unreachable, causing alert fatigue and misplaced priorities',
            'subtopics' => [
                'How to use EPSS scores to cut your actionable vulnerability list by 90%',
                'Building a CISA KEV-first patching workflow that your team will actually follow',
                'Why CVSS scores alone mislead SMBs — and what contextual scoring looks like in practice',
                'Using MITRE ATT&CK to filter scanner noise by real attacker relevance',
                'Choosing a vulnerability intelligence platform on a limited SMB budget',
            ],
            'frameworks' => 'EPSS (Exploit Prediction Scoring System), CISA KEV (Known Exploited Vulnerabilities Catalog), CVSS contextual scoring, MITRE ATT&CK, Risk-Based Vulnerability Management (RBVM), Tenable VPR, Qualys TruRisk',
        ],

        'unreachable-theoretical-vulnerabilities' => [
            'title'   => 'Unreachable & Theoretical Vulnerabilities',
            'problem' => 'teams spending time patching vulnerabilities that are completely unreachable due to network segmentation or compensating controls, while genuinely exposed risks go unaddressed',
            'subtopics' => [
                'Network reachability analysis: how to prove a vulnerability cannot be exploited before deprioritizing it',
                'Attack path analysis — mapping realistic multi-hop attacker routes through your environment',
                'Using compensating controls documentation to formally defer low-risk patches',
                'Building a risk acceptance workflow that satisfies auditors and reduces busy-work',
                'Defense-in-depth as an auditable argument against patching theoretical risks',
            ],
            'frameworks' => 'Network Reachability Analysis (Skybox, RedSeal, Tufin), Attack Path Analysis (XM Cyber, Microsoft Defender for Cloud), NIST SP 800-53 Compensating Controls, Risk Acceptance per ISO 27005 and NIST SP 800-39',
        ],

        'enterprise-frameworks-too-complex' => [
            'title'   => 'Enterprise Frameworks Too Complex for Small Teams',
            'problem' => 'SMBs being handed frameworks designed for Fortune 500 security teams, leading to paralysis or cargo-cult compliance that misses the controls that actually matter',
            'subtopics' => [
                'CIS Controls v8 Implementation Group 1 — the 56 safeguards every SMB should start with',
                'Right-sizing NIST CSF 2.0 for a team of one or two people',
                'ACSC Essential Eight maturity levels: a practical on-ramp for resource-constrained teams',
                'SCuBA from CISA: free Microsoft 365 and Google Workspace hardening for SMBs',
                'The Pareto principle in cybersecurity — the handful of controls that prevent most breaches',
            ],
            'frameworks' => 'CIS Controls v8 Implementation Group 1, NIST CSF 2.0 Organizational Profiles, ACSC Essential Eight Maturity Levels 0–3, CISA SCuBA (Secure Cloud Business Applications), Verizon DBIR breach causation data',
        ],

        'merged-it-security-roles' => [
            'title'   => 'Merged IT & Security Roles',
            'problem' => 'small businesses where one person wears every hat — sysadmin, helpdesk, and CISO — with no clear ownership of security decisions, leading to gaps and burnout',
            'subtopics' => [
                'How to structure a vCISO engagement that actually works for an SMB',
                'Security Champions programs: scaling security awareness without adding headcount',
                'Defining MSSP scope so you outsource the right functions without losing control',
                'Using a RACI matrix to clarify security ownership across blended IT roles',
                'How to build the business case for a dedicated security hire using the NIST NICE Framework',
            ],
            'frameworks' => 'vCISO (Virtual CISO) Model per ISACA guidance, OWASP Security Champions Guide, MSSP scope framing with SOC 2 / ISO 27001, RACI Matrix for security functions, NIST NICE Framework (SP 800-181)',
        ],

        'translating-risk-to-financial-terms' => [
            'title'   => 'Translating Cyber Risk into Financial Terms',
            'problem' => 'security teams unable to get budget or executive buy-in because they speak in CVE counts and severity ratings that mean nothing to a CFO or board member',
            'subtopics' => [
                'FAIR methodology: how to produce a probable loss range your CFO will understand',
                'Using Verizon DBIR data to benchmark breach probability for a business your size',
                'Framing security spend as cyber insurance premium reduction — the most CFO-legible argument',
                'Running a NIST SP 800-30 risk assessment that links vulnerabilities to dollar impact',
                'Board-level security reporting: turning technical metrics into business outcomes',
            ],
            'frameworks' => 'FAIR (Factor Analysis of Information Risk), FAIR-CAM Controls Analytics Model, Verizon DBIR annual breach data, Cyber Insurance actuarial models (Coalition, At-Bay), NIST SP 800-30 Risk Assessment Guide',
        ],

        'overpriced-automated-scans-as-pentests' => [
            'title'   => 'Overpriced Automated Scans Sold as Penetration Tests',
            'problem' => 'vendors selling automated vulnerability scans repackaged as "penetration tests" at premium prices, leaving SMBs with a false sense of security and no real exploit validation',
            'subtopics' => [
                'PTES (Penetration Testing Execution Standard): what a real pen test includes and how to hold vendors accountable',
                'OSSTMM-compliant deliverables — the report requirement that exposes scan-as-pentest vendors',
                'How to use CREST and GIAC certifications as a vendor filter when procuring security testing',
                'Writing an SOW that legally requires manual exploitation evidence, not just scan output',
                'FTC guidance on misleading cybersecurity service claims and how SMBs can push back',
            ],
            'frameworks' => 'PTES (Penetration Testing Execution Standard), OSSTMM (Open Source Security Testing Methodology Manual), CREST accreditation, GIAC GPEN/GWAPT certifications, FTC cybersecurity service guidance',
        ],

        'vanity-metrics-checkbox-compliance' => [
            'title'   => 'Vanity Metrics & Check-Box Compliance',
            'problem' => 'security programs measured by vulnerability counts, patches applied, and compliance checkboxes that look good in reports but do not correlate with actual breach prevention',
            'subtopics' => [
                'Outcome-based security metrics: what to measure instead of vulnerability counts',
                'MITRE ATT&CK Evaluations: shifting from coverage theater to real detection capability',
                'CISA Cross-Sector Cybersecurity Performance Goals — a minimum meaningful baseline, not a checkbox',
                'Adapting DORA metrics for security operations to replace alert count theater',
                'Applying a Balanced Scorecard to security so every stakeholder gets metrics that matter to them',
            ],
            'frameworks' => "MITRE ATT&CK Evaluations, CISA Cross-Sector Cybersecurity Performance Goals (CPGs), Andrew Jaquith's Security Metrics framework, DORA Metrics adapted for SecOps, Balanced Scorecard (Kaplan & Norton) applied to security",
        ],

        'tool-overlap-security-as-product' => [
            'title'   => 'Tool Overlap & The "Security as a Product" Misconception',
            'problem' => 'SMBs accumulating overlapping security tools from persuasive sales pitches, each solving a narrow problem, resulting in integration gaps, alert duplication, and no coherent defensive posture',
            'subtopics' => [
                'Zero Trust Architecture per NIST SP 800-207: a strategy that tells you which tools you actually need',
                'XDR platform consolidation — replacing a stack of siloed tools with unified endpoint, network, and identity detection',
                'Using SABSA security architecture to evaluate tools against real business requirements',
                'ITIL continual improvement reviews: a process for regularly cutting tools that do not generate value',
                'SANS Security Awareness Maturity Model: why people and process outperform tools for most SMBs',
            ],
            'frameworks' => 'Zero Trust Architecture (NIST SP 800-207), XDR (Extended Detection and Response) consolidation, SABSA Security Architecture, TOGAF security extension, ITIL Service Management, SANS Security Awareness Maturity Model',
        ],

        'asset-visibility-configuration-awareness' => [
            'title'   => 'Asset Visibility & Configuration Awareness',
            'problem' => 'businesses that cannot protect what they do not know they have — shadow IT, unmanaged cloud resources, and misconfigured systems creating blind spots that attackers exploit before defenders discover them',
            'subtopics' => [
                'CIS Controls 1 & 2: building a practical asset inventory without enterprise tooling',
                'Implementing a lightweight CMDB for SMBs using free and low-cost discovery tools',
                'CIS Benchmark configuration scanning: hardening every OS and cloud service you own',
                'Cloud Security Posture Management (CSPM) for SMBs using open-source tools like Prowler',
                'Network segmentation review: mapping how systems connect to find the gaps attackers see',
            ],
            'frameworks' => 'CIS Controls v8 Controls 1 & 2, CMDB per ITIL and ISO 27001 A.8.1, CIS Benchmarks for OS/cloud hardening, CSPM tools (Prowler open source, Wiz), runZero/Rumble agentless discovery, NIST SP 800-125B network segmentation',
        ],

        'self-resolving-non-exploited-vulnerabilities' => [
            'title'   => 'Self-Resolving & Non-Exploited Vulnerabilities',
            'problem' => 'vulnerability management programs that treat every finding identically regardless of exploitability, wasting time on CVEs that auto-patched weeks ago or have zero exploitation history while genuinely dangerous exposures age in the queue',
            'subtopics' => [
                'NIST SP 800-40 patch management tiers: building SLA windows that match real attacker timelines',
                'KEV-first plus EPSS scoring: a defensible triage order that any auditor will accept',
                'Aligning your scan schedule to Patch Tuesday so auto-patching clears findings before you act',
                'Exception and deferral management per ISO 27001 and SOC 2 — turning "we skipped it" into a documented decision',
                'Integrating real-time threat intelligence to auto-suppress CVEs with no exploitation history',
            ],
            'frameworks' => 'NIST SP 800-40 Patch Management Guide, CISA KEV catalog, EPSS scoring, Patch Tuesday cadence, Exception management per ISO 27001 Annex A and SOC 2 CC7.1, Threat intelligence integration (Recorded Future, GreyNoise, AlienVault OTX)',
        ],
    ];

    /**
     * Pick a topic+subtopic, avoiding recently covered topic slugs.
     *
     * @param array $recentTopicSlugs  Slugs of topics used in recent posts.
     * @return array{slug, title, subtopic, problem, frameworks}
     */
    public static function selectTopic(array $recentTopicSlugs = []): array
    {
        $topics = self::$topics;

        // Build weighted pool: topics used recently get weight 1, others get weight 10
        $pool = [];
        foreach ($topics as $slug => $topic) {
            $weight = in_array($slug, array_slice($recentTopicSlugs, 0, 5), true) ? 1 : 10;
            for ($i = 0; $i < $weight; $i++) {
                $pool[] = $slug;
            }
        }

        // Pick from weighted pool
        shuffle($pool);
        $chosenSlug = $pool[array_rand($pool)];
        $topic      = $topics[$chosenSlug];

        // Pick a subtopic not used recently (tracked by matching text in recent titles is approximate;
        // full subtopic tracking would need a DB column — for now rotate through all 5 fairly)
        $subtopicIndex = array_rand($topic['subtopics']);
        $subtopic      = $topic['subtopics'][$subtopicIndex];

        return [
            'slug'       => $chosenSlug,
            'title'      => $topic['title'],
            'subtopic'   => $subtopic,
            'problem'    => $topic['problem'],
            'frameworks' => $topic['frameworks'],
        ];
    }

    public static function getAllSlugs(): array
    {
        return array_keys(self::$topics);
    }
}
