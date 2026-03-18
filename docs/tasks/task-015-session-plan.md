# TASK-015: Produktkonfigurator — Sessionsplan

**Skapad**: 2026-03-18  
**Uppdaterad**: 2026-03-18  
**Status**: Planning

---

## Kontext & förutsättningar

### Vad som är klart (TASK-020)
- ✅ Commerce Product Types: `led_luminaire_max_opti` och `led_luminaire_srow`
- ✅ `field_configurator_schema` (Long text) finns på båda typerna
- ✅ 12 Commerce-produkter skapade (en per modell)
- ✅ JSON-scheman finns i `/docs/product-schemas/` (max, opti, srow)
- ✅ Scheman är kompletta med `dependsOn`/`dependsOnAny`-logik
- ✅ Befintliga felaktiga produkter/varianter är borttagna
- ⚠️ Oklart: Är `field_configurator_schema` ifyllt på produkterna?
- ⚠️ Oklart: Finns det en Commerce-variation kopplad till varje produkt? (krävs för cart)

### Arkitekturbeslut (från task-015-konfigurator-arkitektur.md)
- **Ingen** Commerce-variantsökning — konfiguratorn lägger custom order item direkt i cart
- `tritonled_configurator` modul med `configurator_item` order item type
- JS läser `field_configurator_schema` → renderar steg → bygger SKU → lägger i cart
- SKU-formatet är produktdefinitionen — ingen variant-matchning behövs

---

## Teknisk arkitektur

### Drupal-backend
```
tritonled_configurator/
├── tritonled_configurator.info.yml
├── tritonled_configurator.module
├── tritonled_configurator.services.yml
├── config/install/
│   └── commerce_order_item_type.configurator_item.yml   ← Custom order item type
└── js/
    └── configurator.js                                    ← Frontend-logik
```

### Custom order item type: `configurator_item`
- Lagrar: `purchased_entity` (NULL — ingen variation-referens)
- Extra fält: `field_configurator_sku` (text) — det genererade SKU:t
- Extra fält: `field_configurator_data` (text_long/JSON) — valen som JSON
- Kopplas till en Commerce-produkt via `field_product_reference` eller liknande
- Priset sätts manuellt via en PriceResolver (eller sätts till 0 för offert-flöde)

### JavaScript-konfigurator
```
Flöde:
1. Sidan laddas → konfiguratorn läser schema från ett JSON-script-tag eller JSON:API
2. Renderar dropdowns i ordning (steps-array)
3. Varje val triggar dependsOn-filtrering på nästa steg
4. watt-steget filtrerar på dependsOnAny (length + cri/chips kombination)
5. SKU byggs live: prefix + middle-delar + kelvin + watt + optic + color
6. "Lägg i offert"-knapp: POST till Commerce Cart API med configurator_item
```

### SKU-format (från scheman)
```
MAX BASE: M-{length}{driver}{endcap}{cri}{kelvin}{watt}{optic}{color}
Exempel:  M-A0C8-J19N1

SROW BASE: S-{length}{driver}{endcap}{chips}{ipClass}{kelvin}{watt}{optic}{color}
Exempel:   S-B02Y-K30M1
```
Notera: kelvin har bindestreck som prefix i koden (t.ex. `-J`)

---

## Sessionsplan

### SESSION 1 (denna session) — Backend-grund
**Mål**: Modulstruktur + custom order item type klar och aktiverad

**Sub-tasks:**
- TASK-015-01: Verifiera att 12 produkter finns och att `field_configurator_schema` är ifyllt
- TASK-015-02: Skapa `tritonled_configurator` modul (info.yml, grundstruktur)
- TASK-015-03: Skapa `configurator_item` order item type config YAML
- TASK-015-04: Skapa fält på order item: `field_configurator_sku`, `field_configurator_data`
- TASK-015-05: Verifiera att modulen kan aktiveras utan fel (`ddev drush en tritonled_configurator`)

**Leverabler:**
- Modul-katalog med filer
- Order item type registrerad i Drupal
- Drush-kommandon att köra
- Commit: `[TASK-015-01] Add tritonled_configurator module with configurator_item order type`

**Tokens-bedömning:** Räcker för hela SESSION 1.

---

### SESSION 2 — JSON:API + Cart API-integration
**Mål**: Kan lägga `configurator_item` i cart via Drupal Commerce Cart API

