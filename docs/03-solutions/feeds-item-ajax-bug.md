# Lösning: Feeds Item AJAX 500-fel

**Datum**: 2026-02-21  
**Uppdaterad**: 2026-02-22  
**Modul**: Feeds 8.x-3.2  
**Symptom**: 500-fel vid formulärvalidering, "modified by another user" vid produktredigering

---

## Problem

`feeds_item`-fältet på importerade entiteter orsakar fel vid AJAX-formulärvalidering.

Felmeddelande i watchdog:
```
'fid' not found in Drupal\Core\Entity\Query\Sql\Tables->ensureEntityTable()
```

Drupal validerar ALLA entity reference-fält vid formulärvalidering. `feeds_item` refererar
till `feeds_feed`-entiteter. Om dessa references är korrupta misslyckas valideringen.

**OBS:** `feeds_item` finns på BÅDE `commerce_product` och `commerce_product_variation`.
Båda måste rensas.

## Symptom

- 500-fel vid "Add media" (Media Library AJAX)
- "The content has been modified by another user" vid produktredigering
- Formulär går inte att spara

## Lösning

Rensa `feeds_item` på både produkter och variationer:

```bash
# Rensa på variationer
ddev drush php:eval "
\$variations = \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->loadMultiple();
foreach(\$variations as \$v) {
  \$v->set('feeds_item', []);
  \$v->save();
  echo 'Cleared variation ' . \$v->id() . PHP_EOL;
}
"

# Rensa på produkter
ddev drush php:eval "
\$products = \Drupal::entityTypeManager()->getStorage('commerce_product')->loadMultiple();
foreach(\$products as \$p) {
  \$p->set('feeds_item', []);
  \$p->save();
  echo 'Cleared product ' . \$p->id() . PHP_EOL;
}
"

ddev drush cr
```

## OBS – Måste upprepas efter import

`feeds_item` sätts om automatiskt vid nästa CSV-import. Kör kommandona ovan igen efter import.

## Diagnos

```bash
# Identifiera vilket fält som failar på en produkt
ddev drush php:eval "
\$p = \Drupal::entityTypeManager()->getStorage('commerce_product')->load(3);
foreach(\$p->getFields() as \$name => \$field) {
  try { \$field->validate(); } catch(\Exception \$e) {
    echo 'FAILED: ' . \$name . ' - ' . \$e->getMessage() . PHP_EOL;
  }
}
"
```

## Permanent fix

Bevaka Feeds module-uppdateringar. Buggen kan vara fixad i senare versioner än 8.x-3.2.
