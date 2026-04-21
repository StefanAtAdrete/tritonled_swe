# TritonLED - Sessionsstart Guide

⚠️ **CLAUDE: LÄS DENNA FIL FÖRST VID VARJE SESSION**

**DÄREFTER**: Läs `/docs/CURRENT-TASK.md` (om den finns) för pågående uppgift

---

## 🔄 Sessionsstruktur (ALLTID följa)

### DEL 1 — START
Claude gör vid varje sessionsstart:
1. Läser denna fil (`00-START-HERE.md`)
2. Läser `CURRENT-TASK.md`
3. Presenterar: var vi är, öppna tasks, förslag på vad vi tar tag i

Stefan kontrollerar:
```bash
ddev start
ddev drush status
```

### DEL 2 — CHECKPOINT (mitt i session)
**Claude påminner aktivt** när något av följande inträffar:
- ✅ En task markeras som klar
- ⏱️ Lång session utan naturligt avbrott
- 🔀 Vi byter task/inriktning

Claude säger då: *"✅ [TASK-NNN] klar. Dags för checkpoint — kör vi det nu?"*

Checkpoint-steg:
```bash
ddev drush cex -y
git add -A
git commit -m "[TASK-NNN] Checkpoint: vad som är klart"
```
- Uppdatera `CURRENT-TASK.md` (status, vad återstår)
- Stefan tar Backup & Migrate snapshot

### DEL 3 — SESSIONSSLUT
```bash
ddev drush cex -y
git add -A
git commit -m "[TASK-NNN] Session slut: sammanfattning"
git push origin main
```
- Uppdatera `CURRENT-TASK.md` + `00-START-HERE.md` (nya beslut)
- Stefan tar Backup & Migrate snapshot (om inte nyligen gjort)
- Deploy till prod om redo

**Detaljerad SOP**: `/docs/04-workflows/session-sop.md`

---

## 🚨 KRITISKT: CEX-VARNING

**`drush cex` raderar config-filer som inte finns i lokal DB.**

När lokal DB importerats från prod (via snapshot) men inte sedan `cim`:ats, finns config i YAML-filerna som saknas i DB. `cex` skriver då över YAML med DB-innehållet och raderar filerna.

### Säker exportrutin:
```bash
# 1. Kontrollera alltid delta INNAN export:
ddev drush config:status
# Om det finns "Only in sync dir" — kör ALDRIG cex direkt
# Importera först: ddev drush cim --partial -y
# 2. Exportera sedan:
ddev drush cex -y
```

### Återställning om det ändå gick fel:
```bash
git diff HEAD~1 --diff-filter=D --name-only | xargs git checkout HEAD~1 --
git commit -m "[TASK-NNN] Restore accidentally deleted config files from cex"
```

---

## 🚨 KRITISKT: FILSYSTEM-REGLER (BRYTS ALDRIG!)

### Claude har tillgång till 2 datorer:

**1. STEFANS MAC (Drupal-projektet)** ← **ANVÄND ALLTID FÖR PROJEKTET**
- Sökväg: `/Users/steffes/Projekt/tritonled/`
- Verktyg: `Filesystem:*` (Capital F)

**2. CLAUDES DATOR (temporära filer)**
- Sökväg: `/home/claude/`
- Verktyg: `bash_tool`, `create_file`

### ✅ RÄTT för Drupal-projektet:
```
Filesystem:read_text_file     → Läsa filer
Filesystem:write_file         → Skapa/uppdatera filer
Filesystem:list_directory     → Lista kataloger
Filesystem:search_files       → Söka filer
Filesystem:move_file          → Flytta/byta namn
Filesystem:create_directory   → Skapa kataloger
```

### ❌ FEL för Drupal-projektet:
```
bash_tool                     → Kör BARA på Claudes dator
create_file                   → Skapar på Claudes dator
ls, find, cat kommandon       → Fungerar INTE på Stefans Mac
```

### 🔧 För DDEV/Drush kommandon:
```
✅ GE Stefan kommandot att köra själv
❌ ALDRIG försök köra ddev/drush själv
```

**OM DU GLÖMMER DETTA = PROJEKTET FUNGERAR INTE!**

---

## 📋 Snabbfakta

- **Projekt**: TritonLED E-commerce (LED luminaires)
- **CMS**: Drupal 11
- **Miljö**: DDEV lokal utveckling
- **Theme**: Radix (Bootstrap 5.3)
- **Layout**: Layout Builder + Bootstrap Layout Builder
- **Commerce**: Drupal Commerce (quote-baserat system)
- **Målgrupp**: Professionella köpare (installatörer, elektriker, projektledare)

---

## 🧩 Huvuduppgifter delas ALLTID upp i sub-tasks

**Innan du börjar med någon uppgift – identifiera sub-tasks och deras ordning.**

### Standardordning för frontend-sektioner:

