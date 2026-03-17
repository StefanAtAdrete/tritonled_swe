# Arkitektur: Produktkonfigurator vs Commerce Attributes

**Datum:** 2026-03-17  
**Status:** Beslutsdokument — läs innan TASK-015 påbörjas

---

## Frågan

> Behövs det en speciell konfigurator eller fungerar Commerce med attributes?

**Svar: Båda — men i lager.**  
Commerce hanterar *lagring och varianter*. En custom konfigurator behövs för *UX och dependsOn-logiken*.

---

## Vad Commerce attributes klarar av

Drupal Commerce kan hantera alla attribut som *fält på varianter*:

```
attribute_length     → 0,5m / 0,6m / 1,0m ...
attribute_driver     → On/Off / DALI2 / B2LD2
attribute_endcap     → Cable Gland / EnstoNet / W1 ...
attribute_cri        → Ra80 / Ra90
attribute_chips      → 48 / 72 chips
attribute_ip_class   → IP54 / IP65
attribute_kelvin     → 3000K / 4000K / 5000K / 6500K
attribute_watt       → 19W / 22W / 29W ...
attribute_optic      → 30° / 60° / 90° / 145° ...
attribute_color      → Anodized Grey / Black / White
attribute_sensor     → MultiSensor XS / OnOff / 1H Battery ...
```

Commerce matchar sedan dropdowns → hitta rätt variant → lägger i cart.

**Det fungerar tekniskt.** Alla 12 modeller kan lagras som Commerce-varianter.

---

## Vad Commerce attributes INTE klarar av

### 1. dependsOn — villkorliga val

I schemat finns beroenden som Commerce inte hanterar nativt:

```json
// Ändstycke "W1" är bara tillgänglig om driver = "On/Off"
{ "code": "V", "label": "W1", "dependsOn": [{ "stepId": "driver", "codes": ["0"] }] }

// Watt "19W" är bara tillgänglig om CRI=Ra80 OCH Längd=0,5m
{ "code": "19", "dependsOnAny": [[{ "stepId": "cri", "codes": ["8"] }, { "stepId": "length", "codes": ["A"] }]] }
```

Commerce visar *alla* attributvärden och filtrerar bort kombinationer som saknar varianter — men det ger dålig UX: användaren ser ogiltiga val och får felmeddelanden.

### 2. Hierarkisk attributordning

Commerce väljer variant uppifrån-och-ner. Ändrar man ett icke-sista attribut nollställs allt nedanför. Det är grunden till TASK-014/015-problemet vi redan stött på.

### 3. Modell-specifika fältetiketter

- `sensor` heter "Sensortyp" för MAX-S men "Batteritid" för MAX-E
- Samma Commerce-attribut, olika semantisk betydelse
- Svårt att lösa med bara attribut-config

### 4. SKU synlighet och live-preview

Tritons konfigurator visar `SKU: M-A0C8-J19N1` live när användaren väljer. Commerce visar inte detta utan custom kod.

---

## Rekommenderad arkitektur: Lager-modellen

```
┌─────────────────────────────────────────────────────┐
│  LAGER 1: Commerce Products + Variations             │
│  → Lagrar alla varianter med SKU, pris, attribut     │
│  → CSV-import via Feeds                              │
│  → JSON:API exponerar data                           │
├─────────────────────────────────────────────────────┤
│  LAGER 2: Konfigurator-schema (JSON-fält)            │
│  → field_configurator_schema på varje produkt        │
│  → Innehåller steps, dependsOn, labels               │
│  → Hämtas från /docs/product-schemas/                │
├─────────────────────────────────────────────────────┤
│  LAGER 3: Frontend-konfigurator (TASK-015)           │
│  → Läser schema → renderar dropdowns                 │
│  → Filtrerar alternativ via dependsOn-logik i JS     │
│  → Matchar val → söker variant via JSON:API          │
│  → Visar SKU live → skickar till cart                │
└─────────────────────────────────────────────────────┘
```

Commerce används för *allt som har med data och order att göra*.  
Konfiguratorn används för *allt som har med UX och validering att göra*.  
Commerce's egna attribut-dropdowns döljs/ersätts av konfiguratorn på produktsidan.

