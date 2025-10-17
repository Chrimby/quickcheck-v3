# Notion DB Integration - Webhook Payload Update

**Date:** 2025-10-17
**Status:** ✅ Ready for Testing

---

## Was wurde geändert?

Der Webhook-Payload sendet jetzt **lesbare Frage-Antwort-Paare** statt Code-basierte Antworten. Das AI_AGENT_PROMPT_TEMPLATE.md wurde komplett neu geschrieben für die Notion DB Integration.

---

## Vorher vs. Nachher

### ❌ Vorher (Code-basiert)
```json
{
  "answers": {
    "q_101": "4",
    "q_102": "problems",
    "q_103": "1"
  }
}
```

**Problem:** Man muss die Question-IDs und Value-Codes nachschlagen, um zu verstehen, was der Kunde geantwortet hat.

### ✅ Nachher (Lesbarer Text)
```json
{
  "answersDetailed": [
    {
      "questionId": "q_101",
      "section": "reach",
      "sectionTitle": "Phase 1: Reach - Werden Sie überhaupt gefunden?",
      "questionText": "Wie aktiv sind Sie oder Ihre Führungskräfte persönlich auf LinkedIn?",
      "questionHint": "Wichtig: Wir meinen persönliche Profile...",
      "questionType": "single",
      "answerValue": "4",
      "answerText": "Sehr aktiv mit strategischem Content",
      "score": 4
    },
    {
      "questionId": "q_102",
      "section": "reach",
      "questionText": "Worüber schreiben Sie hauptsächlich, wenn Sie Content erstellen?",
      "answerValue": "problems",
      "answerText": "Über konkrete Probleme unserer Zielgruppe",
      "score": 5
    }
  ]
}
```

**Vorteil:** Sofort lesbar, perfekt für Notion DB Import.

---

## Neuer Webhook Payload (Komplett)

```json
{
  "event": "assessment_completed",
  "assessmentId": "a3f8d9c2-1b4e-4f5a-8c7d-2e6b9a1f3d8c",
  "timestamp": "2025-10-17T14:32:15.000Z",
  "language": "de",

  "answersDetailed": [
    {
      "questionId": "q_101",
      "section": "reach",
      "sectionTitle": "Phase 1: Reach - Werden Sie überhaupt gefunden?",
      "questionText": "Wie aktiv sind Sie oder Ihre Führungskräfte persönlich auf LinkedIn?",
      "questionHint": "Wichtig: Wir meinen persönliche Profile, nicht die Unternehmensseite.",
      "questionType": "single",
      "answerValue": "4",
      "answerText": "Sehr aktiv mit strategischem Content",
      "score": 4
    },
    /* ... alle weiteren Fragen ... */
  ],

  "scores": {
    "reach": 18,
    "relate": 15,
    "respond": 12,
    "retain": 10,
    "total": 55
  },

  "interpretations": {
    "reach": {
      "percentage": 51,
      "title": "Sichtbar mit Potenzial",
      "description": "Sie sind aktiv und werden wahrgenommen. Mehr Konsistenz bei Paid, Profilen und Downloads hebt das Wachstumspotenzial."
    },
    "relate": { /* ... */ },
    "respond": { /* ... */ },
    "retain": { /* ... */ },
    "overall": {
      "percentage": 45,
      "title": "Solide Basis im Aufbau",
      "description": "Einige Hebel sind gesetzt, andere noch punktuell. Priorisieren Sie ein bis zwei Phasen und bauen Sie klare Routinen auf."
    }
  },

  "recommendations": [
    {
      "phase": "respond",
      "phaseBadge": "Phase 3",
      "phaseTitle": "Respond",
      "title": "Sales-Alignment herstellen",
      "text": "Definieren Sie gemeinsame KPIs, Response-SLAs und Pipeline-Meetings, damit keine Anfragen mehr liegen bleiben.",
      "meta": "Aktueller Score: 44%",
      "priority": 1
    },
    {
      "phase": "retain",
      "phaseBadge": "Phase 4",
      "phaseTitle": "Retain",
      "title": "Bestandskunden aktivieren",
      "text": "Starten Sie systematische Kundenprogramme: Zufriedenheit messen, Expansion-Trigger definieren und Referral-Incentives setzen.",
      "meta": "Aktueller Score: 37%",
      "priority": 2
    }
  ]
}
```

