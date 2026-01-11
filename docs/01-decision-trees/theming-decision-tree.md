# Theming Beslutsträd

## 🎯 Layout, Design & Styling

Detta träd kompletterar `/docs/DRUPAL-DECISION-TREE.md` med theming-specifika lösningar.

**Tech Stack:**
- **Base theme**: Radix
- **CSS**: Bootstrap 5.3 (via CDN)
- **Layout**: Layout Builder + Bootstrap Layout Builder
- **Field gruppering**: Field Group module

---

## Problem: Layout & Struktur

### Steg 1: Bootstrap-klasser via UI?

#### ✅ Använd UI-baserat styling FÖRST:

**Layout Builder:**
```
Structure → Content types → [Type] → Manage Display
→ Enable Layout Builder
→ Allow custom layouts (optional per content)
→ Add section → Bootstrap Grid
→ Dra fields/blocks till regioner
```

**Bootstrap-klasser:**
- `container`, `container-fluid`
- `row`, `col-md-6`, `col-lg-4`
- `card`, `card-body`, `card-title`
- `btn`, `btn-primary`, `btn-lg`
- `d-flex`, `justify-content-center`, `align-items-center`

**Block Class module:**
```
Block → Configure → CSS class(es)
Lägg till: "mb-4 shadow-sm" etc
```

#### ❌ Custom CSS endast om:
- Bootstrap saknar komponenten
- Mycket specifik design (t.ex. brand colors)
- Efter godkännande från Stefan

---

### Steg 2: Layout Builder för sidstruktur

**När använda Layout Builder:**
- Olika layout per sida/node
- Flexibel block-placering
- Landing pages
- Per-content-type layouts
- Commerce produkter (ALLTID för dessa!)

**Setup:**
```
Structure → Content types → [Type] → Manage Display
→ Enable Layout Builder
→ Allow custom layouts (optional per content)
```

**Bootstrap Layout Builder:**
```bash
ddev composer require drupal/bootstrap_layout_builder
ddev drush en bootstrap_layout_builder -y
```

**Användning:**
```
Layout Builder → Add section → Bootstrap Grid
→ Välj layout (1-col, 2-col, 3-col, etc)
→ Konfigurera breakpoints
→ Drag blocks/fields in
```

**Bootstrap Layout Builder ger:**
- Responsive grids (automatic breakpoints)
- Pre-made Bootstrap layouts
- Column configuration via UI
- Ingen CSS behövs!

---

### Steg 3: Field Groups för gruppering

**Field Group module för semantic gruppering:**

```bash
ddev composer require drupal/field_group
ddev drush en field_group -y
```

**Använd för:**
```
Structure → Content types → Manage Display
→ Add field group
→ Välj format: Fieldset, Details, Div, HTML element
→ Dra fields in i grupp
→ CSS classes på grupp
```

**Field Group format:**
- **Fieldset**: `<fieldset>` med legend
- **Details**: Collapsible `<details>` element
- **Div**: Wrapper `<div>` med klasser
- **HTML element**: Valfri tag (section, article, etc)
- **Accordion**: Bootstrap accordion
- **Tabs**: Horisontella tabs

**Fördelar:**
- Semantic HTML
- Ingen template nödvändig
- Bootstrap-klasser via UI
- Accessibility built-in

---

## Problem: Responsive Design

### Breakpoints (Bootstrap 5.3)

```css
/* Mobile first */
Default: <768px (mobile)

/* Breakpoints */
sm: ≥576px
md: ≥768px (tablet)
lg: ≥992px
xl: ≥1200px (desktop)
xxl: ≥1400px
```

### Responsive Images

**VIKTIGT**: Se `/docs/03-solutions/responsive-images.md`

**Sammanfattning:**
1. **Samma aspect ratio** över alla breakpoints (t.ex. 4:3)
2. **Focal Point module** för crop-kontroll
3. **CSS aspect-ratio** på container, `object-fit: cover` på img

**Image Styles:**
```
Configuration → Media → Image styles

Mobile (576x432)   - 4:3
Tablet (768x576)   - 4:3
Desktop (1200x900) - 4:3
```

**Responsive Image Style:**
```
Configuration → Media → Responsive image styles
→ Select image styles per breakpoint
→ Use on image fields
```

---

## Problem: Skapa Komponenter

### Steg 1: Finns i Bootstrap?

**Bootstrap 5.3 komponenter:**
- Accordion
- Alerts
- Badges
- Breadcrumb
- Buttons
- Cards
- Carousel
- Collapse
- Dropdowns
- Forms
- Modal
- Navbar
- Tabs
- Tooltips

