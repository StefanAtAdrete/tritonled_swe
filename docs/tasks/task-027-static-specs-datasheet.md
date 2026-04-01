# Task 027: Statiska produktspecifikationer i JSON-schema + utskrift

**Created**: 2026-04-01  
**Status**: Not Started  
**Last Updated**: 2026-04-01  
**Related Tasks**: TASK-024, TASK-026

---

## 1. DEFINE

### Mål
Lägga till ett `staticSpecs`-objekt i varje produkts JSON-schema med tekniska data som inte är valbara i konfiguratorn men som ska synas i utskriften (datasheets). Dessa fylls från Stefans Excel-exportfil med 55 000+ rader.

### Syfte
Utskriften ska motsvara SROW-databladet med samtliga tekniska fält — inklusive sådana som beror på konfiguration (t.ex. livslängd varierar per watt/CCT-kombination) och sådana som är statiska per modell (material, IK, certifieringar).

### Acceptanskriterier
- [ ] `staticSpecs` tillaggt i samtliga 12 produktscheman (MAX, OPTI, SROW)
- [ ] Följande fält finns som minimum: `lifetime`, `temperature`, `material`, `ik`, `certifications`
- [ ] Fälten renderas i specs-blocket vid utskrift
- [ ] `ConfiguratorSpecsBlock.php` och `updateSpecs()` i JS uppdaterade
- [ ] Testat på MAX, OPTI och SROW

**Godkänt av Stefan**: ⏳ Väntar

---

## 2. PLAN

### Strategi: Excel → JSON

Stefans Excel-fil innehåller 55 000+ rader med produktdata. Planen är:

1. **Stefan laddar upp Excel-filen** (eller en CSV-export av relevanta kolumner) i chatten
2. **Claude analyserar strukturen** och identifierar relevanta kolumner
3. **Claude genererar `staticSpecs` per modell** direkt från Excel-datan
4. **Stefan godkänner** — Claude uppdaterar JSON-schemafiler

### Fält att lägga till i `staticSpecs`

Baserat på SROW-databladet:

```json
"staticSpecs": {
  "lifetime": "72.000h/@49C",
  "temperature": "15-30°C",
  "material": "6061 Anodized Alloy",
  "ik": "IK08",
  "certifications": "CE (EMC, LVD, RoHS), EUR1"
}
```

### Fält som kan vara konfigurationsberoende
`lifetime` kan variera per watt-klass. I så fall läggs de som en lookup-tabell per `watt`-kod:

```json
"lifetimeByWatt": {
  "19N": "72.000h/@49C",
  "22N": "70.000h/@49C"
}
```

Beslut om detta tas när Excel-filen analyserats.

### Rendering
- `ConfiguratorSpecsBlock.php`: lägg till rader för static specs i `specRows()`
- `configurator.js` `updateSpecs()`: hämta `schema.staticSpecs` och sätt värden direkt
- Statiska rader visas alltid (ingen `data-spec-hidden`-logik)

**Godkänt av Stefan**: ⏳ Väntar

---

## 3. IMPLEMENT

*Startar när Excel-filen analyserats och plan godkänts.*

### Steg
1. Analysera Excel/CSV och extrahera staticSpecs per modell
2. Uppdatera `max-configurator-schemas.json`
3. Uppdatera `opti-configurator-schemas.json`
4. Uppdatera `srow-configurator-schemas.json`
5. Uppdatera `ConfiguratorSpecsBlock.php` med nya rader
6. Uppdatera `updateSpecs()` i `configurator.js`
7. Importera uppdaterade scheman till Drupal via `drush php:eval`

---

## 4. VERIFY

*Utförs efter implementation.*

---

## 5. COMPLETION

### Nästa steg
- Stefan laddar upp Excel-filen för analys
- Claude extraherar och föreslår `staticSpecs` per modell
- Stefan godkänner → implementation
