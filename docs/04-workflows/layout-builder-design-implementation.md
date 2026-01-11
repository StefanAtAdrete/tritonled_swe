# Layout Builder: Design → Implementation Workflow

**Skapad**: 2025-01-11  
**Syfte**: Exakt process för hur Claude implementerar design med Layout Builder

---

## 🎯 Vad Claude GÖR och INTE gör

### ✅ Claude GÖR:

1. **Analysera design** → Bootstrap komponenter & klasser
2. **Ge UI-instruktioner** → Steg-för-steg Layout Builder konfiguration
3. **Skapa supporting content** → Views, blocks, fields via AI agents
4. **Lista exakta CSS klasser** → Bootstrap + Layout Builder Styles
5. **Skapa minimal custom CSS** → Endast om Bootstrap inte räcker
6. **Visual testing** → Puppeteer screenshots efteråt

### ❌ Claude gör INTE:

- Klicka i Layout Builder UI (Stefan gör det enligt instruktioner)
- Skapa layouts direkt (Stefan placerar i UI)

---

## 📚 Vad Claude lärt sig från exempel-configs

### **Layout Builder Struktur:**

```yaml
third_party_settings:
  layout_builder:
    enabled: true
    allow_custom: false  # För content type-wide layout
    sections:
      - layout_id: 'bootstrap_layout_builder:blb_col_2'  # 2-column layout
        layout_settings:
          label: 'Product features'
          container: container  # Bootstrap container
          breakpoints:
            desktop: blb_col_6_6     # 50/50 på desktop
            tablet: blb_col_6_6      # 50/50 på tablet
            mobile: blb_col_12       # Full width på mobil
          layout_regions_classes:
            blb_region_col_1:
              - col-lg-6   # Bootstrap responsive klasser
              - col-md-6
              - col-12
            blb_region_col_2:
              - col-lg-6
              - col-md-6
              - col-12
        components:
          <uuid>:
            region: blb_region_col_1
            configuration:
              id: 'field_block:commerce_product:luminaire:field_product_media'
            additional:
              component_attributes:
                block_attributes:
                  class: 'bg-light p-4 rounded-3'  # Bootstrap klasser
              layout_builder_styles_style:
                card_style: card_style  # Predefined style
```

### **Tillgängliga Layout IDs:**

- `bootstrap_layout_builder:blb_col_1` - En kolumn
- `bootstrap_layout_builder:blb_col_2` - Två kolumner (responsive breakpoints)
- `bootstrap_layout_builder:blb_col_3` - Tre kolumner
- `bootstrap_layout_builder:blb_col_4` - Fyra kolumner
- `layout_onecol` - Core one column (basic)

### **Responsive Breakpoints:**

```yaml
breakpoints:
  desktop: blb_col_6_6      # Options: blb_col_12, blb_col_6_6, blb_col_9_3, blb_col_3_9, blb_col_4_4_4
  tablet: blb_col_6_6       # Options: blb_col_12, blb_col_6_6
  mobile: blb_col_12        # Usually: blb_col_12 (full width)
```

### **Layout Builder Styles (Predefined):**

Från `/config/sync/layout_builder_styles.style.*`:
- `card_style` → `p-3 bg-white shadow-sm rounded-2`
- `shadow` → Bootstrap shadow
- `shadow_sm` → Bootstrap shadow-sm
- `bg_white` → Bootstrap bg-white
- `rounded_2` → Bootstrap rounded-2
- `rounded_3` → Bootstrap rounded-3
- `p_4` → Bootstrap p-4
- `mb_3`, `mb_4`, `mb_5` → Bootstrap margin-bottom
- `mt_3` → Bootstrap margin-top
- `border_light` → Bootstrap border-light
- `overflow_hidden` → Bootstrap overflow-hidden

### **Bootstrap Styles (via Bootstrap Layout Builder):**

```yaml
container_wrapper:
  bootstrap_styles:
    background:
      background_type: video  # or image, or null
    padding:
      class: _none  # or p-0, p-1, p-2, p-3, p-4, p-5
    margin:
      class: _none  # or m-0, m-1, m-2, m-3, m-4, m-5
    text_color:
      class: null  # or text-primary, text-secondary, etc
    text_alignment:
      class: null  # or text-start, text-center, text-end
    border:
      rounded_corners:
        class: _none  # or rounded, rounded-1, rounded-2, rounded-3
```

### **Views i Layout Builder:**

Views blocks kan placeras:
```yaml
components:
  <uuid>:
    region: blb_region_col_1
    configuration:
      id: 'views_block:product_cards_top_3-block_1'  # View machine name + display
      label: 'Featured Products'
      label_display: visible
```

