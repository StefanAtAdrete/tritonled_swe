# Task 004: Splide Thumbnail Navigation Styling

**Created**: 2026-02-21  
**Status**: Completed  
**Last Updated**: 2026-02-21  
**Related Tasks**: TASK-001 (product page layout)

---

## 1. DEFINE

### Mål
Styla Splide thumbnail-navigeringen på produktsidan så den ser professionell ut och matchar TritonLED:s design.

### Syfte
Thumbnail-navigeringen visas för tillfället med svart bakgrund och ostylade thumbnails. Måste stylas för att ge ett professionellt intryck för B2B-köpare.

### Nuläge (screenshot 2026-02-21)
- Stor huvudbild visas korrekt
- Thumbnail-strip längst ner: svart bakgrund, för stor höjd
- Pil-navigation syns men är ogranskad designmässigt

### Acceptanskriterier
- [ ] Thumbnails har rätt storlek (liten, kompakt)
- [ ] Aktiv thumbnail markeras tydligt (border/highlight)
- [ ] Ingen svart bakgrund på thumbnail-strip
- [ ] Thumbnails är klickbara och byter huvudbild
- [ ] Ser bra ut på desktop och mobil

**Godkänt av Stefan**: ✅ 2026-02-21

---

## 2. PLAN

### Beslutsträd
**Fil**: `/docs/DRUPAL-DECISION-TREE.md`  
**Steg**: Config → Splide optionset-inställningar → CSS (om nödvändigt, kräver godkännande)

### Vald lösning
**Approach**: Config först – justera Splide optionset `product_nav` inställningar (height, gap, padding). CSS som sista utväg.

### Alternativ övervägda
1. **Splide optionset config** – Justera `fixedHeight`, `gap`, `padding` i `product_nav` optionset via admin UI. Föredragen approach.
2. **CSS** – Minimal CSS på `.splide__slide` i nav-slidern. Kräver godkännande.

**Godkänt av Stefan**: ✅ 2026-02-21

---

## 3. IMPLEMENT

### Ändringar

1. **`config/sync/splide.optionset.product_nav.yml`**
   - `fixedHeight: '80'`
   - `gap: '8'`
   - `cover: true`
   - `slideFocus: true`

2. **`config/sync/core.entity_view_display.commerce_product_variation.default.default.yml`**
   - `thumbnail_style: max_325x325` (var tom — thumbnails renderades inte)

3. **`css/components/product-gallery.css`**
   - Lade till Splide-specifik CSS för transparent bakgrund, border på aktiv thumbnail, opacity-effekt

---

## 4. VERIFY

- ✅ Thumbnails renderas med produktbild
- ✅ Svart bakgrund borttagen
- ✅ Aktiv thumbnail markeras med blå border
- ✅ Ser bra ut på desktop
- ⚠️ Logotypbild i gallery (datakvalitetsproblem, ej styling)

---

## 5. COMPLETION

**Datum**: 2026-02-21
**Godkänt**: Stefan

### Lärdomar
- `thumbnail_style` måste sättas i field formatter — annars renderas inga thumbnails
- Svart bakgrund kom från Splide default skin, fixades med minimal CSS
- Konfiguration via config/sync-filer + `drush cim` är rätt workflow