---

## Bilder i konfiguratorn

### Hur Triton hanterar det

Tritons konfigurator (triton-solutions.co) visar **en fast produktbild per modell** — bilden ändras inte dynamiskt när man väljer optik, färg eller längd. Schemat innehåller inga `image`-fält per option. Verifierat via browser automation 2026-03-17.

| Modell | Produktbild |
|--------|-------------|
| MAX BASE/PRO | TMCG_e7f34647f3.png |
| MAX-S | TMSCG_7ef8a111b3.png |
| MAX-E/ED | TMEEN_e3c96c21ec.png |
| OPTI BASE | TOCG_88a299511d.png |
| OPTI-S | TOSCG_2c362aa1b8.png |
| OPTI-E/ED | TOEEN_3a8711354f.png |
| SROW BASE | TSCG_275b6720c1.png |
| SROW-E/ED | TSECG_59d5d4c1b3.png |

### Tre nivåer för TritonLED.se

**Nivå 1 — En bild per modell (rekommenderas att börja med)**
- Matchar Tritons befintliga site
- Befintligt `field_variation_media` räcker
- Inga ändringar i konfiguratorn behövs

**Nivå 2 — Per färg (nästa steg när bildmaterial finns)**
- Tre bilder per modell: Anodized Grey, Black, White
- Bilden byts när `color`-attributet ändras i konfiguratorn
- Konfiguratorn triggar bildbyte via JS
- Kräver att Triton Engineering levererar färgvarianter

**Nivå 3 — Per optik/kombination (framtid)**
- Bild för t.ex. 30° HighRack vs 145° Batwing
- Kräver ett `images`-objekt i schemat per option
- Inte realistiskt utan massivt bildmaterial

### Hur schemat utökas för Nivå 2/3

Om/när bildmaterial finns kan schemat utökas med ett `image`-fält per option:

```json
{
  "id": "color",
  "options": [
    { "code": "1", "label": "Anodized Grey", "image": "max-base-grey.jpg" },
    { "code": "2", "label": "Black", "image": "max-base-black.jpg" },
    { "code": "3", "label": "White", "image": "max-base-white.jpg" }
  ]
}
```

Konfiguratorn kontrollerar om `image` finns på valt alternativ och byter produktbilden via JS. Bakåtkompatibelt — saknas `image` används standardbilden.

---

## Commerce Product Type-struktur

### Två Product Types (inte en)

**`led_luminaire_max_opti`** — för MAX och OPTI  
Attribut: `length`, `driver`, `endcap`, `cri`, `sensor`, `kelvin`, `watt`, `optic`, `color`

**`led_luminaire_srow`** — för SROW  
Attribut: `length`, `driver`, `endcap`, `chips`, `ip_class`, `kelvin`, `watt`, `optic`, `color`

**Varför separata?** SROW har `chips` och `ip_class` istället för `cri` och `sensor`. Watt-beroendet är på chips+längd, inte cri+längd. Optiker är helt annorlunda. En gemensam typ hade gett onödigt många oanvända attribut.

### Commerce Products (en per modell)

| Commerce Product | SKU-prefix | Product Type |
|-----------------|-----------|--------------|
| Triton MAX BASE | M- | led_luminaire_max_opti |
| Triton MAX PRO | MP- | led_luminaire_max_opti |
| Triton MAX-S | MS- | led_luminaire_max_opti |
| Triton MAX-E | ME- | led_luminaire_max_opti |
| Triton MAX-ED | MED- | led_luminaire_max_opti |
| Triton OPTI BASE | O- | led_luminaire_max_opti |
| Triton OPTI-S | OS- | led_luminaire_max_opti |
| Triton OPTI-E | OE- | led_luminaire_max_opti |
| Triton OPTI-ED | OED- | led_luminaire_max_opti |
| Triton SROW BASE | S- | led_luminaire_srow |
| Triton SROW-E | SE- | led_luminaire_srow |
| Triton SROW-ED | SED- | led_luminaire_srow |

