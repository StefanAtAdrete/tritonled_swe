# TritonLED - Produktstruktur & Datamodell

**Uppdaterad:** 2026-01-20
**Status:** Beslutad arkitektur

---

## PRODUKTTYPER & VARIATIONER

### LED-produktkategorier har olika attribut

**Olika produkttyper:**
- **High Bay** - Strålkastare för höga tak (har attribut: mounting height, beam angle)
- **Linear** - Lysrörsliknande armaturer (har attribut: linkable, mounting type)
- **Strips** - LED-remsor (har attribut: length, IP rating, cut points)
- **Panels** - Paneler för tak/vägg (har attribut: dimensions, mounting type)
- **Vapor Tight** - Fukt- och dammskyddade armaturer
- **Explosion Proof** - ATEX/IECEx-certifierade för farliga miljöer

### Varje produkt har variationer

**Commerce Product Variations används för:**
- **Wattage** (100W, 150W, 200W, 240W)
- **Color Temperature / CCT** (4000K, 5000K, 5700K)
- **Voltage** (100-277V AC, 347-480V AC)
- **Accessories** (Standard, Dimming, Emergency backup)

**Variation = SKU med unikt:**
- SKU-nummer (ex: HB-TITAN-200W)
- Stock level
- Specs (lumens, efficiency anpassas per wattage)

---

## COMMERCE STRUKTUR

### Quote-based Commerce (Ingen prisvisning)

- ✅ **INGEN prisvisning** på sida
- ✅ **"Request Quote" som primär CTA**
- ✅ **"Volume Pricing Available"** meddelande
- ✅ Dölj `price` field på Product Variation
- ✅ Använd Commerce Quote modul (contrib)

### Stock Management

- ✅ **Commerce Stock module** för stock level
- ✅ Visa "In Stock" / "Out of Stock" badge baserat på stock_level
- ✅ Grönt checkmark icon + text "In Stock"

---

## FÄLTSTRUKTUR

### Commerce Product (innehållslager)

**Bas-fält (alla produkttyper):**
- Title
- Body (beskrivning)
- SKU Prefix (ex: "HB-TITAN")
- Product Series (taxonomy reference)
- Images (multi-value media reference)
- Technical Specifications (se nedan)
- Application Areas (multi taxonomy reference)
- Certifications (multi taxonomy reference)
- Product Tags (multi taxonomy reference för badges)
- Downloads (multi media reference: Spec Sheet, IES Files, Installation Guide)

**Tekniska specifikationer (shared fields, olika visibility per typ):**
- Lumens (integer + "lm" suffix)
- Efficiency (integer + "lm/W" suffix)
- **IP Rating** (text field: "IP65", "IP66", "IP67") - INTE taxonomy
- CRI (integer + "Ra" suffix)
- Color Temperature Range (text: "4000K-5700K")
- Beam Angle (integer + "°" suffix)
- Warranty (integer + "Years" suffix)
- Mounting Type (list/select: Hook, Chain, Surface, Recessed)
- Dimensions (text: "L x W x H")
- Weight (decimal + "kg" suffix)

**Produkttyp-specifika fält (conditional visibility):**
- `field_mounting_height_min` (High Bay only)
- `field_linkable` (Linear only - boolean)
- `field_cut_points` (Strips only - text)
- `field_atex_zone` (Explosion Proof only - list)

### Commerce Product Variation (SKU-lager)

- SKU (auto-generated: PREFIX-WATTAGE-CCT)
- Wattage (attribute - integer)
- CCT (attribute - integer)
- Voltage (attribute - select)
- Accessories (attribute - select)
- Stock Level (commerce_stock)
- Title (auto: "{Product} - {Wattage}W {CCT}K")

---

## TAXONOMIER

### 1. Product Series (single select)
*Gruppering av produkter i serier*

**Terms:**
- Titan Series (High Bays)
- Linear Pro Series
- Vapor Guard Series
- ATEX Series (Explosion Proof)
- Strip Light Series

**Används för:**
- Breadcrumb: "High Bay LEDs / Titan Series"
- Filtering i produktlistor
- "Related products" från samma serie

---

### 2. Application Areas (multi select)
*Användningsområden där produkten passar*

**Terms:**
- Warehousing
- Cold Storage
- Hazardous Locations
- Manufacturing
- Sports Facilities
- Parking Garages
- Retail Spaces
- Cleanrooms

**Används för:**
- "Browse by Application" grid på homepage
- "Ideal Applications" sektion på produktsida
- Faceted search filtering
- RAG/AI kategorisering

---

### 3. Certifications (multi select)
*Officiella certifieringar och standarder*

**Terms (varje term har):**
- Name: "DLC Premium Listed"
- Logo (media field - icon/badge image)
- Description: "Kvalificerar för energirabatter"
- URL: Länk till certifieringsmyndighet

**Exempel terms:**
- DLC Premium Listed
- NSF Certified
- UL Listed
- ATEX Certified
- IECEx Certified
- CE Marked
- RoHS Compliant

