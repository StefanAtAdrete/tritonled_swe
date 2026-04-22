# TASK-032 — Product Card (display-component)

## FDT-klass: display-component + data-model
## Status: Steg 5 — VERIFY

---

## Steg 0 — SPEC ✓ KLAR

**UX-mål:** Lugnt, lättläst produktkort för elektriker.
**Kortets struktur:**
- Produktbild med miljöbadge i övre högra hörnet
- Titel (länkad till produktsidan)
- Feature-punkter via `field_short_description`
- (Watt · Lumen · CCT parkerat för senare)

---

## Steg 1 — DATA ✓ KLAR

- `field_short_description` på alla 10 bundles ✓
- `field_product_categories` på `surge_protection` ✓
- Taxonomi `installation_environment` skapad: Allround, Utomhus, Industri, Ex-miljö, Kontor, Gata & Area ✓
- `field_installation_environment` på alla 10 bundles ✓
- Bugg fixad: `attribute_watt` saknade `target_bundles` på `ex_hazardous_variation` och `street_area_variation` ✓

---

## Steg 2 — CONTENT ✓ KLAR

- DB53 Hilton (linear_led, prod 35): short_description + Allround ✓
- Explosionssäker hallbelysning (ex_hazardous, prod 40): short_description + Ex-miljö ✓

---

## Steg 3 — DISPLAY ✓ KLAR

- View mode `commerce_product.card` skapad ✓
- 10 entity view displays (`commerce_product.{bundle}.card`) ✓
  - Formatter `basic_string` för `field_short_description` ✓
  - `variation__layout_builder` och `variation_price` dolda ✓
- Twig-template `commerce-product--card.html.twig` ✓
  - Använder `product.field_xxx` (Commerce-specifikt, INTE `content.field_xxx`)
  - Bootstrap card-markup med dynamisk badge-färg
- Views-block `tritonled_product_cards` ✓
  - Rendered entity → Card view mode
  - Språkfilter: Interface text language ✓
  - Bootstrap Grid 4 kolumner

---

## Steg 4 — LAYOUT ✓ KLAR

- Block placerat i Layout Builder ✓
- Renderar korrekt utan add-to-cart ✓

---

## Steg 5 — VERIFY ⏳ PÅGÅR

**Aktuellt läge:**
- Bild, titel, badge och short_description renderar ✓
- Inga dubbletter ✓
- Inga add-to-cart-formulär ✓

**Kvar att verifiera mot mockup:**
- [ ] Badge-färger stämmer (gul=Ex-miljö, grön=Utomhus etc.)
- [ ] Bildaspekt rätt (4:3)
- [ ] Responsivt (mobil)
- [ ] Produkter utan `field_short_description` ser OK ut
- [ ] Stefan godkänner visuellt mot ursprunglig mockup

**Kvar att fylla i:**
- `field_installation_environment` saknas på de flesta produkter — behöver fyllas i
- `field_short_description` saknas på de flesta produkter — behöver fyllas i

---

## Steg 6 — API ⏸ Väntar

Avgörs efter steg 5.

---

## Tekniska lärdomar (denna task)

- Commerce product templates använder `{{ product.field_xxx }}` INTE `{{ content.field_xxx }}`
- `string_long` fält kräver formatter `basic_string`, INTE `string`
- Views + Rendered Entity kräver språkfilter för att undvika dubbletter
- `variation__layout_builder` och `variation_price` måste döljas explicit i entity view display
- `attribute_watt` på `ex_hazardous_variation` och `street_area_variation` saknade `target_bundles` — pre-existerande bugg, triggas vid Rendered Entity-rendering
