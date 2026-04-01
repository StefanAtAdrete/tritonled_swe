# Task 028: Lägg till staticSpecs i JSON-scheman

**Created**: 2026-04-01  
**Status**: Not Started  
**Last Updated**: 2026-04-01  
**Related Tasks**: TASK-027

---

## 1. DEFINE

### Mål
Lägga till ett `staticSpecs`-objekt i varje produktmodells JSON-schema med tekniska data som är statiska per modell och inte valbara i konfiguratorn.

### Syfte
TASK-027 (StaticSpecsBlock) kräver att `staticSpecs` finns i schemat. Dessa fält behövs för att utskriften ska matcha SROW-databladet med fullständiga tekniska specifikationer.

### Fält (minimum)
| Fält | Exempel | Källa |
|------|---------|-------|
| `lifetime` | `72.000h/@49C` | Datablad / triton-solutions.co |
| `temperature` | `15-30°C` | Datablad |
| `material` | `6061 Anodized Alloy` | Datablad |
| `ik` | `IK08` | Datablad |
| `certifications` | `CE (EMC, LVD, RoHS), EUR1` | Datablad |

### Acceptanskriterier
- [ ] `staticSpecs` tillaggt i alla modeller i `max-configurator-schemas.json`
- [ ] `staticSpecs` tillaggt i alla modeller i `opti-configurator-schemas.json`
- [ ] `staticSpecs` tillaggt i alla modeller i `srow-configurator-schemas.json`
- [ ] Scheman importerade till Drupal via `drush php:eval`
- [ ] Värdena verifierade mot datablad/triton-solutions.co

**Godkänt av Stefan**: ⏳ Väntar

---

## 2. PLAN

### Datakälla
Excel-filen (`SKU_2026_0331.xlsx`) innehåller INTE statiska specs som lifetime/material/IK.
Dessa måste hämtas från:
1. **triton-solutions.co** — referensimplementationen (Next.js/React)
2. **Befintliga datablad** (SROW-databladet visar rätt struktur)
3. **Stefan fyller i manuellt** om källorna inte räcker

### Modeller att uppdatera (12 st)
**MAX-serien:**
- max-base, max-pro, max-s-sensor, max-e-emergency, max-ed-emergency-daylight

**OPTI-serien:**
- opti-base, opti-s-sensor, opti-e-emergency, opti-ed

**SROW-serien:**
- srow-base, srow-e-emergency, srow-ed

### Deploy av uppdaterade scheman
```bash
# Efter JSON-uppdatering — importera till Drupal
ddev drush php:eval "
\$products = \Drupal\commerce_product\Entity\Product::loadMultiple([15,16,17,18,19,20,21,22,23,24,25,26]);
foreach (\$products as \$p) {
  // uppdatera field_configurator_schema med nytt JSON
}
"
```

**Godkänt av Stefan**: ⏳ Väntar

---

## 3. IMPLEMENT

*Startar när värden för staticSpecs är bekräftade.*

### Steg
1. Hämta staticSpecs-värden från triton-solutions.co eller datablad
2. Uppdatera `max-configurator-schemas.json`
3. Uppdatera `opti-configurator-schemas.json`
4. Uppdatera `srow-configurator-schemas.json`
5. Importera till Drupal via `drush php:eval`
6. Git commit: `[TASK-028] Add staticSpecs to all product JSON schemas`

---

## 4. VERIFY

*Utförs efter implementation.*

---

## 5. COMPLETION

### Nästa steg
- När klar: påbörja TASK-027 (StaticSpecsBlock)
