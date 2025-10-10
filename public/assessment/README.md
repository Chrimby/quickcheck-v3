# B2B Marketing Assessment - WordPress Integration

## Übersicht
Dieses Assessment-Tool ist ein standalone HTML-Questionnaire basierend auf dem Brixon 4R-System. Es ist vollständig in sich geschlossen und kann einfach in WordPress integriert werden.

## Features
- ✅ Multi-Step Questionnaire mit 35+ Fragen
- ✅ 4 Marketing-Phasen: Reach, Relate, Respond, Retain
- ✅ Automatische Score-Berechnung (max. 121 Punkte)
- ✅ Interaktive Ergebnisseite mit Visualisierungen
- ✅ Webhook-Integration für Datenübertragung
- ✅ Responsive Design für alle Geräte
- ✅ Microinteractions und Animationen
- ✅ Branding: Schwarz/Weiß mit #f7e74f Akzent

## Dateien
- `index.html` - Haupt-Datei mit embedded CSS und JavaScript
- `README.md` - Diese Anleitung

**Gesamt: 2 Dateien** (Sie wollten max. 5)

## WordPress Integration

### Methode 1: Als separate Seite einbinden

1. **Datei hochladen:**
   - Laden Sie `index.html` in Ihr WordPress Media Library hoch
   - Oder speichern Sie die Datei auf Ihrem Server im Ordner `/wp-content/uploads/assessment/`

2. **Neue Seite erstellen:**
   - Gehen Sie zu WordPress → Seiten → Neu hinzufügen
   - Titel: "Marketing Assessment" (oder beliebig)
   - Wählen Sie "Vollbreite" als Template (falls verfügbar)

3. **HTML Block einfügen:**
   - Fügen Sie einen "Custom HTML" Block ein
   - Kopieren Sie folgenden Code:

```html
<iframe 
    src="/wp-content/uploads/assessment/index.html" 
    style="width: 100%; min-height: 100vh; border: none;"
    title="B2B Marketing Assessment">
</iframe>
```

### Methode 2: Direkt in eine Seite einbetten

1. **Neuen Custom HTML Block erstellen**
2. **Kopieren Sie den kompletten Inhalt der `index.html` Datei**
3. **Fügen Sie ihn in den Custom HTML Block ein**

### Methode 3: Shortcode (Advanced)

Erstellen Sie einen Shortcode in Ihrer `functions.php`:

```php
function brixon_assessment_shortcode() {
    ob_start();
    include(ABSPATH . 'wp-content/uploads/assessment/index.html');
    return ob_get_clean();
}
add_shortcode('brixon_assessment', 'brixon_assessment_shortcode');
```

Dann verwenden Sie einfach `[brixon_assessment]` auf jeder Seite.

## Webhook Konfiguration

### Webhook URL einrichten

1. **Öffnen Sie die `index.html` Datei**
2. **Suchen Sie nach Zeile ~430:**
```javascript
const CONFIG = {
    webhookUrl: 'YOUR_WEBHOOK_URL_HERE', // <-- Hier ändern
    ...
}
```

3. **Ersetzen Sie `YOUR_WEBHOOK_URL_HERE` mit Ihrer Webhook-URL**

Beispiele:
- Make.com (Integromat): `https://hook.eu1.make.com/xxxxxxxxxxxxx`
- Zapier: `https://hooks.zapier.com/hooks/catch/xxxxx/xxxxx/`
- n8n: `https://your-n8n-instance.com/webhook/xxxxx`
- Eigener Server: `https://ihre-domain.de/api/assessment-webhook`

### Datenformat des Webhooks

Der Webhook empfängt folgende Daten im JSON-Format:

```json
{
  "timestamp": "2025-01-10T10:30:00.000Z",
  "answers": {
    "q_001": "26-50",
    "q_002": "16-30",
    "q_101": "3",
    ...
  },
  "scores": {
    "reach": 18,
    "relate": 15,
    "respond": 12,
    "retain": 10,
    "total": 55
  },
  "interpretation": "Grundlagen vorhanden, aber noch sehr viel Potenzial."
}
```

### Webhook Testing