Views kan ha Bootstrap markup direkt:
```yaml
# I views.view.*.yml
fields:
  nothing:
    alter:
      alter_text: true
      text: |
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">{{ title }}</h5>
          </div>
        </div>
```

---

## 🔄 Exakt Workflow: Design → Implementation

### **Steg 1: Design Analysis (Claude)**

Stefan skickar design. Claude analyserar:

```markdown
**DESIGN ANALYSIS**

Hero Section:
- Layout: Full-width video background + text overlay
- Bootstrap: .position-relative, .vh-100, .d-flex, .align-items-center
- Custom CSS: Linear gradient overlay
- Components: Background video (field), Title (field), CTA button (field)

Product Grid:
- Layout: 3 columns desktop, 2 tablet, 1 mobile
- Bootstrap: .row .g-4, .col-lg-4 .col-md-6 .col-12, .card
- Components: Views block "Featured Products" (3 items)

Specifications:
- Layout: 2 columns (9/3) desktop, 1 column mobile
- Bootstrap: .row, .col-lg-9, .col-lg-3, .nav .nav-tabs
- Components: Quicktabs block, File downloads

**IMPLEMENTATION STRATEGY:**
1. Views: "Featured Products" view (card view mode, 3 items)
2. Layout Builder: 3 sections (hero, grid, specs)
3. Bootstrap: 90% (minimal custom CSS för gradient)
4. SDC: 0% (ej behövs)
```

### **Steg 2: Pre-requisites Check (Claude)**

```markdown
**PRE-REQUISITES:**

Fields needed:
✅ field_hero_video (exists)
✅ field_product_media (exists)
❌ field_cta_button (MISSING - needs AI agent)

Views needed:
❌ "Featured Products" view (MISSING - needs creation)

Content types:
✅ Product (luminaire) exists

**ACTION PLAN:**
1. Create field_cta_button via AI agent
2. Create "Featured Products" view
3. Configure Layout Builder
```

### **Steg 3: Create Supporting Content (Claude + AI Agents)**

```javascript
// Claude kör AI agents

// 1. Lägg till CTA button field
aif_field_type_agent({
  prompt: "Add 'field_cta_button' to commerce_product luminaire:
  - Type: Link
  - Label: Call to Action Button
  - Required: No
  - Display: In hero section"
})

// 2. Skapa View (eller ge UI-instruktioner)
"CREATE VIEW: Featured Products
Path: /admin/structure/views/add
- Name: featured_products
- Show: Product variations (luminaire)
- Display: Block
- Items per page: 3
- Format: Grid (3 columns)
- Fields: field_product_media (card view mode)
- Filter: Status = Published, In hero = TRUE"
```

### **Steg 4: Layout Builder UI Instructions (Claude → Stefan)**

````markdown
**LAYOUT BUILDER CONFIGURATION**

Enable Layout Builder:
1. Go to: /admin/structure/commerce/product-types/luminaire/edit/display
2. Enable "Use Layout Builder"
3. Enable "Allow each content item to have its layout customized" (optional)
4. Save

Configure Layout (default display):
1. Click "Manage layout"

---

## SECTION 1: Hero

Click "Add section"
- Select: "One column" (bootstrap_layout_builder:blb_col_1)
- Section label: "Hero"

Section settings:
- Container: container-fluid
- Section classes: (leave empty)
- Breakpoints: (default)

Section Bootstrap Styles:
- Background → Background type: video
- Padding → class: _none
- Margin → class: _none

Add components:
1. "Add block" in region
   - Select: Field → field_hero_video
   - Label: Hidden
   - Block settings → CSS classes: "position-absolute top-0 start-0 w-100 h-100 object-fit-cover"

2. "Add block" in region
   - Select: Field → Title
   - Label: Hidden
   - Block settings → CSS classes: "position-relative text-white display-1 fw-bold mb-4 text-center"

3. "Add block" in region
   - Select: Field → field_cta_button
   - Label: Hidden
   - Block settings → CSS classes: "btn btn-primary btn-lg px-5 py-3"

Save section

---

## SECTION 2: Product Grid

Click "Add section"
- Select: "Three column" (bootstrap_layout_builder:blb_col_3)
- Section label: "Featured Products"

Section settings:
- Container: container
- Section classes: (leave empty)
- Breakpoints:
  * Desktop: blb_col_4_4_4 (three equal columns)
  * Tablet: blb_col_6_6 (two columns, third wraps)
  * Mobile: blb_col_12 (one column, stack)