**Används för:**
- Badge-visning på produktkort
- "Certifications" tab på produktsida
- Filtering i produktlistor

---

### 4. Product Tags (multi select)
*Flexibla markörer för status och kampanjer*

**Terms:**
- Best Seller (blå badge)
- New Product (grön badge)
- Featured (orange badge)
- Limited Stock (röd badge)
- Clearance (gul badge)

**Används för:**
- Badges på produktkort och produktsida
- Conditional styling via taxonomy term ID
- Kampanjfiltering

---

## SKALBARHET & FRAMTIDA EXPANSION

### Kan vi dela upp i olika bundles senare?

✅ **JA** - Vi kan migrera till flera bundles om det behövs:

**Nuläge (Phase 1):**
- Ett bundle: "LED Product" med alla fält
- Conditional field visibility via Display Suite / Layout Builder
- Enklare att starta, snabbare att bygga

**Framtid (Phase 2 vid behov):**
- Separata bundles: "High Bay", "Linear", "Strips", "Panels"
- Delad bas via Field Groups eller Inherit Fields module
- Migration via Migrate API om nödvändigt

**Beslutsregel:**
- Om >30% av fälten ENDAST används av en produkttyp → Överväg split
- Om <30% unika fält per typ → Behåll ett bundle

---

## PRODUKTSIDA - DESIGN & FUNKTIONER

### Layout (från design-referens)

**Vänster kolumn (60%):**
- Primär produktbild (stor)
- Thumbnails (3-4 bilder)
- Zoom-funktion

**Höger kolumn (40%):**
- Series badge (färgad: "TITAN SERIES")
- SKU (liten text: "SKU: HB-TITAN-200W")
- Titel (H1: "Titan Series High Bay LED - 200W")
- Beskrivning (2-3 meningar)
- **Key specs (4 ikoner i grid):**
  - Luminous Flux: 28,000 lm
  - Efficiency: 140 lm/W
  - Rating: IP65 Waterproof
  - Warranty: 10 Years
- **Variation selectors:**
  - Select Wattage (buttons: 100W, 150W, 200W, 240W)
  - Color Temperature (buttons: 4000K, 5000K, 5700K)
  - Voltage (dropdown)
  - Accessories (dropdown)
- "Volume Pricing Available" text
- "In Stock" status (grön med checkmark)
- **CTA buttons:**
  - "Request Quote" (primary blue button)
  - "Spec Sheet" (secondary outline button med PDF icon)
- "Free shipping on orders over $5,000" text

**Tabs under produktbilden:**
1. **Specifications** - Full teknisk specifikation
2. **Photometrics** - IES-filer, ljuskurvor
3. **Certifications** - Badges med beskrivningar
4. **Downloads** - PDF/ZIP-filer

**Ideal Applications sektion:**
- 3 image cards med hover-effekt
- Application namn + kort beskrivning
- "View Case Studies →" link

---

## BYGGA-ORDNING (Rekommenderad)

### Fas 1: Grundstruktur
1. ✅ Skapa Taxonomier (4 st)
2. ✅ Konfigurera Commerce Product bundle
3. ✅ Lägg till fält på Product
4. ✅ Konfigurera Product Variations med Attributes
5. ✅ Installera Commerce Stock module
6. ✅ Skapa 5-10 dummy produkter via Drush

### Fas 2: Display & Views
7. Konfigurera Product Display med Layout Builder
8. Skapa Views för produktlistor
9. Konfigurera variation selector (AJAX)
10. Styla med Bootstrap klasser

### Fas 3: Quote & Commerce
11. Installera Commerce Quote module
12. Konfigurera "Request Quote" workflow
13. Testa stock-badge logic

---

## FÄLTANVÄNDNING - SMART ÅTERANVÄNDNING

**Exempel på kombination via Views:**

```yaml
Product Card (Compact):
  - Image (thumbnail)
  - Title (linked)
  - Series badge (från taxonomy term)
  - Lumens + Efficiency (2 fält, format: "XX,XXX lm | XXX lm/W")
  - "In Stock" badge (computed från stock_level)
  - Product Tags badges (från taxonomy)
  - "Request Quote" link

Product Card (Full):
  - Ovanstående +
  - IP Rating (text field)
  - Warranty (format: "XX Years")
  - Application icons (från taxonomy: application_areas)
  - Certifications logos (från taxonomy media fields)
```

**Samma fält, olika formatering:**
- Produktkort: Visa endast lumens siffra
- Produktsida: Visa lumens med ikon och label
- Compare-vy: Visa lumens i tabell-cell

---

## NEXT STEPS

1. **Skapa taxonomierna** (4 vocabularies med terms)
2. **Konfigurera Product bundle** (lägg till alla fält)
3. **Sätt upp Product Variations** (attributes + SKU-pattern)
4. **Generera dummy content** (Drush generate)
5. **Bygg produktsida layout** (Layout Builder)

**Vill du att jag börjar med steg 1: Skapa taxonomierna?**
