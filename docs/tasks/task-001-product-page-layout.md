# TASK-001: Product Page Layout

**Status**: ✅ COMPLETED  
**Skapad**: 2026-02-16  
**Slutförd**: 2026-02-16  
**Ansvarig**: Claude + Stefan

---

## 1. DEFINE

### Mål
Skapa produktsidans layout med 2-kolumns Bootstrap grid (60/40) som bevarar Commerce AJAX-funktionalitet för produktvarianter.

### Syfte
- Produktsidor ska visa bilder vänster (60%) och produktinfo höger (40%)
- Variant-switching via AJAX ska fungera perfekt
- Layout ska vara responsiv (1 kolumn mobil, 2 kolumner desktop)
- Bootstrap utility classes ska vara justerbara via template

### Acceptance Criteria
- ✅ Template i `themes/custom/tritonled_radix/templates/commerce/`
- ✅ AJAX variant-switching fungerar (bilder + specs uppdateras)
- ✅ Bootstrap grid: `col-md-7` + `col-md-5` för 60/40 split
- ✅ Bootstrap utility classes justerbara (`mb-3`, `mt-4`, `p-3`)
- ✅ Layout matchar design (exakta fält kan skilja)
- ✅ Responsiv: 1 kolumn mobil, 2 kolumner desktop
- ✅ Inga PHP/Twig errors
- ✅ Cache clear fungerar

**Godkänt av Stefan**: ✅ 2026-02-16

---

## 2. PLAN

### Decision Tree Path
1. **Steg 1-4**: Kollat befintlig config och moduler → Inga lösningar hittades
2. **Steg 5**: Layout Builder försök → REJECTED (bryter Commerce AJAX enligt `/docs/03-solutions/commerce-ajax-solution.md`)
3. **Steg 6**: Template approach → VALD

### Lösning
Custom Twig template: `commerce-product--default.html.twig`

**Varför detta fungerar:**
- Använder `{{ product }}` variabel (EJ `{{ content }}`)
- Använder `{{ product|without(...) }}` för att låta Commerce injicera variation fields
- Bevarar `{{ product.variations }}` intakt för AJAX
- Bootstrap grid struktur runt Drupal rendering
- Inga field-level template overrides

### Alternativ som övervägdes
1. **Layout Builder** - Bryter AJAX (dokumenterat i `/docs/03-solutions/commerce-ajax-solution.md`)
2. **Display Suite** - Borttaget från projektet (se docs v2.0)
3. **Field Groups** - Otillräcklig layout-kontroll för 2-kolumns grid
4. **Preprocess + minimal template** - Template enklare för detta use case

**Godkänt av Stefan**: ✅ 2026-02-16

---

## 3. IMPLEMENT

### Iterationer

#### Iteration 1: Fel template-namn (MISSLYCKADES)
- **Fil**: `commerce-product--luminaire--full.html.twig`
- **Problem**: Produkttypen är "default", inte "luminaire"
- **Resultat**: Template matchade inte, användes INTE

#### Iteration 2: Fel variabel-namn (MISSLYCKADES)
- **Fil**: `commerce-product--default--full.html.twig`
- **Problem**: Använde `{{ content.field_name }}` istället för `{{ product.field_name }}`
- **Resultat**: Alla fields renderande tomma

#### Iteration 3: Fel view mode (MISSLYCKADES)
- **Fil**: `commerce-product--default--full.html.twig`
- **Problem**: `--full` suffix felaktig, produktsidor använder inte view modes
- **Resultat**: Template användes INTE

#### Iteration 4: Rätt approach (LYCKADES)
- **Fil**: `commerce-product--default.html.twig`
- **Innehåll**:
  - Bootstrap grid: `col-md-7` (bilder) + `col-md-5` (info)
  - Använder `{{ product }}` variabel
  - Använder `{{ product|without(...) }}` för att exkludera specifika fields från vänster kolumn
  - Bevarar `{{ product.variations }}` för AJAX
  - Låter Commerce injicera `field_variation_media` automatiskt
- **AJAX-säkert**: Ja - verifierat med test
- **Bootstrap utility classes**: `mb-3`, `mb-4`, `mb-5`, `p-3` (justerbara)