- Remove gutters: No (keep Bootstrap gutters)

Section Bootstrap Styles:
- Padding → class: p-5
- Margin → class: my-5
- Background color → class: bg-light

Add components:
1. In FIRST column:
   - "Add block"
   - Select: Views → Featured Products
   - Display: Block (first 1 item)
   - Block settings → Layout Builder Styles: card_style
   
2. In SECOND column:
   - Same as first (view will show item 2)
   
3. In THIRD column:
   - Same as first (view will show item 3)

**IMPORTANT:** If Views block shows ALL items in one column, create 3 separate Views blocks with offset:
- Block 1: Items to display: 1, Offset: 0
- Block 2: Items to display: 1, Offset: 1
- Block 3: Items to display: 1, Offset: 2

Save section

---

## SECTION 3: Specifications

Click "Add section"
- Select: "Two column" (bootstrap_layout_builder:blb_col_2)
- Section label: "Technical Specifications"

Section settings:
- Container: container
- Breakpoints:
  * Desktop: blb_col_9_3 (75% / 25%)
  * Tablet: blb_col_6_6 (50/50)
  * Mobile: blb_col_12 (stack)

Add components:
1. In LEFT column (blb_region_col_1):
   - "Add block"
   - Select: Quicktabs → technical_specifications
   - Label: "Technical Specifications"
   - Block settings → Layout Builder Styles: nav_nav_tabs
   
2. In RIGHT column (blb_region_col_2):
   - "Add block"
   - Select: Field → field_installation_guide
   - Label: "Installation Guide"
   
   - "Add block"
   - Select: Field → field_certificates
   - Label: "Certificates"

Save section

---

## FINAL STEPS

1. Click "Save layout"
2. Clear cache: `ddev drush cr`
3. Test: Visit product page
4. Report back if anything looks wrong
````

### **Steg 5: Custom CSS (Om behövs)**

```markdown
**CUSTOM CSS NEEDED**

File: `/web/themes/custom/tritonled_radix/css/components/hero.css`

```css
/* Hero overlay gradient */
.layout-builder__layout .hero-section::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    rgba(0, 0, 0, 0.3), 
    rgba(0, 0, 0, 0.6)
  );
  pointer-events: none;
}

/* Ensure hero content is above overlay */
.hero-section > .layout__region > * {
  position: relative;
  z-index: 1;
}
```

Add to theme library:
File: `/web/themes/custom/tritonled_radix/tritonled_radix.libraries.yml`

```yaml
hero-section:
  css:
    component:
      css/components/hero.css: {}
```

Attach via preprocess (if needed):
File: `/web/themes/custom/tritonled_radix/tritonled_radix.theme`

```php
function tritonled_radix_preprocess_commerce_product(&$variables) {
  if ($variables['view_mode'] == 'full') {
    $variables['#attached']['library'][] = 'tritonled_radix/hero-section';
  }
}
```

Stefan: Run `ddev drush cr` after adding CSS.
```

### **Steg 6: Visual Testing (Claude)**

```javascript
// Stefan: "Test it"

// Claude exporterar logs först
"Stefan, kör först: ddev exec /var/www/html/scripts/export-drupal-status.sh"

// Claude läser logs
Filesystem:read_text_file('web/sites/default/files/logs/summary.json')

// Claude testar visuellt
puppeteer_navigate('http://tritonled.ddev.site/product/1')
puppeteer_screenshot(1920, 1080, 'product-desktop')
puppeteer_screenshot(768, 1024, 'product-tablet')
puppeteer_screenshot(375, 667, 'product-mobile')

// Claude rapporterar
"VISUAL TEST RESULTS:

✅ Hero section:
   - Video background works
   - Text overlay visible
   - CTA button centered

✅ Product grid:
   - 3 columns on desktop
   - 2 columns on tablet
   - 1 column on mobile
   - Cards have shadow and rounded corners

✅ Specifications:
   - Tabs work (nav_nav_tabs style)
   - Downloads visible in sidebar
   - Responsive: stacks on mobile

⚠️ Minor issue:
   - Hero gradient could be darker (adjust CSS)

🎨 Custom CSS used: 15 lines (hero gradient only)
📊 Bootstrap coverage: 95%
🚫 SDC needed: 0%"
```

---

## 🎨 Layout Builder Styles Reference

### **Tillgängliga Styles:**

Från `/config/sync/layout_builder_styles.style.*`:

