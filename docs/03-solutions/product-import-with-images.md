# Lösning: Produktimport med bilder och varianter

**Skapad**: 2026-02-25  
**Task**: TASK-004  
**Gäller**: Alla produktserier

---

## Problem

Hur importerar man Commerce-produkter med varianter och kopplar bilder till rätt varianter baserat på kopplingstyp?

---

## Lösning

### 1. CSV-struktur

En CSV-fil innehåller både produkt- och variantdata. Samma fil används för två separata Feeds-importers.

**Feeds:**
- `tritonled_products` — skapar/uppdaterar Commerce-produkten
- `tritonled_variations` — skapar/uppdaterar varianter och kopplar till produkten

**Unik nyckel:**
- Produkt: `field_product_sku`
- Variation: `sku`

### 2. Importordning

**ALLTID i denna ordning:**
1. Kör products-feed först
2. Kör variations-feed
3. Rensa `feeds_item` från varianter (se nedan)

### 3. feeds_item måste rensas (KRITISKT)

Efter varje variations-import måste `feeds_item` rensas annars kraschar Media Library AJAX.

```bash
ddev drush php-eval "
\$vids = \Drupal::entityQuery('commerce_product_variation')->accessCheck(FALSE)->execute();
\$count = 0;
foreach (\$vids as \$vid) {
  \$v = \Drupal\commerce_product\Entity\ProductVariation::load(\$vid);
  if (\$v && \$v->hasField('feeds_item')) {
    \$v->set('feeds_item', NULL);
    \$v->save();
    \$count++;
  }
}
echo 'Cleaned: ' . \$count;
"
```

Se även: `feeds-item-ajax-bug.md`

### 4. Bildstrategi

**En bild per kopplingstyp** — delas av alla varianter med samma `attribute_accessories`.

Bilderna namnges efter kopplingstyp:
```
TM_W1(m).png  → alla varianter med accessories = W1
TM_W2(m).png  → alla varianter med accessories = W2
TM_W3(m).png  → alla varianter med accessories = W3
TM_CG(m).png  → alla varianter med accessories = CG
TM_EN(m).png  → alla varianter med accessories = EN
```

### 5. Bildupload

**Lokalt (utveckling):**  
Drush php-eval för att kopiera filer från `/Produkter/` och skapa Media-entiteter programmatiskt.

**Produktion (manuellt):**  
Modul: `media_bulk_upload`  
- Gå till `/admin/content/media`
- Drag-and-drop flera bilder samtidigt
- Notera Media-ID:na

### 6. Koppla bilder till varianter

**Produktion:** VBO (Views Bulk Operations)  
- Filtrera varianter per `attribute_accessories`-värde
- Bulk-sätt `field_variation_media` till rätt Media-entitet

**Logik:** `attribute_accessories ID → Media ID`

Exempel MAX IP20:
```
W1 (60) → Media 12
W2 (62) → Media 13
W3 (63) → Media 14
CG (64) → Media 15
EN (65) → Media 16
```

---

## Accessories-attribut ID:n

```
49 | Driver included
50 | Mounting frame included
51 | Mounting bracket included
60 | W1
62 | W2
63 | W3
64 | CG
65 | EN
```

Hämta aktuella ID:n:
```bash
ddev drush php-eval "
\$values = \Drupal::entityTypeManager()
  ->getStorage('commerce_product_attribute_value')
  ->loadByProperties(['attribute' => 'accessories']);
foreach (\$values as \$v) { echo \$v->id() . ' | ' . \$v->getName() . PHP_EOL; }
"
```

---

## Moduler som krävs

- `feeds` + `commerce_feeds` — CSV-import
- `media_bulk_upload` — multi-upload i produktion
- `vbo` (views_bulk_operations) — bulk-koppla bilder i produktion

---

## Viktigt

- Kör ALDRIG variations-feed utan att rensa feeds_item efteråt
- I produktion: inga scripts — använd media_bulk_upload + VBO
- Bilderna ligger i `/Produkter/[SERIE]/Bilder/`
