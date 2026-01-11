# SDC Component Workflow

**Skapad**: 2025-01-10  
**Syfte**: Systematisk approach för att skapa och använda Single Directory Components

---

## ⚠️ VIKTIGT: När ska SDC användas?

### SDC är SISTA UTVÄGEN

**Använd INTE SDC om:**
- ✅ Bootstrap klasser räcker
- ✅ Core Drupal functions kan lösa det
- ✅ Views + formatters kan lösa det
- ✅ Layout Builder + Display Suite räcker

**Använd SDC ENDAST om:**
- ❌ Bootstrap komponenter inte räcker
- ❌ Core formatters är otillräckliga
- ❌ Modularitet/återanvändbarhet MÅSTE bibehållas
- ❌ Komplex logik krävs (slots, conditional rendering)

---

## 🎯 Design Implementation Hierarchy (REPETERA)

### 1. Bootstrap FÖRST
```html
<!-- Räcker ofta för 80% av fallen -->
<div class="container">
  <div class="row g-4">
    <div class="col-md-6">
      <div class="card">
        <img src="..." class="card-img-top">
        <div class="card-body">
          <h5 class="card-title">Title</h5>
          <p class="card-text">Text</p>
        </div>
      </div>
    </div>
  </div>
</div>
```

### 2. Core Drupal Functions
```yaml
# Configure via Admin UI
Image Styles: large, medium, thumbnail
View Modes: teaser, full, card
Views: product_list, news_list
Layout Builder: section placement
Display Suite: field formatting
```

### 3. Check: Kan Core lösa det?
```
IF Bootstrap + Core = Complete Solution
  THEN: Use config only ✅
ELSE IF Views + formatters = 90% done
  THEN: Minor template tweak (Views template) ✅
ELSE IF truly modular component needed
  THEN: Consider SDC (last resort) ⚠️
```

### 4. SDC (endast om nödvändigt)
```yaml
# Exempel: Complex interactive component
# That Bootstrap can't handle
name: Interactive Product Configurator
props:
  - product_variations
  - price_calculator
slots:
  - configuration_form
  - preview_area
```

---

## 🔍 Design → Implementation Process

### Phase 1: Analysis

**När du får en design:**

```markdown
## 🎨 Design Analysis

### 1. Identify Bootstrap Components
- Hero section → Bootstrap carousel ✅
- Product grid → Bootstrap card grid ✅
- Tabs → Bootstrap nav-tabs ✅
- Accordion → Bootstrap accordion ✅

### 2. Map to Core Drupal
- Product images → Responsive Image (core) ✅
- Product listing → Views (core) ✅
- Layout → Layout Builder (core) ✅
- Field placement → Display Suite (contrib) ✅

### 3. Check: Do we need SDC?
- Can Bootstrap handle layout? YES ✅
- Can core handle media? YES ✅
- Can Views handle listing? YES ✅
- Do we need custom logic? NO ✅

### 4. Decision
**NO SDC NEEDED!**

Implementation:
1. Configure image styles (Admin → Structure → Image styles)
2. Create view (Admin → Structure → Views)
3. Apply Bootstrap classes (Block Class module)
4. Use Layout Builder for placement

CODE NEEDED: NONE ✅
```

---

## 🛠️ When SDC IS Needed

### Example: Interactive Component

**Scenario:**
- Bootstrap tabs exists ✅
- BUT needs dynamic content loading ❌
- AND conditional rendering ❌
- AND complex state management ❌

**Solution:** Create SDC with JS behavior

**Directory Structure:**
```
components/dynamic-tabs/
├── dynamic-tabs.component.yml    # Metadata + props
├── dynamic-tabs.twig              # Template
├── dynamic-tabs.css               # Styles (if Bootstrap not enough)
└── dynamic-tabs.js                # Behavior
```

---

## 📝 SDC Creation Process

### Step 1: Verify SDC is Necessary

**Checklist:**
- [ ] Bootstrap components tested → insufficient
- [ ] Core formatters tested → insufficient
- [ ] Views templates tested → insufficient
- [ ] Modularitt required → YES
- [ ] Approved by Stefan → YES