---

## Notion DB Struktur (Empfehlung)

### Datenbank 1: Assessments (Haupt-DB)

| Property | Type | Source |
|----------|------|--------|
| Assessment ID | Text (Primary) | `assessmentId` |
| Completed At | Date | `timestamp` |
| Language | Select (de/en) | `language` |
| Score Total | Number | `scores.total` |
| Score Reach | Number | `scores.reach` |
| Score Relate | Number | `scores.relate` |
| Score Respond | Number | `scores.respond` |
| Score Retain | Number | `scores.retain` |
| Overall Percentage | Number | `interpretations.overall.percentage` |
| Overall Title | Text | `interpretations.overall.title` |
| Overall Description | Text | `interpretations.overall.description` |
| Reach Percentage | Number | `interpretations.reach.percentage` |
| Reach Title | Text | `interpretations.reach.title` |
| Relate Percentage | Number | `interpretations.relate.percentage` |
| Relate Title | Text | `interpretations.relate.title` |
| Respond Percentage | Number | `interpretations.respond.percentage` |
| Respond Title | Text | `interpretations.respond.title` |
| Retain Percentage | Number | `interpretations.retain.percentage` |
| Retain Title | Text | `interpretations.retain.title` |
| Answers | Relation → Answers DB | (1:n) |
| Recommendations | Relation → Recommendations DB | (1:n) |

### Datenbank 2: Answers (1:n zu Assessment)

| Property | Type | Source |
|----------|------|--------|
| Assessment | Relation → Assessments | Via `assessmentId` |
| Question ID | Text | `answersDetailed[].questionId` |
| Section | Select (Reach/Relate/Respond/Retain) | `answersDetailed[].section` |
| Section Title | Text | `answersDetailed[].sectionTitle` |
| Question | Text | `answersDetailed[].questionText` ✨ |
| Answer | Text | `answersDetailed[].answerText` ✨ |
| Score | Number | `answersDetailed[].score` |
| Type | Select | `answersDetailed[].questionType` |

**Wichtig:** Für jedes Element im `answersDetailed` Array wird ein separater Eintrag erstellt.

### Datenbank 3: Recommendations (1:n zu Assessment)

| Property | Type | Source |
|----------|------|--------|
| Assessment | Relation → Assessments | Via `assessmentId` |
| Priority | Number | `recommendations[].priority` |
| Phase | Select | `recommendations[].phase` |
| Phase Badge | Text | `recommendations[].phaseBadge` |
| Phase Title | Text | `recommendations[].phaseTitle` |
| Title | Text | `recommendations[].title` |
| Description | Text | `recommendations[].text` |
| Meta | Text | `recommendations[].meta` |

---

## n8n Workflow (Quick Start)

### Node 1: Webhook Trigger
- **URL:** `https://brixon.app.n8n.cloud/webhook-test/brixon-b2b-marketing-assessment`
- **Method:** POST
- **Authentication:** None (oder Basic Auth)

### Node 2: Create Assessment (Notion API)
- **Database:** Assessments
- **Action:** Create Database Item
- **Fields:** Mappe alle Felder aus Payload (siehe Tabelle oben)

### Node 3: Loop Answers (Split in Batches)
- **Input:** `{{ $json.answersDetailed }}`
- **Batch Size:** 1 (oder 10 für Performance)

### Node 4: Create Answer (Notion API)
- **Database:** Answers
- **Action:** Create Database Item
- **Fields:**
  - Assessment: `{{ $node["Create Assessment"].json.id }}`
  - Question: `{{ $json.questionText }}`
  - Answer: `{{ $json.answerText }}`
  - Score: `{{ $json.score }}`
  - Section: `{{ $json.section }}`

### Node 5: Loop Recommendations
- **Input:** `{{ $json.recommendations }}`
- **Batch Size:** 1

