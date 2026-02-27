# Lösning: Feeds Item AJAX 500-fel

**Datum**: 2026-02-21  
**Uppdaterad**: 2026-02-27  
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

## ✅ Permanent lösning (2026-02-27)

`FeedsImportSubscriber` i `tritonled_compat` rensar feeds_item automatiskt efter varje import.
Kör via cron — ingen manuell åtgärd behövs.

- Filtrerar på `feeds_item.target_id` per feed (rensar bara aktuell feeds entiteter)
- Processar i chunks om 50 för att hantera 15 000+ varianter
- Loggar antal rensade entiteter i watchdog (`type=tritonled_compat`)

**Manuell rensning behövs inte längre** om `tritonled_compat` är aktiverad.

---

## Manuell lösning (fallback om modulen ej är aktiv)

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

---

## Feeds private-mapp (viktigt vid CSV-uppdatering)

Feeds lagrar uppladdade CSV-filer i containerns private-mapp:
```
/var/www/html/private/feeds/
```

När CSV uppdateras på Mac måste den synkas manuellt till containern:
```bash
ddev exec cp /var/www/html/data/import/max-ip20.csv /var/www/html/private/feeds/max-ip20_2.csv
```

Filnamnet i containern (t.ex. `max-ip20_2.csv`) kan ses via:
```bash
ddev drush feeds:list-feeds
```

---

## Feeds hash-cache

Om Feeds inte importerar nya rader trots att de finns i CSV — rensa hash:
```bash
ddev drush sqlq "UPDATE commerce_product_variation__feeds_item SET feeds_item_hash = '';"
```

---

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
