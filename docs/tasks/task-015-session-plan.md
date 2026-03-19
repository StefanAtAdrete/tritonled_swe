# TASK-015: Produktkonfigurator — Sessionsplan

**Skapad**: 2026-03-18  
**Uppdaterad**: 2026-03-18  
**Status**: In Progress — SESSION 4 klar, SESSION 5 nästa

---

## Kontext & förutsättningar

### Vad som är klart
- ✅ 12 Commerce-produkter med `field_configurator_schema` ifyllt
- ✅ `tritonled_configurator` modul aktiverad
- ✅ Order item type: `qoute` med `field_configurator_sku` + `field_configurator_data`
- ✅ 12 dummy-varianter (CONFIGURATOR-15..26)
- ✅ Block plugin `ConfiguratorBlock` — synlig i Layout Builder under "TritonLED"
- ✅ `configurator.js` — dropdowns + dependsOn + dependsOnAny + live SKU + cart POST
- ✅ Custom route `/triton/configurator/add-to-cart` + `ConfiguratorCartController`
- ✅ End-to-end verifierat: konfigurera → lägg i offert → stannar på sidan
- ✅ Bildkartläggning klar — alla endcap-bilder mappade per produktmodell

### Modulstruktur (aktuell)
```
tritonled_configurator/
├── tritonled_configurator.info.yml
├── tritonled_configurator.module        ← preprocess hook (schema + variationId)
├── tritonled_configurator.libraries.yml ← registrerar configurator.js
├── tritonled_configurator.routing.yml   ← /triton/configurator/add-to-cart
├── src/
│   ├── Plugin/Block/ConfiguratorBlock.php  ← block plugin
│   └── Controller/ConfiguratorCartController.php ← cart POST handler
└── js/
    └── configurator.js  ← dropdown + dependsOn + SKU + cart POST
```

### Dummy-variation-mappning
| Produkt ID | Produkt | Variation SKU | Variation ID |
|------------|---------|---------------|--------------|
| 15 | MAX BASE | CONFIGURATOR-15 | 20841 |
| 16 | MAX-PRO | CONFIGURATOR-16 | 20842 |
| 17 | MAX-S | CONFIGURATOR-17 | 20843 |
| 18 | MAX-E | CONFIGURATOR-18 | 20844 |
| 19 | MAX-ED | CONFIGURATOR-19 | 20845 |
| 20 | OPTI BASE | CONFIGURATOR-20 | 20846 |
| 21 | OPTI-S | CONFIGURATOR-21 | 20847 |
| 22 | OPTI-E | CONFIGURATOR-22 | 20848 |
| 23 | OPTI-ED | CONFIGURATOR-23 | 20849 |
| 24 | SROW BASE | CONFIGURATOR-24 | 20850 |
| 25 | SROW-E | CONFIGURATOR-25 | 20851 |
| 26 | SROW-ED | CONFIGURATOR-26 | 20852 |

---

## Sessionsöversikt

| Session | Fokus | Status |
|---------|-------|--------|
| SESSION 1 | Backend: modul + order item type + fält | ✅ Klar |
| SESSION 2 | Backend: dummy-varianter + Cart API | ✅ Klar |
| SESSION 3 | JS: dropdowns + dependsOn + SKU-display | ✅ Klar |
| SESSION 4 | JS: Cart API POST + feedback | ✅ Klar |
| SESSION 5 | Styling + imageMap + bildväxling + antal + autoselect | 🔄 Nästa |
| SESSION 6 | Produktseriesida (View) + live specs-display | ⏳ Planerad |

---

## SESSION 1 ✅ Klar — 2026-03-18
- Skapade `tritonled_configurator` modul
- Skapade fält på `qoute`: `field_configurator_sku` + `field_configurator_data`
- **Commit:** `[TASK-015-01]`

## SESSION 2 ✅ Klar — 2026-03-18
- 12 dummy-varianter skapade
- Cart API verifierat med `qoute`
- Beslut: `drupalSettings` för schema-exponering

## SESSION 3 ✅ Klar — 2026-03-18
- `libraries.yml` + preprocess hook + `ConfiguratorBlock` + `configurator.js`
- Dropdowns, dependsOn, dependsOnAny, live SKU verifierat i webbläsaren
- Hinder: Basic block escapade HTML → löst med block plugin

