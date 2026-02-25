# Task: Produktsida — Struktur, Priser & UX

**Status**: Aktiv — ST-1 + ST-2 klara  
**Prioritet**: Hög  
**Skapad**: 2026-02-23  
**Uppdaterad**: 2026-02-24

---

## Bakgrund

Triton LED är ett B2B-system med quote-baserad försäljning. Priser ska inte visas för anonyma besökare — istället ska offertflödet vara primärt. Priser behöver däremot vara tillgängliga för inloggade partners på olika nivåer.

**Kärnan i systemet**: En "Master" CSV-fil som körs varje midnatt via cron för att importera nya produkter eller uppdatera befintliga (inklusive priser). Alla fält på produkten måste vara definierade utifrån vad denna CSV-fil ska innehålla.

---

## Rollstruktur (beslutad 2026-02-24)

| Roll | Ser listpris | Rabatt | Offertflöde |
|------|-------------|--------|-------------|
| Anonymous | ❌ | ❌ | ✅ "Request Quote" |
| Elektriker | ✅ | ❌ | ✅ |
| Partner Silver | ✅ | ✅ nivå 1 | ✅ |
| Partner Gold | ✅ | ✅ nivå 2 | ✅ |
| Admin | ✅ | ✅ | ✅ |

**Prisdöljning**: Renderas INTE för anonymous (ej CSS-hide) — säkerhetsaspekt.  
**Rabatter**: Ett listpris i CSV. Rabatter hanteras i Commerce Price Lists per roll eller individuellt per användare. KISS-modellen.

---

## Master CSV-struktur (beslutad 2026-02-24)

### Princip
- En rad per variant, produktdata upprepas
- Unik nyckel produkt: `product_sku` → `field_product_sku` på Commerce-produkten
- Unik nyckel variant: `sku`
- Varianten kopplas till produkten via `product_sku` (ej titel)
- Ett pris (listpris SEK) — rabatter hanteras i Commerce
- Bilder/video/PDF hanteras manuellt i Drupal (ej i CSV)
- EAN utelämnas tills vidare (framtida behov)
- Attributvärden skapas automatiskt vid import (`autocreate: true`)

### Testfil
`data/import/master-test.csv` — 2 produkter × 2 varianter (COMET-HB, PANEL-60)

### Produktnivå (upprepas per rad)

| Kolumn | Fält |
|--------|------|
| `product_sku` | field_product_sku (unik nyckel) |
| `product_title` | title |
| `product_brand` | field_brand |
| `product_series` | field_series |
| `product_short_description` | field_short_description |
| `product_body` | body |
| `product_features` | field_features |
| `product_status` | status |
| `store_id` | stores |

### Variantnivå (unik per rad)

| Kolumn | Fält |
|--------|------|
| `sku` | sku (unik nyckel) |
| `price` | price (SEK) |
| `variation_status` | status |
| `attribute_watt` | attribute_watt |
| `attribute_cct` | attribute_cct |
| `attribute_ip_rating` | attribute_ip_rating |
| `attribute_beam_angle` | attribute_beam_angle |
| `attribute_color` | attribute_color |
| `attribute_length` | attribute_length |
| `attribute_voltage` | attribute_voltage |
| `attribute_accessories` | attribute_accessories |
| `field_lumens` … `field_installation_notes` | (tekniska specs) |

---

## Subtasks

### ST-1: field_product_sku + Feeds-mappning ✅ KLAR
- ✅ Skapat `field_product_sku` (text, 255 tecken) på commerce_product
- ✅ Feeds tritonled_products: product_sku → field_product_sku (unik nyckel)
- ✅ Feeds tritonled_variations: reference_by bytt från title till field_product_sku
- ✅ map.target_id bytt från product_title till product_sku
- ✅ autocreate: true på alla attributmappningar
- ✅ Config exporterad

**Lärdomar**:
- Feeds-modulens databastabeller var ur sync — krävde avinstallation/reinstallation
- feeds_item-fälten måste tas bort manuellt innan Feeds kan avinstalleras:
  ```bash
  ddev drush php-eval "
  \$field = \Drupal\field\Entity\FieldStorageConfig::loadByName('commerce_product', 'feeds_item');
  if (\$field) { \$field->delete(); }
  \$field2 = \Drupal\field\Entity\FieldStorageConfig::loadByName('commerce_product_variation', 'feeds_item');
  if (\$field2) { \$field2->delete(); }
  "
  ```
- Återinstallation: `ddev drush pmu feeds_log feeds_tamper commerce_feeds feeds -y && ddev drush en feeds commerce_feeds feeds_tamper feeds_log -y && ddev drush cim -y`

---

### ST-2: Test-CSV och verifierad import ✅ KLAR
- ✅ `data/import/master-test.csv` skapad
- ✅ Produktimport: 2 produkter skapade (COMET-HB, PANEL-60)
- ✅ Variationsimport: 4 varianter skapade och kopplade till rätt produkter
- ✅ Re-import: "Updated 4 items" utan varningar
- ✅ Attributvärden auto-skapas via autocreate: true

**Importordning (ALLTID)**:
1. TritonLED Products Import (produkter först)
2. TritonLED Variations Import (varianter efter)

**Not om dummyprodukter**: Triton MAX, OPTI, SROW lever kvar — raderas manuellt via VBO när riktiga produkter finns.

---

