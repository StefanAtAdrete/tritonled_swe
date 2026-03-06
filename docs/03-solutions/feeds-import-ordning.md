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
