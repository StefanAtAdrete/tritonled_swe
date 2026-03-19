# Task 016e: Konfigurator — Bildväxling vid val

**Skapad**: 2026-03-18  
**Status**: Planned — väntar på bildfiler  
**Prioritet**: Medium — visuell UX  
**Relaterad**: TASK-015

---

## Beslut

- **Bildblock**: Separat block med samma utseende som bildkarusellen på andra produkter
- **Uppdaterad huvudbild**: En gemensam produktbild som uppdateras beroende på val
- **Steps med bildväxling**: `endcap` + `color` (fler kan läggas till vid behov)
- **Nuläge**: Bara en grå bild finns (Anodized Grey) — color-bildväxling implementeras när fler bilder finns

---

## Teknisk lösning

`imageMap` läggs till per step i `field_configurator_schema`:

```json
{
  "id": "endcap",
  "skuPart": "middle",
  "imageMap": {
    "C": "/sites/default/files/configurator/endcap-cable-gland.jpg",
    "E": "/sites/default/files/configurator/endcap-enstonet.jpg",
    "V": "/sites/default/files/configurator/endcap-wago-w1.jpg",
    "B": "/sites/default/files/configurator/endcap-wago-dali-blue.jpg",
    "W": "/sites/default/files/configurator/endcap-wago-infinity.jpg"
  },
  "defaultImage": "/sites/default/files/configurator/endcap-default.jpg",
  "options": [...]
}
```

```json
{
  "id": "color",
  "skuPart": "end",
  "imageMap": {
    "1": "/sites/default/files/configurator/color-anodized-grey.jpg",
    "2": "/sites/default/files/configurator/color-black.jpg",
    "3": "/sites/default/files/configurator/color-white.jpg"
  },
  "defaultImage": "/sites/default/files/configurator/color-anodized-grey.jpg",
  "options": [...]
}
```

**JS-konfiguratorn:**
- Renderar ett `<div class="configurator-image-display">` med `<img>` i konfiguratorn
- Vid val på steg med `imageMap` → uppdatera `img.src`
- Vid reset (inget val) → visa `defaultImage` om den finns, annars dölj bilden
- Prioritetsordning om flera steps har `imageMap`: sist ändrade vinner

**Block-integration:**
- Bildblocket är ett separat block i Layout Builder (bildkarusell-stil)
- Konfiguratorn och bildblocket kommunicerar via en custom DOM-event:
  `document.dispatchEvent(new CustomEvent('triton:configurator:image', { detail: { src, stepId } }))`
- Bildblocket lyssnar på eventet och uppdaterar sin `<img>`

---

## Förutsättningar (Stefan)

- [ ] Bilder per endcap-variant uppladdade till `/sites/default/files/configurator/`
  - `endcap-cable-gland.jpg`
  - `endcap-enstonet.jpg`
  - `endcap-wago-w1.jpg`
  - `endcap-wago-dali-blue.jpg`
  - `endcap-wago-infinity.jpg`
- [ ] `imageMap` tillagd i `field_configurator_schema` för MAX BASE (och övriga)
- [ ] Color-bilder (black, white) när de finns

## Acceptanskriterier

- [ ] Bildblocket renderas på produktsidan med samma stil som bildkarusell
- [ ] Bild uppdateras vid val av endcap
- [ ] Bild uppdateras vid val av color (när fler bilder finns)
- [ ] Default-bild visas vid sidladdning / återställning
- [ ] Fungerar generiskt för alla steps med `imageMap`

## Implementation (när bilder finns)

1. Uppdatera `field_configurator_schema` med `imageMap` per step
2. Lägg till `dispatchEvent` i `configurator.js` vid val
3. Skapa bildblock (enkel HTML-wrapper som lyssnar på eventet)
4. Placera bildblocket i Layout Builder bredvid/ovanför konfiguratorn

Kräver godkännande + bildfiler innan implementation.
