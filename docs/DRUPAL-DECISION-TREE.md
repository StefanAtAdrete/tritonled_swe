# Drupal Problemlösning - Decision Tree

## 🎯 Grundprincip
**Config > Modules > Themes > Custom Code** (i exakt den ordningen)

Drupal är ett modulärt, konfigurationsbaserat CMS. 90% av all funktionalitet finns redan färdig i core eller contrib-moduler. Templates och custom kod ska endast användas som absolut sista utväg.

---

## ⚠️ STEG 0: RÄTT FILSYSTEM (KRITISKT!)

**Claude har tillgång till 2 datorer - använd ALLTID rätt verktyg:**

### DIN MAC (Drupal-projektet)
**ALLTID använd:** `Filesystem:*` verktyg (Capital F)  
**Sökväg:** `/Users/steffes/Projekt/tritonled/`

| Filtyp | Verktyg | Path |
|--------|---------|------|
| Config YAML | `Filesystem:write_file` | `/Users/steffes/Projekt/tritonled/config/sync/` |
| Theme files | `Filesystem:write_file` | `/Users/steffes/Projekt/tritonled/web/themes/custom/tritonled/` |
| Templates | `Filesystem:write_file` | `/Users/steffes/Projekt/tritonled/web/themes/custom/tritonled/templates/` |
| Custom modules | `Filesystem:write_file` | `/Users/steffes/Projekt/tritonled/web/modules/custom/` |
| Dokumentation | `Filesystem:write_file` | `/Users/steffes/Projekt/tritonled/docs/` |

### CLAUDE'S DATOR (Temporära filer)
**Använd:** `filesystem:*` verktyg (lowercase f) eller `create_file`  
**Sökväg:** `/home/claude/` eller `/mnt/user-data/`

