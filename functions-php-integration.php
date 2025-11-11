<?php
/**
 * Malta Assessment - functions.php Integration
 *
 * INSTALLATION:
 * Kopiere diesen gesamten Code-Block ans ENDE deiner functions.php
 * Pfad: /wp-content/themes/DEIN-THEME/functions.php
 *
 * Das war's! Keine Plugin-Installation nötig.
 */

// =============================================================================
// CONFIGURATION
// =============================================================================

if (!defined('MALTA_WEBHOOK_URL')) {
    define('MALTA_WEBHOOK_URL', 'https://brixon.app.n8n.cloud/webhook/dwp-quickcheck');
}

if (!defined('MALTA_WEBHOOK_ENABLED')) {
    define('MALTA_WEBHOOK_ENABLED', true);
}

if (!defined('MALTA_DEBUG_MODE')) {
    define('MALTA_DEBUG_MODE', true); // Auf false setzen für Production
}

if (!defined('MALTA_RATE_LIMIT_MAX')) {
    define('MALTA_RATE_LIMIT_MAX', 10);
}

if (!defined('MALTA_RATE_LIMIT_WINDOW')) {
    define('MALTA_RATE_LIMIT_WINDOW', 3600);
}

// =============================================================================
// AJAX ENDPOINTS
// =============================================================================

add_action('wp_ajax_malta_assess_submit', 'malta_assess_handle_submission');
add_action('wp_ajax_nopriv_malta_assess_submit', 'malta_assess_handle_submission');

// Inject nonce via wp_head (funktioniert auf allen Seiten)
add_action('wp_head', 'malta_assess_inject_nonce');

function malta_assess_inject_nonce() {
    ?>
    <script>
        window.maltaAssessment = {
            ajaxUrl: "<?php echo admin_url('admin-ajax.php'); ?>",
            nonce: "<?php echo wp_create_nonce('malta_assess_nonce'); ?>"
        };
    </script>
    <?php
}

// =============================================================================
// AJAX HANDLER
// =============================================================================

function malta_assess_handle_submission() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malta_assess_nonce')) {
        wp_send_json_error(['message' => 'Security check failed'], 403);
    }

    // Rate limiting
    if (!malta_assess_check_rate_limit()) {
        wp_send_json_error(['message' => 'Rate limit exceeded'], 429);
    }

    // Get data
    $raw_data = isset($_POST['data']) ? $_POST['data'] : null;
    if (empty($raw_data)) {
        wp_send_json_error(['message' => 'No data received'], 400);
    }

    $data = json_decode(stripslashes($raw_data), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(['message' => 'Invalid JSON'], 400);
    }

    if (!isset($data['answers']) || !is_array($data['answers'])) {
        wp_send_json_error(['message' => 'Missing answers'], 400);
    }

    // Sanitize
    $sanitized = [
        'answers' => malta_assess_sanitize_answers($data['answers']),
        'email' => isset($data['email']) ? sanitize_email($data['email']) : '',
        'firstName' => isset($data['firstName']) ? sanitize_text_field($data['firstName']) : '',
        'lastName' => isset($data['lastName']) ? sanitize_text_field($data['lastName']) : '',
        'phone' => isset($data['phone']) ? sanitize_text_field($data['phone']) : '',
        'company' => isset($data['company']) ? sanitize_text_field($data['company']) : '',
        'language' => isset($data['language']) ? sanitize_text_field($data['language']) : 'de',
    ];

    try {
        $scoreData = malta_assess_calculate_score($sanitized['answers']);
        $interpretation = malta_assess_get_interpretation($scoreData['percentage']);

        if (MALTA_WEBHOOK_ENABLED) {
            malta_assess_send_webhook($sanitized, $scoreData, $interpretation);
        }

        if (MALTA_DEBUG_MODE) {
            error_log('[Malta] Score: ' . $scoreData['percentage'] . '% for ' . $sanitized['email']);
        }

        wp_send_json_success([
            'percentage' => $scoreData['percentage'],
            'weightedScore' => $scoreData['weightedScore'],
            'totalPossibleWeightedScore' => $scoreData['totalPossibleWeightedScore'],
            'category' => $interpretation['category'],
            'categoryLabel' => $interpretation['categoryLabel'],
            'interpretation' => $interpretation['interpretation'],
            'detailedResults' => $scoreData['detailedResults'],
        ]);

    } catch (Exception $e) {
        if (MALTA_DEBUG_MODE) {
            error_log('[Malta] Error: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()], 500);
        } else {
            wp_send_json_error(['message' => 'Error occurred'], 500);
        }
    }
}

