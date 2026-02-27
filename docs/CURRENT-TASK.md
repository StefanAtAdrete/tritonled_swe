# Aktuell Task

**Task**: TASK-008 (Produktkatalog — IP43 varianter, Certifieringar & Feeds-automation)  
**Status**: In Progress  
**Senast uppdaterad**: 2026-02-27

## Vad som gjordes idag (2026-02-27)

### Klart ✅
- 8 IP43 CG-varianter importerade till TRITON-MAX (SKU-suffix `-IP43`)
- IP-attributvärde `43` tillagt i Drupal
- FeedsImportSubscriber i `tritonled_compat` — automatisk feeds_item-rensning per feed i chunks om 50
- Taxonomy vocabulary `certifications` med bildfält `field_certification_logo` (Media)
- 6 certifieringstermer: CE, RoHS, ENEC, B2L ready, Dimmable, Flicker Free
- `field_certifications` (entity reference) på commerce_product default

### Nästa steg ⏳
1. Lägg till `field_certifications` kolumn i CSV med `|`-separator
2. Konfigurera Feeds-mappning för certifieringar
3. Ladda upp certifieringslogotyper per term
4. Skapa separata Feeds-importers per produktserie (MAX, OPTI, SROW)
5. Testa cron-baserad import