```
1. Innehåll       → Finns rätt content type / media type?
2. Image styles   → Rätt bildformat per breakpoint
3. View modes     → Hur renderas innehållet i sin kontext?
4. Views          → Samlar och strukturerar med contrib format-plugins
5. Layout Builder → Placerar blocket på sidan
6. Styling        → Bootstrap klasser FÖRST, sedan minimal CSS (kräver godkännande)
7. SDC/Template   → Sista utväg, kräver EXPLICIT godkännande
```

### Vad kräver godkännande?

| Åtgärd | Kräver godkännande? |
|--------|---------------------|
| Config via admin UI | NEJ |
| Image styles, view modes, views | NEJ |
| Bootstrap klasser | NEJ |
| Preprocess hook | JA |
| Custom CSS-fil | JA |
| Template (.html.twig) | JA – explicit |
| SDC-komponent | JA – explicit |
| Custom modul | JA – explicit |

### Commerce-undantag:
- Drupal Commerce kräver templates som **inte stör AJAX**
- Templates för produktsidor får ALDRIG blockera variation field injection
- Se: `03-solutions/commerce-ajax-solution.md`

---

## 🎯 Task-Driven Workflow (ALLTID)

**Vid ny uppgift:**
1. ✅ Skapa `/docs/tasks/task-NNN-beskrivning.md` från TASK-TEMPLATE.md
2. ✅ Fyll i **DEFINE** → Vänta på Stefan OK
3. ✅ Fyll i **PLAN** → Vänta på Stefan OK
4. ✅ **IMPLEMENT** steg-för-steg med git commits `[TASK-NNN] Message`
5. ✅ **VERIFY** mot acceptanskriterier
6. ✅ Om PASS → Dokumentera i `/docs/03-solutions/` och markera task som Completed

**Varje git commit:**
```bash
git commit -m "[TASK-NNN] Beskrivning av ändring"
```

---

## 🚫 Arbetsregler - ALDRIG

❌ **ALDRIG koda innan godkänt**
❌ **ALDRIG skapa templates utan explicit tillstånd**
❌ **ALDRIG hoppa över beslutsträdet**
❌ **ALDRIG gissa - fråga om osäker**

## ✅ Arbetsregler - ALLTID

✅ **ALLTID** config och moduler först
✅ **ALLTID** contrib-moduler före custom kod
✅ **ALLTID** förklara VARFÖR, inte bara HUR
✅ **ALLTID** följ `/docs/DRUPAL-DECISION-TREE.md`
✅ **ALLTID** Layout Builder för sidlayouter

---

## 🌍 Språk

- **Frontend**: Svenska (produktbeskrivningar, UI)
- **Admin/Backend**: Engelska (Drupal standard)
- **Kod/kommentarer**: Engelska (best practice)
- **Dokumentation**: Svenska (denna) + Engelska (kod)

---

## 🔧 Tech Stack

- **Base theme**: Radix / Bootstrap 5.3 (via CDN)
- **Layout**: Layout Builder + Bootstrap Layout Builder
- **Custom CSS**: Minimalt — endast i `css/components/` när absolut nödvändigt
- **Commerce**: Quote-baserat (EJ direktköp), priser ej på frontend
- **Import**: CSV in, JSON ut (partner-API)

---

## 🚀 Deploy
Se `/docs/skills/server-management/SKILL.md`

---

## 📊 Beslut & lösningar
Se `/docs/03-solutions/` för historik och beslutsdokumentation.

---

## 🔍 När du är osäker

1. Kolla `/docs/03-solutions/` för tidigare lösningar
2. Följ `/docs/DRUPAL-DECISION-TREE.md`
3. Fråga Stefan

---

## 🎨 Design → Implementation (KRITISK ORDNING)

1. **Bootstrap klasser FÖRST** — 80% kan lösas här
2. **Core Drupal** — Responsive images, view modes, image styles
3. **Layout Builder, Views, field formatters**
4. **Views + minimal templates** — Endast om nödvändigt
5. **SDC** — Sista utväg

---

## 🧪 Testing

```bash
ddev drush cr
ddev logs
ddev drush watchdog:show --severity=Error
```

---

## 🚀 Quick Commands

```bash
ddev drush cr
ddev drush cex -y
ddev drush cim -y
ddev composer require drupal/[module]
ddev drush en [module] -y
ddev logs -f
ddev snapshot
ddev snapshot restore [name]
```

---

## 📚 Fil-struktur

```
/docs/
├── 00-START-HERE.md          ← Du är här
├── CURRENT-TASK.md           ← Läs efter 00-START-HERE
├── DRUPAL-DECISION-TREE.md   ← Huvudbeslutsträd
├── 01-decision-trees/
├── 02-standards/
├── 03-solutions/             ← Beslut & lösningshistorik
├── 04-workflows/
└── tasks/
```

---

**Version**: 2.7
**Skapad**: 2025-01-10
**Uppdaterad**: 2026-04-11 — Rensad (beslut → 03-solutions, deploy → server-management/SKILL.md)
**Författare**: Stefan + Claude
