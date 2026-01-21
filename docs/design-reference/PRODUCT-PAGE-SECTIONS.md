# Produktsida - Design Reference & Layout Builder Sektioner

## Design Source
- **HTML referens:** product-page-reference.html
- **Screenshot:** product-page-screenshot.png
- **Målimplementation:** Bootstrap 5.3 + Layout Builder + Commerce Display

---

## LAYOUT ÖVERSIKT

### Two-column layout (60/40 split på desktop)

**Vänster kolumn:**
- Huvudbild (stor)
- Thumbnail gallery (3-4 bilder)

**Höger kolumn:**
- Product metadata
- Variation selectors
- CTA buttons
- Stock status

**Full-width under:**
- Tabs (Specifications, Photometrics, Certifications, Downloads)
- Ideal Applications sektion

---

## SEKTION 1: PRODUKTBILDER (Vänster kolumn)

**Layout:** One region, full height

**Innehåll:**
- Huvudbild (aspect-ratio 4:3, dark background)
- "BEST SELLER" badge (overlay top-left)
- Thumbnail carousel/grid (3-4 thumbnails)
  - Produktbild från olika vinklar
  - Lifestyle-bild (installerad miljö)
  - Detaljbild (närbild)

**Bootstrap komponenter:**
- Card med card-img-top
- Badge (position absolute)
- Thumbnails: Row med col-4 eller carousel-indicators

**Funktionalitet:**
- Click thumbnail → swap main image
- Zoom on hover (optional)

---

## SEKTION 2: PRODUKT-INFO (Höger kolumn)

### 2A: Header
- Series badge (pill-shaped, colored background)
  - "TITAN SERIES" (uppercase, bold, 10px font-size)
- SKU (small text, muted)
  - "SKU: HB-TITAN-200W"
- Titel (H1, 32px, bold)
  - "Titan Series High Bay LED - 200W"
- Beskrivning (p, 14px, light)
  - 2-3 meningar om produkten

### 2B: Key Specs Grid (2x2)
**Layout:** Row med 4 col-6

**Varje spec innehåller:**
- Icon (colored, 24px)
- Label (uppercase, 10px, muted)
- Value (bold, 18px)

**Specs:**
1. **Luminous Flux** (sun icon, blue)
   - "28,000 lm"
2. **Efficiency** (lightning icon, blue)
   - "140 lm/W"
3. **Rating** (droplet icon, blue)
   - "IP65 Waterproof"
4. **Warranty** (shield icon, blue)
   - "10 Years"

**Bootstrap:**
- Row / Col-6
- D-flex align-items-start gap-2
- Icon i colored div

---

### 2C: Variation Selectors

**Select Wattage:**
- Label: "Select Wattage"
- 4 buttons i rad (pill-shaped)
  - 100W, 150W, **200W** (selected - blue), 240W
- Link: "View Photometrics →" (höger)

**Color Temperature (CCT):**
- Label: "Color Temperature (CCT)"
- 3 buttons i rad
  - 4000K, **5000K** (selected), 5700K

**Voltage:**
- Label: "Voltage"
- Dropdown select
  - "100-277V AC (Standard)"

**Accessories:**
- Label: "Accessories"
- Dropdown select
  - "Standard (None)"

**Bootstrap:**
- Btn-group för wattage/CCT (toggle buttons)
- Form-select för dropdowns
- Active state: btn-primary

---

### 2D: Pricing & Stock

**Volume Pricing:**
- Text: "Volume Pricing Available" (muted, 12px)

**Stock Status:**
- Green checkmark icon
- Text: "In Stock" (green, bold)

**Bootstrap:**
- Text-muted för pricing
- Text-success för stock
- D-flex align-items-center gap-2

---

### 2E: CTA Buttons

**Primary CTA:**
- Button: "Request Quote" (large, primary blue)
- Full-width på mobile, auto på desktop

**Secondary CTA:**
- Button: "📄 Spec Sheet" (outline, secondary)
- Icon + text

**Shipping info:**
- Text: "Free shipping on orders over $5,000. Ships within 24 hours." (small, muted)

**Bootstrap:**
- Btn btn-primary btn-lg
- Btn btn-outline-secondary
- D-flex flex-column gap-3

---

## SEKTION 3: TABS (Full-width under bilder)

**Tab Navigation:**
- Specifications (active)
- Photometrics
- Certifications
- Downloads

**Bootstrap:**
- Nav nav-tabs
- Tab-content med tab-pane

---

### Tab 1: Specifications

**Layout:** Two columns

**Vänster kolumn:**
**KEY FEATURES** (heading)
- Checkmark list (3 items)
  - "Die-cast aluminum housing for superior heat dissipation"
  - "Polycarbonate lens, shatter-resistant and anti-glare"
  - "Integrated surge protection (6kV standard)"

**Höger kolumn:**
**QUICK DOWNLOADS** (heading)
- PDF icon + "Spec Sheet (PDF)"
- ZIP icon + "IES Files (ZIP)"
- DOC icon + "Installation Guide"

**Bootstrap:**
- Row / Col-md-6
- List-unstyled med checkmark icons
- Links med icons

---

### Tab 2: Photometrics
(Framtida content: IES-filer, ljuskurvor, fotometrisk data)

### Tab 3: Certifications
(Visa certification badges från taxonomy med beskrivningar)

### Tab 4: Downloads
(Lista alla nedladdningsbara filer med ikoner och storlek)

---

## SEKTION 4: IDEAL APPLICATIONS

**Layout:** Full-width, three columns

**Heading:**
- "Ideal Applications" (H2, left)
- "View Case Studies →" (link, right)

**Content:**
3 image cards i row:

**Card 1: Warehousing**
- Image: Warehouse med höga hyllor
- Title: "Warehousing"
- Text: "Optimized for high racks and narrow aisles."

**Card 2: Manufacturing**
- Image: Fabrik med maskiner
- Title: "Manufacturing"
- Text: "High CRI for precision tasks and safety."

**Card 3: Gymnasiums**
- Image: Idrottshall
- Title: "Gymnasiums"
- Text: "Impact-resistant and uniform lighting."

**Bootstrap:**
- Row / Col-md-4
- Card med card-img-top
- Card-body med card-title + card-text
- Hover: Scale transform

---

## COMMERCE AJAX - VARIATION SWITCHING

**När användaren klickar Wattage/CCT button:**
1. AJAX request till Commerce
2. Uppdatera:
   - Lumens value
   - Efficiency value
   - SKU text
   - Stock status
   - Main image (om variationen har egen bild)
3. Highlight selected button (btn-primary)

**Drupal implementation:**
- Commerce Product Variation AJAX
- Views AJAX för stock status
- Custom JS för button states (minimal)

---

## RESPONSIVE BREAKPOINTS

**Mobile (<768px):**
- Stack columns vertically
- Full-width buttons
- Thumbnails i horizontal scroll
- Hide secondary specs

**Tablet (768-992px):**
- Two column layout (50/50)
- Smaller spacing
- Stack variation selectors

**Desktop (>992px):**
- 60/40 column split
- All features visible
- Optimal spacing

---

## NEXT STEPS IMPLEMENTATION

1. Configure Commerce Product display mode
2. Enable Layout Builder för Product
3. Add regions: Left (images), Right (info)
4. Add fields to regions med Bootstrap classes
5. Configure Variation Field Widget (AJAX)
6. Style variation buttons via CSS/Bootstrap
7. Add Tabs region med Views/Blocks för content
8. Add Ideal Applications View block

**Börja med display mode + Layout Builder aktivering?**
