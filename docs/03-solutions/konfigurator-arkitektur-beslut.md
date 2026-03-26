# Konfigurator — Arkitekturbeslut och Roadmap

**Skapad**: 2026-03-25  
**Beslutsfattare**: Stefan + Claude  
**Status**: Aktivt vägledande dokument — uppdatera vid nya beslut

---

## Bakgrund

TritonLED har 12 produkter i tre serier (MAX, OPTI, SROW) med upp till 55 000+
möjliga varianter per serie när all produktdata är inlagd. Varje produkt har
en JavaScript-driven konfigurator som låter användaren välja attribut (längd,
driver, anslutning, CRI, färgtemperatur, watt, optik, färg) och genererar ett SKU.

---

## Nuvarande arkitektur (Fas 1 — Interim)

### Hur det fungerar idag
- 12 Commerce-produkter, en per modell (MAX, MAX-PRO, MAX-S, MAX-E, MAX-ED, OPTI, ...)
- Varje produkt har EN dummy-variation (`CONFIGURATOR-{product_id}`)
- Konfigurationslogiken lagras i `field_configurator_schema` (JSON) på produkten
- `configurator.js` läser schemat från `drupalSettings` och renderar dropdowns client-side
- Användaren väljer kombination → SKU byggs i JS → POST till `/triton/configurator/add-to-cart`
- Ordern sparar SKU + JSON-data i custom fält (`field_configurator_sku`, `field_configurator_data`)
- **Priser hanteras ej i Commerce** — offert-baserat flöde

### Varför denna lösning valdes
- Möjliggjorde lansering utan att vänta på 55 000+ varianter i databasen
- JSON-schemat är enkelt att underhålla och uppdatera
- Commerce-strukturen är korrekt uppsatt för framtida migration

### Begränsningar
- Skalbar ej till 55 000+ varianter (allt laddas i browser via drupalSettings)
- Modellval (MAX vs MAX-S vs MAX-E) kräver sidnavigering — inte inline i konfiguratorn
- Ingen riktig Commerce-variation per konfiguration — prislogik saknas

---

## Beslut: Syskonprodukter — Alternativ C (badges med aktiv markering)

**Datum**: 2026-03-25  
**Kontext**: Diskussion om hur användaren navigerar mellan syskonmodeller
(t.ex. från OPTI Base till OPTI-S Sensor).

### Alternativ som utvärderades

| Alt | Beskrivning | Beslut |
|-----|-------------|--------|
| A | Badges/block — navigering till ny sida | Förkastas — kontextbrott |
| B | Modellval som dropdown i konfiguratorn — schema byts dynamiskt | Framtida fas |
| C | Badges med aktiv markering — nuvarande modell highlightad | **Valt** |

### Varför Alternativ C valdes
- Enkelt att bygga nu (Views-block, inga schemaändringar)
- Tydlig UX — användaren ser var de är och kan hoppa mellan modeller
- Konfiguratorn auto-väljer första giltiga kombination vid sidladdning — kontextbrottet är minimalt
- Alternativ B kräver att alla schemas laddas på varje produktsida — onödig komplexitet i nuläget

### Varför Alternativ B är rätt på sikt
När 55 000+ varianter finns i Commerce är JSON-schemat obsolet.
Konfiguratorn ska då fråga Commerce API i realtid (server-side filtering via AJAX).
Modellvalet blir ett Commerce-attribut — inte separata produktsidor.
Alternativ B implementeras naturligt som en del av Fas 3 (se Roadmap nedan).

---

## Roadmap — Konfigurator

### Fas 1 — Nu (Interim JSON-schema) ✅
- [x] 12 produkter med JSON-schema i `field_configurator_schema`
- [x] `configurator.js` — client-side filtrering och SKU-byggande
- [x] Cart/offert-flöde via custom controller
- [x] `ConfiguratorImageBlock` — bildväxling per val
- [x] `ConfiguratorSpecsBlock` — live specs + print/PDF (TASK-024)
- [ ] Syskonprodukter-badges med aktiv markering (TASK-021, Alternativ C)

### Fas 2 — Variantdata i Commerce
**Förutsättning**: Komplett produktdata finns (55 000+ varianter)

- [ ] Feeds-import av alla varianter per produkt (12 feeds, en per produkt)
- [ ] Commerce-attribut korrekt uppsatta (watt, CCT, CRI, längd, driver, endcap, optik, färg)
- [ ] Priser inlagda per variation
- [ ] Validering: alla kombinationer korrekta, inga ogiltiga varianter

### Fas 3 — API-driven konfigurator
**Förutsättning**: Fas 2 klar

- [ ] Custom JSON API endpoint: `/api/konfigurator/{product_id}/options`
  - Input: aktuella selections
  - Output: tillgängliga nästa val (inga ogiltiga kombinationer visas)
- [ ] `configurator.js` byggs om till API-driven:
  - Varje val triggar AJAX-anrop mot endpoint
  - Dropdown-options uppdateras dynamiskt baserat på response
  - Ingen förladdat schema i drupalSettings
- [ ] Modellval (MAX/MAX-S/MAX-E) blir ett attribut — ett block med en konfigurator per serie
- [ ] Alternativ B implementeras naturligt här

### Fas 4 — SDC/React och headless (framtid)
**Förutsättning**: Fas 3 klar, headless-beslut fattat

- [ ] Konfiguratorn byggs om som SDC-komponent med React
- [ ] Kommunicerar mot samma API endpoint som Fas 3
- [ ] Kan köras fristående (headless) eller inbäddad i Drupal
- [ ] Radix + SDC-strukturen vi har idag möjliggör detta utan att byta Commerce-backend

---

## Tekniska noteringar

### Commerce är alltid sanningskällan
Oavsett om konfiguratorn är JSON-driven (Fas 1) eller API-driven (Fas 3) ska
Commerce-strukturen (produkter, varianter, attribut) vara korrekt uppsatt.
Konfiguratorn är ett UI-lager — inte en ersättning för Commerce-data.

### JS som tillfällig lösning
`configurator.js` i nuvarande form är dokumenterat som **Fas 1 — tillfällig lösning**.
Den ska inte byggas ut med mer komplex logik — istället migreras till API-driven
approach i Fas 3. Nya features på konfiguratorn ska utvärderas mot: "Finns detta
kvar i Fas 3, eller kastar vi det?"

### SDC och headless-beredskap
Radix + SDC-strukturen är rätt val. Konfiguratorn i `tritonled_configurator`-modulen
är byggd så att JS-logiken är fristående — den kan lyftas ut till en React-komponent
utan att Commerce-backend eller API behöver ändras.

---

## Relaterade dokument
- `/docs/tasks/task-021-syskonprodukter.md` — Badges implementation
- `/docs/tasks/task-024-konfigurator-specs-pdf.md` — Specs-block
- `/docs/03-solutions/commerce-ajax-solution.md` — Commerce AJAX-mönster
