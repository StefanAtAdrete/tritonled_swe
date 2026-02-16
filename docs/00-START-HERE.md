# TritonLED - Sessionsstart Guide

⚠️ **CLAUDE: LÄS DENNA FIL FÖRST VID VARJE SESSION**

**DÄREFTER**: Läs `/docs/CURRENT-TASK.md` (om den finns) för pågående uppgift

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

### Exempel:
```bash
# ❌ FEL (försöker på Claudes dator):
bash_tool: ls /Users/steffes/Projekt/tritonled/web/themes

# ✅ RÄTT (använder Stefans Mac):
Filesystem:list_directory
path: /Users/steffes/Projekt/tritonled/web/themes
```

**OM DU GLÖMMER DETTA = PROJEKTET FUNGERAR INTE!**

## 📋 Snabbfakta

- **Projekt**: TritonLED E-commerce (LED luminaires)
- **CMS**: Drupal 11.2.9
- **Miljö**: DDEV lokal utveckling
- **Theme**: Radix (Bootstrap 5.3)
- **Layout**: Layout Builder + Bootstrap Layout Builder
- **Commerce**: Drupal Commerce (quote-baserat system)
- **Målgrupp**: Professionella köpare (installatörer, elektriker, projektledare)

## 🎯 Task-Driven Workflow (ALLTID)

**Vid ny uppgift:**
1. ✅ Skapa `/docs/tasks/task-NNN-beskrivning.md` från TASK-TEMPLATE.md
2. ✅ Fyll i **DEFINE** (mål, syfte, acceptanskriterier) → Vänta på Stefan OK
3. ✅ Fyll i **PLAN** (beslutsträd, lösning, motivering) → Vänta på Stefan OK
4. ✅ **IMPLEMENT** steg-för-steg med git commits `[TASK-NNN] Message`
5. ✅ **VERIFY** mot acceptanskriterier
6. ✅ Om FAIL → Iteration 2 i samma task-fil
7. ✅ Om PASS → Dokumentera i `/docs/03-solutions/` och markera task som Completed

**Varje git commit:**
```bash
git commit -m "[TASK-NNN] Beskrivning av ändring"
git commit -m "[TASK-NNN-01] Sub-task beskrivning"
```

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

## 🌍 Språk

- **Frontend**: Svenska (produktbeskrivningar, UI)
- **Admin/Backend**: Engelska (Drupal standard)
- **Kod/kommentarer**: Engelska (best practice)
- **Dokumentation**: Svenska (denna) + Engelska (kod)

## 🔧 Tech Stack Detaljer

### Tema & Styling
- **Base theme**: Radix
- **CSS Framework**: Bootstrap 5.3 (via CDN)
- **Layout**: Layout Builder + Bootstrap Layout Builder module

- **Custom CSS**: Minimalt - endast i `css/components/` när absolut nödvändigt

### Commerce
- **System**: Quote-baserat (EJ direktköp)
- **Produkter**: LED luminaires med varianter
- **Attribut**: Watt, CCT (färgtemperatur), CRI, IP-rating, monteringstyp
- **Import**: JSON-data ~2x dagligen

### Verktyg
- **MCP Tools**: Direkt Drupal entity-manipulation
- **DDEV**: Lokal utveckling
- **Git**: Versionskontroll
- **Drush**: CLI administration

## 📊 Senaste Viktiga Beslut

### Commerce AJAX (2025-01-08)
- ✅ Använd Event Subscribers för custom beteende
- ❌ Använd INTE custom product templates (förstör AJAX)
- ✅ Layout Builder för layout
- Se: `03-solutions/commerce-ajax-solution.md`

### Responsive Images (2025-01-08)
- ✅ 4:3 aspect ratio över ALLA breakpoints
- ✅ Focal Point module
- ✅ CSS aspect-ratio på containers
- Se: `03-solutions/responsive-images.md`

### Layout Approach
- ✅ Layout Builder för alla sidlayouter
- ✅ Bootstrap Layout Builder för grids
- ✅ Field formatters + view modes för field display
- ❌ INTE Paragraphs (överdrivet)

## 🔍 När du är osäker

### 1. Sök i befintlig dokumentation
```
Läs: /docs/01-decision-trees/[relevant-tree].md
Kolla: /docs/03-solutions/ för tidigare lösningar
```

### 2. Kolla i projektet
```
view /Users/steffes/Projekt/tritonled/docs/[fil]
```