## SESSION 4 ✅ Klar — 2026-03-18

### Vad byggdes
- ✅ `tritonled_configurator.routing.yml` — route `/triton/configurator/add-to-cart`
- ✅ `ConfiguratorCartController.php`:
  - Tar emot JSON POST: `variationId`, `sku`, `selections`
  - Laddar dummy-variation
  - Skapar order item manuellt (undviker Commerce redirect)
  - Sätter `field_configurator_sku` + `field_configurator_data`
  - Returnerar JSON `{success, sku, order_item_id, cart_id}`
- ✅ `configurator.js` uppdaterad:
  - Hämtar CSRF-token från `/session/token`
  - POSTar till `/triton/configurator/add-to-cart`
  - Visar success/error-feedback utan sidladdning
  - "Lägg i offert"-knapp aktiveras när alla steg valts
- ✅ Preprocess hook uppdaterad: skickar med `variationId` i `drupalSettings`

### Hinder under SESSION 4
- `/cart/add` finns inte — `commerce_cart_api` modul saknas → custom controller istället
- Language prefix orsakade 404 → `/session/token` använder baseUrl utan prefix
- `$entityTypeManager` typed property konflikt med ControllerBase → tog bort property-deklaration, använder `$this->entityTypeManager()` metod istället
- Commerce redirect till `/cart` efter addEntity → skapar order item manuellt istället

### Verifierat
- ✅ SKU `M-A0C8-J19N1` giltig kombination (alla 8 steg validerade)
- ✅ "Lägg i offert" lägger produkt i cart och stannar på produktsidan
- ✅ Feedback-meddelande visas med SKU

---

## SESSION 5 — Nästa

**Mål:** Komplett UX — styling, bildväxling, antal, autoselect

### Sub-tasks
- TASK-016d: Antal-fält (`<input type="number">` + `setQuantity()` i controller)
- TASK-016c: Auto-select första giltiga kombination vid sidladdning
- TASK-016e: `imageMap` i schemat + JS bildväxling vid endcap-val
  - Bildkartläggning klar: se `/docs/tasks/task-016e-bildkartlaggning.md`
  - MAX BASE: TM-CableGland, TM-Ensto, TM-Wago W1/W2/W3
  - DOM-event: `triton:configurator:image` för kommunikation med bildblocket
- TASK-015-22: Bootstrap-styling (3-kolumns grid, labels, SKU-bar, knapp)
- TASK-015-23: Dölj Commerce's egna attribut-dropdowns
- TASK-015-24: Responsiv layout
- TASK-015-25: Placera blocket på alla 12 produktsidor
- TASK-015-26: Test OPTI BASE (produkt 20) + SROW BASE (produkt 24)

### Designreferens
- `triton-solutions.co/sw/catalogue/industrial/linear/config/max-s`
- Layout: produktbild vänster, konfigurator höger, SKU + knapp under
- En produktbild per modell + bildväxling per endcap-val

---

## SESSION 6 — Planerad

**Mål:** Produktseriesida + live specs-display

### Bakgrund
Stefan föreslog att slå ihop Tritonleds steg 2+3 — konfiguratorn visar specs live utan extra sidladdning. Bättre UX än referensimplementationen.

### Sub-tasks
- TASK-017b-01: View för produktserie (t.ex. alla MAX-produkter som kort)
- TASK-017b-02: Live specs-tabell i konfiguratorn — uppdateras vid val
- TASK-017b-03: URL-parametrar för delbar konfiguration (nice-to-have)

---

## Öppna frågor

| Fråga | Svar | Session |
|-------|------|---------|
| Schema-exponering? | `drupalSettings` via preprocess | SESSION 2 |
| Var renderas konfiguratorn? | Block plugin i Layout Builder | SESSION 3 |
| Variation ID i JS? | ✅ Via `drupalSettings.tritonConfigurator.variationId` | SESSION 4 |
| Commerce redirect? | ✅ Manuell order item skapning | SESSION 4 |
| imageMap i schemat? | ✅ Bildkartläggning klar, implementation SESSION 5 | SESSION 5 |
| Commerce dropdowns döljas? | ⏳ SESSION 5 | SESSION 5 |
| Specs-display live? | ⏳ SESSION 6 | SESSION 6 |
| Produktseriesida (View)? | ⏳ SESSION 6 | SESSION 6 |
