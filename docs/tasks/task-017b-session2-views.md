# TASK-017b SESSION 2 — Views: Produktöversikt + Seriesidor

**Skapad**: 2026-03-23
**Status**: Planned — startar nästa session
**Föregående**: TASK-017b SESSION 1 (taxonomier, fält, view mode series_card)

---

## Var vi är

Följande är klart sedan förra sessionen:
- ✅ Taxonomier: MAX (tid=13), OPTI (tid=14), SROW (tid=15) i `product_categories`
- ✅ Taxonomy `product_type`: Base (16), Sensor (17), Emergency (18), Emergency Daylight (19)
- ✅ Taxonomy `producers`: TritonLED (tid=20)
- ✅ `field_product_type` tillagd på `led_luminaire_max_opti` och `led_luminaire_srow`
- ✅ Alla 12 produkter kopplade: serie, typ, producent
- ✅ View mode `series_card` skapad och konfigurerad (title, field_short_description, field_configurator_media)
- ✅ Vy `produktserier` skapad (machine name: `produktserier`, URL: `/produkter`)
- ⚠️ Vyn visar fortfarande "default" view mode — måste ändras till "series_card"
- ⚠️ Vyn visar alla produkter utan filtrering/gruppering

---

## Vad som ska byggas

### Vy 1: Översiktssida `/produkter`
Visar en rad per **serie** (MAX, OPTI, SROW) — inte en rad per produkt.

**Lösning:** Vy med gruppering på `field_product_categories` + visa bara BASE-produkten per serie (eller representativ produkt).

**Alternativ approach:** Visa taxonomy-termer från `product_categories` istället för produkter direkt — ger en rad per serie automatiskt. Varje term-rad länkar till seriesidan.

**Rekommenderad approach: Taxonomy Terms-vy**
- Visa: `product_categories` taxonomy terms (MAX, OPTI, SROW)
- Fält: term name, beskrivning (lägg till på termen), bild (lägg till på termen)
- Länk: till `/produkter/serie/[term-slug]`
- Format: Unformatted list med Bootstrap-klasser

### Vy 2: Seriesida `/produkter/serie/%`
Visar alla produkter i en serie.

**Lösning:** Views page med contextual filter på `field_product_categories` taxonomy term (via URL-argument).

- URL: `/produkter/serie/%` där `%` = taxonomy term ID eller slug
- Filter: `field_product_categories` = URL-argument
- View mode: `series_card`
- Format: Unformatted list

---

## Implementation — steg för steg

### Steg 5a — Fixa befintlig vy `produktserier`
1. Gå till `/en/admin/structure/views/view/produktserier`
2. Ändra "Show" från default till **series_card** view mode
3. Lägg till sortering på `field_product_categories` (gruppering) eller byt approach till taxonomy-vy

### Steg 5b — Skapa taxonomy-vy för översiktssidan (rekommenderas)
Alternativt: Skapa en **ny** vy baserad på `Taxonomy terms` → `product_categories` vocabulary istället för produkter. Detta ger automatiskt en rad per serie.

Behöver:
- Lägga till fält på taxonomy-termerna: beskrivning + bild
- Skapa taxonomy term view mode med dessa fält

### Steg 5c — Seriesida med contextual filter
1. I vyn `produktserier`: lägg till en ny **Page display**
2. URL: `produkter/serie/%`
3. Lägg till **Contextual filter**: `field_product_categories` → taxonomy term ID
4. View mode: `series_card`
5. Lägg till en länk från varje produkt-kort till produktsidan (`/product/[slug]`)

### Steg 5d — Länk från Series Card till produktsidan
I view mode `series_card` behöver titeln vara en länk till produktsidan.
- Ändra `title`-formatteraren till "Link to entity" = Yes

### Steg 5e — Taxonomy terms: lägg till beskrivning och bild
För översiktssidan behöver varje taxonomy term (MAX, OPTI, SROW) ha:
- Beskrivning (finns som standard på taxonomy terms)
- Bild (nytt fält `field_term_image` på `product_categories`)

---

## Tekniska detaljer

### Views-konfiguration för seriesidan
```
Machine name: produktserier
Display: Page (seriesida)
URL: produkter/serie/%
Show: Product (series_card view mode)
Filter: Status = Published
Contextual filter: field_product_categories (taxonomy term ID)
  - When the filter value is NOT in the URL: display all results
  - Validator: Taxonomy term (product_categories vocabulary)
Sort: title ASC
```

### Series Card view mode — länk till produktsida
```
Fält: title
Formatter: Plain string
Link to entity: Yes  ← Detta måste aktiveras
```

---

## Acceptanskriterier

- [ ] `/produkter` visar MAX, OPTI, SROW med bild och beskrivning
- [ ] Klick på serie → `/produkter/serie/max` (eller liknande)
- [ ] `/produkter/serie/max` visar alla MAX-produkter som kort
- [ ] Varje produktkort har länk till produktsidan
- [ ] `/produkter/serie/opti` och `/produkter/serie/srow` fungerar likadant
- [ ] Config exporterad och committad

---

## Öppna frågor

| Fråga | Svar |
|-------|------|
| Ska översiktssidan baseras på taxonomy terms eller produkter? | Rekommendation: taxonomy terms (enklare) |
| Ska taxonomy-termerna ha egna bilder? | Ja — eller återanvänd default-produktbild per serie |
| URL-format för seriesida | `/produkter/serie/max` (taxonomy term slug) |
| Ska seriesidan ha Layout Builder? | Nej — Views räcker |
