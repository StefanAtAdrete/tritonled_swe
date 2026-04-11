# Aktuell Task

**Senast uppdaterad**: 2026-04-11

---

## ⏭️ Nästa session startar här: TASK-029 Fas 3

**Läs**: `docs/tasks/task-029-produkttyper-faltstruktur.md`

**Fas 1 klar** ✅
- Taxonomy `product_categories` skapad (machine name med s)
- 6 termer: Linear LED Luminaire, Highbay, Floodlight, High Mast, Street & Area, EX / Hazardous
- Svenska översättningar tillagda på alla termer
- 9 fält skapade på commerce_product
- `field_product_category` tilldelad på: max, opti, srow, surge_protection

**Fas 2 klar** ✅ (2026-04-11)
- 7 produkttyper + variation types skapade via `drush php:eval`
- Order item type: `quote` på alla
- Fält återanvänds från `default` bundle (field_lumens, field_warranty_years, field_cri, field_variation_media, field_mounting_type)
- OBS: fältnamnen skiljer sig från SKILL.md — rätt namn är `field_lumens` (inte `field_lumen`) och `field_warranty_years` (inte `field_warranty`)

| Produkttyp | Variation type | Status |
|---|---|---|
| `linear_led` | `linear_led_variation` | ✅ |
| `highbay` | `highbay_variation` | ✅ |
| `floodlight` | `floodlight_variation` | ✅ |
| `high_mast` | `high_mast_variation` | ✅ |
| `street_area` | `street_area_variation` | ✅ |
| `ex_hazardous` | `ex_hazardous_variation` | ✅ |
| `accessories` | `accessories_variation` | ✅ |

**Fas 3 — CSV-mallar & feeds** (nästa steg):
- En feeds-konfiguration + CSV-mall per produkttyp
- Börja med `linear_led` (enklast)

---

## Senast gjort: Session 2026-04-11

### TASK-029 Fas 2 klar ✅
- 7 Commerce produkttyper skapade via `drush php:eval` (inte admin UI)
- Fält tilldelade per typ enligt SKILL.md fältöversikt
- Lärdomar: fälten på `default` bundle heter `field_lumens`/`field_warranty_years` — inte `field_lumen`/`field_warranty`
- Alla commits pushade till GitHub

### Prod cache-problem löst ✅
- `twig_debug: 'true'` borttagen från `system.performance` i prod DB
- `system.performance` lagd i Config Split `local` complete_list

### Private-mapp fixad på prod ✅
- Skapad på `/home/tritonled/htdocs/tritonled.se/private`

### Drupal core + moduler uppdaterade ✅
- Drupal core 11.3.5 → 11.3.6, Commerce 3.3.3 → 3.3.4, Klaro 3.0.8 → 3.0.9

### TASK-018 Cart page — påbörjad 🔄
- Problem: `surge_protection` variation type använder `orderItemType: default`
- Fix: Ändra till `quote` på `admin/commerce/config/product-variation-types/surge_protection/edit`
- Fortsätt nästa session

---

## Senast gjort: Session 2026-04-01

### SSH-access etablerad ✅
- SSH port 2222, IP: `168.231.108.87`, alias `ssh tritonled`
- Dokumenterat i `/docs/skills/server-management/SKILL.md`

---

## Senast gjort: Session 2026-03-29

### TASK-019 — Klaro GDPR ✅ Klar
- Svenska texter via `getLanguageConfigOverride('sv', $name)->setData($data)->save()`
- **OBS**: `cim` importerar inte `language/sv/`-filer automatiskt

---

## Öppna tasks

| Task | Status | Beskrivning |
|------|--------|-------------|
| TASK-028 | ✅ Klar | staticSpecs i JSON-scheman + Drupal-import |
| TASK-027 | ✅ Klar | StaticSpecsBlock + PrintButtonBlock |
| TASK-025 | ✅ Klar | Konfigurator dropdown-layout |
| TASK-022 | ✅ Klar | Översättning SV/EN |
| TASK-019 | ✅ Klar | Klaro GDPR |
| TASK-017b | 🔄 In Progress | Produktseriesidor + Views |
| TASK-018 | 🔄 In Progress | Cart page layout |
| TASK-029 | 🔄 In Progress | Fas 3: CSV-mallar & feeds per produkttyp |
| TASK-013 | 🔄 In Progress | Attribut-cleanup |

---

## Media-entiteter per produkt (konfigurator)

| Product | ID | MIDs |
|---------|-----|------|
| MAX BASE | 15 | 41(TM-C), 42(TM-E), 43(TM-V), 44(TM-B), 45(TM-W), 67(TM-default) |
| MAX-PRO | 16 | 116(TMP-C), 117(TMP-E), 118(TMP-B), 119(TMP-W), 120(TMP-default) |
| MAX-E | 18 | 81(TME-E), 82(TME-V), 83(TME-B), 84(TME-W), 101(TME-default) |
| MAX-ED | 19 | 112(TMED-E), 113(TMED-V), 114(TMED-B), 115(TMED-W), 102(TMED-default) |
