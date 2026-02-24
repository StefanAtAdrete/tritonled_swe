# Aktuell Task

**Task**: TASK-003 (Produktsida — Struktur, Priser & UX)  
**Status**: Aktiv — ST-1 + ST-2 klara  
**Senast uppdaterad**: 2026-02-24

## Vad som gjordes idag (2026-02-24)

### Master CSV-import ✅
- Lade till `field_product_sku` på Commerce-produkten (unik nyckel, 255 tecken)
- Uppdaterade Feeds-mappningar: varianter refererar nu produkter via `field_product_sku` (ej titel)
- Satte `autocreate: true` på alla attributmappningar
- Feeds-modulen hade korrupta databastabeller — avinstallerades och reinstallerades
- feeds_item-fälten måste tas bort manuellt innan Feeds kan avinstalleras
- Testimport verifierad: 2 produkter + 4 varianter importerade och korrekt kopplade
- Re-import fungerar utan fel eller varningar

## Startpunkt nästa session

Master CSV-importen fungerar end-to-end.

**Nästa steg (ST-3):**
Skapa rollstruktur i Drupal:
- Elektriker, Partner Silver, Partner Gold
- Permissions per roll
- Prisdöljning för anonymous (renderas ej)
- Commerce Price Lists per roll

## Kvarstående städning
- Dummyprodukter (Triton MAX, OPTI, SROW) lever kvar — raderas via VBO när riktiga produkter finns
- CSS-aggregering är AV under dev — slå PÅ igen inför produktion:
  `ddev drush config-set system.performance css.preprocess 1 -y`
