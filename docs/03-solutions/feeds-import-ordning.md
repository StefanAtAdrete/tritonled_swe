# Feeds Import: Korrekt ordning och store-koppling

**Skapad**: 2026-03-06  
**Relaterad task**: TASK-014

---

## Problem

Commerce-produkter som skapats manuellt (utan products-feed) saknar store-koppling.
Symptom vid AJAX-variantbyte:

```
Exception: The given entity is not assigned to any store.
in Drupal\commerce_cart\Form\AddToCartForm->selectStore()
HTTP 500
```

## Orsak

`commerce_product__stores`-tabellen saknar rad för produkten.
Products-feeden ansvarar för att sätta denna koppling via `stores`-mappningen.

## Lösning

### Alltid köra feeds i rätt ordning

1. **Products-feed FÖRST** — skapar/uppdaterar produkten med store-koppling
2. **Variations-feed SEDAN** — importerar variationerna

### Products-CSV struktur (minsta krav)

```csv
product_sku,product_title,product_brand,product_series,product_short_description,product_body,product_features,product_status,store_id
TRITON-OPTI,Triton OPTI,TritonLED,OPTI,"Short description","Body text","Feature 1|Feature 2",1,TritonLED Sweden AB
```

- `store_id` = store-namnet exakt som det heter i Drupal (`TritonLED Sweden AB`)
- Feeds-mappningen använder `reference_by: name`
- En rad per produkt (inte per variant)
- En rad på engelska — svenska via Drupal Translate-UI

### Verifiera store-koppling

```bash
ddev drush sql:query "SELECT entity_id, stores_target_id FROM commerce_product__stores WHERE entity_id = X;"
```

Ska returnera en rad med `stores_target_id = 1`.

### Om store-kopplingen saknas efter products-feed

Detta kan hända om products-feeden kördes men produkten redan existerade utan store-koppling (t.ex. skapad manuellt eller via en tidigare felaktig import). Feeden uppdaterar inte alltid store-kopplingen.

**Fix**:
```bash
ddev drush sql:query "INSERT INTO commerce_product__stores (bundle, deleted, entity_id, revision_id, langcode, delta, stores_target_id) VALUES ('default', 0, X, X, 'en', 0, 1);"
```
Ersätt `X` med produktens `product_id`.

Eller kör products-feeden på nytt efter att ha raderat den befintliga store-raden:
```bash
ddev drush sql:query "DELETE FROM commerce_product__stores WHERE entity_id = X;"
```
Sedan kör products-feeden igen.

---

## AJAX 500-fel vid variantbyte — checklista

Om AJAX-anrop returnerar 500 vid variantbyte, kontrollera alltid i denna ordning:

1. **Store-koppling** — `SELECT * FROM commerce_product__stores WHERE entity_id = X;`
2. **Drupal-loggen** — `ddev drush watchdog:show --count=5 --severity=3`
3. **feeds_item** — rensas automatiskt av `tritonled_compat` men verifiera via watchdog

Det vanligaste felet är alltid store-kopplingen — kontrollera detta FÖRST.

### Om produkten saknar store-koppling (nödfix)

```bash
ddev drush sql:query "INSERT INTO commerce_product__stores (bundle, deleted, entity_id, revision_id, langcode, delta, stores_target_id) VALUES ('BUNDLE', 0, ID, ID, 'en', 0, 1);"
```

Ersätt `BUNDLE` med produkttyp (t.ex. `triton_opti`) och `ID` med produkt-ID.
**OBS:** Kör products-feeden efteråt så att produkten registreras i feeds_item korrekt.

---

## feeds_item-rensning

### Automatisk (sedan TASK-014)

`tritonled_compat` FeedsImportSubscriber rensar `feeds_item` automatiskt efter varje import.
Verifiera i loggen:

```bash
ddev drush watchdog:show --count=10 --type=tritonled_compat
```

### Manuell rensning (vid behov)

Tabellen heter `commerce_product_variation__feeds_item` (INTE `feeds_item`):

```bash
ddev drush sql:query "DELETE FROM commerce_product_variation__feeds_item WHERE entity_id IN (SELECT variation_id FROM commerce_product_variation_field_data WHERE product_id = X);"
```

---

## Feeds-tabeller (rätt namn)

| Vad                        | Tabellnamn                              |
|----------------------------|-----------------------------------------|
| Feeds_item på variationer  | `commerce_product_variation__feeds_item` |
| Feeds_item på produkter    | `commerce_product__feeds_item`           |
| Store-koppling på produkt  | `commerce_product__stores`               |
| Alla feeds-instanser       | `feeds_feed`                             |
| Media-entiteter och bilder | `media__field_media_image`               |

---

## Media entity-ID vs file-ID — KRITISKT

Feeds CSV måste använda **media entity-ID** (`mid`), INTE **file-ID** (`fid`).
Dessa är OLIKA tal och förväxlas lätt.

### Hitta rätt media entity-ID:n
```bash
ddev drush sql:query "SELECT entity_id, field_media_image_target_id FROM media__field_media_image WHERE field_media_image_target_id IN (151,152,153);"
```
Returerar: `entity_id` = media-ID att använda i CSV

### Feed-konfiguration (korrekt)
```yaml
target: field_variation_media
settings:
  reference_by: mid
  autocreate: 0
```

---

## Feeds cached-fil problem

När du laddar upp ny CSV via GUI byter Drupal namn: `file.csv` → `file_0.csv` → `file_0_0.csv`

**Lösning**: Kopiera rätt fil över den cachade:
```bash
ddev exec cp /var/www/html/private/feeds/max-variations-v1.csv /var/www/html/private/feeds/max-variations-v1_0_0.csv
```

---

## Bildbytet fungerar inte vid variantbyte — AJAX-problem, inte import

När bilder ser ut att "inte bytas" vid variantbyte på frontend är det **TASK-010b** (Commerce AJAX).
Detta är INTE ett importproblem.

**Verifiera snabbt att bilden är korrekt importerad:**
```bash
ddev drush php:eval "
\$storage = \Drupal::entityTypeManager()->getStorage('commerce_product_variation');
\$vars = \$storage->loadByProperties(['sku' => 'TSA16K0-CG']);
\$var = reset(\$vars);
print_r(\$var->get('field_variation_media')->getValue());
"
```
Om `target_id` är rätt → import OK, problemet är AJAX (TASK-010b).
Om `target_id` saknas → import-problem, felsök feeds-konfigurationen.

---

## Nya attributvärden innan import

Om `autocreate: false` i feed-config måste attributvärdet skapas först:
```bash
ddev drush eval "
\$storage = \Drupal::entityTypeManager()->getStorage('commerce_product_attribute_value');
\$value = \$storage->create([
  'attribute' => 'model',
  'name' => 'Emergency Daylight',
  'langcode' => 'en',
]);
\$value->save();
print 'Created: ' . \$value->id();
"
```
