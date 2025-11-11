# Malta Assessment QuickCheck

Interaktiver Eignungscheck für Malta-Interessenten mit WordPress-Integration und n8n Webhook.

## 📁 Projektstruktur

```
qc-malta-server/
├── public/
│   └── malta-assessment-v2/
│       ├── update-de.html           # QuickCheck Deutsch
│       ├── update-en.html           # QuickCheck English
│       ├── update-nl.html           # QuickCheck Nederlands
│       └── translations/
│           ├── de.json              # Deutsche Übersetzungen
│           ├── en.json              # English Translations
│           └── nl.json              # Nederlandse vertalingen
├── functions-php-integration.php    # WordPress Integration Code
├── INSTALLATION-OHNE-PLUGIN.md      # Installationsanleitung
├── CLAUDE.md                        # Development Guidelines
└── README.md                        # Diese Datei
```

## 🚀 Quick Start

### 1. QuickCheck in WordPress einbinden

**Option A: HTML direkt einbinden (Elementor/Custom HTML)**
```html
<!-- Deutsch -->
<!-- Kopiere den Inhalt von public/malta-assessment-v2/update-de.html -->

<!-- English -->
<!-- Kopiere den Inhalt von public/malta-assessment-v2/update-en.html -->

<!-- Nederlands -->
<!-- Kopiere den Inhalt von public/malta-assessment-v2/update-nl.html -->
```

**Option B: Via iframe**
```html
<!-- Deutsch -->
<iframe src="/malta-assessment-v2/update-de.html" width="100%" height="800"></iframe>

<!-- English -->
<iframe src="/malta-assessment-v2/update-en.html" width="100%" height="800"></iframe>

<!-- Nederlands -->
<iframe src="/malta-assessment-v2/update-nl.html" width="100%" height="800"></iframe>
```

### 2. WordPress Integration aktivieren

1. Öffne deine WordPress Theme `functions.php`
2. Kopiere den kompletten Inhalt von `functions-php-integration.php`
3. Füge ihn ans Ende der `functions.php` ein
4. Speichern - Fertig!

**Webhook ist bereits konfiguriert:**
```php
define('MALTA_WEBHOOK_URL', 'https://brixon.app.n8n.cloud/webhook/dwp-quickcheck');
```

## ✨ Features

- **12 Fragen** für präzise Eignung
- **3 Sprachen**: Deutsch, English, Nederlands
- **Dynamische Übersetzungen** via JSON (Backend + Frontend)
- **Echtzeit-Berechnung** via WordPress AJAX
- **Webhook-Integration** zu n8n
- **Responsive Design** (Mobile-First)
- **Brand-konform** (Dr. Werner & Partner Design System)
- **Security**: Nonce-Verification, Rate Limiting, Input Sanitization

## 🔧 Konfiguration

In `functions-php-integration.php`:

```php
// Debug-Modus (für Production auf false setzen)
define('MALTA_DEBUG_MODE', true);

// Rate Limiting
define('MALTA_RATE_LIMIT_MAX', 10);     // Max Requests pro Stunde
define('MALTA_RATE_LIMIT_WINDOW', 3600);

// Webhook
define('MALTA_WEBHOOK_ENABLED', true);
define('MALTA_WEBHOOK_URL', 'https://brixon.app.n8n.cloud/webhook/dwp-quickcheck');
```

## 📤 Webhook Payload

```json
{
  "timestamp": "2025-11-11 10:30:45",
  "contact": {
    "email": "max@example.com",
    "firstName": "Max",
    "lastName": "Mustermann",
    "phone": "+49 123 456789",
    "company": "Example GmbH",
    "language": "de"
  },
  "score": {
    "percentage": 78,
    "weightedScore": 145.5,
    "totalPossibleWeightedScore": 186.0,
    "category": "good",
    "categoryLabel": "Malta ist sehr gut geeignet"
  },
  "interpretation": "Großartig! Malta bietet...",
  "answers": {
    "q001": "3",
    "q002": "4",
    ...
  },
  "detailedResults": [...],
  "metadata": {
    "ip": "192.168.1.1",
    "userAgent": "Mozilla/5.0...",
    "referrer": "https://..."
  }
}
```

## 🐛 Debugging

### JavaScript Console prüfen:
```javascript
console.log(window.maltaAssessment);
// Sollte ausgeben: {ajaxUrl: "...", nonce: "abc123..."}
```

### WordPress Debug Log:
```bash
tail -f /wp-content/debug.log
```

### Häufige Probleme:

**"Nonce is missing"**
→ functions-php-integration.php nicht korrekt eingefügt

**400 Error**
→ AJAX-Endpunkt nicht registriert, WordPress-Integration fehlt

**Webhook kommt nicht an**
→ Prüfe Debug Log, teste Webhook-URL manuell mit curl

## 🌍 Multi-Language Support

Das System unterstützt vollständig **Deutsch**, **English** und **Nederlands**:

### Backend (PHP)
- `malta_assess_load_translations($language)` - Lädt Übersetzungen aus JSON
- `malta_assess_get_interpretation($percentage, $language)` - Gibt sprachspezifische Kategorien zurück
- Automatische Spracherkennung via `$_POST['language']` Parameter

### Frontend (HTML/JS)
- 3 separate HTML-Dateien (`update-de.html`, `update-en.html`, `update-nl.html`)
- JSON-basierte Übersetzungen in `/translations/` Ordner
- Dynamische UI-Übersetzung für alle Elemente (Buttons, Ergebnisse, CTAs)

### CTA Links (sprachspezifisch)
- **DE**: `/de/weiteres/terminvereinbarung/`
- **EN**: `/en/other/book-an-appointment/`
- **NL**: `/nl/overige/een-afspraak-maken/`

### Privacy Policy (alle Sprachen gleich)
- **Alle**: `/en/other/datenschutzerklaerung/`

Siehe `CLAUDE.md` für detaillierte Dokumentation zur Multi-Language-Implementierung.

---

## 📋 Changelog

### v2.1 (2025-11-11) - Multi-Language Update
- ✅ Vollständige Multi-Language-Unterstützung (DE, EN, NL)
- ✅ Backend Translation Loader in PHP
- ✅ JSON-basierte Übersetzungsdateien
- ✅ Dynamische UI-Übersetzungen (Frontend)
- ✅ Sprachspezifische CTA-Links
- ✅ Ergebnisseite vollständig übersetzt (Kategorien, Details, CTAs)

### v2.0 (2025-11-11)
- ✅ Formular-Felder vereinheitlicht (gleiche Größe, Border-Radius)
- ✅ Submit-Button unter Formular verschoben
- ✅ Progress Bar Z-Index auf 0
- ✅ Q012 (Familie) behalten, Freitext-Felder entfernt
- ✅ n8n Webhook konfiguriert

## 🔒 Security

- ✅ WordPress Nonce Verification
- ✅ Input Sanitization (alle Felder)
- ✅ Rate Limiting (IP-basiert)
- ✅ CSRF Protection
- ✅ No SQL Injection (Prepared Statements)

## 📞 Support

Bei Problemen:
1. Debug-Modus aktivieren
2. Console Output prüfen (Browser DevTools)
3. WordPress Debug Log checken
4. Network Tab prüfen (admin-ajax.php Response)

---

**Version:** 2.1 (Multi-Language)
**Last Updated:** 2025-11-11
**Author:** Dr. Werner & Partner
**Repository:** https://github.com/Chrimby/quickcheck-v3