**If all checked → proceed to Step 2**

### Step 2: Create Component Directory

```bash
cd web/themes/custom/tritonled_radix
mkdir -p components/my-component
```

### Step 3: Define Metadata (component.yml)

**my-component.component.yml:**
```yaml
'$schema': 'https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/modules/sdc/src/metadata.schema.json'
name: My Component
status: stable
description: 'Brief description of what this does'

# Props = configuration options
props:
  type: object
  required:
    - title
  properties:
    title:
      type: string
      title: Title
      description: Component heading
    
    variant:
      type: string
      title: Style Variant
      enum:
        - primary
        - secondary
      default: primary
    
    items:
      type: array
      title: Items
      description: List of items to display

# Slots = content areas
slots:
  content:
    title: Main Content
    description: Primary content area
  
  aside:
    title: Sidebar Content
    description: Optional sidebar
```

### Step 4: Create Template

**my-component.twig:**
```twig
{#
/**
 * @file
 * My Component template
 * 
 * Available variables:
 * - title: Component title
 * - variant: Style variant (primary/secondary)
 * - items: Array of items
 * - content: Main content slot
 * - aside: Sidebar slot
 */
#}
<div class="my-component my-component--{{ variant }}">
  {% if title %}
    <h2 class="my-component__title">{{ title }}</h2>
  {% endif %}
  
  <div class="my-component__body">
    <div class="my-component__content">
      {{ content }}
    </div>
    
    {% if aside %}
      <aside class="my-component__aside">
        {{ aside }}
      </aside>
    {% endif %}
  </div>
  
  {% if items %}
    <ul class="my-component__list">
      {% for item in items %}
        <li class="my-component__item">{{ item }}</li>
      {% endfor %}
    </ul>
  {% endif %}
</div>
```

### Step 5: Add Styles (if Bootstrap not enough)

**my-component.css:**
```css
/* ONLY if Bootstrap utilities are insufficient */

.my-component {
  /* Use Bootstrap variables when possible */
  /* margin: var(--bs-gutter-x); */
}

/* Scope all styles to component */
.my-component__title {
  /* Component-specific styles */
}
```

### Step 6: Add Behavior (if needed)

**my-component.js:**
```javascript
/**
 * @file
 * My Component behavior
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.myComponent = {
    attach: function (context, settings) {
      once('my-component', '.my-component', context).forEach(function (element) {
        // Component logic here
      });
    }
  };
})(Drupal, once);
```

### Step 7: Clear Cache & Test

```bash
# Clear cache
ddev drush cr

# Check component appears in Layout Builder
Admin → Structure → Layout Builder
→ Add block → Theme Components
→ Should see "My Component"
```

---

## 🏗️ Layout Builder Integration

### Using SDC in Layout Builder

**1. Navigate:**
```
Structure → Content types → [Type] → Manage display
OR
Structure → Pages → [Page] → Layout
```

**2. Add Component:**
```
→ Add block
→ Theme Components
→ Select: My Component
```

**3. Configure Props:**
```
Form appears with:
- Title field
- Variant dropdown (primary/secondary)
- Items textarea

Fill in and save.
```

**4. Map Drupal Fields to Props:**
```twig
{# In Layout Builder block config #}
title: {{ content.field_title }}
items: {{ content.field_items }}
```

---

## ✅ SDC Testing Checklist

### After Creating SDC

- [ ] Cache cleared (`ddev drush cr`)
- [ ] Component appears in Layout Builder
- [ ] Props form displays correctly
- [ ] All props work as expected
- [ ] Slots render content
- [ ] Responsive on all breakpoints
- [ ] No console errors (JS)
- [ ] No layout shift (CLS < 0.1)
- [ ] Accessible (WCAG AA)
- [ ] Performance (Lighthouse >90)

### Validation Questions

- [ ] Could this have been done with Bootstrap? (if YES → remove SDC)
- [ ] Could core formatters do this? (if YES → remove SDC)
- [ ] Is it truly reusable? (if NO → reconsider)
- [ ] Is it well documented? (if NO → add comments)

---

## 📚 Component Library