// =============================================================================
// SCORING LOGIC
// =============================================================================

function malta_assess_calculate_score(array $answers): array {
    $questions = malta_assess_get_questions();
    $weightedScore = 0;
    $totalPossibleWeightedScore = 0;
    $detailedResults = [];

    foreach ($questions as $question) {
        if ($question['type'] !== 'single_choice' || !isset($question['options'])) {
            continue;
        }

        $questionId = $question['id'];
        $weight = $question['weight'] ?? 1.0;
        $maxOptionScore = max(array_column($question['options'], 'score'));
        $totalPossibleWeightedScore += $maxOptionScore * $weight;

        if (!isset($answers[$questionId])) {
            continue;
        }

        $selectedValue = $answers[$questionId];
        $selectedOption = null;

        foreach ($question['options'] as $option) {
            if ($option['value'] === $selectedValue) {
                $selectedOption = $option;
                break;
            }
        }

        if ($selectedOption === null) {
            continue;
        }

        $score = $selectedOption['score'];
        $questionWeightedScore = $score * $weight;
        $weightedScore += $questionWeightedScore;

        $category = 'neutral';
        if ($score >= 8) {
            $category = 'positive';
        } elseif ($score <= 4) {
            $category = 'critical';
        }

        $detailedResults[] = [
            'questionId' => $questionId,
            'questionText' => $question['text'],
            'answer' => $selectedOption['label'],
            'answerDescription' => $selectedOption['description'] ?? '',
            'score' => $score,
            'category' => $category,
        ];
    }

    $percentage = $totalPossibleWeightedScore > 0
        ? round(($weightedScore / $totalPossibleWeightedScore) * 100)
        : 0;

    return [
        'percentage' => $percentage,
        'weightedScore' => $weightedScore,
        'totalPossibleWeightedScore' => $totalPossibleWeightedScore,
        'detailedResults' => $detailedResults,
    ];
}

function malta_assess_get_interpretation(int $percentage): array {
    if ($percentage < 20) {
        return [
            'category' => 'explore',
            'categoryLabel' => 'Lassen Sie uns sprechen',
            'interpretation' => 'Ihre Situation erfordert eine individuelle Beratung. Kontaktieren Sie uns für ein persönliches Gespräch über Ihre Möglichkeiten. Malta bietet flexible Lösungen für verschiedenste Situationen.',
        ];
    } elseif ($percentage < 35) {
        return [
            'category' => 'fair',
            'categoryLabel' => 'Malta ist möglich',
            'interpretation' => 'Malta könnte für Sie funktionieren. Lassen Sie uns gemeinsam herausfinden, wie wir dies optimal gestalten. Mit einigen Anpassungen können Sie von Maltas Vorteilen profitieren.',
        ];
    } elseif ($percentage < 55) {
        return [
            'category' => 'moderate',
            'categoryLabel' => 'Malta ist gut geeignet',
            'interpretation' => 'Malta bietet gute Möglichkeiten für Sie. Mit einigen Anpassungen können Sie optimal profitieren. Die Kombination aus niedrigen Steuern, EU-Mitgliedschaft und hoher Lebensqualität macht Malta einzigartig.',
        ];
    } elseif ($percentage < 75) {
        return [
            'category' => 'good',
            'categoryLabel' => 'Malta ist sehr gut geeignet',
            'interpretation' => 'Großartig! Malta bietet signifikante Vorteile für Ihre Situation. Mit der richtigen Planung wird dies ein Erfolg. Wir helfen Ihnen, die optimale Struktur für Ihre spezifische Situation zu finden.',
        ];
    } else {
        return [
            'category' => 'excellent',
            'categoryLabel' => 'Malta ist hervorragend geeignet',
            'interpretation' => 'Perfekt! Ihre Situation ist ideal für Malta. Sie können von allen Vorteilen profitieren - lassen Sie uns die Details besprechen! Hohe Erfolgswahrscheinlichkeit bei korrekter Umsetzung.',
        ];
    }
}