### 3. Fråga Stefan
❓ **Fråga ALLTID innan du gissar**
- "Har vi redan löst detta?"
- "Vilket är önskat beteende exakt?"
- "Ska jag fortsätta till steg 6 (preprocess) eller finns annan lösning?"

## 📝 Arbetsflöde - Steg för steg

### Vid ny uppgift:

1. **Förstå**: Läs uppgiften och be om förtydliganden
2. **Kolla docs**: Finns lösning i `03-solutions/`?
3. **Välj beslutsträd**: 
   - Allmän Drupal → `DRUPAL-DECISION-TREE.md`
   - Commerce → `01-decision-trees/commerce-decision-tree.md`
   - Theming → `01-decision-trees/theming-decision-tree.md`
4. **Presentera plan**: 
   - Problem
   - Vilka steg i beslutsträdet
   - Föreslagen lösning (med alternativ)
   - Varför denna lösning
5. **Vänta på OK**
6. **Implementera**
7. **Testa**: Följ `04-workflows/testing-checklist.md`
8. **Dokumentera**: Uppdatera `03-solutions/` om nytt

### Vid design-uppgift:

1. **Läs**: `04-workflows/design-testing.md` - Design Implementation Hierarchy
2. **Analysera**: Följ ALLTID hierarkin:
   - Bootstrap klasser FÖRST
   - Core funktioner (responsive images, view modes)
   - Kan core lösa det utan SDC?
   - Views + templates (sista utväg)
3. **Planera**: Skapa implementation plan
4. **Presentera**: Visa plan för Stefan
5. **Vänta på OK**
6. **Implementera**: Layout Builder + Bootstrap
7. **Testa**: Design testing checklist (responsive, accessibility, performance)

## 🎨 Design → Implementation

**KRITISK ORDNING (följ ALLTID):**
1. **Bootstrap klasser FÖRST** - 80% kan lösas här
2. **Core Drupal functions** - Responsive images, view modes, image styles
3. **Kan core lösa det?** - Layout Builder, Views, field formatters, view modes
4. **Views + minimal templates** - Endast om nödvändigt
5. **SDC** - Sista utväg (nästan aldrig behövs)

**Läs mer**: 
- `04-workflows/design-testing.md` - Design Testing Framework
- `04-workflows/sdc-workflow.md` - SDC (när absolut nödvändigt)
- `02-standards/design-system.md` - Bootstrap + TritonLED standards

## 🧪 Testing

**Efter varje ändring:**
```bash
ddev drush cr                    # Cache rebuild
ddev logs                        # Error check
ddev drush watchdog:show --severity=Error
```

**Browser:**
- Firefox + Chrome
- Desktop (≥1200px), Tablet (768-1199px), Mobile (<768px)
- Console för JS-errors

**Läs mer**: `04-workflows/testing-checklist.md`

## 📚 Fil-struktur

```
/docs/
├── 00-START-HERE.md          ← Du är här
├── CURRENT-TASK.md           ← Symlänk till aktiv task (läs efter 00-START-HERE)
├── DRUPAL-DECISION-TREE.md   ← Huvudbeslutsträd
├── 01-decision-trees/
│   ├── commerce-decision-tree.md
│   └── theming-decision-tree.md
├── 02-standards/
│   ├── coding-standards.md
│   ├── module-preferences.md
│   ├── approved-modules.md
│   └── design-system.md
├── 03-solutions/             ← Lärdomar från completade tasks
│   ├── commerce-ajax-solution.md
│   └── responsive-images.md
├── 04-workflows/
│   ├── design-testing.md
│   ├── sdc-workflow.md
│   └── testing-checklist.md
└── tasks/                    ← Task-Driven Workflow
    ├── TASK-TEMPLATE.md      ← Mall för alla tasks
    ├── README.md
    ├── task-001-hero-carousel.md
    └── task-002-product-listing.md
```

## 🚀 Quick Commands

```bash
# Cache
ddev drush cr

# Config export/import
ddev drush cex -y
ddev drush cim -y

# Module install
ddev composer require drupal/[module]
ddev drush en [module] -y

# Watch logs
ddev logs -f

# DB backup
ddev snapshot

# DB restore
ddev snapshot restore [name]
```

## 🎓 Kom ihåg

1. **Config > Modules > Themes > Custom Code**
2. **Layout Builder för layouts**
3. **Bootstrap för styling**
4. **Field formatters + view modes för fields**
5. **Fråga innan koda**

---

**Version**: 2.0  
**Skapad**: 2025-01-10  
**Uppdaterad**: 2025-02-16 - Task-Driven Workflow  
**Författare**: Stefan + Claude