**Använd dessa FÖRST** via HTML + Bootstrap-klasser.

### Steg 2: Layout Builder Blocks?

**För återanvändbara sektioner:**
```
Structure → Block layout → Custom block library
→ Add custom block type
→ Lägg till fields
→ Använd i Layout Builder
```

**Fördelar:**
- Återanvändbart innehåll
- Versionshanterat
- Översättningsbart
- Placera var som helst via Layout Builder

### Steg 3: Field Groups för struktur

**För field-grupper:**
```
Manage Display → Add field group
→ Välj layout: Div, Fieldset, Accordion, Tab, etc
→ Lägg fields i grupp
→ CSS classes på grupp
```

**Exempel: Product Specifications**
```
Field Group: "Technical Specs" (Accordion)
├── Field: Wattage
├── Field: CCT
├── Field: CRI
├── Field: IP Rating
└── Field: Dimensions
```

---

## Problem: Styling & CSS

### Custom CSS - Struktur

```
/themes/custom/tritonled/
├── css/
│   ├── components/
│   │   ├── hero-section.css
│   │   ├── product-card.css
│   │   └── footer.css
│   └── global.css
└── tritonled.libraries.yml
```

### Ladda CSS

**tritonled.libraries.yml:**
```yaml
global-styling:
  css:
    theme:
      css/global.css: {}
      css/components/hero-section.css: {}

product-page:
  css:
    theme:
      css/components/product-card.css: {}
```

**Attach i template/preprocess:**
```php
// tritonled.theme
function tritonled_preprocess_page(&$variables) {
  $variables['#attached']['library'][] = 'tritonled/global-styling';
}

function tritonled_preprocess_node__product(&$variables) {
  $variables['#attached']['library'][] = 'tritonled/product-page';
}
```

### CSS Best Practices

**GÖR:**
```css
/* BEM-naming */
.product-card { }
.product-card__title { }
.product-card__price { }
.product-card--featured { }

/* Mobile first */
.hero { padding: 1rem; }
@media (min-width: 768px) {
  .hero { padding: 3rem; }
}
```

**GÖR INTE:**
```css
/* ALDRIG !important (förstå specificity istället) */
.title { color: red !important; }

/* ALDRIG overly specific */
.page .main .content .product .title { }

/* ALDRIG hardcoded values utan variabler */
.btn { background: #3498db; } /* Använd Bootstrap variables */
```

---

## Problem: Preprocess Hooks

**Använd för:**
- Lägga till CSS-klasser
- Ta bort attribut (width/height på media)
- Attacha JavaScript libraries
- Lägga till variabler till template

**INTE för:**
- Ändra field rendering
- Manipulera content arrays
- Komplex logik (använd services)

### Exempel

**Lägg till klasser:**
```php
function tritonled_preprocess_node(&$variables) {
  $node = $variables['node'];
  
  // Lägg till class baserat på content type
  $variables['attributes']['class'][] = 'node--type-' . $node->bundle();
  
  // Featured node?
  if ($node->isPromoted()) {
    $variables['attributes']['class'][] = 'node--featured';
  }
}
```

**Ta bort attribut (responsive images):**
```php
function tritonled_preprocess_responsive_image(&$variables) {
  // Remove width/height to allow CSS aspect-ratio
  unset($variables['img_element']['#attributes']['width']);
  unset($variables['img_element']['#attributes']['height']);
}
```

**Se mer**: `/docs/02-standards/coding-standards.md`

---

## Problem: JavaScript

### Drupal Behaviors (FÖREDRA)

**tritonled.js:**
```javascript
(function ($, Drupal) {
  'use strict';
  
  Drupal.behaviors.tritonledCustom = {
    attach: function (context, settings) {
      // Runs on page load AND after AJAX
      $('.product-gallery', context).once('product-gallery').each(function() {
        // Init carousel, etc
      });
    }
  };
})(jQuery, Drupal);
```

**Lägg till library:**
```yaml
# tritonled.libraries.yml
custom-js:
  js:
    js/tritonled.js: {}
  dependencies:
    - core/jquery
    - core/drupal
```

### Vanilla JavaScript

**Om jQuery inte behövs:**
```javascript
(function (Drupal) {
  'use strict';
  
  Drupal.behaviors.vanillaExample = {
    attach: function (context) {
      const buttons = context.querySelectorAll('.custom-button');
      buttons.forEach(button => {
        if (!button.dataset.processed) {
          button.addEventListener('click', handleClick);
          button.dataset.processed = true;
        }
      });
    }
  };
})(Drupal);
```