function malta_assess_get_questions(): array {
    return [
        [
            'id' => 'q001',
            'text' => 'Was beschreibt Ihre geschäftliche Situation am besten?',
            'type' => 'single_choice',
            'weight' => 2.0,
            'options' => [
                ['value' => '1', 'label' => 'Ich plane, in Malta ein komplett neues Business zu starten', 'score' => 8],
                ['value' => '2', 'label' => 'Ich habe ein bestehendes Business (unter 500k EUR Umsatz)', 'score' => 6],
                ['value' => '3', 'label' => 'Ich habe ein etabliertes Business (500k - 2 Mio. EUR)', 'score' => 8],
                ['value' => '4', 'label' => 'Ich habe ein größeres Business (über 2 Mio. EUR)', 'score' => 10],
                ['value' => '5', 'label' => 'Ich möchte mich erstmal informieren / keine Angabe', 'score' => 7],
            ],
        ],
        [
            'id' => 'q002',
            'text' => 'Wie international ist Ihr Business ausgerichtet (oder soll es sein)?',
            'type' => 'single_choice',
            'weight' => 1.5,
            'options' => [
                ['value' => '1', 'label' => 'Neues Business - plane internationale Ausrichtung', 'score' => 8],
                ['value' => '2', 'label' => 'Hauptsächlich lokal, aber offen für internationale Expansion', 'score' => 6],
                ['value' => '3', 'label' => 'Mix aus lokalen und internationalen Kunden', 'score' => 8],
                ['value' => '4', 'label' => 'Vollständig international / digitales Business', 'score' => 10],
                ['value' => '5', 'label' => 'Noch in Planung / keine Angabe', 'score' => 7],
            ],
        ],
        [
            'id' => 'q003',
            'text' => 'Sind Sie bereit, nach Malta umzuziehen und dort mindestens 183 Tage pro Jahr zu verbringen?',
            'type' => 'single_choice',
            'weight' => 2.0,
            'options' => [
                ['value' => '1', 'label' => 'Nein, auf keinen Fall', 'score' => 3],
                ['value' => '2', 'label' => 'Ungern, nur wenn unbedingt nötig', 'score' => 6],
                ['value' => '3', 'label' => 'Ja, aber nur vorübergehend (2-3 Jahre)', 'score' => 8],
                ['value' => '4', 'label' => 'Ja, langfristig bereit', 'score' => 10],
            ],
        ],
        [
            'id' => 'q004',
            'text' => 'Welches Geschäftsmodell beschreibt Ihr Unternehmen am besten?',
            'type' => 'single_choice',
            'weight' => 1.5,
            'options' => [
                ['value' => '1', 'label' => 'Lokale Dienstleistung mit persönlichem Kundenkontakt', 'score' => 4],
                ['value' => '2', 'label' => 'E-Commerce / Handel', 'score' => 7],
                ['value' => '3', 'label' => 'SaaS / Digitale Produkte', 'score' => 9],
                ['value' => '4', 'label' => 'Holding / Beteiligungsgesellschaft', 'score' => 10],
                ['value' => '5', 'label' => 'Beratung / Professional Services (ortsunabhängig)', 'score' => 8],
            ],
        ],
        [
            'id' => 'q005',
            'text' => 'Können Sie echte wirtschaftliche Substanz in Malta aufbauen (Büro, Mitarbeiter, Management)?',
            'type' => 'single_choice',
            'weight' => 2.0,
            'options' => [
                ['value' => '1', 'label' => 'Nein, nur Briefkastenfirma ohne Aktivität', 'score' => 3],
                ['value' => '2', 'label' => 'Minimale Substanz (Virtual Office, keine Mitarbeiter)', 'score' => 5],
                ['value' => '3', 'label' => 'Moderate Substanz (kleines Büro, 1-2 lokale Teilzeitmitarbeiter)', 'score' => 8],
                ['value' => '4', 'label' => 'Volle Substanz (eigenes Büro, mehrere Vollzeitmitarbeiter, Management vor Ort)', 'score' => 10],
            ],
        ],
        [
            'id' => 'q006',
            'text' => 'Sind Sie bereit, höhere Compliance-Anforderungen auf sich zu nehmen?',
            'type' => 'single_choice',
            'weight' => 1.5,
            'options' => [
                ['value' => '1', 'label' => 'Nein, ich bevorzuge minimale Compliance', 'score' => 4],
                ['value' => '2', 'label' => 'Unsicher / möchte mehr erfahren', 'score' => 6],
                ['value' => '3', 'label' => 'Ja, bei angemessenem Nutzen', 'score' => 8],
                ['value' => '4', 'label' => 'Ja, volle Compliance ist mir wichtig', 'score' => 10],
            ],
        ],
        [
            'id' => 'q007',
            'text' => 'Haben Sie bereits Niederlassungen in anderen Ländern oder planen Sie diese?',
            'type' => 'single_choice',
            'weight' => 1.5,
            'options' => [
                ['value' => '1', 'label' => 'Nein, und nicht geplant', 'score' => 6],
                ['value' => '2', 'label' => 'Noch nicht, aber für die Zukunft geplant', 'score' => 7],
                ['value' => '3', 'label' => 'Ja, eine Niederlassung in einem weiteren Land', 'score' => 8],
                ['value' => '4', 'label' => 'Ja, mehrere Niederlassungen / Tochtergesellschaften', 'score' => 10],
                ['value' => '5', 'label' => 'Unsicher / keine Angabe', 'score' => 6],
            ],
        ],
        [
            'id' => 'q008',
            'text' => 'Wie würden Sie Ihre Profitabilität einschätzen?',
            'type' => 'single_choice',
            'weight' => 1.5,
            'options' => [
                ['value' => '1', 'label' => 'Noch nicht profitabel / Start-up Phase', 'score' => 5],
                ['value' => '2', 'label' => 'Break-even oder leicht profitabel', 'score' => 7],
                ['value' => '3', 'label' => 'Solide Profitabilität', 'score' => 9],
                ['value' => '4', 'label' => 'Sehr profitabel', 'score' => 10],
                ['value' => '5', 'label' => 'Keine Angabe', 'score' => 6],
            ],
        ],
        [
            'id' => 'q009',
            'text' => 'Wie wichtig ist Ihnen EU-Marktzugang?',
            'type' => 'single_choice',
            'weight' => 1.5,
            'options' => [
                ['value' => '1', 'label' => 'Nicht wichtig / fokussiere auf Nicht-EU', 'score' => 6],
                ['value' => '2', 'label' => 'Etwas wichtig / nice to have', 'score' => 7],
                ['value' => '3', 'label' => 'Wichtig / plane EU-Expansion', 'score' => 9],
                ['value' => '4', 'label' => 'Sehr wichtig / kritisch für mein Geschäftsmodell', 'score' => 10],
            ],
        ],
        [
            'id' => 'q010',
            'text' => 'Haben Sie bereits Erfahrung mit internationalen Unternehmensstrukturen?',
            'type' => 'single_choice',
            'weight' => 1.0,
            'options' => [
                ['value' => '1', 'label' => 'Nein, vollständig neu für mich', 'score' => 6],
                ['value' => '2', 'label' => 'Etwas Erfahrung', 'score' => 7],
                ['value' => '3', 'label' => 'Gute Erfahrung mit internationalen Strukturen', 'score' => 9],
                ['value' => '4', 'label' => 'Umfangreiche Erfahrung', 'score' => 10],
            ],
        ],
        [
            'id' => 'q011',
            'text' => 'Wie wichtig ist Ihnen Privatsphäre / Diskretion?',
            'type' => 'single_choice',
            'weight' => 1.0,
            'options' => [
                ['value' => '1', 'label' => 'Nicht wichtig / volle Transparenz ist OK', 'score' => 8],
                ['value' => '2', 'label' => 'Etwas wichtig', 'score' => 7],
                ['value' => '3', 'label' => 'Wichtig / möchte diskrete Strukturen', 'score' => 7],
                ['value' => '4', 'label' => 'Sehr wichtig / maximale Diskretion gewünscht', 'score' => 6],
            ],
        ],
        [
            'id' => 'q012',
            'text' => 'Welche Zeitschiene haben Sie für die Umsetzung?',
            'type' => 'single_choice',
            'weight' => 1.0,
            'options' => [
                ['value' => '1', 'label' => 'Informationsphase / über 12 Monate', 'score' => 7],
                ['value' => '2', 'label' => 'Mittelfristig (6-12 Monate)', 'score' => 8],
                ['value' => '3', 'label' => 'Kurzfristig (3-6 Monate)', 'score' => 9],
                ['value' => '4', 'label' => 'Sofort / unter 3 Monaten', 'score' => 10],
            ],
        ],
    ];
}

