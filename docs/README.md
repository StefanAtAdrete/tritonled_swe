# TritonLED Documentation

**Uppdaterad**: 2025-01-11

Komplett dokumentation för TritonLED Drupal 11 e-handelsplattform.

---

## 📚 Hur man använder denna dokumentation

### För Claude (AI Assistant)

**Vid VARJE ny session:**
1. Läs först: `/docs/00-START-HERE.md`
2. Vid uppgift: Välj relevant beslutsträd från `/01-decision-trees/`
3. Kolla: `/03-solutions/` om problemet lösts tidigare
4. Presentera PLAN innan implementation
5. Vänta på "OK" från Stefan

### För Stefan (Utvecklare)

**Vid ny uppgift:**
1. Välj relevant workflow från `/04-workflows/`
2. Följ beslutsträd från `/01-decision-trees/`
3. Kolla standards i `/02-standards/`

**Vid problem:**
1. Sök i `/03-solutions/` först
2. Följ relevant beslutsträd
3. Dokumentera ny lösning i `/03-solutions/` när löst

---

## 📁 Filstruktur

```
/docs/
├── README.md                           ← Du är här
├── 00-START-HERE.md                    ← LÄSES FÖRST (Claude)
├── DRUPAL-DECISION-TREE.md             ← Huvudbeslutsträd
├── SESSION-*.md                        ← Sessions-dokumentation
│
├── 01-decision-trees/                  ← Problemlösning steg-för-steg
│   ├── commerce-decision-tree.md       ← Commerce-specifika problem
│   └── theming-decision-tree.md        ← Layout, design, styling
│
├── 02-standards/                       ← Kodstandarder & moduler
│   ├── approved-modules.md             ← Godkända moduler (prioritet)
│   ├── coding-standards.md             ← OOP, DI, preprocess, templates
│   └── design-system.md                ← Bootstrap + TritonLED standards
│
├── 03-solutions/                       ← Lösta problem (fallstudier)
│   ├── commerce-ajax-solution.md       ← Commerce AJAX fix
│   ├── responsive-images.md            ← Focal Point + aspect-ratio
│   └── views/
│       └── VIEWS-FIELD-CONFIGURATION.md ← Drupal 11 Views PHP warnings fix
│
└── 04-workflows/                       ← Arbetsflöden
    ├── ai-agents-workflow.md           ← AI Agents via MCP
    ├── design-testing.md               ← Visual testing + Puppeteer
    ├── sdc-workflow.md                 ← SDC (sista utvägen)
    ├── design-to-drupal.md             ← Design → Implementation
    └── testing-checklist.md            ← Testing efter ändringar
```

---

## 🎯 Snabbguide per Scenario

### "Jag ska bygga en ny sida/layout"
1. Läs: `/04-workflows/design-testing.md`
2. Använd: **Layout Builder** + Bootstrap (förstagansval)
3. Undvik: Custom templates

### "Jag behöver skapa content types/fields snabbt"
1. Läs: `/04-workflows/ai-agents-workflow.md`
2. Använd: AI Agents via MCP
3. Workflow: Export logs → AI Agent → Cache clear → Verify

### "Jag behöver skapa produkter med variations"
1. Läs: `/04-workflows/ai-agents-workflow.md`
2. Använd: Commerce Product Agent
3. Exempel: Skapa 6 variations på sekunder

### "Jag har ett Commerce-problem"
1. Kolla: `/03-solutions/commerce-ajax-solution.md` (om AJAX-relaterat)
2. Följ: `/01-decision-trees/commerce-decision-tree.md`
3. Undvik: Custom product templates!

### "Bilder ser fel ut på mobil"
1. Läs: `/03-solutions/responsive-images.md`
2. Använd: 4:3 aspect ratio + Focal Point
3. Test: Alla breakpoints

### "PHP warnings från Views på sidan"
1. Läs: `/03-solutions/views/VIEWS-FIELD-CONFIGURATION.md`
2. Lägg till: `element_default_classes: true` på alla fields
3. Test: Watchdog för nya fel

### "Jag vet inte vilken modul jag ska använda"
1. Kolla: `/02-standards/approved-modules.md`
2. Prioritet: 1 (använd först) → 3 (fallback)
3. Testa: I DDEV innan produktion

### "Ska jag skriva custom kod?"
1. Läs: `/docs/DRUPAL-DECISION-TREE.md` (ALLTID först)
2. Steg 1-4: Config, Moduler, Views, Layout Builder
3. Steg 5: **STOPP** - Fråga Stefan innan kod

### "Jag har gjort ändringar, hur testar jag?"
1. Följ: `/04-workflows/testing-checklist.md`
2. Minimum: Cache + Firefox + Chrome + Mobile
3. Exportera: `ddev drush cex -y`

---

## 🚨 Kritiska Regler