### ST-3: Rollstruktur i Drupal ✅ KLAR
- ✅ Skapat roller: Elektriker, Partner Silver, Partner Gold
- ✅ Permissions per roll: access checkout, access content, view commerce_product, view media, view own commerce_order
- ✅ Prisdöljning via hook_entity_field_access() i tritonled_compat
  - anonymous + authenticated ser INGA priser
  - elektriker, partner_silver, partner_gold, administrator ser priser
- ✅ Commerce Promotions skapade (ej Price Lists — promotions klarar % per roll bättre):
  - "Quote - Partner Silver 5%" — automatisk 5% rabatt, order type: Quote
  - "Quote - Partner Gold 10%" — automatisk 10% rabatt, order type: Quote
- ✅ unit_price + total_price dolda i order item form display (Add to cart)
- ✅ Hårdkodad "Volume Pricing Available" och "Free shipping on orders over $5,000" borttagna från template
- ✅ Hårdkodad dubblettknapp "Request Quote" borttagen från template

**Beslut**:
- Listpris döljs även för authenticated (ej verifierad)
- Verifiering sker manuellt av TritonLED-personal
- Kunden kan ej ändra unit_price i formuläret
- field_permissions installerad men ej i bruk (Commerce base fields stöds ej)
- commerce_pricelist installerad men ej i bruk (promotions räcker för % per roll)

---

### ST-4: Produktsidans UX — Människor, Robotar & AI-agenter
*(Aktiv)*

#### ST-4a: Schema.org strukturerad data (SEO + AI + API) ✅ KLAR (delvis)
- ✅ Installerat: metatag, metatag_open_graph, schema_metatag, schema_product
- ✅ Konfigurerat Schema.org Product för commerce_product:
  - @type: Product
  - name: [commerce_product:title]
  - description: [commerce_product:field_short_description]
  - url: [commerce_product:url:absolute]
  - sku: [commerce_product:current_variation:sku]
- ⏸ offers: parkerat — pris-token hanteras separat pga rollbaserad synlighet
- [ ] Verifiera med Google Rich Results Test
- [ ] Verifiera att AI-agenter (ChatGPT, Perplexity) kan läsa strukturen

**Token-lärdomar**:
- Fältnamn i schema_metatag: `schema_product_name`, `schema_product_url` etc (ej `schema_product[name]`)
- Absolut URL: `[commerce_product:url:absolute]`
- SKU från variation: `[commerce_product:current_variation:sku]`

#### ST-4b: Views-baserade dataströmmar
Views används som central lösning för alla dataformat — samma data, olika display.
Inga extra moduler behövs utöver core Views.

| View | Format | Målgrupp |
|------|--------|----------|
| Produktlistning | HTML | Människor |
| Produkter JSON | JSON | Återförsäljare/API-partners |
| llms.txt | Plain text/Markdown | AI-agenter (Perplexity, ChatGPT m.fl.) |
| Produkt-export | CSV | Intern export |

**llms.txt** (emerging standard av Jeremy Howard/Answer.AI):
- `/llms.txt` — index över produkter i markdown
- `/llms-full.txt` — fullständigt produktinnehåll
- Cloudflare AI Gateway och ledande AI-agenter börjar respektera standarden
- Drupal View med Plain text display renderar detta utan extra modul

**api_partner-rollen** (stub nu, utbyggnad senare):
- [ ] Skapa roll: api_partner (manuellt verifierad)
- [ ] JSON-view exponerar listpris för api_partner
- [ ] Push/webhooks: parkeras som eget framtida task
- [ ] Rabatter per nivå: parkeras som eget framtida task

**Views att bygga:**
- [ ] products-html (produktlistning för människor)
- [ ] products-json (JSON för återförsäljare)
- [ ] products-llms (plain text/markdown för AI-agenter → /llms.txt)
- [ ] products-csv (intern export)

#### ST-4c: UX för människor
- [ ] Produktsida: visa "Kontakta oss för pris" för anonymous/authenticated
- [ ] Produktlistning: korrekt visning per roll
- [ ] Mobilanpassning

**Beslut**:
- Views är central lösning för alla dataströmmar — The Drupal Way
- Schema.org är standard för Google, AI-agenter och återförsäljare
- api_partner-rollen får listpris (rabatter per nivå = framtida task)
- Pull-feed via Views JSON-display — ingen JSON:API-modul behövs i första steget
- ProductGroup + Offer är rätt Schema.org-struktur för produkter med varianter
- llms.txt löses via View, ingen extra modul behövs

---

### ST-5: Cron-import (midnatt)
*(Efter ST-2 — kan påbörjas nu)*
- [ ] Konfigurera Feeds för automatisk körning varje midnatt
- [ ] Testa cron-körning
- [ ] feeds_item-bugg: rensa efter import (se 03-solutions/feeds-item-ajax-bug.md)

---

### ST-6: Produktsidans UX
*(När ST-3–ST-4 är klara)*

---

### ST-7: Offertflöde — styling
*(Redan byggt, stylas sist)*

---

## Tekniska noter

- `field_product_sku` skapad på commerce_product (255 tecken, required)
- Feeds-tabeller måste vara synkade — kör `ddev drush updb -y` vid schema-problem
- Commerce Price Lists för rabatter per roll (ej i CSV)
- EAN utelämnas tills kund levererar produktlista
- feeds_item-bugg: måste rensas på varianter efter varje import

---

## Startordning

**ST-1 ✅ → ST-2 ✅ → ST-3 → ST-4 → ST-5 → ST-6 → ST-7**
