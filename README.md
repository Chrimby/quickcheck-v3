# Malta Assessment QuickCheck

Interaktiver Eignungscheck für Malta-Interessenten mit WordPress-Integration und n8n Webhook.

## 📁 Projektstruktur

```
qc-malta-server/
├── public/
│   └── malta-assessment-v2/
│       └── update.html              # Haupt-QuickCheck Datei (produktiv)
├── functions-php-integration.php    # WordPress Integration Code
├── INSTALLATION-OHNE-PLUGIN.md      # Installationsanleitung
└── README.md                        # Diese Datei
```

## 🚀 Quick Start

### 1. QuickCheck in WordPress einbinden

**Option A: HTML direkt einbinden (Elementor/Custom HTML)**
```html
<!-- Kopiere den Inhalt von public/malta-assessment-v2/update.html -->
```

**Option B: Via iframe**
```html
<iframe src="/malta-assessment-v2/update.html" width="100%" height="800"></iframe>
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

## 📋 Changelog

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

**Version:** 2.0
**Last Updated:** 2025-11-11
**Author:** Dr. Werner & Partner
