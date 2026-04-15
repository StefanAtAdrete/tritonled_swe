# Aktuell Task

**Senast uppdaterad**: 2026-04-15

---

## ⏭️ Nästa session startar här: TASK-031 Site Audit — Dubblettfält

**Läs**: `docs/tasks/task-031-site-audit.md`

### Vad är gjort idag (2026-04-15)
- DDEV migrerad från MariaDB 10.11 → MySQL 8.0
- Lokal DB synkad från prod-backup
- Bilder synkade från prod via rsync
- A-records dokumenterade för tritonled.se
- TASK-030 skapad (UX-feedback Thomas)
- TASK-031 skapad och påbörjad (Site Audit)
- 031-A (systemstatus) ✅
- 031-B (fältmatris alla produkttyper) ✅

### Nästa steg: 031-D Dubblettfält
Beslut behövs om följande:

| Fält 1 | Fält 2 | Fråga |
|---|---|---|
| `field_product_categories` | `field_product_category` | Vilken behålls? |
| `field_product_type` | core `type` | Kan `field_product_type` tas bort? |
| `field_product_media` | `field_product_media_files` | Olika syften? |
| `field_warranty` (produkt) | `field_warranty_years` (variation) | Samma data? |
| `field_producers` | `field_brand` | Olika syften? |

### Kända blockerande fynd
- 5 produkttyper saknar Layout Builder: `floodlight`, `high_mast`, `street_area`, `ex_hazardous`, `accessories`
- 5 produkttyper saknar `feeds_item`: samma
- Bundle-namn avviker från TASK-029: `max`/`opti` → `led_luminaire_max_opti`

---

## Öppna tasks

| Task | Status | Beskrivning |
|------|--------|-------------|
| TASK-031 | 🔄 In Progress | Site Audit — nästa: 031-D dubbletter |
| TASK-029 | ⏸️ Pausad | Fas 3: CSV-mallar & feeds (väntar på audit) |
| TASK-018 | 🔄 In Progress | Cart page layout |
| TASK-017b | 🔄 In Progress | Produktseriesidor + Views |
| TASK-013 | 🔄 In Progress | Attribut-cleanup |
| TASK-030 | ⏳ Parkerad | UX-feedback Thomas |
