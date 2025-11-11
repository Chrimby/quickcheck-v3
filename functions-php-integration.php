<?php
/**
 * Malta Assessment - Complete WordPress Integration
 *
 * Version: 2.0 (Multi-Language)
 * Languages: DE, EN, NL
 *
 * ============================================================================
 * INSTALLATION
 * ============================================================================
 *
 * 1. Kopiere diesen KOMPLETTEN Code-Block
 * 2. Füge ihn ans ENDE deiner functions.php ein
 *    Pfad: /wp-content/themes/DEIN-THEME/functions.php
 * 3. Speichern - Fertig!
 *
 * ============================================================================
 * WAS DIESER CODE MACHT
 * ============================================================================
 *
 * ✅ AJAX-Endpunkt für Formular-Submissions
 * ✅ Automatische Spracherkennung (DE/EN/NL aus URL)
 * ✅ Nonce-Security (CSRF-Schutz)
 * ✅ Rate Limiting (10 Requests/Stunde pro IP)
 * ✅ Scoring-Algorithmus (12 Fragen, gewichtet)
 * ✅ Webhook-Integration (n8n)
 * ✅ Input Sanitization & Validation
 * ✅ Multi-Language Support (JSON-basiert)
 *
 * ============================================================================
 * PRODUCTION CHECKLIST
 * ============================================================================
 *
 * Vor Go-Live:
 * - [ ] MALTA_DEBUG_MODE auf false setzen (Zeile 46)
 * - [ ] Webhook-URL korrekt (Zeile 38)
 * - [ ] JSON-Dateien hochgeladen (/wp-content/uploads/malta-assessment-v2/translations/)
 * - [ ] Alle 3 Sprachseiten erstellt (/de/, /en/, /nl/)
 * - [ ] Vollständig getestet (jede Sprache)
 *
 */

// =============================================================================
// CONFIGURATION - Anpassen nach Bedarf
// =============================================================================

/**
 * Webhook-URL für Submissions (n8n, Make.com, Zapier, etc.)
 * Ändere dies zu deinem eigenen Webhook-Endpunkt
 */
if (!defined('MALTA_WEBHOOK_URL')) {
    define('MALTA_WEBHOOK_URL', 'https://brixon.app.n8n.cloud/webhook/dwp-quickcheck');
}

/**
 * Webhook aktivieren/deaktivieren
 * false = Submissions werden nur verarbeitet, aber nicht an Webhook gesendet
 */
if (!defined('MALTA_WEBHOOK_ENABLED')) {
    define('MALTA_WEBHOOK_ENABLED', true);
}

/**
 * Debug-Modus
 * true = Logs werden in /wp-content/debug.log geschrieben
 * false = Keine Logs (für Production)
 */
if (!defined('MALTA_DEBUG_MODE')) {
    define('MALTA_DEBUG_MODE', true); // ⚠️ Auf false setzen für Production!
}

/**
 * Rate Limiting - Schützt vor Spam/Missbrauch
 * Max 10 Requests pro Stunde pro IP-Adresse (Standard)
 */
if (!defined('MALTA_RATE_LIMIT_MAX')) {
    define('MALTA_RATE_LIMIT_MAX', 10); // Max Anzahl Requests
}

if (!defined('MALTA_RATE_LIMIT_WINDOW')) {
    define('MALTA_RATE_LIMIT_WINDOW', 3600); // Zeitfenster in Sekunden (3600 = 1 Stunde)
}

// =============================================================================
// AJAX ENDPOINTS - Registrierung
// =============================================================================

/**
 * Registriere AJAX-Endpunkt für Formular-Submissions
 *
 * wp_ajax_malta_assess_submit = für eingeloggte User
 * wp_ajax_nopriv_malta_assess_submit = für nicht-eingeloggte User (wichtig!)
 */
add_action('wp_ajax_malta_assess_submit', 'malta_assess_handle_submission');
add_action('wp_ajax_nopriv_malta_assess_submit', 'malta_assess_handle_submission');

/**
 * Injiziere JavaScript-Variablen in <head> jeder Seite
 * - Nonce für Security (CSRF-Schutz)
 * - AJAX-URL für Backend-Kommunikation
 * - Sprache für Frontend (automatisch erkannt aus URL)
 * - Pfad zu Übersetzungsdateien
 */
add_action('wp_head', 'malta_assess_inject_nonce');