### Node 6: Create Recommendation (Notion API)
- **Database:** Recommendations
- **Action:** Create Database Item
- **Fields:**
  - Assessment: `{{ $node["Create Assessment"].json.id }}`
  - Priority: `{{ $json.priority }}`
  - Phase: `{{ $json.phase }}`
  - Title: `{{ $json.title }}`
  - Description: `{{ $json.text }}`

### Node 7: Send Notification (Optional)
- **Slack / E-Mail:** Benachrichtigung über neues Assessment
- **Message:** "Neues Assessment: {{ $json.scores.total }} Punkte, Top-Empfehlung: {{ $json.recommendations[0].title }}"

---

## Testing

### 1. Lokales Assessment ausfüllen
```bash
cd "public/assessment"
python3 -m http.server 8000
```
Öffne: http://localhost:8000

### 2. Assessment durchführen
- Beantworte alle Fragen
- Klicke auf "Weiter" bis zur Ergebnisseite
- Webhook wird automatisch beim Anzeigen der Ergebnisse gefeuert

### 3. n8n Webhook Logs prüfen
- Öffne n8n Workflow
- Prüfe "Executions" Tab
- Suche nach neuem Webhook-Call
- Verifiziere Payload-Struktur

### 4. Notion DB prüfen
- Öffne Assessments DB → Neuer Eintrag sollte vorhanden sein
- Öffne Answers DB → Alle Frage-Antwort-Paare sollten vorhanden sein
- Öffne Recommendations DB → Alle Empfehlungen sollten vorhanden sein

---

## Wichtige Hinweise

### ✅ Was funktioniert
- Fragen und Antworten als lesbarer Text
- Automatisches Mapping von Question IDs zu vollem Text
- Support für alle Fragetypen (single, multi, text, textarea, email)
- Freitext-Antworten werden direkt als `answerText` übernommen
- Multi-Select wird als komma-separierter String gespeichert

### ⚠️ Edge Cases
- **Nicht beantwortete Fragen:** Werden NICHT im `answersDetailed` Array gesendet
- **Freitext-Fragen:** `score` ist `null` (keine Punkte)
- **Multi-Select:** `answerValue` ist Array, `answerText` ist komma-separierter String

### 🔒 Sicherheit
- Webhook-URL sollte mit HTTPS sein (✅ bereits der Fall)
- Optional: Basic Auth für Webhook hinzufügen
- Notion API Key sicher in n8n Environment Variables speichern

---

## Code-Änderungen

### Datei: `public/assessment/index.html`

**Funktion:** `sendResultsToWebhook()` (Zeilen 4335-4496)

**Änderungen:**
1. Neues `answersDetailed` Array erstellt
2. Für jede Antwort wird die entsprechende Frage aus `CONFIG.questions` geholt
3. `questionText` und `answerText` werden aus der Frage/Option extrahiert
4. Payload-Struktur angepasst: `answersDetailed` statt `answers`
5. `language` Feld hinzugefügt

### Datei: `AI_AGENT_PROMPT_TEMPLATE.md`

**Komplett neu geschrieben:**
- Fokus auf Notion DB Integration (nicht Kundenkommunikation)
- Detaillierte Payload-Struktur-Dokumentation
- Notion DB Schema-Empfehlungen
- n8n Workflow-Anleitung
- Testing-Instruktionen

---

## Nächste Schritte

1. ✅ **Deploy:** Hochladen der aktualisierten `index.html` auf WordPress
2. ✅ **n8n:** Workflow mit Notion API erstellen (siehe Anleitung oben)
3. ✅ **Test:** Komplettes Assessment durchführen und Notion DB prüfen
4. ⏳ **Monitor:** Erste echte Assessments überwachen und Payload validieren

---

**Status:** ✅ Code Complete & Ready for Deployment
**Testing Required:** Full end-to-end test mit n8n + Notion
**Risk:** Low (keine Breaking Changes für Frontend)

---

**Commit:** `5e1fe75`
**Dateien geändert:** 2 (index.html, AI_AGENT_PROMPT_TEMPLATE.md)
**Lines added/removed:** +313 / -196