---

## Radix Theme - Specifikt

### Radix Features

**Built-in Bootstrap:**
- Bootstrap 5.3 SCSS sources
- Radix Layout Builder layouts
- Subtheme generator

### Skapa Subtheme (om behövt)

```bash
cd web/themes/contrib/radix
drush generate radix-subtheme

# Följ prompts
# Theme name: TritonLED
# Machine name: tritonled
```

**OBS**: Vi har troligen redan `tritonled` som subtheme.

### Radix Layout Builder

**Inbyggda layouts:**
- Radix Brenham (50/50 columns)
- Radix Burr (sidebar + content)
- Radix Boxton (3-column)

**Använd dessa FÖRST** innan custom layouts.

---

## Layout Builder - Best Practices

### Rätt användning:

✅ **Sidstruktur** via Layout Builder sections
✅ **Field placering** i layout-regions
✅ **CSS-klasser** på blocks via Block Class
✅ **Responsive grids** via Bootstrap Layout Builder
✅ **Commerce products** (ALLTID för dessa - AJAX fungerar!)

### Viktigt för Commerce:

**ANVÄND Layout Builder för Commerce produkter:**
- Bevara Commerce field injection
- AJAX fungerar utan problem
- Event Subscribers för custom beteende
- Field Groups för gruppering

**UNDVIK templates för Commerce produkter:**
- Templates förstör AJAX
- Field injection slutar fungera
- Variation switching bryter

**Se**: `/docs/03-solutions/commerce-ajax-solution.md`

---

## Mobile-First Design

### Approach

1. **Design för mobil FÖRST**
2. **Progressively enhance** för desktop
3. **Testa på riktiga enheter**

### CSS Mobile-First

```css
/* Base: Mobile (<768px) */
.hero {
  padding: 1rem;
  font-size: 1.5rem;
}

/* Tablet (≥768px) */
@media (min-width: 768px) {
  .hero {
    padding: 2rem;
    font-size: 2rem;
  }
}

/* Desktop (≥1200px) */
@media (min-width: 1200px) {
  .hero {
    padding: 4rem;
    font-size: 3rem;
  }
}
```

### Bootstrap Grid Mobile-First

```html
<!-- Stack på mobil, 2-col på tablet, 3-col på desktop -->
<div class="row">
  <div class="col-12 col-md-6 col-lg-4">...</div>
  <div class="col-12 col-md-6 col-lg-4">...</div>
  <div class="col-12 col-md-6 col-lg-4">...</div>
</div>
```

---

## 🧪 Testing - Theming

### Browser DevTools

**Responsive test (Ctrl+Shift+M):**
- [ ] 375px (iPhone SE)
- [ ] 768px (iPad)
- [ ] 1200px (Desktop)

**Console:**
- [ ] Inga JS-errors
- [ ] Inga CSS-load errors

**Network:**
- [ ] CSS laddar
- [ ] Fonts laddar
- [ ] Images optimerade (<500kb)

### Cross-Browser

- [ ] Firefox (primary test)
- [ ] Chrome/Edge
- [ ] Safari (om tillgänglig)

### Accessibility

**Basic checks:**
- [ ] Semantic HTML (header, main, footer, nav)
- [ ] Alt-text på bilder
- [ ] Keyboard navigation fungerar (Tab, Enter)
- [ ] Color contrast OK (WCAG AA)

**Verktyg:**
- Lighthouse (Chrome DevTools)
- WAVE browser extension
- axe DevTools

---

## 📚 Bootstrap 5.3 Resources

**Dokumentation:**
- https://getbootstrap.com/docs/5.3/

**Viktiga sektioner:**
- Layout: Grid, Breakpoints, Containers
- Components: Cards, Buttons, Forms, Navbar
- Utilities: Spacing, Display, Flex, Text
- Customize: Variables, SCSS

**Radix dokumentation:**
- https://www.drupal.org/project/radix
- https://github.com/radixtheme/radix

**Layout Builder:**
- https://www.drupal.org/docs/8/core/modules/layout-builder

**Bootstrap Layout Builder:**
- https://www.drupal.org/project/bootstrap_layout_builder

---

**Version**: 1.1  
**Skapad**: 2025-01-10  
**Uppdaterad**: 2025-01-11 (Display Suite ersatt med Layout Builder)  
**Författare**: Stefan + Claude