### ✅ CHECKLISTA INNAN FIL SKAPAS:
- [ ] Ska filen till `/Users/steffes/Projekt/tritonled/`?
- [ ] Om JA → Använd `Filesystem:write_file` (Capital F)
- [ ] Om NEJ → Använd `create_file` (Claude's dator)
- [ ] Verifiera med `ls -la [path]` direkt efter skapande
- [ ] Om filen INTE finns → FEL verktyg använt, gör om!

**Se även:** `/docs/CLAUDE-FILESYSTEM-RULES.md` för detaljer

---

## 🔍 RESEARCH FÖRE ALLT ANNAT (KRITISKT)

**Claude ska ALLTID göra detta innan något föreslås eller görs:**

### 1. Förstå uppdraget
- Läs frågan noggrant
- Om något är oklart → **fråga Stefan**, gärna en gång för mycket
- Formulera uppdraget med egna ord och bekräfta med Stefan

### 2. Kartlägg förutsättningarna
- **Kolla DOM/HTML** först → vilka klasser finns faktiskt på elementen?
- **Kolla CSS** → vilka regler gäller redan?
- **Kolla config** → hur är Views/Layout/moduler uppsatta?
- **Använd verktygen** (Filesystem, Drupal MCP, browser) — inte minnet

### 3. Välj lösning baserat på verkliga förutsättningar
- Följ beslusträdet: Config → Moduler → Layout Builder → CSS → Kod
- Presentera alternativ för Stefan
- Vänta på OK

### 4. Verifiera att det är löst
- Kolla i browser/DOM att ändringen faktiskt syns
- Bekräfta med Stefan

### ❌ GISSA ALDRIG — ANTA ALDRIG
- Oklara klassnamn? → Kolla DOM
- Oklart var CSS appliceras? → Kolla computed styles
- Osäker på förutsättningarna? → Fråga Stefan
- **Varje gissning kostar mer tid än att fråga**

---

## 🚫 ALDRIG GÖR DETTA

Dessa saker bryter mot Drupal best practices och förstör oftast mer än de hjälper:

- ❌ **Skapa templates utan explicit tillstånd** - Templates overridar Drupal's rendering och blockerar inbyggd funktionalitet
- ❌ **Hårdkoda innehåll i templates** - All content ska hanteras via fields och config
- ❌ **Skapa custom moduler för standardfunktioner** - Sök alltid drupal.org först
- ❌ **Overrida Drupal core rendering** - Förstå EXAKT vad som händer innan override
- ❌ **Föreslå kod innan config/modul-lösningar testats** - Utforska alla UI-alternativ först
- ❌ **Använda `!important` i CSS** - Förstå specificity istället
- ❌ **Modifiera contrib-modulers kod** - Använd patches eller hooks
- ❌ **Gissa lösningar** - Fråga om osäker, verifiera med dokumentation
- ❌ **Skapa filer på fel dator** - Följ STEG 0 ovan!

---

## STEG 1: FÖRSTÅ PROBLEMET

**Innan du föreslår någon lösning:**

- [ ] Vad ska visas/göras på frontend?
- [ ] Finns funktionaliteten redan i Drupal Core/Commerce/moduler?
- [ ] Är det ett rendering-problem, data-problem eller konfigurationsproblem?
- [ ] Har användaren beskrivit önskat beteende tydligt?

**Frågor att ställa:**
- "Kan du visa mig exakt vad som ska hända?"
- "Finns det ett exempel på en annan Drupal-site som gör detta?"
- "Är detta standardbeteende i Commerce/Drupal?"

---

## STEG 2: KOLLA BEFINTLIG KONFIGURATION

**Navigera via UI och kontrollera:**

- [ ] **Structure → Content types** (för nodes) eller **Commerce → Product types** (för produkter)
- [ ] **→ Manage fields** - Finns alla nödvändiga fält?
- [ ] **→ Manage display** - Är fälten synliga eller dolda?
- [ ] **→ Manage form display** - Rätt widgets vid redigering?

**Kontrollera specifikt:**
- Display mode korrekt? (default/full/teaser)
- Formatter korrekt för varje fält?
- View modes konfigurerade?
- Field injection aktiverat? (för Commerce variations)

**Verktyg:**
```bash
# Visa display config
ddev drush config:get core.entity_view_display.[entity].[bundle].[view_mode]

# Exportera config
ddev drush cex -y

# Importera config
ddev drush cim -y
```

---

## STEG 3: SÖK OCH AKTIVERA MODULER

**Innan du skriver en rad kod:**

### A) Sök contrib-moduler
1. Gå till https://www.drupal.org/project/project_module
2. Sök efter funktionaliteten
3. Filtrera på Drupal 11 compatibility
4. Kolla "Project information" → Downloads, usage, maintenance status

### B) Vanliga moduler för vanliga problem

| Problem | Modul |
|---------|-------|
| Layout och sidstruktur | Layout Builder (core) |
| Komplexa formulär | Webform |
| Produktvariation field injection | Commerce (built-in) |
| Block klasser | Block Class |
| Field groups | Field Group |
| Responsive bilder | Responsive Image (core) |
| Media hantering | Media (core) |
| Bootstrap grids i Layout Builder | Bootstrap Layout Builder |

### C) Aktivera och konfigurera
```bash
# Installera
ddev composer require drupal/[module_name]

# Aktivera
ddev drush en [module_name] -y

# Kolla status
ddev drush pml | grep [module_name]
```

**Viktigt:** Läs modulens dokumentation och README innan konfiguration!

---

## STEG 4: LAYOUT BUILDER

**Layout Builder är Drupal 11's standard för layout management**

### Aktivera Layout Builder:
```
Structure → Content types → [Type] → Manage Display
→ Enable Layout Builder
→ Allow custom layouts (optional per content)
```

### Vad Layout Builder gör:
- ✅ Placera fields i olika regioner
- ✅ Skapa kolumnlayouter
- ✅ Lägg till block och Views
- ✅ Custom layouts per sida/content
- ✅ Bevara Drupal rendering (Commerce AJAX fungerar!)

### Bootstrap Layout Builder:
```bash
# Installera för Bootstrap grids
ddev composer require drupal/bootstrap_layout_builder
ddev drush en bootstrap_layout_builder -y
```

**Ger dig:**
- Responsive 1-4 kolumn layouts
- Bootstrap grid-klasser automatiskt
- Breakpoint-kontroll via UI

### Field Groups för gruppering:
```bash
# Installera Field Group
ddev composer require drupal/field_group
ddev drush en field_group -y
```

**Använd för:**
- Gruppera relaterade fields
- Semantic HTML (fieldset, details)
- Accordion/tabs utan templates

---

## STEG 5: CACHE & DEBUG

**Rensa ALLTID cache först:**

```bash
# Rensa all cache
ddev drush cr

# Rensa render cache (vid template-problem)
ddev drush sqlq "TRUNCATE cache_render"

# Rensa CSS/JS
ddev drush cc css-js
```

### Debugging-verktyg:

**A) Twig Debug**
```bash
# Aktivera
ddev drush state:set twig_debug 1

# Inaktivera för production
ddev drush state:set twig_debug 0
```

