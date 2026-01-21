# Startsida - Status & Målbild

**Datum:** 2026-01-11  
**Design-referens:** `/docs/assets/frontpage-target-design.png`  
**Nuvarande status:** 40% komplett - struktur finns, styling & innehåll saknas

---

## 🎯 MÅLDESIGN (Target)

### Sektion 1: Hero Carousel
**Layout:** Full-width, dark warehouse bakgrund med text overlay  
**Innehåll:**
- Badge: "NEW GENERATION" (grön, top-left)
- Heading: "Precision in Darkness" (display-4, vit text)
- Description: "Engineered for high-ceiling warehouses..." (lead, vit text)
- CTA: "View HB-Series →" knapp (vit outline)
- Navigation: Carousel indicators (bottom-center)
- Background: Dark industrial warehouse image
- Auto-rotation: 5000ms

**Teknisk:** Bootstrap 5.3 Carousel med fade transition

---

### Sektion 2: Social Proof - Industry Leaders
**Layout:** Centered, light gray background  
**Innehåll:**
- Heading: "TRUSTED BY INDUSTRY LEADERS" (small caps, centered)
- Logos: 5 företag i rad (Maruco, Logistix, Aerotech, Portal, Buildcorp)
- Grayscale logos med hover effect

**Teknisk:** Simple grid eller flexbox, kommer senare

---

### Sektion 3: Browse by Application
**Layout:** Container, 2 rows × 3 columns grid  
**Innehåll:**
- Heading: "Browse by Application"
- Subheading: "Find the right lighting certification for your environment"
- Link: "View all sectors →" (top-right)
- 6 badges/pills (svart bakgrund, vit text, icon + label):
  1. Warehousing 📦
  2. Cold Storage ❄️
  3. Hazardous Locations ⚠️
  4. Manufacturing 🏭
  5. Sports Facilities ⚽
  6. Parking Garages 🅿️

**Teknisk:** Bootstrap grid (col-lg-4 col-md-6), pill badges

---

### Sektion 4: Featured Products (Top Rated High Bays)
**Layout:** Container, 4 produkter i rad (Bootstrap grid)  
**Innehåll:**
- Heading: "Top Rated High Bays"
- Subheading: "High efficiency fixtures for ceilings 20ft+"
- 4 produktkort med:
  - Image/Media
  - "IN STOCK" badge (grön, top-left)
  - Series badge (t.ex. "HB-SERIES", top)
  - Product title
  - Specs: Lumens, Efficacy, CCT, CRI
  - Price (from X kr)
  - "Datasheet →" link (bottom)
  - Certification logos (DLC Premium Listed, UL/UL8 Standard)

**Teknisk:** Bootstrap cards i grid (col-lg-3 col-md-6)

---

### Sektion 5: Engineered for Performance
**Layout:** 2-kolumn (text vänster, image höger)  
**Innehåll:**
- Intro text: "Our proprietary thermal management systems..."
- 3 feature items med icon + beskrivning:
  1. 🔥 **Active Thermal Management** - "Oversized heat sinks optimized with CFD modeling..."
  2. ⚡ **Surge Protection** - "Standard 6kV/10kV surge protection..."
  3. 📶 **Smart Controls Ready** - "0-10V dimming standard..."
- Hero image: PRO-SERIES Intelligent Design (höger, med overlay text)

**Teknisk:** Bootstrap row med col-lg-6 + col-lg-6

---

### Sektion 6: CTA Section
**Layout:** Full-width, blå gradient bakgrund  
**Innehåll:**
- Heading: "Ready to upgrade your facility?" (vit, centered)
- Description: "Get a free photometric layout and ROI analysis..." (vit)
- 2 knappar:
  - "Request a Quote" (vit bakgrund, blå text)
  - "Speak to an Engineer" (transparent outline)

**Teknisk:** ✅ REDAN KLAR OCH FUNKAR PERFEKT!

---

## 📊 NUVARANDE STATUS

### ✅ FUNGERAR:
1. **CTA Section** - Perfekt implementation! Blå bakgrund, vita texter, knappar
2. **Layout Builder** - 5 sektioner konfigurerade i Layout Builder
3. **Browse by Application** - 6 badges visas (men fel layout)
4. **Featured Products** - Produkter visas (men fel styling)
5. **Views** - Alla 4 views finns och är konfigurerade

### ❌ SAKNAS/FEL:

#### PRIO 1 - Hero Carousel (Sektion 1)
**Problem:** Renderas inte alls  
**Orsak:** Produkter saknar `field_in_hero = TRUE`  
**Lösning:** 
```bash
# Markera 3-5 produkter för hero:
ddev drush php:eval "
\$products = [1, 2, 3, 4, 5]; // Produkt-IDs
foreach (\$products as \$id) {
  \$product = \Drupal\commerce_product\Entity\Product::load(\$id);
  if (\$product) {
    \$product->set('field_in_hero', TRUE);
    \$product->save();
  }
}"
```

#### PRIO 2 - Browse by Application Layout (Sektion 3)
**Problem:** Vertical stack istället för 2×3 grid  
**Nuläge:** Badges visas men i vertikal lista  
**Behöver:** Bootstrap grid klasser på view rows

