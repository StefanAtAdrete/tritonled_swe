# Konfigurator bildväxling — Arkitektur & Lärdomar

**Löst**: 2026-03-20 (SESSION 5b)
**Status**: Implementerat för MAX BASE ✅ — övriga produkter återstår
**Task**: TASK-015 SESSION 5b

---

## Arkitektur — Översikt

Konfiguratorn visar rätt produktbild när användaren väljer attribut som
påverkar produktens utseende (t.ex. anslutningstyp/endcap).

### Tre delar samverkar

```
1. PHP (preprocess-hook)
   Bygger imagePictures-array → drupalSettings

2. Drupal Block (ConfiguratorImageBlock)
   Renderar default-bild server-side → DOM

3. JavaScript (configurator.js → maybeUpdateImage())
   Byter img src/srcset i DOM vid val-ändring
```

---

## Del 1 — Namnkonvention på media-entiteter

Format: `{imagePrefix}-{visuellkod1}{visuellkod2}`

Exempel för MAX BASE (`imagePrefix = "TM"`):
```
TM-C       → endcap = Cable Gland
TM-E       → endcap = EnstoNet
TM-V       → endcap = Wago W1
TM-B       → endcap = Wago Dali Blue
TM-W       → endcap = Wago Infinity
TM-default → fallback (visas vid sidladdning)
```

När `color` läggs till som visual-steg i framtiden:
```
TM-C1      → endcap=C, color=1 (Anodized Grey)
TM-C2      → endcap=C, color=2 (Anodized Black)
```

Ordningen i namnet följer ordningen av `visual: true`-steg i JSON-schemat.

### imagePrefix per produktserie
| Serie | imagePrefix |
|-------|-------------|
| MAX BASE | TM |
| MAX-S | TMS |
| MAX-E | TME |
| OPTI BASE | TO |
| OPTI-S | TOS |
| OPTI-E | TOE |
| SROW BASE | TS |
| SROW-E | TSE |

---

## Del 2 — JSON-schema på produkten

Två tillägg i `field_configurator_schema`:

**Schema-rot:**
```json
{
  "skuPrefix": "M-",
  "imagePrefix": "TM"
}
```

**På steg som påverkar utseendet:**
```json
{
  "id": "endcap",
  "visual": true,
  "skuPart": "middle",
  ...
}
```

Steg utan `"visual": true` (length, driver, cri, kelvin, watt, optic)
påverkar inte bildvalet.

---

## Del 3 — PHP: preprocess-hook + imagePictures

`tritonled_configurator_preprocess_commerce_product()` anropar
`_tritonled_configurator_build_image_pictures()` som:

1. Läser alla media från `field_configurator_media` på produkten
2. Bygger karta: `medianamn → renderad <img> HTML`
3. Identifierar visual-steg från schemat
4. Genererar alla kombinationer av visual-steg-koder
5. Matchar mot mediakartan via namnkonventionen
6. Renderar varje match som `<img>` via view mode `configurator_image`
7. Lägger till `{imagePrefix}-default` som fallback

Resultat i `drupalSettings.tritonConfigurator.imagePictures`:
```json
[
  {"conditions": {"endcap": "C"}, "html": "<img src='..TM-C..' srcset='...'>"},
  {"conditions": {"endcap": "E"}, "html": "<img src='..TM-E..' srcset='...'>"},
  {"conditions": {}, "html": "<img src='..TM-default..' srcset='...'>"}
]
```

---

## Del 4 — Block: ConfiguratorImageBlock

**Fil:** `web/modules/custom/tritonled_configurator/src/Plugin/Block/ConfiguratorImageBlock.php`
**Block ID:** `tritonled_configurator_image_block`
**Admin-label:** "Konfigurator-bild"
**Kategori:** TritonLED

Renderar `field_configurator_media[0]` (default-bilden) server-side med
view mode `configurator_image`, inuti wrapper `<div class="triton-configurator-image">`.

**Viktigt:** Separerat från `ConfiguratorBlock` (konfigurator-UI) så att
bilden och konfiguratorn kan placeras oberoende i Layout Builder.

```
Layout Builder layout exempel:
┌──────────────────┬──────────────────────┐
│ Konfigurator-    │ Produktkonfigurator  │
│ bild             │ (dropdowns + SKU +   │
│ (ConfiguratorI-  │  Lägg i offert)      │
│  mageBlock)      │ (ConfiguratorBlock)  │
└──────────────────┴──────────────────────┘
```

---

## Del 5 — View mode: configurator_image på media.image

**Plats:** `/admin/structure/media/manage/image/display/configurator_image`

Inställningar:
- `Image` (field_media_image) → Content, formatter: Responsive image, style: Product Responsive
- `Authored by` → Disabled
- `Authored on` → Disabled
- `Thumbnail` → Disabled
- `Name`, `Language` → Disabled

**Lärdom:** `media.html.twig` (Radix) renderar ALLT som ligger i Content-regionen,
inklusive author-länk och thumbnail. Dessa måste explicit Disabled-sättas.

---

## Del 6 — JS: maybeUpdateImage()

**Fil:** `web/modules/custom/tritonled_configurator/js/configurator.js`

```javascript
function maybeUpdateImage() {
  var pictures = config.imagePictures;
  // 1. Samla aktiva visual-selections
  // 2. Poängsätt varje imagePictures-entry (antal matchande conditions)
  // 3. Välj entry med högst poäng (default = 0 poäng, alltid fallback)
  // 4. Hitta img: document.querySelector('.triton-configurator-image img')
  // 5. Byt src + srcset på befintligt element
}
```

Körs vid:
- `autoSelectFirst()` (sidladdning)
- `change`-event på visual-steg

---

## Lärdomar

### #prefix/#suffix fungerar inte på media-render-arrayer
`$build['image']['#prefix'] = '<div class="...">'` ignoreras när
`media.html.twig` tar över renderingen.

**Lösning:** Använd `'#type' => 'container'` som wrapper runt media-renderingen:
```php
$build['image'] = [
  '#type' => 'container',
  '#attributes' => ['class' => ['triton-configurator-image']],
  'media' => $view_builder->view($media, 'configurator_image'),
];
```

### Separera bild och konfigurator i olika block
Att rendera bilden inuti `ConfiguratorBlock` låser layouten — båda hamnar
i samma kolumn. Separata block ger full flexibilitet i Layout Builder.

### View mode måste städas explicit
Drupal/Radix renderar alla fält i Content-regionen. Även om man bara vill
ha bilden måste alla övriga fält (Authored by, Thumbnail etc.) sättas till
Disabled — annars syns de.

### imagePictures renderas server-side, byts client-side
PHP renderar alla möjliga bilder som HTML-strängar och skickar dem till
`drupalSettings`. JS parser dem och byter bara `src`/`srcset` på det
redan befintliga `<img>`-elementet. Inga dolda bilder i DOM, inga CSS-tricks.