**B) Browser DevTools**
- Inspektera element - finns rätt HTML?
- Network tab - laddas CSS/JS?
- Console - finns JavaScript-fel?

**C) Devel module**
```bash
ddev composer require drupal/devel
ddev drush en devel -y

# Använd kint() i templates för debugging
{{ kint(content) }}
```

**D) Watchdog logs**
```bash
# Visa senaste fel
ddev drush watchdog:show --count=20
```

---

## 🚨 STOPPSIGNAL - FRÅGA INNAN NÄSTA STEG

**Om steg 1-5 inte löste problemet:**

**STOPP! Fråga användaren:**
- "Jag har provat config, moduler och Layout Builder. Inget fungerade."
- "Ska jag kolla preprocess hooks eller behöver vi en annan approach?"
- "Kan du verifiera att [specifik funktionalitet] verkligen inte finns i Drupal/Commerce?"

**Vänta på explicit godkännande innan du går till steg 6-7!**

---

## STEG 6: PREPROCESS HOOKS (med tillstånd)

**Endast för:**
- Lägga till CSS-klasser
- Ta bort attribut (t.ex. width/height på media)
- Attacha JavaScript libraries
- Lägga till variabler till template
- Enkel data-transformation

**INTE för:**
- Ändra rendering av fields
- Manipulera content arrays
- Ersätta core funktionalitet
- Komplex logik

### Exempel på godkänd preprocess hook:

```php
/**
 * Implements hook_preprocess_file_video().
 */
function tritonled_preprocess_file_video(&$variables) {
  // Ta bort hårdkodade attribut för responsive video
  unset($variables['attributes']['width']);
  unset($variables['attributes']['height']);
}
```

### Exempel på DÅLIG preprocess hook:

```php
// ❌ GÖR INTE DETTA - Återimplementerar core rendering
function tritonled_preprocess_commerce_product(&$variables) {
  $variations = [];
  foreach ($product->getVariations() as $variation) {
    $variations[] = render_variation($variation); // Blockerar field injection!
  }
  $variables['custom_variations'] = $variations;
}
```

---

## STEG 7: TEMPLATES (SISTA UTVÄG - KRÄVER GODKÄNNANDE)

**Templates ska ENDAST skapas när:**
1. Alla config-alternativ testats
2. Inga moduler kan lösa problemet
3. Layout Builder inte räcker
4. Preprocess hooks inte räcker
5. Användaren sagt "ok, skapa template"

### Template-skapande process:

**A) Hitta Drupal's default template**
```bash
# Sök i core/contrib
find web/core web/modules/contrib -name "[template-name].html.twig"

# Visa template suggestions med Twig Debug
# (visas i HTML-källa som kommentarer)
```

**B) Kopiera default som bas**
```bash
# Kopiera EXAKT från core/contrib
cp [source] web/themes/custom/tritonled/templates/[category]/

# Namnge enligt Drupal suggestions
# t.ex: node--article.html.twig, commerce-product--luminaire.html.twig
```

**C) Modifiera MINIMALT**
- Behåll ALL Drupal rendering: `{{ content.field_name }}`
- Lägg endast till HTML-struktur kring fields
- Använd Bootstrap-klasser för styling
- ALDRIG hårdkoda innehåll