### Konfiguration som krävdes
**Product Variation Display** (för AJAX image switching):
1. Gick till: Commerce → Configuration → Product variation types → Default → Manage display
2. Drog "Variation Media (Images/Videos)" från Disabled till synlig (överst)
3. Format: Rendered entity
4. Sparade

### Git Commits
```bash
# Iteration 1
git add web/themes/custom/tritonled_radix/templates/commerce/commerce-product--luminaire--full.html.twig
git commit -m "[TASK-001] Create product page layout template with Bootstrap grid"

# Iteration 2  
git add web/themes/custom/tritonled_radix/templates/commerce/commerce-product--default--full.html.twig
git commit -m "[TASK-001-01] Fix template naming - use default product type"

# Iteration 3
git add web/themes/custom/tritonled_radix/templates/commerce/commerce-product--default.html.twig
git commit -m "[TASK-001-03] Fix template: use product variable, not content"

# Iteration 4 (final)
git add web/themes/custom/tritonled_radix/templates/commerce/commerce-product--default.html.twig
git commit -m "[TASK-001-04] Let Commerce inject all fields including variation media"
```

### Hinder/Problem

**Problem 1: Template naming confusion**
- Försökte använda `--luminaire--full` när produkttyp är "default"
- Lösning: Läste original Commerce template för att se naming convention

**Problem 2: Content vs Product variabel**
- Commerce använder `{{ product }}` variabel, INTE `{{ content }}`
- Upptäcktes genom att läsa `/web/modules/contrib/commerce/modules/product/templates/commerce-product.html.twig`
- Lösning: Bytte alla `content.` till `product.`

**Problem 3: View mode suffix**
- Produktsidor använder INTE `--full` suffix i template-namn
- Lösning: Tog bort `--full` suffix

**Problem 4: Variation images syntes inte**
- Varianterna hade bilder men de visades inte
- Problem: `field_variation_media` var disabled i Variation Display
- Lösning: Aktiverade fältet via UI (Commerce → Product variation types → Default → Manage display)

**Problem 5: Bilder byttes inte via AJAX**
- Template använde `{{ product.field_product_media }}` (produkt-nivå)
- Commerce behövde injicera variation fields automatiskt
- Lösning: Använde `{{ product|without(...) }}` för att rendera ALLA fields i vänster kolumn, låter Commerce injicera variant-bilder

**Problem 6: Config corruption**
- Försök att sätta config via Drush skapade trasig responsive_image formatter
- Lösning: Raderade config från databas, återställde från YAML, aktiverade via UI istället

---

## 4. VERIFY

### Test 1: Visuell jämförelse ✅ PASS
**Metod**: Claude screenshot + jämförelse  
**Resultat**: 
- ✅ 2-kolumns layout (60/40)
- ✅ Bilder vänster kolumn
- ✅ Product info höger kolumn
- ✅ Series badge synlig
- ✅ Brand info synlig
- ✅ Variation dropdowns synliga
- ✅ Request Quote knapp synlig

### Test 2: AJAX-funktionalitet ✅ PASS
**Metod**: Claude bytade variant (DALI → Dimmer)  
**Resultat**:
- ✅ Bilder byttes (Variation Media uppdaterades)
- ✅ Pris uppdaterades (800,00 kr → 900,00 kr)
- ✅ Attribut byttes korrekt (Color: Black → White, Length: 100 → 120, Watt: 25 → 5)
- ✅ URL uppdaterades (?v=2)
- ✅ INGEN sidladdning
- ✅ Inga console errors

**Kommandon körda:**
```bash
ddev drush watchdog:show --severity=Error  # Inga errors
ddev logs | grep -i error  # Inga errors
```

### Test 3: Responsiv layout ✅ PASS
**Metod**: Claude testade mobil (375px) och desktop (1214px)  
**Resultat**:
- ✅ Mobile (<768px): 1 kolumn, bilder + info stackar vertikalt
- ✅ Desktop (≥768px): 2 kolumner, bild vänster + info höger
- ✅ Bootstrap breakpoints fungerar korrekt

