# 🚀 Malta Assessment Server-Side - Quick Start

## Was ist das?

Server-seitige Auswertungslogik für das Malta Assessment Questionnaire. Die Scoring-Regeln sind damit unsichtbar für User und können nicht manipuliert werden.

## Warum brauche ich das?

**Vorher (Client-Side):**
- ❌ Scoring-Logik im Browser sichtbar
- ❌ User können Punktzahlen manipulieren
- ❌ Geschäftsregeln sind öffentlich

**Nachher (Server-Side):**
- ✅ Scoring-Logik komplett geheim
- ✅ Keine Manipulation möglich
- ✅ Professionell und sicher

## Files in diesem Worktree

```
qc-malta-server/
├── malta-assessment-evaluator.php    ← Haupt-Script (auf Server deployen)
├── client-integration-example.js     ← Code für HTML Update
├── README.md                          ← Vollständige Dokumentation
├── DEPLOYMENT-CHECKLIST.md            ← Schritt-für-Schritt Anleitung
├── test-evaluator.php                 ← Test Script (optional)
└── QUICKSTART.md                      ← Dieses Dokument
```

## Schnellstart (5 Minuten)

### 1. Upload PHP Script

**Option A: WordPress (empfohlen)**
```
Upload: malta-assessment-evaluator.php
Nach:   /wp-content/themes/[your-theme]/malta-assessment-evaluator.php
```

**Option B: Standalone**
```
Upload: malta-assessment-evaluator.php
Nach:   /public_html/api/malta-evaluator.php
```

### 2. Configure Domain

Öffne `malta-assessment-evaluator.php` und ändere Zeile 35:

```php
const ALLOWED_ORIGINS = [
    'https://www.drwerner.com',  // ← Deine Domain hier
];
```

### 3. Add WordPress Endpoint (nur Option A)

Öffne `functions.php` und füge hinzu:

```php
add_action('rest_api_init', function () {
    register_rest_route('drwerner/v1', '/malta-evaluator', [
        'methods' => 'POST',
        'callback' => function() {
            require_once get_template_directory() . '/malta-assessment-evaluator.php';
            exit;
        },
        'permission_callback' => '__return_true',
    ]);
});
```

### 4. Update HTML

Öffne `public/malta-assessment-v2-dwp/index.html`

**A) Add Configuration (Zeile ~1438):**
```javascript
const API_ENDPOINT = 'https://www.drwerner.com/wp-json/drwerner/v1/malta-evaluator';
```

**B) Replace calculateScore() (Zeile ~1982):**
```javascript
async function calculateScore() {
    const response = await fetch(API_ENDPOINT, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({answers: answers})
    });
    const result = await response.json();
    return result.data;
}
```

**C) Update calculateAndShowResults() (Zeile ~2038):**
```javascript
async function calculateAndShowResults() {
    // Show loading
    resultsScreen.innerHTML = '<div>Loading...</div>';

    // Get score from server
    const scoreData = await calculateScore();

    // Render results (rest bleibt gleich)
    renderResults(/* ... */);
}
```

> 💡 **Tipp:** Siehe `client-integration-example.js` für kompletten Code mit Error Handling

### 5. Test

Öffne Browser Console (F12) und teste:

```javascript
fetch('https://www.drwerner.com/wp-json/drwerner/v1/malta-evaluator', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    answers: {"q001": "4", "q002": "4", "q003": "4"}
  })
})
.then(r => r.json())
.then(console.log)
```

**Expected:**
```json
{
  "success": true,
  "data": {
    "percentage": 85,
    "category": "excellent",
    ...
  }
}
```

## Fertig! 🎉

Deine Auswertungslogik läuft jetzt server-seitig und ist unsichtbar für User.

## Next Steps

- ✅ **Production:** Setze `DEBUG_MODE = false` in PHP
- ✅ **Security:** Entferne `localhost` aus `ALLOWED_ORIGINS`
- ✅ **Monitoring:** Check Server Logs erste 24h
- ✅ **Testing:** Test verschiedene Score-Ranges

## Probleme?

### CORS Error
→ Prüfe `ALLOWED_ORIGINS` in PHP (Zeile 35)
→ Domain muss EXAKT matchen (mit `https://`)

### 500 Error
→ Aktiviere `DEBUG_MODE = true` in PHP
→ Check PHP Error Logs

### Rate Limit
→ Erhöhe `RATE_LIMIT_MAX_REQUESTS` in PHP (Zeile 43)

## Mehr Details

- **Vollständige Doku:** Siehe `README.md`
- **Deployment Guide:** Siehe `DEPLOYMENT-CHECKLIST.md`
- **Code Examples:** Siehe `client-integration-example.js`

## Support

Bei Fragen oder Problemen:
1. Lies `README.md` Troubleshooting Section
2. Check `DEPLOYMENT-CHECKLIST.md`
3. Aktiviere Debug Mode für Details

---

**Version:** 2.0
**Branch:** `malta-server-logic`
**Commit:** `380e73e`