**ALLTID:**
- ✅ Följ beslutsträdet: Config > Moduler > Layout Builder > Custom Code
- ✅ Layout Builder för layouts
- ✅ Bootstrap för styling
- ✅ Testa på mobil + desktop
- ✅ Exportera config (`ddev drush cex`)

**ALDRIG:**
- ❌ Skapa kod innan godkänt av Stefan
- ❌ Custom templates för Commerce products (AJAX!)
- ❌ Hoppa över beslutsträdet
- ❌ Gissa lösningar - fråga om osäker
- ❌ Commit utan config export

---

## 📖 Beslutsträd - Snabbreferens

### Generell Drupal
```
1. Finns i Core?        → Använd Core
2. Finns contrib-modul? → Använd modul
3. Kan Views lösa det?  → Skapa View
4. Kan Layout Builder?  → Använd Layout Builder
5. → STOPP → Fråga Stefan
6. Preprocess hook?     → (med tillstånd)
7. Template?            → (med tillstånd)
```

### Commerce
```
Produkter med varianter?
  → JA:  Layout Builder + Event Subscriber (EJ templates!)
  → NEJ: Layout Builder OK

Produktlistor?
  → Views först
  → Search API + Facets (om avancerad)

Quote-system?
  → Webform (rekommenderat)
  → Custom order type (avancerat)
```

### Theming
```
1. Bootstrap-klasser via UI?   → Använd UI
2. Layout Builder?              → Ja för struktur
3. Field Groups?                → Ja för gruppering
4. Custom CSS?                  → Minimal, efter OK
5. Template?                    → Sista utväg, efter OK
```

---

## 🎓 Viktiga Koncept

### Config > Code
- 90% löses med konfiguration
- Moduler > Custom kod
- UI > Preprocess > Templates

### Layout Builder
- Layout Builder för alla sidlayouter
- Bootstrap Layout Builder för grids
- Field Groups för field gruppering

### Commerce AJAX
- Använd ALDRIG custom product templates
- Event Subscribers för custom beteende
- Layout Builder för struktur

### Responsive Images
- Samma aspect ratio alla breakpoints
- Focal Point för konsistent crop
- CSS aspect-ratio på containers

### Testing
- Cache + Logs först
- Firefox + Chrome minimum
- Mobil + Desktop obligatoriskt
- Config export innan commit

---

## 🔗 Externa Resurser

**Drupal:**
- Drupal.org API: https://api.drupal.org
- Module search: https://www.drupal.org/project/project_module
- Best practices: https://www.drupal.org/docs/develop/standards

**Commerce:**
- Docs: https://docs.drupalcommerce.org
- Modules: https://www.drupal.org/project/project_module?f[2]=commerce

**Bootstrap:**
- Docs: https://getbootstrap.com/docs/5.3/
- Components: https://getbootstrap.com/docs/5.3/components/

**Radix Theme:**
- Project: https://www.drupal.org/project/radix
- GitHub: https://github.com/radixtheme/radix

---

## 📝 Uppdatera Dokumentation

**När ny lösning hittas:**

1. Skapa fil: `/docs/03-solutions/[problem]-solution.md`
2. Template:
```markdown
# [Problem] - LÖST ✅

**Problem löst**: YYYY-MM-DD
**Status**: Verifierad lösning

## Problemet
[Beskriv symptom]

## Root Cause
[Teknisk förklaring]

## Lösningen
[Steg-för-steg]

## Testing
[Hur verifiera]

## Datum: YYYY-MM-DD
```

3. Länka från relevant beslutsträd
4. Commit: `git add docs/ && git commit -m "Docs: Add [problem] solution"`

---

## 🤝 Contribuera

**Stefan eller Claude kan uppdatera:**

1. **Korrigera fel**: Gör PR eller direkt commit
2. **Lägg till exempel**: Fler code examples alltid bra
3. **Uppdatera moduler**: Om nya moduler godkänns
4. **Nya workflows**: Om nya arbetsflöden utvecklas

**Format:**
- Markdown (.md)
- Svenska för beskrivningar
- Engelska för kod/tekniska termer
- Code blocks med syntax highlighting

---

## ✅ Checklist: Är dokumentationen fullständig?

- [x] Startsida (00-START-HERE.md)
- [x] Huvudbeslutsträd (DRUPAL-DECISION-TREE.md)
- [x] Commerce beslutsträd
- [x] Theming beslutsträd
- [x] Coding standards
- [x] Godkända moduler
- [x] Design system (Bootstrap)
- [x] Commerce AJAX lösning
- [x] Responsive images lösning
- [x] Views field configuration lösning (Drupal 11)
- [x] Design testing workflow
- [x] SDC workflow
- [x] AI Agents workflow
- [x] Testing checklist
- [x] README (denna fil)

---

**Version**: 1.1  
**Skapad**: 2025-01-10  
**Uppdaterad**: 2025-01-11 (Display Suite ersatt med Layout Builder)  
**Nästa review**: 2025-02-10  
**Maintainers**: Stefan Carlsson + Claude