**Sub-tasks:**
- TASK-015-06: Verifiera JSON:API endpoint för produkter med schema-fält
  - `/jsonapi/commerce_product/led_luminaire_max_opti?include=field_configurator_schema`
- TASK-015-07: Custom Cart API endpoint eller verifiering att standard Cart API fungerar
  - `POST /cart/add` med custom order item type
- TASK-015-08: PriceResolver för `configurator_item` (returnerar 0 för offert-flöde)
- TASK-015-09: Enkel test: lägg ett `configurator_item` i cart via curl/Postman

**Förutsättning:** SESSION 1 avklarad.  
**Tokens-bedömning:** Måttlig session — primärt config och PHP.

---

### SESSION 3 — JavaScript-konfigurator (del 1: rendering + dependsOn)
**Mål**: Dropdowns renderas korrekt med dependsOn-filtrering

**Sub-tasks:**
- TASK-015-10: JS-modul `configurator.js` — grundstruktur
- TASK-015-11: Schema-läsning (från inline JSON eller JSON:API)
- TASK-015-12: Rendera alla steps som dropdowns i ordning
- TASK-015-13: `dependsOn`-filtrering — dölj ogiltiga alternativ
- TASK-015-14: `dependsOnAny`-filtrering för watt-steget
- TASK-015-15: Visuell test med MAX BASE — alla steg fungerar

**Förutsättning:** SESSION 2 avklarad.  
**Tokens-bedömning:** Stor session — fokus på JS-logik.

---

### SESSION 4 — JavaScript-konfigurator (del 2: SKU + cart)
**Mål**: SKU byggs live, "Lägg i offert"-knapp fungerar

**Sub-tasks:**
- TASK-015-16: SKU-byggare i JS (middle-delar + end-delar)
- TASK-015-17: Live SKU-visning i UI
- TASK-015-18: "Lägg i offert"-knapp → POST till Cart API
- TASK-015-19: Feedback till användaren (success/error)
- TASK-015-20: Test med MAX BASE end-to-end

**Förutsättning:** SESSION 3 avklarad.  
**Tokens-bedömning:** Medelstor session.

---

### SESSION 5 — Styling + integration med produktsidan
**Mål**: Konfiguratorn ser bra ut och sitter rätt på produktsidan

**Sub-tasks:**
- TASK-015-21: Bootstrap-styling av konfiguratorn (dropdowns, SKU-display, knapp)
- TASK-015-22: Dölj Commerce's egna attribut-dropdowns på produktsidan
- TASK-015-23: Placera konfiguratorn i Layout Builder som ett block
- TASK-015-24: Test responsiv layout (mobil/desktop)
- TASK-015-25: Test med OPTI BASE och SROW BASE (generalisering)

**Förutsättning:** SESSION 4 avklarad.  
**Tokens-bedömning:** Medelstor session.

---

### SESSION 6 — Schema-fyllning + produkter (om ej klart)
**Mål**: Alla 12 produkter har `field_configurator_schema` ifyllt korrekt

**Sub-tasks:**
- Verifiera vilka produkter som saknar schema
- Drush-skript för att fylla i schema per produkt
- Alternativ: CSV-import om schema-fält stöds via Feeds

**Not:** Kan göras parallellt med SESSION 2-5 om Stefan fyller i manuellt via admin UI.

---

## Öppna frågor att besvara i SESSION 1

1. **Är `field_configurator_schema` redan ifyllt på de 12 produkterna?**
   - Om nej → måste fyllas i (SESSION 6 eller manuellt)
   
2. **Har varje Commerce-produkt en dummy-variation?**
   - Commerce kräver minst en variation för att en produkt ska vara köpbar
   - Vår `configurator_item` kanske kringgår detta — måste verifieras
   
3. **Hur ska konfiguratorn läggas in på produktsidan?**
   - Option A: Block via Layout Builder (rekommenderas — ingen template)
   - Option B: Direkt i product template (kräver godkännande)
   - Option C: Pseudo-field via `hook_entity_extra_field_info` (elegant men kräver PHP)
   
4. **Priset på `configurator_item`**
   - Offert-flöde → pris = 0, men måste visas som "Offert" eller tomt
   - Alternativ: Partner-pris via JSON:API och PriceResolver

---

## Nästa steg

→ Starta SESSION 1:
1. Verifiera produkter och schema-status via Drush
2. Godkänn modulstruktur
3. Skapa filer

