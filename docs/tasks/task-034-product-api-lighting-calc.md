# Task 034: Produkt-API för ljusberäkningsapplikationer

**Created**: 2026-04-24  
**Status**: Not Started  
**Last Updated**: 2026-04-24  
**Related Tasks**: TASK-028 (static-specs-json), TASK-029 (produkttyper-fältstruktur)

---

## 1. DEFINE

### Mål
Exponera TritonLED:s produktdata som ett öppet, maskinläsbart JSON-API som 3:e parts applikationer (ljusberäkningsprogram, partnersystem, AI-agenter) kan konsumera utan autentisering — utan priser.

### Syfte
Ljusberäkningsprogram (t.ex. DIALux, Relux, eller egenutvecklade kalkylatorer hos partners) behöver tillgång till teknisk produktdata:
- Watt, lumen, lm/W
- Färgtemperatur (CCT), CRI
- Optik/strålningsvinkel
- IP/IK-klass
- Mått och vikt
- SKU och produktnamn

**Priser ska ALDRIG ingå** — detta är rent teknisk data för beräkningsändamål.

### Scope
- **Inkluderar**: Alla Category A-produkter (MAX, OPTI, SROW) + Category C (Floodlight, High Mast, Street/Area m.fl.)
- **Exkluderar**: Priser, lagerinfo, kundspecifik data, konfigurator-schema

### Acceptanskriterier
- [ ] Endpoint tillgänglig utan autentisering (publik, anonym åtkomst)
- [ ] JSON-format med förutsägbar struktur (stabil för API-konsumenter)
- [ ] Inga priser i responsen
- [ ] Filtrering på produkttyp möjlig (t.ex. `?filter[type]=floodlight`)
- [ ] Inkluderar tekniska fält: SKU, watt, lumen, CCT, CRI, optik, IP, mått
- [ ] Dokumenterad URL-struktur
- [ ] Fungerar i produktion (Hostinger VPS)

**Godkänt av Stefan**: ⏳ Väntar

---

## 2. PLAN

### Beslutsträd
**Fil**: `/docs/DRUPAL-DECISION-TREE.md`  
**Steg**: Steg 1–3 (Config → Core-moduler → Contrib)

### Tekniska alternativ

#### Alternativ A: JSON:API (Core) + JSON:API Extras (Contrib)
- **Vad**: Drupal JSON:API är aktivt i core, exponerar alla entiteter per spec
- **URL**: `/jsonapi/commerce_product/floodlight?fields[commerce_product--floodlight]=title,sku,...`
- **Fördel**: JSON:API-spec standard, välkänt för externa konsumenter
- **Fördel**: `jsonapi_extras` låter oss dölja prisfält på resursnivå
- **Nackdel**: URL-struktur är verbose, filtreringsparametrar komplexa

#### Alternativ B: Views REST Export (Core)
- **Vad**: Skapa en View med "REST export" display
- **URL**: `/api/products/lighting` eller `/api/products/lighting/{type}`
- **Fördel**: Full kontroll över exakt vilka fält exponeras — priser enkelt uteslutna
- **Fördel**: Ren, enkel URL som är stabil och lättdokumenterad
- **Fördel**: Inga extra moduler behövs
- **Nackdel**: Inte JSON:API-spec-kompatibel (men enklare för ljusberäkningsappar)

#### Alternativ C: Kombination — Views + JSON:API Extras
- Views REST Export för den "rena" ljusberäknings-endpointen
- JSON:API Extras för mer avancerade partnersystem om behövs längre fram

### Rekommendation
**Alternativ B (Views REST Export)** som primär lösning — enklast att underhålla, full kontroll utan priser, ingen extra modul behövs. Kan kompletteras med JSON:API Extras längre fram.

**Godkänt av Stefan**: ⏳ Väntar

---

## 3. IMPLEMENT

*(Fylls i när Plan är godkänd)*

### Planerade steg
1. Inventera vilka tekniska fält som finns på varje produkttyp
2. Skapa View "Product API - Lighting Data" med REST export display
3. Konfigurera fältval (exkl. priser)
4. Konfigurera permissions (anonym åtkomst)
5. Testa endpoint
6. `drush cex` + commit
7. Deploy till prod

---

## 4. VERIFY

*(Fylls i under implementation)*

---

## 5. COMPLETION

*(Fylls i när klar)*

### Nästa steg
- Dokumentera API-URL i `/docs/03-solutions/product-api.md`
- Informera Laurits/partners om endpoint