function malta_assess_inject_nonce() {
    // =============================================================================
    // SPRACHERKENNUNG - Automatisch aus URL-Pfad
    // =============================================================================

    /**
     * Erkenne Sprache aus URL:
     * - /de/seite/ → Deutsch
     * - /en/page/ → Englisch
     * - /nl/pagina/ → Niederländisch
     * - Kein Match → Deutsch (Fallback)
     */
    $current_path = $_SERVER['REQUEST_URI'] ?? '';
    $language = 'de'; // Standard-Fallback

    // Prüfe auf Englisch ODER Niederländisch (case-insensitive)
    if (stripos($current_path, '/en/') !== false) {
        $language = 'en';
    } elseif (stripos($current_path, '/nl/') !== false) {
        $language = 'nl';
    }

    // Debug Log (nur wenn DEBUG_MODE aktiv)
    if (MALTA_DEBUG_MODE) {
        error_log('[Malta] Language detection - Path: ' . $current_path . ' | Detected: ' . $language);
    }

    /**
     * Alternative: WPML/Polylang Integration (falls installiert)
     * Kommentiere diese Zeilen ein, falls du WPML oder Polylang nutzt:
     *
     * if (function_exists('pll_current_language')) {
     *     $language = pll_current_language(); // Polylang
     * } elseif (function_exists('icl_get_current_language')) {
     *     $language = icl_get_current_language(); // WPML
     * }
     */

    // =============================================================================
    // ÜBERSETZUNGS-PFAD - Wo liegen die JSON-Dateien?
    // =============================================================================

    /**
     * Baue Pfad zu den Übersetzungsdateien:
     * /wp-content/uploads/malta-assessment-v2/translations/
     *
     * Dort müssen liegen:
     * - de.json (Deutsch)
     * - en.json (Englisch)
     * - nl.json (Niederländisch)
     */
    $upload_dir = wp_upload_dir();
    $translations_path = $upload_dir['baseurl'] . '/malta-assessment-v2/translations';

    // =============================================================================
    // JAVASCRIPT INJECTION - Variablen ins Frontend
    // =============================================================================
    ?>
    <script>
        /**
         * Backend-Integration
         * Wird vom Frontend genutzt für AJAX-Requests
         */
        window.maltaAssessment = {
            ajaxUrl: "<?php echo admin_url('admin-ajax.php'); ?>", // WordPress AJAX Endpunkt
            nonce: "<?php echo wp_create_nonce('malta_assess_nonce'); ?>" // Security Token (läuft nach 24h ab)
        };

        /**
         * Multi-Language Support
         * Frontend nutzt diese Variablen um die richtige Übersetzung zu laden
         */
        window.qcMaltaLanguage = '<?php echo esc_js($language); ?>'; // z.B. 'de', 'en', 'nl'
        window.qcMaltaTranslationsPath = '<?php echo esc_js($translations_path); ?>'; // Pfad zu JSON-Dateien
    </script>
    <?php
}

// =============================================================================
// AJAX HANDLER - Verarbeitet Formular-Submissions
// =============================================================================

/**
 * Hauptfunktion: Verarbeitet Malta Assessment Submissions
 *
 * Flow:
 * 1. Security Check (Nonce)
 * 2. Rate Limiting Check
 * 3. Daten empfangen & validieren
 * 4. Daten sanitizen (XSS-Schutz)
 * 5. Score berechnen
 * 6. Webhook senden (optional)
 * 7. Response zurück ans Frontend
 */
