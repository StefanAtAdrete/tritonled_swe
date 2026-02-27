# Lösning: Produktbilder — Uppladdning och koppling till varianter

**Datum**: 2026-02-25  
**Gäller**: Commerce produktvarianter med `field_variation_media`  
**Princip**: Bilder kopplas per kopplingstyp (attribute_accessories), inte per SKU

---

## Bakgrund

Triton MAX-bilder är namngivna efter kopplingstyp, inte individuell SKU.
En bild delas av alla varianter med samma koppling, oavsett längd/watt/CCT.

| Koppling | Bildfil | Accessories attribute |
|----------|---------|-----------------------|
| W1 | `TM_W1(m).png` | W1 |
| W2 | `TM_W2(m).png` | W2 |
| W3 | `TM_W3(m).png` | W3 |
| CG | `TM_CG(m).png` | CG |
| EN | `TM_EN(m).png` | EN |

Bilderna för kommande produktserier följer samma mönster:
- MAX Emergency: `TMEW1(m).png`, `TMEW2(m).png`, `TMEW3(m).png`, `TMEEN(m).png`
- MAX Sensor: `TMSW1(m).png`, `TMSW2(m).png`, `TMSW3(m).png`, `TMSCG(m).png`, `TMSEN(m).png`

---

## Arbetsflöde (produktion — manuellt)

### 1. Ladda upp bilder

Använd **Media Bulk Upload** för att ladda upp flera bilder samtidigt:

- Gå till `/admin/content/media`
- Klicka **"Bulk upload"**
- Dra och släpp alla bilder för produktserien
- Namnge Media-entiteterna konsekvent (t.ex. `TM_W1`, `TM_W2` etc.)

### 2. Koppla bilder till varianter via VBO

- Gå till `/admin/commerce/products` → öppna produkten
- Eller gå till `/admin/commerce/product-variations` (filtrera på produktserie)
- Välj varianter med samma kopplingstyp via VBO (Views Bulk Operations)
- Kör bulk-action: **"Edit selected"** → sätt `field_variation_media`

### 3. Upprepa per kopplingstyp

Gör detta för varje kopplingstyp (W1, W2, W3, CG, EN).

---

## Arbetsflöde (lokal utveckling — via Drush)

### Steg 1: Importera bilder från filsystemet

```bash
ddev drush php-eval "
\$dir = 'public://products/max';
\Drupal::service('file_system')->prepareDirectory(\$dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);
\$files = ['TM_W1(m).png','TM_W2(m).png','TM_W3(m).png','TM_CG(m).png','TM_EN(m).png'];
foreach (\$files as \$filename) {
  \$source = '/var/www/html/Produkter/MAX/Bilder/' . \$filename;
  \$destination = 'public://products/max/' . \$filename;
  \Drupal::service('file_system')->copy(\$source, \$destination, \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE);
  \$file = \Drupal\file\Entity\File::create(['uri' => \$destination, 'status' => 1]);
  \$file->save();
  \$media = \Drupal\media\Entity\Media::create([
    'bundle' => 'image',
    'name' => \$filename,
    'field_media_image' => ['target_id' => \$file->id(), 'alt' => \$filename],
  ]);
  \$media->save();
  echo \$media->id() . ' | ' . \$filename . PHP_EOL;
}
"
```

Notera Media-ID:na från output.

### Steg 2: Koppla Media till varianter

Ersätt ID:na i kartan med de faktiska Media-ID:na och accessories attribute-ID:na:

```bash
# Hämta accessories attribute ID:n
ddev drush php-eval "
\$values = \Drupal::entityTypeManager()
  ->getStorage('commerce_product_attribute_value')
  ->loadByProperties(['attribute' => 'accessories']);
foreach (\$values as \$v) { echo \$v->id() . ' | ' . \$v->getName() . PHP_EOL; }
"
```

Koppla sedan via php-eval med kartan `[accessories_id => media_id]`:

```php
// Exempel: anpassa ID:na efter faktiska värden
$map = [
  60 => 12,  // W1 → Media 12
  62 => 13,  // W2 → Media 13
  63 => 14,  // W3 → Media 14
  64 => 15,  // CG → Media 15
  65 => 16,  // EN → Media 16
];
```

---

## Installerade moduler

- **Media Bulk Upload** (`media_bulk_upload`) — bulk-upload av bilder via UI
- **Views Bulk Operations** (VBO) — bulk-koppling av bilder till varianter

---

## OBS: Efter CSV-import

Kom ihåg att `feeds_item`-rensning måste köras efter varje import.
Se: `feeds-item-ajax-bug.md`

Bilder kopplas EJ om automatiskt vid re-import — de bevaras på varianten.
