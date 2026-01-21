# Homepage Design Reference - Layout Builder Sektioner

## Design Source
- **HTML referens:** code.html (Tailwind-baserad)
- **Screenshot:** screen.png
- **Målimplementation:** Bootstrap 5.3 + Layout Builder + Radix

---

## Sektioner att bygga (från topp till botten)

### 1. HEADER (Global Region)
**Typ:** Sticky Navigation Bar
**Layout:** Container Fluid
**Innehåll:**
- Logo + brand name (vänster)
- Navigation menu (center): Products, Applications, Resources
- Search button + "Contact Sales" CTA (höger)
**Bootstrap komponenter:**
- Navbar (sticky-top)
- Nav, Nav-item
- Button (primary för CTA)

---

### 2. HERO CAROUSEL
**Typ:** Full-width Image Carousel med text overlay
**Layout:** One column (full width)
**Antal slides:** 3
**Innehåll per slide:**
- Background image (warehouse/industrial)
- Gradient overlay (left to right fade)
- Badge/label (färgad bar + uppercase text)
- H2 rubrik (stor, bold)
- Beskrivande text (light, max 2 rader)
- CTA link med arrow icon
**Bootstrap komponenter:**
- Carousel
- Carousel-item
- Position absolute för overlay-innehåll
- Badge

**Slide 1:** "NEW GENERATION - Precision in Darkness" (grön accent)
**Slide 2:** "IP66 RATED - Built for Extremes" (blå accent)
**Slide 3:** "MAX EFFICIENCY - Linear Performance" (orange accent)

---

### 3. TRUSTED BY LOGOS
**Typ:** Logo strip med grayscale logos
**Layout:** One column
**Innehåll:**
- Rubrik: "TRUSTED BY INDUSTRY LEADERS" (uppercase, liten, centered)
- 5 företagslogos i rad (Maruoco, Logistix, Aerotech, Portal, Buildcorp)
**Bootstrap komponenter:**
- Row med 5 kolumner (col-md-2 med offset eller flexbox space-evenly)
- Grayscale filter på logos

---

### 4. BROWSE BY APPLICATION
**Typ:** Category icon grid med link
**Layout:** One column
**Innehåll:**
- Rubrik: "Browse by Application" (stor, bold)
- Underrubrik: "Find the right lighting certification for your environment."
- "View all sectors →" link (höger)
- 6 kategori-ikoner i rad med labels:
  - Warehousing
  - Cold Storage
  - Hazardous Locations
  - Manufacturing
  - Sports Facilities
  - Parking Garages
**Bootstrap komponenter:**
- Row med col-md-2 för varje kategori
- Icon + text label under
- Card eller enkel div med hover-effekt

---

### 5. TOP RATED HIGH BAYS
**Typ:** Product grid
**Layout:** One column
**Innehåll:**
- Rubrik: "Top Rated High Bays"
- Underrubrik: "High efficiency fixtures for ceilings 20ft+"
- 4 produktkort i grid:

**Produktkort innehåller:**
- "IN STOCK" badge (grön)
- Produktbild (svart bakgrund)
- Serie-namn (blå, uppercase, liten)
- Produktnamn (bold)
- Specifikationer (2 kolumner):
  - LUMENS: XX,XXX lm
  - EFFICIENCY: XXX lm/w
  - CCT: XXXXK
  - CRI: XX
  - WATTS: XXW
  - IP: IPXX
- Certifiering badge (t.ex. "DLC Premium Listed", "NSF Certified")
- "Datasheet →" link

**Produkter:**
1. ProLine UFO High Bay 240W
2. Linear Halo Light 160W
3. Vapor Tight 4ft 40W
4. Explosion Proof 100W

**Bootstrap komponenter:**
- Row med col-md-3 för varje produkt
- Card med card-body
- Badge för "IN STOCK"
- Table eller definition list för specs

---

### 6. ENGINEERED FOR PERFORMANCE
**Typ:** Two-column split (text + image)
**Layout:** Two column (50/50 på desktop)
**Innehåll:**

**Vänster kolumn (text):**
- Rubrik: "Engineered for Performance" (stor, bold)
- Ingress-text (2 meningar om thermal management)
- 3 feature items med icon + rubrik + beskrivning:
  1. **Active Thermal Management** (thermostat icon)
     "Oversized heat sinks optimized with CFD modeling..."
  2. **Surge Protection** (bolt icon)
     "Standard 6kV/10kV surge protection..."
  3. **Smart Controls Ready** (sensors icon)
     "0-10V dimming standard. Zigbee/DALI compatible..."

**Höger kolumn (image):**
- Stor produktbild med mörk overlay
- Text overlay längst ner:
  - "PRO-SERIES" (liten, bold)
  - "Intelligent Design" (stor, bold)
  - Beskrivande text (1-2 meningar)

**Bootstrap komponenter:**
- Row med col-lg-6
- Icon i cirkel med primary bakgrund
- Card för bilden med overlay

---

### 7. CTA SECTION
**Typ:** Full-width colored background med centered content
**Layout:** One column, centered text
**Innehåll:**
- Blå bakgrund (primary color)
- Rubrik: "Ready to upgrade your facility?"
- Beskrivande text: "Get a free photometric layout and ROI analysis..."
- 2 knappar i rad:
  - "Request a Quote" (vit bakgrund)
  - "Speak to an Engineer" (transparent border)

**Bootstrap komponenter:**
- Container
- Text-center
- Btn btn-primary / btn-outline-light

---

### 8. FOOTER
**Typ:** Multi-column footer med links
**Layout:** Five columns på desktop
**Innehåll:**

**Kolumn 1-2 (bred):**
- Logo + brand
- Beskrivande text
- Social media ikoner (3 st)

**Kolumn 3-5:**
- **Products:** High Bays, Linear Fixtures, Vapor Tight, Explosion Proof, Retrofit Kits
- **Resources:** Rebate Finder, Photometrics, ROI Calculator, Warranty Info, Installation Guides
- **Company:** About Us, Contact Sales, Distributors, Careers, Privacy Policy

**Bottom row:**
- Copyright © 2024 LumaIndustrial
- Terms | Privacy | Sitemap

**Bootstrap komponenter:**
- Row med col-md-x
- Nav eller list-unstyled för länkar
- Border-top för bottom row

---

## Design Tokens (för Bootstrap anpassning)

**Färger (från Tailwind config):**
- Primary: #137fec (blå)
- Background Light: #f6f7f8
- Background Dark: #101922
- Accent colors: Green (#10b981), Blue (#3b82f6), Orange (#f97316)

**Typografi:**
- Font: Space Grotesk (skulle kunna ersättas med Bootstrap default eller Google Font)
- Sizes: Large headings (3xl = ~30px), body (sm = ~14px)

**Spacing:**
- Sections: py-16 (motsvarar Bootstrap py-5)
- Containers: max-w-7xl (Bootstrap container-xl)

---

## Byggordning (rekommenderad)

1. ✅ Aktivera tritonled_radix tema
2. Skapa dummy content (produkter, kategorier)
3. Skapa Custom Block Types eller använda core blocks
4. Bygg varje sektion i Layout Builder:
   - Börja med enklaste (CTA, Logo strip)
   - Sedan produktgrid
   - Sist: carousel (kan kräva contrib module eller custom SDC)
5. Styla med Bootstrap klasser via Layout Builder
6. Finjustera med minimal custom CSS om behövs
