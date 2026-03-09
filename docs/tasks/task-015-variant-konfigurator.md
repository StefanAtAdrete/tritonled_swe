# TASK-015: Produktvariant-konfigurator / Galleri AJAX

**Status**: Ready to implement  
**Skapad**: 2026-03-07  
**Uppdaterad**: 2026-03-08 — Inriktning ändrad efter root cause-analys  
**Prioritet**: Hög — löser kritiskt UX-problem med galleri som inte uppdateras

---

## Bakgrund / Problem

Commerce's `AddToCartForm` matchar varianter hierarkiskt. För produkter med oberoende attribut
(SROW: Length × Watt × CCT × Connection) innebär det att:

- Väljer användaren Connection=EN och byter sedan Length → Connection återställs till default (CG)
- **Galleriet visar fel bild** (visar CG-bilder trots att EN var valt)
- Galleriet uppdateras överhuvudtaget INTE vid variantbyte

---

## Root Cause (analyserad 2026-03-08)

Galleriet renderas i **default** view mode på `commerce_product_variation`.
`commerce_variation_blocks` EventSubscribern har `default` i sin `$skip`-lista →
galleriet får aldrig en `ReplaceCommand` → uppdateras inte.

Spec-blocken (electrical, mechanical, certifications) är egna view modes →
de plockas upp av AJAX-loopen → de fungerar korrekt.

**Se**: `/docs/03-solutions/variation-gallery-ajax.md`

---

## Lösning (beslutad 2026-03-08)

Skapa `gallery` view mode på `commerce_product_variation` med `field_variation_media`.
EventSubscribern plockar upp den automatiskt — **ingen kodändring behövs**.

### Varför INTE custom JSON:API-konfigurator (ursprungsplan)?

Den ursprungliga planen var att bygga en SDC-konfigurator baserad på JSON:API som kringgår
Commerce's attributformulär. Detta är rätt väg på sikt mot headless/hybrid, men:

1. Det löser inte bildproblemet — det kringgår det
2. Är oproportionerligt komplext för nuläget
3. View mode-lösningen är ren config, ingen kod, följer arkitekturen

JSON:API-konfiguratorn kan återupptas när sajten är i produktion och headless-strategin behöver realiseras.

---

## Implementation

### Steg 1: Skapa view mode `gallery`
```
Admin → Structure → Display modes → View modes
→ Commerce product variation → Add new
→ Name: Gallery / Machine name: gallery
→ Spara
```

### Steg 2: Konfigurera view mode
```
Admin → Commerce → Configuration → Product variation types → Default → Manage display
→ Tab: Gallery
→ field_variation_media: synlig, formatter Splide Media
  - optionset: product_main
  - optionset_nav: product_nav
→ Alla andra fält: dolda
→ Spara
```

### Steg 3: Placera i Layout Builder
```
Produktsidan → Layout Builder
→ Ta bort nuvarande galleri-block (renderar via default view mode)
→ Lägg till: pseudo-fält "Gallery" (variation_block__gallery)
→ Samma position som tidigare
→ Spara
```

### Steg 4: Test
- [ ] Byt Koppling (CG → EN) → rätt bilder visas
- [ ] Byt Längd → bilder uppdateras
- [ ] Byt Watt → spec-blocken uppdateras som tidigare
- [ ] Mobiltest
- [ ] Verifiera att Splide-navigering (thumbnails) fungerar efter AJAX

---

## Acceptanskriterier

- [ ] Galleri uppdateras vid variantbyte
- [ ] Rätt Connection-bilder visas (CG/EN/DALI)
- [ ] Spec-blocken fortsätter fungera som tidigare
- [ ] Splide thumbnails fungerar efter AJAX-uppdatering
- [ ] Fungerar på OPTI, MAX och SROW

---

## Framtida arbete (parkerat)

**JSON:API Variant-konfigurator (SDC/hybrid)**

För att lösa det underliggande problemet med Commerce's hierarkiska attributmatchning
(Connection återställs vid byte av Längd) behövs en custom konfigurator på sikt.

Arkitekturen är utredd:
- Resource type: `commerce_product_variation--default` ✅
- Attributdata tillgänglig via JSON:API ✅
- SDC-komponent i `components/product-configurator/` ✅
- Ersätter Commerce's attribut-widgets, behåller cart-flödet

Wizard-flöde (stegvis): Modell → Koppling → Längd → Watt → Driver
Koppling sätts tidigt → rätt bilder visas direkt → resten av valen filtrerar utan bildproblem.

Återtas när: Sajten är i produktion + headless-strategi behöver realiseras.

---

## Anteckningar

- `commerce_variation_blocks` $skip = ['default', 'cart', 'card', 'summary'] — ändra aldrig detta utan genomtänkt beslut
- Splide efter AJAX: kan kräva att Splide re-initieras på det nya galleriet (bevaka i test)
- Images är kopplade per Connection-typ — detta är korrekt och intentionellt