### Feeds-instanser (en per modell)

Varje modell får sin egen CSV och Feeds-instans:
```
feeds/max-base-products.csv + feeds/max-base-variations.csv
feeds/max-pro-variations.csv
feeds/max-s-variations.csv
...osv
```

---

## Konfigurator-scheman på produkterna

Varje Commerce Product får ett `field_configurator_schema` (Long text / JSON):

```json
{
  "productName": "Triton MAX Gen. 3 (BASE)",
  "skuPrefix": "M-",
  "steps": [
    { "id": "length", "skuPart": "middle", "options": [...] },
    { "id": "driver", "skuPart": "middle", "options": [...] },
    ...
  ]
}
```

Scheman finns redan färdiga i `/docs/product-schemas/`.

---

## TASK-015: Konfigurator-implementation

### Vad den ska göra

1. Hämta `field_configurator_schema` från aktuell produkt via JSON:API
2. Rendera dropdowns i korrekt ordning (steps-arrayen)
3. Vid varje val: filtrera nästa dropdown via `dependsOn`-logik
4. Visa watt-alternativ filtrerade på `dependsOnAny` (kombination av två attribut)
5. Matcha vald kombination → hämta variant via JSON:API (`?filter[sku]=M-A0C8-J19N1`)
6. Visa SKU live, uppdatera pris (om partner), lägg i cart
7. (Framtid) Byt produktbild om valt alternativ har `image`-fält

### Varför JSON:API och inte Commerce AJAX

- Commerce AJAX nollställer val vid attributbyte (root cause från TASK-014)
- JSON:API låter oss matcha variant *utan* att trigga Commerce's hierarkiska logik
- Konfiguratorn är en "black box" ur Commerce's perspektiv — den lägger bara in en färdig variant-ID i cart

### Teknisk stack

- **Schema-läsning:** JSON:API → `field_configurator_schema` på produkten
- **Variant-sökning:** JSON:API → `/jsonapi/commerce_product_variation/default?filter[sku][value]=...`
- **Cart:** Drupal Commerce Cart API (befintlig)
- **Frontend:** Vanilla JS eller litet Alpine.js — ingen React nödvändig

---

## Byggordning (rekommenderas)

### Steg 1: Rensa och bygg om Commerce-strukturen
- [ ] Skapa `led_luminaire_max_opti` Product Type med rätt attribut
- [ ] Skapa `led_luminaire_srow` Product Type med rätt attribut
- [ ] Lägg till `field_configurator_schema` (Long text JSON) på båda typerna
- [ ] Radera befintliga felaktiga produkter/varianter

### Steg 2: Bygg CSV-filer per modell
- [ ] MAX BASE variations CSV (M- prefix, alla kombinationer)
- [ ] Prioritera MAX BASE först som proof-of-concept
- [ ] Övriga modeller i mån av tid

### Steg 3: Importera via Feeds
- [ ] Products-feed per modell (skapar produkt + sätter schema-fält)
- [ ] Variations-feed per modell (skapar varianter)
- [ ] Verifiera att SKU:er matchar schema

### Steg 4: Bygg konfiguratorn (TASK-015)
- [ ] JavaScript-modul som läser schema och renderar dropdowns
- [ ] dependsOn-filtrering
- [ ] JSON:API variant-matchning
- [ ] Cart-integration
- [ ] Test med MAX BASE

### Steg 5: Generalisera till övriga modeller
- [ ] Konfiguratorn är generisk — fungerar för alla modeller via schema
- [ ] Importera övriga CSV-filer
- [ ] Test med OPTI BASE och SROW BASE

### Steg 6: Bilder (när material finns)
- [ ] Leverera per-modell-bilder från Triton Engineering
- [ ] Ladda upp som Commerce variation media
- [ ] Vid behov: utöka schema med `image` per color-option

---

## Filer att referera

- `/docs/product-schemas/max-configurator-schemas.json`
- `/docs/product-schemas/opti-configurator-schemas.json`
- `/docs/product-schemas/srow-configurator-schemas.json`
- `/docs/product-schemas/README.md`