#### PRIO 3 - Featured Products Styling (Sektion 4)
**Problem:** Produkter visas men utan Bootstrap card grid  
**Behöver:** 
- Bootstrap card markup
- col-lg-3 col-md-6 klasser
- "IN STOCK" badge
- Certification logos
- Datasheet link

#### PRIO 4 - Performance Features Innehåll (Sektion 5)
**Problem:** Visar Lorem Ipsum text  
**Behöver:** 
- Artikel nodes (7, 8, 9) med riktigt innehåll
- 2-kolumn layout (text + image)
- Icon graphics för features

#### PRIO 5 - Social Proof Sektion (Sektion 2)
**Status:** Inte skapad än  
**Behöver:** Ny sektion med företagslogos

---

## 🛠️ TEKNISK SETUP

### Views Konfigurerade:
1. **hero_media** - Block ID: `views_block:hero_media-block_1`
2. **browse_by_application** - Block ID: `views_block:browse_by_application-block_1`
3. **featured_products** - Block ID: `views_block:featured_products-block_1`
4. **performance_features** - Block ID: `views_block:performance_features-block_1`

### Layout Builder Konfiguration:
- **Entity:** `node.page.default`
- **Front page node:** Node 10 (Page bundle)
- **Config:** `/config/sync/core.entity_view_display.node.page.default.yml`

### Bootstrap Version:
- **Version:** 5.3 (via CDN i base theme)
- **Components:** Carousel, Cards, Grid, Badges, Buttons

### Theme:
- **Active:** Radix subtheme (tritonled)
- **Base:** Radix 6.x
- **Custom CSS:** `/web/themes/custom/tritonled/css/`
- **Templates:** `/web/themes/custom/tritonled/templates/`

---

## 📋 NÄSTA STEG (Priority Order)

### Steg 1: Fixa Hero Carousel ✋
**Blocker:** KRITISK - första intrycket  
**Actions:**
1. Verifiera att `field_in_hero` finns på produkter
2. Markera 3-5 produkter med `field_in_hero = TRUE`
3. Lägg till hero media (bilder/videos) på dessa produkter
4. Kontrollera att view mode "hero" finns för media
5. Test: Carousel ska rotera automatiskt

### Steg 2: Grid Layout - Browse by Application
**Blocker:** Layout ser trasig ut  
**Actions:**
1. Lägg till Bootstrap row klasser på view container
2. Lägg till col-lg-4 col-md-6 klasser på view rows
3. Eventuellt custom template för grid layout
4. Test: 2 rows × 3 columns på desktop

### Steg 3: Product Cards Styling
**Blocker:** Produkter ser inte professionella ut  
**Actions:**
1. Skapa Bootstrap card markup
2. Lägg till "IN STOCK" badge template
3. Lägg till certification logos
4. Fixa grid layout (col-lg-3 col-md-6)
5. Style datasheet link

### Steg 4: Performance Features Content
**Blocker:** Lorem Ipsum = unprofessionell  
**Actions:**
1. Skapa/uppdatera artikel nodes 7, 8, 9
2. Lägg till riktiga summaries
3. Fixa 2-kolumn layout
4. Lägg till feature icons

### Steg 5: Social Proof Sektion (Senare)
**Blocker:** Inte kritisk, men nice-to-have  
**Actions:**
1. Skapa ny Layout Builder sektion
2. Lägg till företagslogos (custom block)
3. Style med grayscale + hover effects

---

## 🎨 DESIGN TOKENS

### Colors:
```css
--primary-blue: #0066FF;
--dark-bg: #1a1a1a;
--light-bg: #f8f9fa;
--success-green: #00FF00; /* IN STOCK badge */
--text-white: #ffffff;
--text-dark: #333333;
```

### Typography:
```css
--heading-font: System font stack
--body-font: System font stack
--display-4: 3.5rem (hero headings)
--lead: 1.25rem (hero descriptions)
```

### Spacing:
```css
--section-padding: 5rem 0; /* py-5 */
--container-max-width: 1140px;
```

---

## 🔗 REFERENSER

- **Design mockup:** Uppladdad som screen.png
- **HTML exempel:** code.html (referens för markup)
- **Bootstrap 5.3 Docs:** https://getbootstrap.com/docs/5.3/
- **Tidigare session:** /mnt/transcripts/2026-01-11-19-40-17-frontpage-visual-comparison-analysis.txt
- **Visual testing results:** /docs/VISUAL-TESTING-RESULTS.md

---

## ✅ BESLUT TREE FÖLJT

Per `/docs/DRUPAL-DECISION-TREE.md`:

1. **Config först** ✅ - Layout Builder för struktur
2. **Moduler näst** ✅ - Views för dynamiskt innehåll
3. **Theme/CSS** ⏳ - Bootstrap klasser, minimal custom CSS
4. **Preprocess hooks** ⏸️ - Endast om Bootstrap inte räcker
5. **Templates** ⏸️ - Endast om hooks inte räcker

**Status:** Vid steg 3 (Theme/CSS), inga templates eller hooks än!

---

**Uppdaterad:** 2026-01-11 20:45  
**Nästa review:** Efter hero carousel fix
