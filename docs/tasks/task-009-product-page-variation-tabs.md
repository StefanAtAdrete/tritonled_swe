# Task 009: Produktsida — Variationsfält med tabs

**Created**: 2026-02-28
**Status**: In Progress
**Last Updated**: 2026-02-28
**Related Tasks**: TASK-008

---

## 1. DEFINE

### Mål
Visa variationsfält på produktsidan grupperade i tabs (Electrical, Physical, Certifications).
Fälten ska uppdateras dynamiskt via Commerce AJAX när användaren byter variant.
Tab-etiketter ska vara översättningsbara.

### Syfte
Professionella köpare behöver snabbt hitta tekniska specifikationer per variant.
Tabs ger bättre UX än en lång lista fält.

### Acceptanskriterier
- [ ] Variationsfält visas grupperade i tabs på produktsidan
- [ ] Fälten uppdateras via AJAX vid variantbyte (ingen page reload)
- [ ] Tab-etiketter är översättningsbara via Drupal t()-funktionen
- [ ] Fungerar responsivt på mobil
- [ ] Inga JS-fel i console vid variantbyte

**Godkänt av Stefan**: Godkänd

---

## 2. PLAN

### Beslutsträd
**Fil**: `/docs/01-decision-trees/commerce-decision-tree.md`
**Steg**: Config > Contrib Module > Layout Builder > Custom Code

### Utredning genomförd

#### Field Group (testat, fungerar INTE)
- Field Group 4.0.0 installerades och testades
- "Add field group"-knappen saknas på Commerce entity types (product, variation)
- Field Group fungerar BARA på standard Drupal content types
- Verifierat: Basic page har knappen, Commerce variation type har den inte
- Slutsats: Field Group är inkompatibelt med Commerce entity displays

#### Field Group på produkt-nivå (testat, fungerar INTE)
- Commerce product type display saknar också "Add field group"
- Commerce entity types stöds inte av Field Group 4.0.0

### Vald lösning
**Approach**: Template (commerce-product--default.html.twig)
**Specifik lösning**:
Bootstrap tabs i befintlig template med `t()`-funktionen för översättningsbara etiketter.
Variationsfält renderas via `product.field_X` som Commerce injicerar via AJAX.

### Motivering
- Field Group fungerar inte för Commerce-entiteter
- Template finns redan — minimal förändring
- `t()` ger översättningsbarhet via Configuration > Languages > Translate interface
- Inga andra config/contrib-alternativ tillgängliga

### Tab-uppdelning
**Tab 1 — Electrical**
field_lumens, field_efficacy, field_cri, field_power_factor, field_current,
field_dimmable, field_dimming_protocol, field_frequency, field_energy_class, field_rated_life

**Tab 2 — Physical**
field_dimension_length, field_dimension_width, field_dimension_height,
field_weight, field_material, field_housing_color, field_mounting_type,
field_operating_temp_min, field_operating_temp_max, field_ik_rating

**Tab 3 — Certifications**
field_ce_marking, field_rohs, field_enec, field_warranty_years

### Alternativ övervägda
1. **Field Group** — Testat, inkompatibelt med Commerce entity types
2. **Field Group på produkt-nivå** — Testat, fungerar inte heller
3. **Bootstrap tabs i template med t()** — VALD LÖSNING

**Godkänt av Stefan**: Godkänd

---

## 3. IMPLEMENT

### Steg
1. **Verifiera att variationsfält är synliga i variation default view display**
   - Redan klart — fält är aktiva
   
2. **Uppdatera commerce-product--default.html.twig med Bootstrap tabs**
   - Ersätt Key Features-sektionen med tabbar
   - Använd t() för etiketter
   - Rendera variationsfält via product.field_X
   - Git commit: [TASK-009] Add Bootstrap tabs for variation fields in product template

3. **Verifiera Commerce AJAX**
   - Byt variant — kontrollera att fält uppdateras i tabs
   - Console — inga JS-fel
   - Git commit vid godkänt test

### Hinder/Problem
- Field Group inkompatibelt med Commerce — dokumenterat ovan

---

## 4. VERIFY

### Testresultat
Pending

---

## 5. COMPLETION

### Status: In Progress

### Lardomar
- Field Group 4.0.0 fungerar INTE på Commerce entity types
- Commerce entity displays (product, variation) stöder inte Field Group
- För Commerce-specifika layouts: template eller Event Subscriber är enda alternativen
- t() i Twig ger översättningsbarhet utan UI-kontroll men utan hårdkodning av text

### Dokumentation
- [ ] `/docs/03-solutions/commerce-product-display.md` skapas efter completion
- [ ] Uppdatera commerce-ajax-solution.md med Field Group-begränsning

### Nasta steg
Implementera Bootstrap tabs i template efter godkännande.
