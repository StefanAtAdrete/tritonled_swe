# TASK-022 — Översättning: Svenska/Engelska

**Skapad**: 2026-03-24
**Status**: Planned
**Prioritet**: Medel

---

## Mål

Säkerställa att alla block, allt innehåll och alla produkter fungerar korrekt på både svenska och engelska. Sajten ska vara fullt flerspråkig med konsekvent UX på båda språken.

---

## DEFINE

### Acceptanskriterier
- [ ] Alla 12 produkter har svenska och engelska titlar + beskrivningar
- [ ] Konfiguratorn (dropdowns, labels, feedback-texter) visas på rätt språk
- [ ] Featured Products-blocket fungerar på /en och /sv
- [ ] Syskonprodukter-blocket (TASK-021) fungerar på båda språken
- [ ] Taxonomy-termer översatta (product_categories, product_type, producers)
- [ ] Navigation-menyer fungerar på båda språken
- [ ] URL-alias fungerar per språk (/en/product/... och /sv/produkt/...)
- [ ] Config exporterad och committad

---

## PLAN

### Del 1 — Produktinnehåll

#### Produkter (commerce_product)
- Titel, kort beskrivning, lång beskrivning
- Metafält (SEO)
- Alla 12 produkter

#### Produktvarianter (commerce_product_variation)
- Attributvärden (om de är translatable)

#### Taxonomy-termer
- `product_categories`: MAX, OPTI, SROW
- `product_type`: Base, Sensor, Emergency, Emergency Daylight, PRO
- `producers`: TritonLED

### Del 2 — Block och UI-texter

#### Konfiguratorn (tritonled_configurator)
- Fältlabels (LÄNGD, DRIVER, ANSLUTNING, CRI, FÄRGTEMPERATUR, EFFEKT, OPTIK, FÄRG)
- Knapptext ("Lägg i offert" / "Add to quote")
- Feedback-texter ("Produkt tillagd!" / "Product added!")
- SKU-label

#### Custom blocks
- Kontakta oss-knapp
- Footer-innehåll

#### Views
- Featured Products block-titel
- Syskonprodukter-block rubrik

### Del 3 — Konfiguration

#### Interface translation (admin/config/regional/translate)
- Alla custom strings i JS och PHP

#### URL-alias per språk
- Verifiera att path_auto genererar rätt alias per språk

---

## Tekniska detaljer

### Drupal-moduler som hanterar detta
- `content_translation` — produkter, taxonomy-termer
- `locale` + `interface_translation` — UI-strängar
- `path_auto` med språkspecifika patterns

### Konfigurator-strängar (tritonled_configurator)
Strängar i JS måste hanteras via `drupalSettings` eller `Drupal.t()`:
```javascript
// Rätt sätt
Drupal.t('Add to quote')
// Eller via drupalSettings från PHP
drupalSettings.tritonConfigurator.strings.addToQuote
```

### Prioritetsordning
1. Produkttitlar + beskrivningar (mest synligt)
2. Konfigurator UI-texter
3. Taxonomy-termer
4. Block-titlar och övrigt

---

## Öppna frågor

| Fråga | Svar |
|-------|------|
| Ska URL-alias vara /sv/produkt/ eller /sv/product/? | Avgörs — rekommendation: /sv/produkt/ |
| Ska attributvärden (watt, CCT etc.) översättas? | Troligen nej — tekniska värden |
| Finns redan content_translation aktiverat? | Verifiera vid start |
| Ska konfigurator-labels komma från schema-JSON eller Drupal t()? | Avgörs vid implementation |
