# TASK-015 SESSION 5b — Responsive Image Bildväxling i Konfiguratorn

**Skapad**: 2026-03-20
**Status**: ✅ KLAR (2026-03-23)
**Föregående**: SESSION 5 (Bootstrap-styling, auto-select, antal, cart POST) ✅

---

## Bakgrund & Beslut

### Problemet vi löste
Konfiguratorn behöver visa rätt produktbild när användaren väljer attribut som
påverkar produktens yttre utseende (endcap/anslutning, color/färg).

### Vad vi INTE gör
- ❌ 30-40 000 Commerce-varianter med bildfält — för många, för mycket overhead
- ❌ UUID:er i JSON-schemat — miljöberoende, krångligt att underhålla
- ❌ Råfilsökvägar i imageMap — ingen bildstil, dålig performance
- ❌ Enkel img.src-byte — ingen responsive image, dålig UX vid oladdat element

### Vad vi GÖR
- ✅ Dedikerat mediafält `field_configurator_media` på produkten
- ✅ Namnkonvention på media-entiteter: `{imagePrefix}-{kod1}{kod2}`
- ✅ Responsive Image Style `Product Responsive` (performance-kritiskt)
- ✅ PHP renderar `<picture>`-HTML server-side, JS byter element i DOM
- ✅ Fungerar bara för konfigurator-produkter — stör inte övriga Commerce-produkter

---

## Arkitektur

### Namnkonvention för media-entiteter

Format: `{imagePrefix}-{visuellkod1}{visuellkod2}`

Exempel för MAX BASE (imagePrefix = "TM"):
```
TM-C1    → endcap=C (Cable Gland), color=1 (Anodized Grey)
TM-E1    → endcap=E (Ensto), color=1
TM-V1    → endcap=V (Wago W1), color=1
TM-B1    → endcap=B (Wago W2), color=1
TM-W1    → endcap=W (Wago W3), color=1
TM-C2    → endcap=C, color=2 (Anodized Black)
TM-default → fallback om ingen specifik bild finns
```

Ordningen på visuella steg i schemat bestämmer ordningen i namnet.
`endcap` kommer före `color` i schemat → alltid `{endcap}{color}`, aldrig tvärtom.

### JSON-schema — tillägg

Två nya fält på schema-rotnivå och på steget:

**Schema-rot:**
```json
{
  "skuPrefix": "M-",
  "imagePrefix": "TM"
}
```

**På steg med visuell påverkan:**
```json
{
  "id": "endcap",
  "visual": true,
  "skuPart": "middle",
  ...
}
{
  "id": "color",
  "visual": true,
  "skuPart": "end",
  ...
}
```

Steg utan `"visual": true` (length, driver, cri, kelvin, watt, optic) påverkar
inte bildvalet.

### Mediafält på produkten

- **Fältnamn**: `field_configurator_media`
- **Typ**: Entity reference (Media → Image)
- **Cardinality**: Obegränsat
- **Produkttyper**: `led_luminaire_max_opti` (skapad ✅), `led_luminaire_srow` (ej skapad än)
- **Label i GUI**: "Konfigurator-bilder"
- **Beskrivning i GUI**: "Namnkonvention: {imagePrefix}-{endcap}{color} t.ex. TM-C1. Default: {imagePrefix}-default"

Bilderna ligger på produkten — inte på varianten. Det är avsiktligt eftersom
konfiguratorn arbetar på produkt-nivå, inte variant-nivå.

---

## Implementation

### Del 1 — JSON-schema uppdateras

För varje konfigurator-produkt (15–26):
- Lägg till `"imagePrefix"` på schema-rotnivå
- Lägg till `"visual": true` på endcap och color-steg
- Ta bort gamla `imageMap`-data från steg (ersätts av namnkonvention)

### Del 2 — preprocess-hook (`tritonled_configurator.module`)

Ny hjälpfunktion `_tritonled_configurator_build_image_pictures()`:

