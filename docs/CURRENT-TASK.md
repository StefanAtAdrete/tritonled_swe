# Aktuell Task

**Task**: TASK-003 (Produktsida — Struktur, Priser & UX)  
**Status**: Aktiv — ST-1 + ST-2 + ST-3 klara  
**Senast uppdaterad**: 2026-02-24

## Vad som gjordes idag (2026-02-24)

### Rollstruktur ST-3 ✅
- Skapade roller: Elektriker, Partner Silver, Partner Gold
- Satte permissions per roll
- Prisdöljning via hook_entity_field_access() i tritonled_compat
- Commerce Promotions: Quote - Partner Silver 5%, Quote - Partner Gold 10%
- Dold unit_price + total_price i order item form display
- Borttagna hårdkodade texter i commerce-product--default.html.twig:
  - "Volume Pricing Available"
  - "Free shipping on orders over $5,000"
  - Dubblettknapp "Request Quote"

## Startpunkt nästa session

ST-3 klar. Produktsidan ser korrekt ut för anonymous (inga priser).

**Nästa steg (ST-4)**:
Fältstruktur & permissions — se task-003 för detaljer.

## Kvarstående städning
- Dummyprodukter (Triton MAX, OPTI, SROW) lever kvar — raderas via VBO när riktiga produkter finns
- commerce_pricelist installerad men ej i bruk — utvärdera om den behövs eller avinstalleras
- field_permissions installerad men ej i bruk — samma utvärdering
- CSS-aggregering är AV under dev — slå PÅ igen inför produktion:
  `ddev drush config-set system.performance css.preprocess 1 -y`