// =============================================================================
// UTILITIES
// =============================================================================

function malta_assess_sanitize_answers(array $answers): array {
    $sanitized = [];
    foreach ($answers as $questionId => $value) {
        if (!preg_match('/^q[0-9]{3}$/', $questionId)) {
            continue;
        }
        if (!is_string($value) && !is_numeric($value)) {
            continue;
        }
        $sanitized[$questionId] = (string)$value;
    }
    return $sanitized;
}

function malta_assess_check_rate_limit(): bool {
    $ip = malta_assess_get_client_ip();
    $transient_key = 'malta_rate_' . md5($ip);
    $requests = get_transient($transient_key);
    if (!$requests) {
        $requests = [];
    }
    $current_time = time();
    $requests = array_filter($requests, function($timestamp) use ($current_time) {
        return ($current_time - $timestamp) < MALTA_RATE_LIMIT_WINDOW;
    });
    if (count($requests) >= MALTA_RATE_LIMIT_MAX) {
        return false;
    }
    $requests[] = $current_time;
    set_transient($transient_key, $requests, MALTA_RATE_LIMIT_WINDOW);
    return true;
}

function malta_assess_get_client_ip(): string {
    $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($ip_keys as $key) {
        if (isset($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

function malta_assess_send_webhook(array $userData, array $scoreData, array $interpretation): bool {
    if (!MALTA_WEBHOOK_ENABLED || empty(MALTA_WEBHOOK_URL)) {
        return false;
    }
    try {
        $payload = [
            'timestamp' => current_time('mysql'),
            'contact' => [
                'email' => $userData['email'],
                'firstName' => $userData['firstName'],
                'lastName' => $userData['lastName'],
                'phone' => $userData['phone'],
                'company' => $userData['company'],
                'language' => $userData['language'],
            ],
            'score' => [
                'percentage' => $scoreData['percentage'],
                'weightedScore' => $scoreData['weightedScore'],
                'totalPossibleWeightedScore' => $scoreData['totalPossibleWeightedScore'],
                'category' => $interpretation['category'],
                'categoryLabel' => $interpretation['categoryLabel'],
            ],
            'interpretation' => $interpretation['interpretation'],
            'answers' => $userData['answers'],
            'detailedResults' => $scoreData['detailedResults'],
            'metadata' => [
                'ip' => malta_assess_get_client_ip(),
                'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            ],
        ];
        $response = wp_remote_post(MALTA_WEBHOOK_URL, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($payload),
            'timeout' => 10,
        ]);
        if (is_wp_error($response)) {
            if (MALTA_DEBUG_MODE) {
                error_log('[Malta] Webhook error: ' . $response->get_error_message());
            }
            return false;
        }
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code >= 200 && $status_code < 300) {
            if (MALTA_DEBUG_MODE) {
                error_log('[Malta] Webhook sent successfully');
            }
            return true;
        } else {
            if (MALTA_DEBUG_MODE) {
                error_log('[Malta] Webhook failed: HTTP ' . $status_code);
            }
            return false;
        }
    } catch (Exception $e) {
        if (MALTA_DEBUG_MODE) {
            error_log('[Malta] Webhook exception: ' . $e->getMessage());
        }
        return false;
    }
}
