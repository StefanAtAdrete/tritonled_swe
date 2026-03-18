# Task 015: Produktkonfigurator

**Created**: 2026-03-18  
**Status**: In Progress  
**Related Tasks**: TASK-020

---

## 1. DEFINE

### Mål
Bygga en frontend-konfigurator för produktsidor som läser `field_configurator_schema` från Commerce-produkten, låter användaren välja attribut steg för steg med dependsOn-logik, genererar SKU live och lägger produkten i cart.

### Syfte
Commerce's inbyggda attribut-dropdowns klarar inte dependsOn-logiken (villkorliga val), hierarkisk attributordning eller live SKU-preview. Konfiguratorn ersätter Commerce's form på produktsidan.

### Acceptanskriterier
- [ ] Konfiguratorn renderar dropdowns från `field_configurator_schema`
- [ ] dependsOn-filtrering fungerar (t.ex. W1 visas bara om driver = On/Off)
- [ ] dependsOnAny-filtrering fungerar (watt filtrerat på cri + length)
- [ ] SKU genereras live och visas för användaren
- [ ] "Lägg i offertförfrågan"-knapp fungerar (lägger SKU som line item i cart)
- [ ] Fungerar för MAX BASE som proof-of-concept
- [ ] Generisk — fungerar för alla 12 modeller utan kodändringar

**Godkänt av Stefan**: ✅ (TASK-020 förutsätter denna approach)

---

## 2. PLAN

### Arkitekturbeslut (från TASK-020 2026-03-18)

**Ingen Commerce-variant används.** SKU genereras dynamiskt i frontend.

```
Commerce Product (en per modell)
  └── field_configurator_schema (JSON)
        └── steps[] med options[], dependsOn, skuPart
  
Konfigurator JS:
  1. Hämtar schema från JSON:API
  2. Renderar dropdowns steg för steg
  3. Filtrerar options via dependsOn/dependsOnAny
  4. Bygger SKU live: skuPrefix + middle-delar + end-delar
  5. Lägger SKU som custom line item i cart
```

### SKU-bygglogik

Från schemat: varje step har `skuPart: "middle"` eller `skuPart: "end"`.

```
SKU = skuPrefix + middle-koder (i steg-ordning) + "-" + end-koder (i steg-ordning)

Exempel MAX BASE:
  skuPrefix = "M-"
  middle: length=A, driver=0, endcap=C, cri=8 → "A0C8"
  end: kelvin=-J (strippa bindestreck → "J"), watt=19, optic=N, color=1 → "J19N1"
  Resultat: M-A0C8-J19N1
```

OBS: kelvin-koden har prefix "-" i schemat (t.ex. "-J") — strippa det vid SKU-bygge.

### Teknisk stack
- **Drupal-modul**: `tritonled_configurator` (custom)
- **JS**: Vanilla JS (ingen React/Vue — håll det enkelt)
- **Data**: JSON:API för att hämta schema från produkten
- **Cart**: Custom order item type `configurator_item` med `field_sku` (text)
- **Placering**: Ersätter `AddToCartForm` på produktsidan via EventSubscriber (befintligt mönster)

### ✅ BESLUT: Custom order item type `configurator_item`

Konfigurator-produkter får eget `configurator_item` order item bundle med `field_sku` (text) istället för variation-referens.

**Varför:**
- Standard Commerce-produkter använder `default` order item — opåverkade
- Båda order item-typer fungerar i samma order/checkout/quote-flow
- Håller alla vägar öppna för framtida priser, lager, partner-rabatter
- Inget hack — ren Commerce-arkitektur

**Alternativ avfärdade:**
- Placeholder variant: hack som ger problem när standard-produkter finns i samma order
- Webform: kopplar bort från Commerce — förlorar order history och partner-priser

### Alternativ övervägda
1. **Alpine.js** — onödig dependency för detta use case
2. **React** — överdrivet, svårt att integrera med Drupal theming
3. **Commerce AJAX** — avfärdat, nollställer val vid attributbyte

**Godkänt av Stefan**: ✅

---

## 3. IMPLEMENT

### Sub-tasks

| Sub-task | Beskrivning | Status |
|----------|-------------|--------|
| TASK-015-01 | Skapa `tritonled_configurator` modul (skeleton) | ⬜ Not Started |
| TASK-015-02 | JSON:API endpoint — hämta schema för produkt | ⬜ Not Started |
| TASK-015-03 | JS — rendera dropdowns från schema | ⬜ Not Started |
| TASK-015-04 | JS — dependsOn/dependsOnAny filtrering | ⬜ Not Started |
| TASK-015-05 | JS — SKU-bygge live | ⬜ Not Started |
| TASK-015-06 | Cart-integration — lägg SKU som line item | ⬜ Not Started |
| TASK-015-07 | Ersätt Commerce AddToCartForm på produktsidan | ⬜ Not Started |
| TASK-015-08 | Test med MAX BASE | ⬜ Not Started |

---

## 4. VERIFY

- [ ] MAX BASE: välj alla 8 steg → korrekt SKU genereras
- [ ] Beroenden fungerar: välj driver=DALI2 → W1 försvinner
- [ ] Lägg i cart → SKU syns som line item
- [ ] Fungerar för MAX-S (9 steg inkl sensor)
- [ ] Fungerar för SROW BASE (chips/ip_class istället för cri/sensor)

---

## 5. FILER ATT REFERERA

- `/docs/product-schemas/max-configurator-schemas.json`
- `/docs/product-schemas/opti-configurator-schemas.json`
- `/docs/product-schemas/srow-configurator-schemas.json`
- `/docs/tasks/task-015-konfigurator-arkitektur.md`
- `/web/modules/custom/tritonled_compat/` (befintlig modul — studera mönstret)