```yaml
# Cards
card_style: 'p-3 bg-white shadow-sm rounded-2'

# Shadows
shadow: 'shadow'
shadow_sm: 'shadow-sm'

# Backgrounds
bg_white: 'bg-white'

# Borders
border_light: 'border border-light'
border_rounded_1px: 'border rounded'
rounded_2: 'rounded-2'
rounded_3: 'rounded-3'

# Spacing
p_4: 'p-4'
mb_3: 'mb-3'
mb_4: 'mb-4'
mb_5: 'mb-5'
mt_3: 'mt-3'

# Navigation
nav_nav_tabs: 'nav nav-tabs'
nav_nav_tabs_section: 'nav nav-tabs' # för section
nav_item: 'nav-item'

# Utilities
overflow_hidden: 'overflow-hidden'
gallery_wrapper: 'gallery-wrapper'
```

### **Skapa Ny Style (Om behövs):**

```yaml
# File: /config/sync/layout_builder_styles.style.hero_section.yml
uuid: <generated>
langcode: en
status: true
dependencies: {}
id: hero_section
label: 'Hero Section'
classes: 'position-relative vh-100 d-flex align-items-center justify-content-center'
type: section  # or 'component' for blocks
group: default
block_restrictions: {}
layout_restrictions: {}
weight: 0
```

Import: `ddev drush cim -y`

---

## 🔧 Bootstrap Layout Builder Options

### **Container Types:**

- `container` - Fixed width (responsive)
- `container-fluid` - Full width
- `container-{breakpoint}` - Responsive container

### **Column Configurations:**

**Two column:**
- `blb_col_12` - Full width (100%)
- `blb_col_6_6` - Equal (50/50)
- `blb_col_9_3` - Sidebar (75/25)
- `blb_col_3_9` - Reverse sidebar (25/75)
- `blb_col_8_4` - Content/sidebar (66/33)
- `blb_col_4_8` - Reverse (33/66)

**Three column:**
- `blb_col_4_4_4` - Equal (33/33/33)
- `blb_col_6_3_3` - Featured (50/25/25)
- `blb_col_3_6_3` - Center featured (25/50/25)

**Four column:**
- `blb_col_3_3_3_3` - Equal (25/25/25/25)
- `blb_col_6_2_2_2` - Featured (50/16/16/16)

### **Remove Gutters:**

- `0` - Keep Bootstrap gutters (default: g-3)
- `1` - Remove gutters (no spacing between columns)

---

## 💡 Best Practices

### **DO:**

✅ Use Bootstrap utilities FIRST
✅ Use Layout Builder Styles for common patterns
✅ Use responsive breakpoints (desktop/tablet/mobile)
✅ Test on all breakpoints
✅ Keep custom CSS minimal (< 50 lines per component)
✅ Use Views for dynamic content lists
✅ Use field blocks for static content

### **DON'T:**

❌ Create SDC components (Bootstrap räcker nästan alltid)
❌ Use inline styles (use classes)
❌ Hardcode content in templates
❌ Skip responsive testing
❌ Forget to clear cache after changes
❌ Use Layout Builder Styles for one-off cases (use block_attributes.class instead)

### **When to use what:**

**Layout Builder Styles:**
- Reusable patterns (cards, sections, nav)
- Team-wide standards
- Multiple instances

**Block attributes.class:**
- One-off styling
- Page-specific tweaks
- Quick adjustments

**Custom CSS:**
- Complex interactions (hover effects)
- Gradients and overlays
- Animations
- When Bootstrap doesn't have it

---

## 📚 Related Documentation

- `/docs/04-workflows/design-testing.md` - Testing workflow
- `/docs/02-standards/design-system.md` - Bootstrap standards
- `/docs/04-workflows/ai-agents-workflow.md` - AI agents for content creation

---

## 🎯 Summary

**Claude's Role:**
1. Analyze design → Bootstrap mapping
2. Create supporting content (Views, fields) via AI agents
3. Give detailed UI instructions for Layout Builder
4. List exact CSS classes (Bootstrap + Layout Builder Styles)
5. Create minimal custom CSS if needed
6. Visual testing with Puppeteer

**Stefan's Role:**
1. Follow UI instructions in Layout Builder
2. Place blocks in regions
3. Apply CSS classes via Block Class / Layout Builder Styles
4. Run cache clear
5. Report results

**Result:**
- Fast iteration (minutes, not hours)
- Minimal custom code (Bootstrap First)
- Maintainable (GUI-based)
- Testable (automated visual tests)

---

**Version**: 1.0  
**Författare**: Stefan + Claude  
**Senast uppdaterad**: 2025-01-11
