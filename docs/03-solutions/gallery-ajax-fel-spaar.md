# LÄRDOM: Galleri-uppdatering vid variantbyte — fel spår (2026-03-08)

**Status**: Dokumenterad lärdom — misslyckat försök  
**Tid förlorad**: ~3 timmar  
**Rotorsak**: Feltolkning av problem → fel lösning

---

## Vad vi försökte lösa

Galleriet (Splide med `field_variation_media`) uppdaterades inte konsekvent vid variantbyte.
Specifikt: väljer användaren EnstoNet och byter sedan Längd → galleriet visar CG-bilden.

## Vad vi trodde var problemet

Att galleriet renderades via `field_block` direkt i Layout Builder och därmed inte fick
en `ReplaceCommand` från `commerce_variation_blocks` EventSubscribern.

## Vad vi gjorde (fel spår)

1. Skapade `gallery` view mode på `commerce_product_variation`
2. Bytte Layout Builder-blocket från `field_block` till `variation_block__gallery`
3. Försökte lösa Splide re-init via `variation-gallery-reinit.js` med:
   - `ajaxComplete` event (fungerade inte — Commerce använder inte Drupal's AJAX)
   - MutationObserver på parent (fungerade inte — `once()` blockerade)
   - MutationObserver på body subtree (fungerade inte — insert ersätter noden)
   - `Drupal.blazy.init()` (fungerade inte — `b-loaded` klasser blockerade)
   - Rensa `b-loaded` + `Drupal.blazy.load()` (renderade tom bild)

## Det verkliga problemet

**Commerce's hierarkiska variantmatchning** — inte galleriets rendering.

Commerce matchar varianter uppifrån i attributordningen. Attributordningen var:
1. Length (överst)
2. Watt
3. Connection (underst)

Byter användaren Length → Commerce matchar närmaste variant med den längden →
hittar CG (default/första Connection) → byter till CG-varianten →
galleriet visar korrekt CG-bilden för den valda varianten.

Galleriet gjorde rätt hela tiden. Det var Commerce som valde fel variant.

## Varför vi inte såg detta direkt

- Vi fokuserade på symptomen (fel bild) inte rotorsaken (fel variant)
- `gallery` view mode + ReplaceCommand verkade logisk eftersom andra view modes fungerade
- Vi grävde oss djupare i JS-debugging istället för att ifrågasätta grundantagandet

## Vad vi återställde

- ❌ `variation-gallery-reinit.js` — borttagen
- ❌ `variation-gallery-reinit` library i `libraries.yml` — borttagen
- ❌ Library-attach i `tritonled_compat.module` — borttagen
- ⚠️ `gallery` view mode finns kvar (ofarlig men onödig)
- ⚠️ Layout Builder bytt tillbaka till `field_block` för galleriet

## Rätt lösning

Ändra **attributordningen** i Commerce Manage form display så att Connection kommer FÖRST:
1. Connection
2. Length
3. Watt
4. Driver

Detta gör att Commerce alltid matchar rätt Connection-variant när användaren byter Längd.

**Men** — detta är fortfarande dåligt UX. Det tvingar användaren att välja Connection
först, vilket inte är intuitivt för en elektriker.

## Verklig lösning på sikt: TASK-015 JSON:API-konfiguratorn

Commerce's hierarkiska matchning är fundamentalt och kan inte konfigureras bort för
produkter med oberoende attribut. Den enda riktiga lösningen är en custom
variant-konfigurator baserad på JSON:API som matchar varianter på hela kombinationen
av valda attribut — oberoende av ordningen.

Se: `/docs/tasks/task-015-variant-konfigurator.md`

---

## Principiell lärdom

> **Debugga rotorsaken innan du väljer lösning.**
> Symptom: "fel bild visas" → Rotorsak: "fel variant väljs av Commerce"
> Vi löste symptomen utan att förstå rotorsaken.

> **Ifrågasätt grundantagandet när lösningen inte fungerar.**
> Efter tredje misslyckade JS-försöket borde vi ha frågat:
> "Är detta verkligen ett JS/rendering-problem?"

---

**Datum**: 2026-03-08  
**Författare**: Stefan + Claude