Zum Testen können Sie eine dieser kostenlosen Services nutzen:
- [webhook.site](https://webhook.site) - Sofort einsatzbereit, zeigt alle eingehenden Requests
- [requestbin.com](https://requestbin.com) - Ähnlich wie webhook.site

## Branding Anpassungen

### ⚠️ WICHTIG: area-normal und area-extended Fonts konfigurieren

**Das Assessment ist vorkonfiguriert für Ihre area Fonts, aber Sie müssen die Font-Dateien selbst einbinden!**

Die App nutzt aktuell `Work Sans` als **Fallback**. Folgen Sie diesen Schritten um Ihre Branding-Fonts zu aktivieren:

#### Schritt 1: Font-Dateien vorbereiten

Stellen Sie sicher, dass Sie haben:
- `area-normal.woff2` (Body-Text)
- `area-extended.woff2` (Headlines)

Optional auch `.woff` Format für ältere Browser.

#### Schritt 2: Fonts hochladen

Laden Sie die Font-Dateien in WordPress hoch:
```
WordPress → Medien → Dateien hochladen
Notieren Sie die URLs z.B.:
- https://ihre-domain.de/wp-content/uploads/fonts/area-normal.woff2
- https://ihre-domain.de/wp-content/uploads/fonts/area-extended.woff2
```

#### Schritt 3: Font-URLs in index.html aktualisieren

Die @font-face Regeln sind **bereits in index.html** (Zeilen 23-39). Sie müssen nur die URLs anpassen:

**Standard-Pfad (falls Fonts unter /wp-content/uploads/fonts/ liegen):**
- Die URLs sind bereits korrekt: `/wp-content/uploads/fonts/area-normal.woff2`
- Einfach die Fonts dort hochladen und fertig!

**Falls Fonts woanders liegen:**
Öffnen Sie `index.html` und passen Sie **Zeile 25 und 26** sowie **34 und 35** an:

```css
/* Beispiel: Fonts in einem anderen Verzeichnis */
src: url('/ihr/anderer/pfad/area-normal.woff2') format('woff2'),
     url('/ihr/anderer/pfad/area-normal.woff') format('woff');
```

**Falls Fonts von externer URL kommen:**
```css
src: url('https://cdn.ihre-domain.de/fonts/area-normal.woff2') format('woff2');
```

**Fertig!** Die CSS-Variablen sind bereits konfiguriert:
```css
--font-normal: 'area-normal', 'Work Sans', ...    /* Bereits drin! */
--font-extended: 'area-extended', 'Work Sans', ... /* Bereits drin! */
```

#### Alternative: Adobe Fonts / Typekit

Falls Ihre Fonts bei Adobe Fonts gehostet sind:

```html
<!-- Im <head> vor den Styles einfügen -->
<link rel="stylesheet" href="https://use.typekit.net/IHRE_ID.css">
```

Dann Schritt 3 überspringen - die Fonts werden automatisch geladen.

### Farben anpassen

Die Farben sind als CSS Custom Properties definiert und können einfach geändert werden:

```css
:root {
    --color-black: #000000;      /* Hauptfarbe */
    --color-white: #FFFFFF;      /* Hintergrund */
    --color-yellow: #f7e74f;     /* Akzentfarbe */
}
```

## Styling Anpassungen

### Border-Radius ändern

```css
:root {
    --radius-sm: 12px;   /* Kleine Elemente */
    --radius-md: 20px;   /* Medium */
    --radius-lg: 32px;   /* Große Cards */
    --radius-xl: 48px;   /* Hero Sections */
    --radius-full: 9999px; /* Buttons */
}
```

### Button-Stil anpassen

Der Button-Stil entspricht Ihrem Screenshot (Schwarz mit gelbem Kreis-Akzent):

```css
.btn-primary {
    background: var(--color-black);
    color: var(--color-white);
    /* Gelber Kreis wird automatisch eingefügt */
}
```

## Erweiterte Anpassungen

### Fragen hinzufügen/entfernen

1. Öffnen Sie `index.html`
2. Suchen Sie nach `const questions = [` (ca. Zeile 450)
3. Fügen Sie neue Fragen im gleichen Format hinzu:

```javascript
{
    id: 'q_108',
    section: 'reach',
    type: 'single_choice',
    text: 'Ihre neue Frage?',
    scoringQuestion: true,  // Falls es Punkte geben soll
    options: [
        { value: '1', label: 'Option 1', score: 0 },
        { value: '2', label: 'Option 2', score: 5 }
    ]
}
```

### Scoring-Logik anpassen

Die maximalen Scores pro Phase sind:
- Reach: 35 Punkte (5 Fragen)
- Relate: 32 Punkte (5 Fragen)
- Respond: 27 Punkte (5 Fragen)
- Retain: 27 Punkte (5 Fragen)
- **Total: 121 Punkte**

Passen Sie die `getInterpretation()` Funktion an, um eigene Score-Bereiche zu definieren.

## Tracking & Analytics

### Google Analytics einbinden

Fügen Sie vor `</head>` ein:

```html
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'GA_MEASUREMENT_ID');
</script>
```

### Event Tracking für Fragen

Sie können Custom Events tracken:

```javascript
// Bei jeder beantworteten Frage
gtag('event', 'question_answered', {
    'question_id': questionId,
    'section': currentQuestion.section
});
```

## Performance Optimierung

### Lazy Loading

Das Assessment lädt alles sofort. Bei sehr langsamen Verbindungen können Sie Critical CSS inline lassen und den Rest später laden.

### Caching

Stellen Sie sicher, dass Ihr Server diese Header setzt:

```
Cache-Control: public, max-age=31536000
```

## Browser-Unterstützung

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Mobile Safari (iOS 14+)
- ✅ Chrome Mobile

## Troubleshooting

### Problem: Webhook wird nicht gesendet

**Lösung:**
1. Prüfen Sie die Browser Console auf Fehler (F12 → Console)
2. Überprüfen Sie die Webhook-URL im Code
3. Testen Sie die Webhook-URL mit webhook.site

### Problem: Styling sieht anders aus in WordPress

**Lösung:**
1. Ihr WordPress Theme hat evtl. globale Styles die überschreiben
2. Fügen Sie `!important` bei kritischen Styles hinzu
3. Oder wrappen Sie alles in einen Container mit eigenem Namespace:

```css
.brixon-assessment * {
    /* Alle Styles hier */
}
```

### Problem: Fortschrittsbalken bleibt bei 0%

**Lösung:**
- JavaScript-Fehler in der Console prüfen
- Stellen Sie sicher, dass JavaScript nicht vom Theme blockiert wird

### Problem: Fonts laden nicht

**Lösung:**
1. Prüfen Sie den Font-Pfad
2. Stellen Sie sicher, CORS-Header erlauben Font-Loading
3. Nutzen Sie Google Fonts als Fallback

## Support & Weitere Anpassungen

Für weitere Anpassungen:

1. **HTML/CSS Grundkenntnisse:** Die Datei ist einfach zu bearbeiten
2. **JavaScript:** Die Logik ist klar kommentiert und modular
3. **Professionelle Hilfe:** Kontaktieren Sie einen Web-Entwickler

## Datenschutz (DSGVO)

**Wichtig:** Ergänzen Sie:

1. **Datenschutzerklärung:** Link zur Datenschutzerklärung bei Kontaktformular
2. **Cookie-Hinweis:** Falls Sie Analytics verwenden
3. **Opt-in:** Die Checkbox für detaillierte Analyse ist bereits implementiert

Beispiel Cookie-Hinweis:

```html
<div class="cookie-notice">
    Wir verwenden Cookies für Analytics. 
    <a href="/datenschutz">Mehr erfahren</a>
</div>
```

## Nächste Schritte

1. ✅ `index.html` in WordPress hochladen
2. ✅ Webhook-URL konfigurieren
3. ✅ Fonts anpassen (optional)
4. ✅ Auf verschiedenen Geräten testen
5. ✅ Go Live! 🚀

---

**Version:** 1.0  
**Letzte Aktualisierung:** Januar 2025  
**Entwickelt für:** Brixon Group

Bei Fragen: Dokumentation durchlesen oder Web-Entwickler kontaktieren.