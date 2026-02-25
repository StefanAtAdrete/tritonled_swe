# Aktuell Task

**Task**: TASK-003 (Produktsida — Struktur, Priser & UX)  
**Status**: Aktiv — ST-1 + ST-2 + ST-3 + ST-4a (delvis) klara  
**Senast uppdaterad**: 2026-02-24

## Vad som gjordes idag (2026-02-24)

### ST-3: Rollstruktur ✅
- Roller: Elektriker, Partner Silver, Partner Gold
- Permissions per roll
- Prisdöljning via hook_entity_field_access() i tritonled_compat
- Commerce Promotions: Quote - Partner Silver 5%, Quote - Partner Gold 10%
- Template-fixes: borttagna hårdkodade texter och dubblettknapp

### ST-4a: Schema.org ✅ (delvis)
- Installerat metatag + schema_metatag + schema_product
- Schema.org Product konfigurerat med name, description, url (absolut), sku
- offers parkerat (pris-token hanteras separat)

## Startpunkt nästa session

**Nästa steg (ST-4b)**:
Views-baserade dataströmmar:
- products-html (produktlistning för människor)
- products-json (JSON för återförsäljare)
- products-llms (plain text/markdown → /llms.txt)
- products-csv (intern export)
- Skapa roll: api_partner

## Kvarstående städning
- Dummyprodukter (Triton MAX, OPTI, SROW) lever kvar
- commerce_pricelist installerad men ej i bruk — utvärdera
- field_permissions installerad men ej i bruk — utvärdera
- Schema.org offers: parkerat
- Google Rich Results Test: ej verifierat ännu
- CSS-aggregering AV under dev