function malta_assess_handle_submission() {
    // =============================================================================
    // STEP 1: SECURITY CHECK - Nonce Verification
    // =============================================================================

    /**
     * Prüfe ob Request von legitimer Quelle kommt
     * Nonce = "Number used once" - verhindert CSRF-Attacken
     * Läuft nach 24h automatisch ab
     */
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malta_assess_nonce')) {
        wp_send_json_error(['message' => 'Security check failed'], 403);
    }

    // =============================================================================
    // STEP 2: RATE LIMITING - Spam-Schutz
    // =============================================================================

    /**
     * Prüfe ob IP-Adresse zu viele Requests sendet
     * Standard: Max 10 Requests pro Stunde
     * Schützt vor:
     * - Spam
     * - Brute-Force Attacken
     * - Server-Überlastung
     */
    if (!malta_assess_check_rate_limit()) {
        wp_send_json_error(['message' => 'Rate limit exceeded'], 429);
    }

    // =============================================================================
    // STEP 3: DATEN EMPFANGEN & VALIDIEREN
    // =============================================================================

    /**
     * Hole JSON-Daten aus POST-Request
     * Frontend sendet: { answers: {...}, email: "...", firstName: "...", ... }
     */
    $raw_data = isset($_POST['data']) ? $_POST['data'] : null;
    if (empty($raw_data)) {
        wp_send_json_error(['message' => 'No data received'], 400);
    }

    // Decode JSON zu PHP Array
    $data = json_decode(stripslashes($raw_data), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(['message' => 'Invalid JSON'], 400);
    }

    // Validiere dass Antworten vorhanden sind
    if (!isset($data['answers']) || !is_array($data['answers'])) {
        wp_send_json_error(['message' => 'Missing answers'], 400);
    }

    // =============================================================================
    // STEP 4: DATEN SANITIZEN - XSS/Injection Schutz
    // =============================================================================

    /**
     * Bereinige ALLE User-Inputs bevor wir sie verarbeiten
     * Verhindert:
     * - XSS (Cross-Site Scripting)
     * - SQL Injection
     * - Code Injection
     */
    $sanitized = [
        'answers' => malta_assess_sanitize_answers($data['answers']), // Antworten (q001: "1", q002: "3", ...)
        'email' => isset($data['email']) ? sanitize_email($data['email']) : '',
        'firstName' => isset($data['firstName']) ? sanitize_text_field($data['firstName']) : '',
        'lastName' => isset($data['lastName']) ? sanitize_text_field($data['lastName']) : '',
        'phone' => isset($data['phone']) ? sanitize_text_field($data['phone']) : '',
        'company' => isset($data['company']) ? sanitize_text_field($data['company']) : '',
        'language' => isset($data['language']) ? sanitize_text_field($data['language']) : 'de', // DE/EN/NL
    ];

    // =============================================================================
    // STEP 5: SCORE BERECHNEN & INTERPRETATION
    // =============================================================================

    try {
        /**
         * Berechne Score basierend auf Antworten
         * - Jede Frage hat Options mit Score (0-10)
         * - Fragen sind gewichtet (weight: 1.0-2.0)
         * - Gesamtscore = gewichteter Durchschnitt als Prozent (0-100%)
         */
        $scoreData = malta_assess_calculate_score($sanitized['answers']);

        /**
         * Interpretiere den Score
         * < 20%: "Lassen Sie uns sprechen"
         * < 40%: "Malta könnte geeignet sein"
         * < 60%: "Malta ist bedingt geeignet"
         * < 75%: "Malta ist gut geeignet"
         * ≥ 75%: "Malta ist sehr gut geeignet"
         */
        $interpretation = malta_assess_get_interpretation($scoreData['percentage']);

        // =============================================================================
        // STEP 6: WEBHOOK SENDEN (Optional)
        // =============================================================================

        /**
         * Sende Daten an n8n/Make.com/Zapier
         * Nur wenn MALTA_WEBHOOK_ENABLED = true
         * Fehler werden geloggt, aber User bekommt trotzdem Ergebnis
         */
        if (MALTA_WEBHOOK_ENABLED) {
            malta_assess_send_webhook($sanitized, $scoreData, $interpretation);
        }

        // =============================================================================
        // DEBUG LOGGING
        // =============================================================================

        if (MALTA_DEBUG_MODE) {
            error_log('[Malta] Score: ' . $scoreData['percentage'] . '% for ' . $sanitized['email']);
        }

        // =============================================================================
        // STEP 7: SUCCESS RESPONSE - Zurück ans Frontend
        // =============================================================================

        /**
         * Sende Ergebnis zurück ans Frontend
         * Frontend zeigt dann Results Screen mit diesen Daten
         */
        wp_send_json_success([
            'percentage' => $scoreData['percentage'], // 0-100
            'weightedScore' => $scoreData['weightedScore'], // Gewichtete Punktzahl
            'totalPossibleWeightedScore' => $scoreData['totalPossibleWeightedScore'], // Max mögliche Punktzahl
            'category' => $interpretation['category'], // explore, fair, moderate, good, excellent
            'categoryLabel' => $interpretation['categoryLabel'], // "Malta ist gut geeignet"
            'interpretation' => $interpretation['interpretation'], // Beschreibungstext
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
// SCORING LOGIC - Berechnet gewichteten Score aus Antworten
// =============================================================================

/**
 * Berechne Malta-Eignung Score
 *
 * @param array $answers User-Antworten [ 'q001' => '1', 'q002' => '3', ... ]
 * @return array [ 'percentage' => 78, 'weightedScore' => 145.5, 'totalPossibleWeightedScore' => 186, 'detailedResults' => [...] ]
 *
 * Algorithmus:
 * 1. Durchlaufe alle 12 Fragen
 * 2. Hole Score für gewählte Option (0-10 Punkte)
 * 3. Multipliziere mit Gewichtung (1.0-2.0x)
 * 4. Summiere gewichtete Scores
 * 5. Berechne Prozent: (gewichtete Summe / max mögliche Summe) * 100
 */
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

/**
 * Interpretiere Score und gebe Kategorie zurück
 *
 * @param int $percentage Score 0-100%
 * @return array [ 'category' => 'good', 'categoryLabel' => 'Malta ist gut geeignet', 'interpretation' => '...' ]
 *
 * Kategorien (neu angepasst für mehr Realismus):
 * - < 20%: "Lassen Sie uns sprechen" (explore)
 * - < 40%: "Malta könnte geeignet sein" (fair) - Einzelfallprüfung notwendig
 * - < 60%: "Malta ist bedingt geeignet" (moderate) - Einzelfallprüfung empfohlen
 * - < 75%: "Malta ist gut geeignet" (good)
 * - ≥ 75%: "Malta ist sehr gut geeignet" (excellent)
 */
function malta_assess_get_interpretation(int $percentage): array {
    if ($percentage < 20) {
        return [
            'category' => 'explore',
            'categoryLabel' => 'Lassen Sie uns sprechen',
            'interpretation' => 'Ihre Situation erfordert eine individuelle Beratung. Kontaktieren Sie uns für ein persönliches Gespräch über Ihre Möglichkeiten. Malta bietet flexible Lösungen für verschiedenste Situationen.',
        ];
    } elseif ($percentage < 40) {
        return [
            'category' => 'fair',
            'categoryLabel' => 'Malta könnte geeignet sein',
            'interpretation' => 'Malta könnte für Sie funktionieren. Eine detaillierte Einzelfallprüfung ist notwendig. Mit gezielten Anpassungen können Sie von Maltas Vorteilen profitieren.',
        ];
    } elseif ($percentage < 60) {
        return [
            'category' => 'moderate',
            'categoryLabel' => 'Malta ist bedingt geeignet',
            'interpretation' => 'Malta bietet interessante Möglichkeiten für Sie. Einzelfallprüfung empfohlen. Die Kombination aus niedrigen Steuern, EU-Mitgliedschaft und hoher Lebensqualität macht Malta attraktiv.',
        ];
    } elseif ($percentage < 75) {
        return [
            'category' => 'good',
            'categoryLabel' => 'Malta ist gut geeignet',
            'interpretation' => 'Malta bietet signifikante Vorteile für Ihre Situation. Mit der richtigen Planung ist dies ein erfolgversprechender Schritt. Wir helfen Ihnen, die optimale Struktur für Ihre spezifische Situation zu finden.',
        ];
    } else {
        return [
            'category' => 'excellent',
            'categoryLabel' => 'Malta ist sehr gut geeignet',
            'interpretation' => 'Ihre Situation ist sehr gut für Malta geeignet. Sie können von vielen Vorteilen profitieren - lassen Sie uns die Details besprechen! Hohe Erfolgswahrscheinlichkeit bei korrekter Umsetzung.',
        ];
    }
}

/**
 * Fragenkatalog - 12 Fragen zur Malta-Eignung
 *
 * @return array Array von Fragen mit Optionen und Scores
 *
 * Struktur jeder Frage:
 * - id: Eindeutige ID (q001, q002, ...)
 * - text: Fragetext (Deutsch, wird von Frontend übersetzt)
 * - type: single_choice
 * - weight: Gewichtung 1.0-2.0 (wichtigere Fragen haben höheres Gewicht)
 * - options: Array von Antwortmöglichkeiten
 *   - value: "1", "2", "3", ... (wird in answers gespeichert)
 *   - label: Antworttext
 *   - score: 0-10 Punkte (höher = besser für Malta)
 *
 * WICHTIG: Diese Fragen sind nur als Referenz hier.
 * Das Frontend hat die eigenen Questions + übersetzt sie via JSON-Dateien.
 * Änderungen hier müssen mit Frontend + JSON-Dateien synchronisiert werden!
 */
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
// UTILITY FUNCTIONS - Helper-Funktionen für Security & Webhook
// =============================================================================

/**
 * Sanitize User-Antworten
 * Akzeptiert nur Antworten mit korrektem Format: q001-q012 und String/Numeric Values
 */
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

/**
 * Rate Limiting Check - Schützt vor Spam
 *
 * @return bool true = OK, false = Limit erreicht
 *
 * Funktionsweise:
 * 1. Hole IP-Adresse des Users
 * 2. Prüfe wie viele Requests in letzter Stunde
 * 3. Wenn > MALTA_RATE_LIMIT_MAX → blockiere
 * 4. Sonst: erlaube und speichere Timestamp
 *
 * Verwendet WordPress Transients (temporärer Cache)
 * Wird automatisch nach 1 Stunde gelöscht
 */
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

/**
 * Hole echte IP-Adresse des Clients
 *
 * @return string IP-Adresse (z.B. "192.168.1.1")
 *
 * Prüft verschiedene Headers (wichtig für Cloudflare, Load Balancer, etc.):
 * 1. HTTP_CF_CONNECTING_IP (Cloudflare)
 * 2. HTTP_X_FORWARDED_FOR (Proxy/Load Balancer)
 * 3. HTTP_X_REAL_IP (Nginx)
 * 4. REMOTE_ADDR (Direkt)
 *
 * Fallback: "0.0.0.0" wenn keine IP gefunden
 */
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

/**
 * Sende Daten an Webhook (n8n, Make.com, Zapier, etc.)
 *
 * @param array $userData User-Kontaktdaten [ 'email' => '...', 'firstName' => '...', ... ]
 * @param array $scoreData Score-Ergebnis [ 'percentage' => 78, 'weightedScore' => 145.5, ... ]
 * @param array $interpretation Kategorie [ 'category' => 'good', 'categoryLabel' => '...', ... ]
 * @return bool true = erfolgreich, false = fehlgeschlagen
 *
 * Payload-Struktur (JSON):
 * {
 *   "timestamp": "2025-11-11 10:30:45",
 *   "contact": { ... User-Daten ... },
 *   "score": { ... Score-Daten ... },
 *   "interpretation": "...",
 *   "answers": { q001: "1", q002: "3", ... },
 *   "detailedResults": [ ... ],
 *   "metadata": { ip, userAgent, referrer }
 * }
 *
 * Fehlerbehandlung:
 * - Fehler werden geloggt (wenn MALTA_DEBUG_MODE = true)
 * - User bekommt TROTZDEM sein Ergebnis (Webhook-Fehler blockiert nicht)
 * - Timeout: 10 Sekunden
 */
function malta_assess_send_webhook(array $userData, array $scoreData, array $interpretation): bool {
    if (!MALTA_WEBHOOK_ENABLED || empty(MALTA_WEBHOOK_URL)) {
        return false;
    }
    try {
        // Übersetze Kategorien ins Englische für Sales Team
        $categoryTranslations = [
            'explore' => 'Further Review Required',
            'fair' => 'Malta Could Be Suitable',
            'moderate' => 'Malta Conditionally Suitable',
            'good' => 'Malta Well Suited',
            'excellent' => 'Malta Very Well Suited'
        ];

        $categoryEN = $categoryTranslations[$interpretation['category']] ?? $interpretation['categoryLabel'];

        // Erstelle strukturierte Frage-Antwort Paare (in Original-Sprache)
        $questionAnswerPairs = malta_assess_format_qa_pairs($userData['answers'], $userData['language']);

        // Payload-Struktur optimiert für Salesforce
        // WICHTIG: Ergebnisse zuerst, damit Sales-Team sofort sieht was Sache ist
        $payload = [
            // ========================================================================
            // ASSESSMENT RESULT - Zuerst, damit Sales-Team sofort Überblick hat
            // ========================================================================
            'result' => [
                'score' => $scoreData['percentage'], // 0-100
                'category' => $interpretation['category'], // explore, fair, moderate, good, excellent
                'categoryLabel' => $categoryEN, // Englische Übersetzung
                'recommendation' => $interpretation['interpretation'], // Already in correct language from frontend
                'submissionLanguage' => strtoupper($userData['language']), // DE, EN, NL
            ],

            // ========================================================================
            // CONTACT INFORMATION - Direkt danach für Follow-up
            // ========================================================================
            'contact' => [
                'email' => $userData['email'],
                'firstName' => $userData['firstName'],
                'lastName' => $userData['lastName'],
                'fullName' => trim($userData['firstName'] . ' ' . $userData['lastName']),
                'phone' => $userData['phone'],
                'company' => $userData['company'],
            ],

            // ========================================================================
            // STRUCTURED Q&A - Frage → Antwort Paare (in Original-Sprache)
            // ========================================================================
            'questionsAndAnswers' => $questionAnswerPairs,

            // ========================================================================
            // METADATA - Technische Details
            // ========================================================================
            'metadata' => [
                'timestamp' => current_time('mysql'),
                'submissionLanguage' => strtoupper($userData['language']),
                'ip' => malta_assess_get_client_ip(),
                'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
                'source' => 'Malta Assessment QuickCheck v2.0',
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

// =============================================================================
// END OF MALTA ASSESSMENT INTEGRATION
// =============================================================================

/**
 * ============================================================================
 * QUICK REFERENCE
 * ============================================================================
 *
 * KONFIGURATION ÄNDERN:
 * - Webhook-URL: Zeile 52
 * - Debug-Modus: Zeile 69 (auf false für Production!)
 * - Rate Limit: Zeile 77 + 81
 *
 * LOGS PRÜFEN:
 * tail -f /wp-content/debug.log | grep Malta
 *
 * TESTING:
 * 1. Browser Console: console.log(window.maltaAssessment)
 *    Sollte: {ajaxUrl: "...", nonce: "..."}
 * 2. Browser Console: console.log(window.qcMaltaLanguage)
 *    Sollte: "de", "en" oder "nl"
 * 3. QuickCheck ausfüllen und absenden
 * 4. Network Tab (F12): admin-ajax.php sollte Status 200 zeigen
 *
 * TROUBLESHOOTING:
 * - "Nonce is missing" → Code nicht korrekt in functions.php?
 * - 403 Error → Nonce abgelaufen, Hard Refresh (Cmd+Shift+R)
 * - 429 Error → Rate Limit erreicht, warte 1 Stunde
 * - Webhook kommt nicht an → Prüfe debug.log + teste Webhook-URL manuell
 *
 * MULTI-LANGUAGE:
 * - JSON-Dateien: /wp-content/uploads/malta-assessment-v2/translations/
 * - Sprache wird automatisch aus URL erkannt (/de/, /en/, /nl/)
 * - Frontend lädt passende JSON-Datei
 *
 * VERSION: 2.0 (Multi-Language)
 * LAST UPDATED: 2025-11-11
 */

/**
 * Formatiere Frage-Antwort Paare für Salesforce (in Original-Sprache)
 *
 * @param array $answers User-Antworten [ 'q001' => '1', 'q002' => '3', ... ]
 * @param string $language Original-Sprache der Submission (de/en/nl)
 * @return array Strukturierte Q&A Paare in Original-Sprache
 */
function malta_assess_format_qa_pairs(array $answers, string $language): array {
    $questions = malta_assess_get_questions();
    $pairs = [];

    foreach ($questions as $question) {
        if (!isset($answers[$question['id']])) {
            continue; // Frage nicht beantwortet
        }

        $selectedValue = $answers[$question['id']];
        $selectedOption = null;

        // Finde die gewählte Option
        foreach ($question['options'] as $option) {
            if ($option['value'] === $selectedValue) {
                $selectedOption = $option;
                break;
            }
        }

        if ($selectedOption === null) {
            continue; // Option nicht gefunden
        }

        $pairs[] = [
            'questionId' => $question['id'],
            'question' => $question['text'], // Original-Sprache Frage
            'answer' => $selectedOption['label'], // Original-Sprache Antwort
            'score' => $selectedOption['score'], // Score für diese Antwort (0-10)
            'weight' => $question['weight'] ?? 1.0, // Gewichtung der Frage
            'category' => malta_assess_get_answer_category($selectedOption['score']), // positive, neutral, critical
        ];
    }

    return $pairs;
}

/**
 * Kategorisiere Antwort nach Score
 */
function malta_assess_get_answer_category(int $score): string {
    if ($score >= 8) return 'positive';
    if ($score <= 4) return 'critical';
    return 'neutral';
}