```
1. Hämta alla media från field_configurator_media på produkten
2. Bygg en karta: medienamn → media-entitet
3. Identifiera visuella steg från schemat (visual: true)
4. För varje möjlig kombination av visuella val:
   a. Bygg söksträng: {imagePrefix}-{kod1}{kod2}
   b. Slå upp i mediakartan
   c. Om träff: rendera <picture> via responsive_image med 'product_responsive'
   d. Spara som { conditions: {endcap: "C", color: "1"}, html: "<picture>..." }
5. Lägg till default: sök efter {imagePrefix}-default
6. Returnera array av { conditions, html }
```

Resultat sparas i:
```json
drupalSettings.tritonConfigurator.imagePictures = [
  {"conditions": {"endcap": "C", "color": "1"}, "html": "<picture>...</picture>"},
  {"conditions": {"endcap": "E", "color": "1"}, "html": "<picture>...</picture>"},
  {"conditions": {}, "html": "<picture>...default...</picture>"}
]
```

### Del 3 — JS bildväxling (`configurator.js`)

Ny funktion `updateImage()` — ersätter `maybeUpdateImage()`:

```
1. Samla aktiva val för steg med visual: true
   → {endcap: "C", color: "1"}

2. Gå igenom imagePictures, poängsätt varje post:
   → +1 poäng per matchande condition
   → conditions: {} = 0 poäng (default, alltid sist)

3. Välj post med högst poäng

4. Hitta befintligt <picture>-element i DOM
   → selector: .field--name-field-configurator-media picture
      eller .field--name-field-product-media picture (om samma block)

5. Ersätt elementet: pictureEl.outerHTML = match.html

6. Kör vid: change-event på visuella steg + autoSelectFirst()
```

### Del 4 — Layout Builder

**Arkitekturbeslut (SESSION 5b, 2026-03-20):**
- `ConfiguratorImageBlock` = separat block plugin (`tritonled_configurator_image_block`)
- `ConfiguratorBlock` = bara konfigurator-UI, ingen bild
- Båda placeras fritt i Layout Builder — bilden vänster, konfiguratorn höger
- JS hittar `.triton-configurator-image img` oavsett var blocket placeras

**View mode `configurator_image` på media.image:**
- Endast `Image` (field_media_image) i Content-region
- `Authored by`, `Authored on`, `Thumbnail`, `Name`, `Language` → Disabled

---

## Saknade bilder (från bildkartläggning)

Se `/docs/tasks/task-016e-bildkartlaggning.md` för fullständig lista.

Bilder som behöver laddas upp och namnges:
- `TM-default` — MAX BASE default (Anodized Grey, Cable Gland)
- `TM-C1`, `TM-E1`, `TM-V1`, `TM-B1`, `TM-W1` — MAX BASE per endcap
- Motsvarande för TMS-, TME-, TO-, TOS-, TOE-, TS- etc.

---

## Sub-tasks

| Task | Beskrivning | Status |
|------|-------------|--------|
| 5b-01 | Uppdatera JSON-schema: imagePrefix + visual-flagga | ⏳ |
| 5b-02 | Ladda upp och namnge media i field_configurator_media | ⏳ |
| 5b-03 | preprocess-hook: build imagePictures | ✅ |
| 5b-04 | configurator.js: updateImage() med srcset-byte | ✅ |
| 5b-05 | ConfiguratorImageBlock — separat placerbart block i Layout Builder | ✅ |
| 5b-06 | Ta bort gamla imageMap från steg i schema | ✅ |
| 5b-07 | Lägg till field_configurator_media på led_luminaire_srow | ✅ (fanns redan) |
| 5b-08 | Test MAX BASE end-to-end | ✅ |
| 5b-09 | imagePrefix + visual-flagga på OPTI/SROW-produkter (16-26) | ✅ (redan i DB) |
| 5b-10 | Koppla TM*/TO*/TS* media till respektive produkt | ✅ (default fallback) |

---

## Öppna frågor

| Fråga | Svar |
|-------|------|
| Bildstil för bildväxling | Responsive Image Style: `product_responsive` |
| En eller flera bilder synliga? | En i taget — växlas vid val |
| Fallback-bild | `{imagePrefix}-default` (Anodized Grey) |
| Mediafält på SROW? | Skapas i sub-task 5b-07 |
| View mode för field_configurator_media? | Avgörs vid implementation |
