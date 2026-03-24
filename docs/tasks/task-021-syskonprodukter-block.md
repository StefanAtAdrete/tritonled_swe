# TASK-021 — Syskonprodukter-block på produktsidan

**Skapad**: 2026-03-24
**Status**: Planned
**Prioritet**: Hög

---

## Mål

Visa ett block på varje produktsida med liggande kort för övriga produkter i samma serie.
Besökaren som landar på t.ex. OPTI BASE ska direkt kunna se och klicka till OPTI-S, OPTI-E, OPTI-ED.

---

## DEFINE

### Acceptanskriterier
- [ ] Block visas på alla 12 produktsidor
- [ ] Visar bara produkter i samma serie (MAX → MAX-produkter, OPTI → OPTI-produkter, etc.)
- [ ] Aktuell produkt visas INTE i blocket
- [ ] Fungerar på mobil — liggande kort, wrappande eller horisontellt scrollbart
- [ ] Varje kort länkar till produktsidan
- [ ] Config exporterad och committad

### Vad som INTE ingår
- Badges/pills på Featured Products-kortet (separat beslut)
- Seriesidor (TASK-017b)

---

## PLAN

### Approach: Views Block med Contextual Filter

**Vy:** Ny display på befintlig vy `produktserier` (eller ny vy)
**Typ:** Block
**Show:** Commerce Product | series_card view mode
**Contextual filter:** `field_product_categories` (taxonomy term ID) — hämtas från URL/aktuell produkt
**Exclude:** `product_id != [current_product]` via filter

### Steg

1. **Skapa ny Views-display** (Block) på vyn `produktserier`
   - URL: ingen (block)
   - Show: series_card view mode
   - Filter: Published = true
   - Contextual filter: `field_product_categories` → Provide default value: Taxonomy term ID from URL
   - Exclude current product: filter `Product ID != [product_id]` (Contextual filter)

2. **series_card view mode** — verifiera att titel är länkbar (Link to entity = Yes)

3. **Placera blocket** i Layout Builder på produktsidorna
   - `led_luminaire_max_opti` default layout
   - `led_luminaire_srow` default layout

4. **Mobilstyling**
   - Bootstrap `d-flex flex-wrap gap-3` eller `row row-cols-2 row-cols-md-4`
   - Alternativt: horisontellt scroll med `overflow-x-auto flex-nowrap`

5. **Exportera config och committa**

---

## Tekniska detaljer

### Contextual filter-inställningar
```
Filter: field_product_categories (taxonomy term ID)
When the filter value is NOT available:
  → Provide default value: Content ID from URL (product entity)
  → OR: Display all results (fallback)
Validator: Taxonomy term (product_categories vocabulary)
```

### Exclude current product
```
Filter: Product ID
Operator: Is not equal to
Value: {{ arguments.nid }} (contextual)
```

### series_card view mode (verifiera)
- Title: Link to entity = Yes
- field_short_description: visa
- field_configurator_media: visa (default-bild)

---

## Öppna frågor

| Fråga | Svar |
|-------|------|
| Ska blocket ha en rubrik ("Fler produkter i MAX-serien")? | Avgörs vid implementation |
| Horisontellt scroll eller wrap på mobil? | Wrap föredras — enklare, mer tillgängligt |
| Placeras blocket via Layout Builder eller hook? | Layout Builder (standard) |
