# Aktuell Task

**Task**: TASK-008 (Produktkatalog — IP43 varianter, Certifieringar & Feeds-automation)
**Status**: In Progress
**Senast uppdaterad**: 2026-02-28

## Vad som gjordes (2026-02-27 - 2026-02-28)

### Klart
- 8 IP43 CG-varianter importerade till TRITON-MAX (SKU-suffix `-IP43`)
- IP-attributvärde `43` tillagt i Drupal
- FeedsImportSubscriber i `tritonled_compat` — automatisk feeds_item-rensning per feed i chunks om 50
- Feeds Tamper konfigurerad med Explode-plugin for multi-value CSV-import
- CLEANUP: field_certifications borttaget (redundant — befintliga boolean-falt racker)
- CLEANUP: taxonomy vocabulary certifications borttaget
- CSV uppdaterad — certifications-kolumn borttagen

### Arkitektursbeslut: Certifieringar
Certifieringar hanteras som separata boolean-falt pa variant-niva, INTE taxonomy:
- field_rohs, field_ce_marking, field_enec, field_dimmable, field_ik_rating
- Taxonomy motiveras endast om logotyper, hierarki eller Views-filtrering behovs
- Se: docs/03-solutions/feeds-csv-import.md

### Lardomar
- Gor alltid research pa befintliga falt INNAN nya skapas
- Feeds CSV-kolumnnamn far inte kollidera med Drupal field-namn (Feeds lagger till _new-suffix)
- Se: docs/03-solutions/feeds-csv-import.md

## Nasta steg

1. Skapa separata Feeds-importers per produktserie (MAX, OPTI, SROW)
2. Testa cron-baserad import
3. Produktsida — visa variationsfalt (lumens, efficacy, certifieringar etc.)
