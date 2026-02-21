# Task 004: Splide Thumbnail Navigation Styling

**Created**: 2026-02-21  
**Status**: Not Started  
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

**Godkänt av Stefan**: ⏳ Väntar

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

**Godkänt av Stefan**: ⏳ Väntar

---

## 3. IMPLEMENT

*(Fylls i vid implementation)*

---

## 4. VERIFY

*(Fylls i vid verifiering)*

---

## 5. COMPLETION

*(Fylls i när task är klar)*
