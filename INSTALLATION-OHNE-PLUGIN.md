# Malta Assessment Installation - OHNE Plugin

## Quick Fix (5 Minuten)

### Schritt 1: functions.php öffnen

Öffne die `functions.php` deines WordPress-Themes:

```
/wp-content/themes/DEIN-THEME/functions.php
```

**Häufige Theme-Pfade:**
- Hello Elementor: `/wp-content/themes/hello-elementor/functions.php`
- Astra: `/wp-content/themes/astra/functions.php`
- Custom Theme: `/wp-content/themes/drwerner/functions.php`

### Schritt 2: Code einfügen

Kopiere den **gesamten Inhalt** von `functions-php-integration.php` und füge ihn **ans Ende** der `functions.php` ein.

**WICHTIG**:
- Füge den Code NACH dem letzten `?>` ein (falls vorhanden)
- Oder einfach ans Ende der Datei

### Schritt 3: Webhook-URL konfigurieren (Optional)

Finde diese Zeile im eingefügten Code:

```php
define('MALTA_WEBHOOK_URL', 'https://hook.eu2.make.com/YOUR_WEBHOOK_ID_HERE');
```

Ersetze mit deiner echten Webhook-URL (Make.com oder n8n).

### Schritt 4: Datei speichern & hochladen

- Speichere die `functions.php`
- Lade sie per FTP/SFTP hoch (überschreibe die alte)
- **ODER** bearbeite direkt in WordPress: `Design → Theme-Editor → functions.php`

### Schritt 5: Testen

1. Öffne die Malta Assessment Seite
2. Öffne Browser Console (F12)
3. Prüfe:

```javascript
console.log(window.maltaAssessment);
// Sollte ausgeben:
// {ajaxUrl: "https://www.drwerner.com/wp-admin/admin-ajax.php", nonce: "abc123..."}
```

4. Fülle den QuickCheck aus und sende ab
5. **Erfolg!** Kein 400-Fehler mehr

## Verification Checklist

✅ **Nonce ist vorhanden**:
```javascript
window.maltaAssessment.nonce !== ""
```

✅ **AJAX-Endpunkt antwortet**:
```
Response status: 200 (nicht 400!)
```

✅ **Daten werden verarbeitet**:
```javascript
Server result: {success: true, data: {...}}
```

✅ **Debug Log zeigt Aktivität** (wenn `MALTA_DEBUG_MODE = true`):
```bash
tail -f /wp-content/debug.log
# [Malta] Score: 78% for max@example.com
```

## Troubleshooting

### Problem: Weißer Bildschirm nach Upload

**Ursache**: Syntax-Fehler in functions.php

**Lösung**:
1. Via FTP die alte `functions.php` wiederherstellen
2. Prüfe ob du den Code richtig eingefügt hast
3. Stelle sicher, dass kein `?>` am Ende der Datei ist (nicht nötig in PHP)

### Problem: "Nonce is missing" weiterhin

**Ursache**: Code wurde nicht geladen oder Theme nutzt Child Theme

**Lösung 1 - Child Theme**:
Wenn du ein Child Theme nutzt, füge den Code in:
```
/wp-content/themes/DEIN-CHILD-THEME/functions.php
```

**Lösung 2 - Prüfe ob Code aktiv ist**:
```php
// Füge temporär diese Zeile am Anfang des eingefügten Codes ein:
error_log('[Malta] Integration loaded!');

// Dann prüfe Debug Log:
tail -f /wp-content/debug.log
```

### Problem: 403 "Security check failed"

**Ursache**: Nonce ist abgelaufen

**Lösung**:
- Hard Refresh: `Cmd + Shift + R` (Mac) oder `Ctrl + Shift + R` (Windows)
- Nonce wird bei jedem Seitenaufruf neu generiert

### Problem: Webhook kommt nicht an

**Ursache**: Falsche Webhook-URL oder Webhook-Service ist down

**Lösung**:
1. Prüfe Debug Log:
   ```bash
   tail -f /wp-content/debug.log | grep Webhook
   ```

2. Teste Webhook manuell:
   ```bash
   curl -X POST https://hook.eu2.make.com/YOUR_ID \
     -H "Content-Type: application/json" \
     -d '{"test": "manual_test"}'
   ```

3. Webhook temporär deaktivieren:
   ```php
   define('MALTA_WEBHOOK_ENABLED', false);
   ```

## Production Ready Checklist

Bevor du live gehst:

- [ ] Webhook-URL ist korrekt konfiguriert
- [ ] Debug-Modus ist deaktiviert: `define('MALTA_DEBUG_MODE', false);`
- [ ] Rate Limiting ist aktiv (Standard: 10 Requests/Stunde)
- [ ] QuickCheck funktioniert ohne Fehler
- [ ] Webhook-Integration wurde getestet

## Konfigurationsoptionen

### Debug-Modus (Development)

```php
define('MALTA_DEBUG_MODE', true);  // Logging aktivieren
```

Logs: `/wp-content/debug.log`

### Rate Limiting

```php
define('MALTA_RATE_LIMIT_MAX', 10);      // Max Requests
define('MALTA_RATE_LIMIT_WINDOW', 3600); // Pro Stunde
```

### Webhook

```php
define('MALTA_WEBHOOK_URL', 'https://your-webhook.com/endpoint');
define('MALTA_WEBHOOK_ENABLED', true);
```

## Unterschied zu Plugin-Lösung

| Aspekt | functions.php | WordPress Plugin |
|--------|---------------|------------------|
| Installation | Copy & Paste | Upload & Aktivieren |
| Updates | Manuell | Automatisch (wenn public) |
| Deaktivierung | Code entfernen | Plugin deaktivieren |
| Übersichtlichkeit | Im Theme verborgen | Eigener Plugin-Eintrag |
| Best Practice | ❌ Nicht empfohlen | ✅ Empfohlen |

**Warum funktioniert es trotzdem?**
WordPress lädt `functions.php` bei jedem Seitenaufruf → Code wird immer ausgeführt.

## Weitere Hilfe

Bei Problemen:
1. Aktiviere `MALTA_DEBUG_MODE`
2. Prüfe `/wp-content/debug.log`
3. Prüfe Browser Console (F12 → Console)
4. Prüfe Network Tab (F12 → Network → admin-ajax.php)

---

**Geschätzte Installationszeit**: 5 Minuten
**Schwierigkeitsgrad**: Einfach
