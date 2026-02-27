# Task: Triton MAX — Produktimport, Bilder & PDF

**Status**: Pågående (ST-3 delvis klar, ST-4 återstår)
**Prioritet**: Hög
**Skapad**: 2026-02-25

---

## Bakgrund

Triton MAX är den första riktiga produktserien som importeras till TritonLED-plattformen.
Materialet finns i `/Produkter/MAX/` och består av:

- `Max IP20.pdf` — datablad med tekniska specs + varianttabell
- `Max-Emergency W1,W2,W3 (IP20).pdf` — datablad för nödbelysningsvariant
- `MAX serien Intro 25-05(Sve).pdf` — marknadsföringstext på svenska
- `Bilder/` — produktbilder namngivna efter kopplingstyp

---

## Subtasks

### ST-1: CSV-generering ✅ KLAR
- SKU-format: `TM-[längdkod]-[watt]-[CCT]-[koppling]-[driver]`
- Exempel: `TM-05-29-4K-W1-D2` (500mm, 29W, 4000K, Wago W1, DALI2)
- 480 varianter genererade i `data/import/max-ip20.csv`
- 4 längder: 500/1000/1500/2000mm
- 4 CCT: 3000/4000/5000/6500K
- 5 kopplingar: W1/W2/W3/CG/EN
- 2 drivers: OnOff/DALI2

### ST-2: Import ✅ KLAR
- Products feed (`tritonled_products`): 1 produkt skapad
- Variations feed (`tritonled_variations`): 480 varianter skapade
- feeds_item rensat från 493 varianter (kritiskt för AJAX-buggen)
- Se: `03-solutions/feeds-item-ajax-bug.md`

### ST-3: Bilder ✅ KLAR (lokalt)
- 5 Media-entiteter skapade via Drush från `/Produkter/MAX/Bilder/`
- Kopplingar accessories → Media ID:
  - W1 (ID 60) → Media 12 → `TM_W1(m).png`
  - W2 (ID 62) → Media 13 → `TM_W2(m).png`
  - W3 (ID 63) → Media 14 → `TM_W3(m).png`
  - CG (ID 64) → Media 15 → `TM_CG(m).png`
  - EN (ID 65) → Media 16 → `TM_EN(m).png`
- 480 varianter kopplade till rätt bild via `field_variation_media`

**Produktionsarbetsflöde för bilder (manuellt):**
- Modul: `media_bulk_upload` installerad
- Upload: `/admin/content/media` → drag-and-drop flera bilder samtidigt
- Koppla bilder till varianter: VBO (Views Bulk Operations)
- Logik: en bild per kopplingstyp (attribute_accessories), delas av alla varianter med samma koppling

### ST-4: PDF-datablad ⏳ ÅTERSTÅR
- [ ] Upload `Max IP20.pdf` till Media (document)
- [ ] Koppla till produkten via `field_datasheet`
- [ ] Verifiera länk på produktsidan

### ST-5: MAX Emergency ⏳ ÅTERSTÅR
- [ ] Generera `data/import/max-emergency.csv` (24 varianter)
- [ ] Varianter: 500mm, 29W+4W, 4 CCT, 3 kopplingar (W1/W2/W3) + EN, OnOff
- [ ] Bilder: TMEW1(m).png, TMEW2(m).png, TMEW3(m).png, TMEEN(m).png
- [ ] Import + feeds_item rensning
- [ ] Bildkoppling

---

## Tekniska beslut

**Bildstrategi**: En bild per kopplingstyp — delas av alla varianter med samma `attribute_accessories`.
Enklare och mer underhållbart än en bild per SKU.

**Accessories-attribut ID:n** (viktigt för bildkoppling):
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

**Import-arbetsflöde**:
1. Kör `tritonled_products` feed → skapar produkten
2. Kör `tritonled_variations` feed → skapar varianter
3. Rensa feeds_item (KRITISKT):
```bash
ddev drush php-eval "
\$vids = \Drupal::entityQuery('commerce_product_variation')->accessCheck(FALSE)->execute();
foreach (\$vids as \$vid) {
  \$v = \Drupal\commerce_product\Entity\ProductVariation::load(\$vid);
  if (\$v && \$v->hasField('feeds_item')) { \$v->set('feeds_item', NULL); \$v->save(); }
}
"
```

**Bildupload lokalt via Drush**:
```bash
ddev drush php-eval "
\$dir = 'public://products/max';
\Drupal::service('file_system')->prepareDirectory(\$dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);
// loop över filer och skapa Media-entiteter
"
```

**OBS**: I produktion används `media_bulk_upload` + VBO — inga scripts.

---

## Material

```
/Produkter/MAX/
├── Max IP20.pdf
├── Max-Emergency W1,W2,W3 (IP20).pdf
├── MAX serien Intro 25-05(Sve).pdf
└── Bilder/
    ├── TM_W1(m).png   ← MAX standard W1
    ├── TM_W2(m).png   ← MAX standard W2
    ├── TM_W3(m).png   ← MAX standard W3
    ├── TM_CG(m).png   ← MAX standard CG
    ├── TM_EN(m).png   ← MAX standard EN
    ├── TMEW1(m).png   ← MAX Emergency W1
    ├── TMEW2(m).png   ← MAX Emergency W2
    ├── TMEW3(m).png   ← MAX Emergency W3
    └── TMEEN(m).png   ← MAX Emergency EN
```
