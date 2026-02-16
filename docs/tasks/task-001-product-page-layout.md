# Task 001: Product Page Layout Structure

**Created**: 2025-02-16  
**Status**: In Progress  
**Last Updated**: 2025-02-16 15:45  
**Related Tasks**: N/A

---

## 1. DEFINE

### Mål
Skapa en grundläggande layout-struktur för produktsidor (Commerce Product display) som:
1. Bevarar Commerce AJAX-funktionalitet för produktvarianter
2. Följer designen från referensbilden (Lumina Industrial)
3. Använder Bootstrap grid-system för responsiv layout

### Syfte
Produktsidor behöver en strukturerad layout som visar:
- Produktbilder (vänster kolumn)
- Produktinformation och specifikationer (höger kolumn)
- Variant-switchers (Wattage, CCT, Voltage, Accessories)
- Call-to-action buttons (Request Quote, Spec Sheet)
- Tabs för specifikationer, photometrics, certifications, downloads
- Ideal Applications sektion längst ner

**Varför template?** Layout Builder förstör Commerce AJAX för produktvarianter (känt problem från tidigare).

### Acceptanskriterier
- [ ] Template finns i `themes/custom/tritonled_radix/templates/commerce/`
- [ ] AJAX variant-switching fungerar (kan växla mellan wattage/CCT utan sidladdning)
- [ ] Bootstrap grid används (col-md-6 eller liknande för 2-kolumns layout)
- [ ] Bootstrap utility classes används för spacing (mb-3, mt-4, p-3 etc) - JUSTERBARA i template
- [ ] Layout liknar designen (exakta fält kan skilja sig)
- [ ] Alla befintliga produktfält renderas korrekt
- [ ] Responsiv: 1 kolumn på mobil (<768px), 2 kolumner på desktop (≥768px)
- [ ] Inga PHP/Twig errors i watchdog
- [ ] Cache clear fungerar utan problem

**Godkänt av Stefan**: ⏳ Väntar

---

## 2. PLAN

### Beslutsträd
**Fil**: `/docs/DRUPAL-DECISION-TREE.md` + `/docs/01-decision-trees/commerce-decision-tree.md`  
**Steg**: 
1. ✅ Steg 1-2: Kolla befintlig config → Commerce Product har redan view modes
2. ✅ Steg 3-4: Kolla contrib modules → Commerce använder AJAX för variants
3. ⚠️ Steg 5: Layout Builder → FÖRSTÖR AJAX (dokumenterat i commerce-ajax-solution.md)
4. → Steg 6: Template approach → **VALD LÖSNING**

### Vald lösning
**Approach**: Custom Template (Twig)  
**Specifik lösning**: 
1. Skapa Twig template: `commerce-product--luminaire--full.html.twig`
2. Använd Bootstrap grid klasser direkt i template
3. Rendera produktfält via `{{ content }}` för att bevara AJAX
4. Strukturera layout med Bootstrap containers och rows
5. INTE överrida field templates (bevara Commerce AJAX injection)

**Template location**: 
```
themes/custom/tritonled_radix/templates/commerce/
  └── commerce-product--luminaire--full.html.twig
```

### Motivering
- Commerce AJAX kräver att field markup genereras av Commerce modules Event Subscribers
- Layout Builder förhindrar detta (dokumenterat i `/docs/03-solutions/commerce-ajax-solution.md`)
- Template med `{{ content.field_name }}` bevarar AJAX eftersom vi inte överridar field-nivå markup
- Bootstrap klasser i template ger full kontroll över layout
- View mode "full" används för produktsidor automatiskt

### Alternativ övervägda
1. **Layout Builder** - Varför inte: Förstör Commerce AJAX (testat tidigare)
2. **Display Suite** - Varför inte: Borttaget från projektet
3. **Field Groups** - Varför inte: Ger inte tillräcklig layoutkontroll för denna design
4. **Preprocess hook + minimal template** - Varför inte: Template är enklare och mer maintainable för denna use-case

**Godkänt av Stefan**: ⏳ Väntar

---

## 3. IMPLEMENT

### Steg

#### Steg 1: Skapa templates-struktur
- **Åtgärd**: Skapade `/web/themes/custom/tritonled_radix/templates/commerce/`
- **Resultat**: ✅ Katalog skapad

#### Steg 2: Skapa product template
- **Fil**: `commerce-product--luminaire--full.html.twig`
- **Innehåll**:
  - Bootstrap grid: `col-md-7` (bilder) + `col-md-5` (info) = 60/40 split
  - Vänster kolumn: Product images
  - Höger kolumn: Series badge, SKU, Title, Body, Key specs (4-grid), Variations (AJAX), CTAs
  - Bootstrap utility classes: `mb-3`, `mb-4`, `mb-5`, `p-3` (justerbara)
  - Tabs: Specifications, Certifications, Downloads
  - Ideal Applications sektion
- **AJAX-säkert**: Använder `{{ content.variations }}` för att bevara Commerce AJAX
- **Git commit**: [Väntar - Stefan behöver köra]

```bash
cd /Users/steffes/Projekt/tritonled
git add web/themes/custom/tritonled_radix/templates/commerce/commerce-product--luminaire--full.html.twig
git commit -m "[TASK-001] Create product page layout template with Bootstrap grid"
```

#### Steg 3: Cache clear och test
**Stefan kör:**
```bash
ddev drush cr
```

**Testa på:**
- https://tritonled.ddev.site/product/2 (TEST - Comet LED Highbay)
- https://tritonled.ddev.site/product/3 (Triton OPTI)

---

## 4. VERIFY

### Testplan

#### Test 1: Visuell jämförelse med design
**Metod**: Screenshot-jämförelse
- [ ] Claude tar screenshot av produktsida (desktop viewport)
- [ ] Jämför side-by-side med uploaded design
- [ ] Kollar: 2-kolumns layout, variant-switchers synliga, spec-ikoner, spacing
- [ ] OBS: Exakta fält kan skilja sig - "så nära som möjligt" är OK

#### Test 2: AJAX-funktionalitet (KRITISKT)
**Metod**: Manuell test av variant-switching
- [ ] Stefan klickar på olika wattage-knappar (100W, 150W, 200W, 240W)
- [ ] Stefan klickar på olika CCT-knappar (4000K, 5000K, 5700K)
- [ ] Verifiera: Specifikationer uppdateras UTAN sidladdning
- [ ] Verifiera: Inga JS errors i browser console

**Stefan kör:**
```bash
# Kolla errors efter AJAX-test
ddev drush watchdog:show --severity=Error
ddev logs | grep -i error
```

#### Test 3: Responsiv layout
**Metod**: Claude testar olika viewports
- [ ] Mobile (<768px): 1 kolumn, stack vertikalt
- [ ] Desktop (≥768px): 2 kolumner, bild vänster + info höger

#### Test 4: Teknisk verifiering
**Stefan kör:**
```bash
ddev drush cr
ddev logs
# Verifiera inga Twig/PHP errors
```

#### Test 5: Bootstrap-klasser justerbara
**Metod**: Verifiera i template-fil
- [ ] Spacing använder Bootstrap utility classes (mb-3, mt-4, p-3)
- [ ] Grid använder Bootstrap classes (col-md-6, row, container)
- [ ] Klasser är "nakna" i Twig (ej hårdkodade inline styles)
- [ ] Stefan kan enkelt ändra mb-3 till mb-4 för mer spacing

### Testresultat (Iteration 1)
**Testad**: [Datum/tid]  
**Testmiljö**: DDEV lokal

[Resultat kommer här efter implementation]

---

## 5. COMPLETION

### Status: 🔄 In Progress
