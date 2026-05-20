# TASK-043 — Offertsystem (Master)

**Created**: 2026-05-19
**Status**: In Progress (umbrella)
**Last Updated**: 2026-05-19
**Related Tasks**: TASK-044 → TASK-055 (+ ev. 056–058)

---

## Syfte

Parapluy-task som spårar bygget av TritonLEDs interna offert- och kundlojalitetssystem. Detaljer, datamodell, faser och beslut finns i:

📄 **`/docs/offertsystem-oversikt.md`** — arkitekturöversikt och beslutsunderlag

Denna task innehåller ingen egen IMPLEMENT-fas. Allt arbete sker i sub-tasks.

---

## Låsta beslut (sammanfattning, se översikt för detaljer)

- Custom Content Entities (Offert, Offertgrupp, Offertrad) — inte Paragraphs
- Platt struktur med grupp-referens (Trello/Jira-pattern)
- `revisionable: TRUE`, `translatable: FALSE`
- Default-grupp "Övrigt" auto-skapas
- Snapshot-pris vid utskick, PDF som arkivkopia
- 5 faser, Excel-pris rullar parallellt tills Fas 3

## Öppna frågor — status (2026-05-19)

| # | Fråga | Status |
|---|---|---|
| 1 | Företagsprofil — Profile-modul vs fält på User | ✅ Profile-modul |
| 2 | Webform-koppling vid anonym förfrågan | ✅ Manuell koppling i Fas 1 |
| 3 | Datablad — separata PDF eller i offerten | 🟡 Skjuts till TASK-050 |
| 4 | Energikalkyl-formel (brinntid, elpris, per produkt/offert) | 🟡 Skjuts till TASK-055 |
| 5 | Hostinger VPS-resurser för Entity Print | 🟡 Testas i Fas 2 |

---

## Sub-tasks

| Task | Titel | Status |
|---|---|---|
| TASK-044 | Kundkonton + företagsprofil | DEFINE väntar godkännande |
| TASK-045 | Webform "Förfrågan" + koppling till user | Not Started |
| TASK-046 | Offert-entity + numreringsservice | Not Started |
| TASK-047 | Offertgrupp + Offertrad-entiteter | Not Started |
| TASK-048 | Prismotor — Category C | Not Started |
| TASK-049 | Prismotor — Konfigurator (Cat A) | Not Started |
| TASK-050 | PDF-generering (Entity Print + Twig) | Not Started |
| TASK-051 | Mail-utskick (Symfony Mailer + Token) | Not Started |
| TASK-052 | Projekt-entity (offert-container) | Not Started |
| TASK-053 | Content Moderation workflow + revisioner | Not Started |
| TASK-054 | Elektriker-portal "Mina projekt" | Not Started |
| TASK-055 | Energikalkyl (service + publik + i offert) | Not Started |

**Eventuellt senare:**
- TASK-056: Sortable.js drag-och-släpp-widget
- TASK-057: Grupp-mallar
- TASK-058: Projekt → Kundcase auto-generering

---

## Loggbok

- **2026-05-19** Master-task skapad. Översiktsdokumentet `offertsystem-oversikt.md` är godkänt som beslutsunderlag. 5 öppna frågor besvarade (3 låsta, 2 parkerade). Startar TASK-044.
