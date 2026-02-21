# Lösning: Feeds Item AJAX 500-fel

**Datum**: 2026-02-21  
**Modul**: Feeds 8.x-3.2  
**Symptom**: "Add media" på variation-editor ger 500-fel, Media Library dialog öppnas inte

---

## Problem

`feeds_item`-fältet på importerade entiteter orsakar 500-fel vid AJAX-formulärvalidering.

Felmeddelande i watchdog:
```
'fid' not found in Drupal\Core\Entity\Query\Sql\Tables->ensureEntityTable()
```

Drupal validerar ALLA entity reference-fält vid AJAX-anrop (t.ex. "Add media"). `feeds_item` refererar till `feeds_feed`-entiteter. Om dessa references är korrupta eller om entity definition-cache är föråldrad misslyckas valideringen.

## Lösning

Rensa `feeds_item` på alla berörda entiteter:

```bash
ddev drush php-eval "
\$variations = \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->loadMultiple();
foreach(\$variations as \$v) {
  \$v->set('feeds_item', []);
  \$v->save();
  echo 'Cleared feeds_item on variation ' . \$v->id() . PHP_EOL;
}
"
```

## OBS – Måste upprepas efter import

`feeds_item` sätts om automatiskt vid nästa CSV-import. Kör kommandot ovan igen om felet återkommer efter import.

## Diagnos-kommandon

```bash
# Identifiera vilket fält som failar
ddev drush php-eval "
\$entity = \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->load(9);
foreach(\$entity->getFields() as \$name => \$field) {
  try { \$field->validate(); } catch(\Exception \$e) {
    echo 'FAILED: ' . \$name . ' - ' . \$e->getMessage() . PHP_EOL;
  }
}
"
```

## Permanent fix

Bevaka Feeds module-uppdateringar. Buggen kan vara fixad i senare versioner än 8.x-3.2.