### Test 4: Teknisk verifiering ✅ PASS
**Kommandon:**
```bash
ddev drush cr  # Cache clear fungerade
ddev logs  # Inga PHP/Twig errors
```
**Resultat**: ✅ Inga errors

### Test 5: Bootstrap-klasser justerbara ✅ PASS
**Metod**: Verifierade template-fil  
**Resultat**:
- ✅ Spacing använder Bootstrap utility classes (`mb-3`, `mb-4`, `p-3`)
- ✅ Grid använder Bootstrap classes (`col-md-7`, `col-md-5`, `row`, `container`)
- ✅ Inga hårdkodade inline styles
- ✅ Kommentarer i template förklarar justerbarhet
- ✅ Stefan kan enkelt ändra `mb-3` → `mb-4` för mer spacing

### Slutgiltiga Test URLs
- http://tritonled.ddev.site/product/2 (TEST - Comet LED Highbay) ✅
- http://tritonled.ddev.site/product/3 (Triton OPTI) ✅

---

## 5. COMPLETION

### Status: ✅ COMPLETED
**Slutfört**: 2026-02-16

### Resultat
- ✅ Product page layout fungerar perfekt
- ✅ Commerce AJAX bevarad och testad
- ✅ Bootstrap 2-kolumn grid (60/40)
- ✅ Responsiv layout verifierad
- ✅ Alla acceptance criteria uppfyllda

### Filer skapade/modifierade
**Skapade:**
- `/web/themes/custom/tritonled_radix/templates/commerce/commerce-product--default.html.twig`
- `/docs/tasks/task-001-product-page-layout.md`

**Borttagna (iterationer):**
- `commerce-product--luminaire--full.html.twig.bak`
- `commerce-product--default--full.html.twig.bak`

**Konfiguration uppdaterad via UI:**
- Commerce Product Variation → Default → Manage display
  - Aktiverade: `field_variation_media` (Rendered entity, weight: -10)

### Lärdomar

1. **Commerce template naming är specifikt:**
   - Format: `commerce-product--[BUNDLE].html.twig` (EJ `--[VIEW_MODE]`)
   - Produkttyp måste matcha exakt (default, luminaire, etc)

2. **Commerce använder `product` variabel, INTE `content`:**
   - Standard Drupal: `{{ content.field_name }}`
   - Commerce Product: `{{ product.field_name }}`
   - Läs alltid original template först!

3. **Commerce field injection kräver rätt setup:**
   - Variation fields måste vara enabled i Variation Display
   - Template får INTE hardcoda field rendering
   - Använd `{{ product|without(...) }}` för att låta Commerce injicera

4. **AJAX bevaras genom att:**
   - ALDRIG overrida `{{ product.variations }}`
   - ALDRIG skapa field-level templates för variation fields
   - Låta Commerce injicera variation fields automatiskt

5. **Drush config commands riskabla:**
   - `drush config:set` kan skapa trasig config om settings saknas
   - Säkrare: Gör ändringar via UI, exportera med `drush cex`
   - Vid config corruption: Radera från databas, återställ från YAML

6. **Bootstrap grid i templates:**
   - Använd utility classes (`mb-3`, `col-md-7`) för enkel justering
   - Dokumentera justerbarhet i kommentarer
   - Undvik hårdkodade styles

### Dokumentation uppdaterad
- ✅ `/docs/tasks/task-001-product-page-layout.md` - Denna fil
- ⏳ `/docs/00-START-HERE.md` - Lägg till TASK-001 som exempel (om behövs)

### Nästa steg
**Efter denna task:**
1. Testa på fler produkter för att säkerställa template fungerar generellt
2. Överväg att lägga till fler fields i template (t.ex. technical specs, certifications)
3. Utvärdera om Key Features section behöver SDC component
4. Överväg att skapa variation av template för andra produkttyper om behövs

**Relaterade tasks som kan skapas:**
- TASK-002: Hero carousel implementation med SDC
- TASK-003: Browse by Application sektion
- TASK-004: Product specification tabs med JavaScript
- TASK-005: Related products section

---

**Version**: 1.0  
**Skapad**: 2026-02-16  
**Slutförd**: 2026-02-16  
**Verifierad**: ✅ Alla tester pass  
**Författare**: Claude + Stefan