**D) Dokumentera**
```twig
{#
/**
 * @file
 * Custom template för [ändamål]
 * 
 * Skapad: [datum]
 * Anledning: [varför var template nödvändig]
 * Baserad på: [original template path]
 */
#}
```

### Exempel på godkänd template:

```twig
{# Lägger endast till Bootstrap grid struktur #}
<article{{ attributes }}>
  <div class="row">
    <div class="col-md-6">
      {{ content.field_image }}
    </div>
    <div class="col-md-6">
      {{ content.title }}
      {{ content.body }}
    </div>
  </div>
</article>
```

### Exempel på DÅLIG template:

```twig
{# ❌ ALDRIG - Hårdkodad content och blockerad rendering #}
<article>
  <h1>Technical Specifications</h1>
  <div id="product-specs">
    <p>Select options...</p>
    {# Detta blockerar Drupal's field injection! #}
  </div>
</article>
```

---

## 📋 CHECKLISTA INNAN LÖSNING

Gå igenom denna VARJE gång:

- [ ] **STEG 0:** Rätt filsystem-verktyg? (`Filesystem:*` för Drupal)
- [ ] Har jag sökt efter contrib-modul?
- [ ] Har jag kollat befintlig config i UI?
- [ ] Har jag testat Layout Builder?
- [ ] Har jag rensat cache?
- [ ] Har jag frågat användaren om osäker?
- [ ] Har jag fått godkännande för kod/template?
- [ ] Följer min lösning Drupal best practices?
- [ ] Är lösningen enkel att underhålla?
- [ ] Blockerar jag INTE någon core-funktionalitet?
- [ ] Verifierat att filen skapades rätt? (`ls -la`)

---

## 🎓 DRUPAL-PRINCIPER ATT MEMORERA

1. **Configuration Management**
   - Allt ska kunna exporteras/importeras via config
   - Använd `ddev drush cex/cim`

2. **Don't Repeat Yourself (DRY)**
   - Om core gör det redan, använd core
   - Om modul finns, använd modul
   - Skriv inte om funktionalitet

3. **Separation of Concerns**
   - Content = Fields & Config
   - Layout = Layout Builder
   - Styling = CSS
   - Beteende = JavaScript
   - Logik = Modules (INTE templates)

4. **Upgrade Path**
   - Tänk Drupal 12, 13...
   - Contrib-moduler uppdateras
   - Custom kod måste du underhålla själv

5. **Community över Custom**
   - Tusentals utvecklare har löst samma problem
   - Contrib-moduler är testade och säkra
   - Rapportera bugs, contributa patches

6. **Rätt Filsystem**
   - Drupal-filer → `Filesystem:*` (Capital F)
   - Temp-filer → `create_file` (Claude's dator)
   - Verifiera alltid med `ls -la`

---

## 📚 RESURSER

- **Drupal.org API**: https://api.drupal.org
- **Module Search**: https://www.drupal.org/project/project_module
- **Commerce Docs**: https://docs.drupalcommerce.org
- **Layout Builder**: https://www.drupal.org/docs/8/core/modules/layout-builder
- **Bootstrap Layout Builder**: https://www.drupal.org/project/bootstrap_layout_builder
- **Stack Exchange**: https://drupal.stackexchange.com

---

## 🔄 ITERATIONSPROCESS

När något "inte fungerar":

1. **Verifiera problemet** - Reproducera, ta screenshot
2. **Isolera orsaken** - Inaktivera moduler en i taget
3. **Testa systematiskt** - En ändring åt gången
4. **Dokumentera** - Vad testades, vad hände
5. **Fråga communityn** - Drupal Slack, Stack Exchange
6. **Sista utväg** - Custom kod med godkännande

---

**Version:** 1.2  
**Skapad:** 2024-12-22  
**Uppdaterad:** 2025-01-11 (Display Suite ersatt med Layout Builder)  
**Författare:** Stefan (med Claude's hjälp för struktur)
