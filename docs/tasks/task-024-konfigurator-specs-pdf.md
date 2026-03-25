# Task 024: Konfigurator Specs-block + Print/PDF

**Created**: 2026-03-25
**Status**: ✅ Completed
**Last Updated**: 2026-03-25
**Related Tasks**: TASK-015 (Konfigurator), TASK-016e (Bildväxling)

---

## 1. DEFINE

### Mål
Ett separat Drupal-block (`ConfiguratorSpecsBlock`) som visar tekniska specifikationer
för den aktuella konfigurationen, uppdateras live via JS vid val i konfiguratorn,
och kan skrivas ut som en snygg PDF med logotyp och kontaktuppgifter.

### Syfte
Säljare ska kunna skicka produktspecifikationer till kunder. Kunder ska kunna
skriva ut/spara en PDF direkt från produktsidan. PDF:en ska se professionell ut
med TritonLED-identitet.

### Acceptanskriterier
- [x] `ConfiguratorSpecsBlock` är registrerat och synligt i Layout Builder
- [x] Blocket placeras under konfiguratorn på produktsidan via Layout Builder
- [x] Specs uppdateras live (utan sidladdning) när val görs i konfiguratorn
- [x] Alla valda parametrar visas: Längd, Driver, Anslutning, CRI, CCT, Watt, Lumen, Efficacy, Optik, Färg
- [x] "Skriv ut / Spara som PDF"-knapp finns i blocket
- [x] Print-layout visar: TritonLED-logotyp, kontaktuppgifter, produktnamn, SKU, produktbild, specifikationstabell, datum
- [x] Vid utskrift döljs navigering, konfigurator och övrig sida — bara specs-blocket syns
- [x] Fungerar i Chrome

**Godkänt av Stefan**: ✅ Godkänd

---

## 2. PLAN

### Beslutsträd
**Fil**: `/docs/DRUPAL-DECISION-TREE.md`
**Steg**: Custom Code (tillägg till befintlig custom modul)

### Vald lösning
1. `ConfiguratorSpecsBlock.php` — nytt block-plugin i `tritonled_configurator`
2. `updateSpecs()` + `syncPrintImage()` i `configurator.js`
3. `configurator-print.css` med `@media print` via `visibility: hidden/visible`

### Motivering
Data finns redan i `drupalSettings.tritonConfigurator.schema` — ingen ny API-endpoint.
`window.print()` med print CSS ger professionellt resultat utan extra infrastruktur.

**Godkänt av Stefan**: ✅ Godkänd

---

## 3. IMPLEMENT

### Steg

#### Steg 1: ConfiguratorSpecsBlock.php ✅
- `Markup::create()` krävs — `#markup` strippar `<button>` via Drupal XSS-filter
- Bild-platshållare `<img id="specs-print-img">` inuti blocket för print-synk

#### Steg 2: configurator.js ✅
- `updateSpecs()` — fyller `data-spec="*"` element live
- `syncPrintImage()` — kopierar konfigurator-bildens src till print-platshållaren
- `parseWattLabel()` — parsar "22W 3771lm 400mA 171lm/W" till separata rader
- Print-knapp: `onclick` strippad av Drupal — löstes med JS eventlistener

#### Steg 3: configurator-print.css ✅
- `body * { visibility: hidden }` + `#configurator-specs, #configurator-specs * { visibility: visible }`
- `position: fixed; top: 0` — specs hamnar längst upp på sidan
- Bild floatar till höger om tabellen
- `@page { margin: 0; size: A4 portrait }`

### Lärdomar
- `#markup` i Drupal strippar `<button>`, `<input>` etc — använd alltid `Markup::create()`
- `onclick` i HTML strippad av Drupal XSS — lägg alltid eventlisteners via JS
- `body > *` i print CSS matchar bara första DOM-nivån — `body *` krävs för djupa element
- `visibility: hidden/visible` fungerar bättre än `display: none/block` för print av nästlade element

---

## 4. VERIFY

### Testresultat
- [x] Blocket syns i Layout Builder block-lista ✅
- [x] Specs uppdateras live vid val ✅
- [x] Watt-parsing fungerar (OPTI testad) ✅
- [x] Print-knappen öppnar utskriftsdialog ✅
- [x] Print-layout: logotyp, kontakt, bild, tabell, footer ✅
- [x] Övrig sida döljs vid utskrift ✅
- [x] Testad i Chrome ✅

---

## 5. COMPLETION

### Status: ✅ Completed — 2026-03-25

### Nästa steg
- Placera blocket på återstående produktsidor (MAX, SROW) via Layout Builder
- TASK-021: Syskonprodukter-block på produktsidan
- TASK-023: Konfigurator mobiloptimering