### Recommended Components (ONLY if needed)

**Layout Components:**
- `section-hero` - IF Bootstrap carousel insufficient
- `section-50-50` - IF Bootstrap grid not enough
- `section-cta` - IF simple div not enough

**Content Components:**
- `product-card` - IF Bootstrap card needs custom logic
- `media-gallery` - IF core media views insufficient
- `specs-table` - IF Views table insufficient

**Interactive Components:**
- `tabs-dynamic` - IF Bootstrap tabs + AJAX insufficient
- `accordion-nested` - IF Bootstrap accordion insufficient
- `modal-complex` - IF Bootstrap modal insufficient

**⚠️ Remember:** Most cases don't need SDC!

---

## 🚫 Anti-Patterns (AVOID)

### DON'T Create SDC For:

**1. Simple Layout**
```
❌ DON'T: Create "two-column" SDC
✅ DO: Use Bootstrap row/col classes
```

**2. Bootstrap Components**
```
❌ DON'T: Recreate Bootstrap card as SDC
✅ DO: Use Bootstrap card with classes
```

**3. Simple Formatters**
```
❌ DON'T: Create image-with-caption SDC
✅ DO: Configure image field formatter
```

**4. Static Content**
```
❌ DON'T: Create hero-banner SDC for static hero
✅ DO: Use Layout Builder + background image field
```

---

## 📖 Examples

### Example 1: When SDC NOT Needed

**Design Request:**
> "Create a 3-column product grid with cards"

**Analysis:**
```markdown
Bootstrap Components:
- Grid system: row/col ✅
- Card component ✅

Core Functions:
- Views: product listing ✅
- View mode: card ✅
- Image style: medium ✅

SDC Needed? NO ✅

Implementation:
1. Create View: product_grid
2. Format: Grid
3. View mode: Card
4. Template: Views template (if minor tweaks)
5. Classes: Bootstrap card classes
```

### Example 2: When SDC IS Needed

**Design Request:**
> "Create interactive product configurator with:
> - Dynamic option selection
> - Real-time price calculation
> - Conditional field display
> - AJAX form submission"

**Analysis:**
```markdown
Bootstrap Components:
- Form components ✅
- BUT: Dynamic behavior required ❌

Core Functions:
- Commerce product variations ✅
- BUT: Custom calculation logic ❌
- BUT: Conditional rendering ❌

SDC Needed? YES ⚠️

Reason:
- Complex state management
- Custom JavaScript behavior
- Multiple interconnected parts
- Reusable across products

Implementation:
1. Create SDC: product-configurator
2. Define props: product_id, variations
3. Add JS: calculation logic
4. Template: form structure
5. Test: all scenarios
```

---

## 🔄 Migration from Views Template to SDC

**IF Views template becomes too complex:**

### Step 1: Extract Logic
```twig
{# OLD: views-view-unformatted--products.html.twig #}
{% for row in rows %}
  <div class="product-card">
    {{ row.content }}
  </div>
{% endfor %}
```

### Step 2: Create SDC
```yaml
# components/product-card/product-card.component.yml
name: Product Card
props:
  product: 
    type: object
```

```twig
{# components/product-card/product-card.twig #}
<div class="product-card">
  <img src="{{ product.image }}" class="card-img-top">
  <div class="card-body">
    <h5>{{ product.title }}</h5>
    <p>{{ product.price }}</p>
  </div>
</div>
```

### Step 3: Use in View
```twig
{# views-view-unformatted--products.html.twig #}
{% for row in rows %}
  {% include 'tritonled_radix:product-card' with {
    'product': row.content
  } %}
{% endfor %}
```

---

## 📚 Resources

**Drupal SDC:**
- Documentation: https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components
- Examples: https://git.drupalcode.org/project/sdc_examples

**Bootstrap:**
- Components: https://getbootstrap.com/docs/5.3/components/
- Before creating SDC, check Bootstrap has it!

**Best Practices:**
- Keep components small and focused
- Document props thoroughly
- Test accessibility
- Validate performance

---

**Version**: 1.0  
**Författare**: Stefan + Claude  
**Nästa review**: 2025-02-10
