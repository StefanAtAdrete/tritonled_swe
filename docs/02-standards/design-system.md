# TritonLED Design System

**Skapad**: 2025-01-10  
**Syfte**: Design standards och Bootstrap 5.3 usage guide

---

## 🎨 Design Philosophy

### Core Principles

1. **Bootstrap First** - Använd Bootstrap utilities före custom CSS
2. **Responsive by Default** - Mobil-först approach
3. **Accessibility** - WCAG 2.1 AA minimum
4. **Performance** - Optimal loading, minimal custom code
5. **Maintainability** - Standard patterns, undvik komplexitet

---

## 🎯 Bootstrap 5.3 Components

### ✅ Components We Use

#### Layout

**Grid System**
```html
<!-- 3-column grid on desktop, 1-column on mobile -->
<div class="container">
  <div class="row g-4">
    <div class="col-md-4">Column 1</div>
    <div class="col-md-4">Column 2</div>
    <div class="col-md-4">Column 3</div>
  </div>
</div>
```

**Container**
```html
<!-- Fixed width container -->
<div class="container">...</div>

<!-- Full width -->
<div class="container-fluid">...</div>

<!-- Responsive breakpoints -->
<div class="container-lg">...</div>
```

#### Components

**Card**
```html
<div class="card">
  <img src="..." class="card-img-top" alt="...">
  <div class="card-body">
    <h5 class="card-title">Card title</h5>
    <p class="card-text">Some text.</p>
    <a href="#" class="btn btn-primary">Go somewhere</a>
  </div>
</div>
```

**Carousel** (för hero)
```html
<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
  <div class="carousel-inner">
    <div class="carousel-item active">
      <img src="..." class="d-block w-100" alt="...">
    </div>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
  </button>
</div>
```

**Accordion**
```html
<div class="accordion" id="accordionExample">
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
        Accordion Item
      </button>
    </h2>
    <div id="collapseOne" class="accordion-collapse collapse show">
      <div class="accordion-body">
        Content here
      </div>
    </div>
  </div>
</div>
```

**Tabs**
```html
<ul class="nav nav-tabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#home">Home</button>
  </li>
</ul>
<div class="tab-content">
  <div class="tab-pane fade show active" id="home" role="tabpanel">
    Content
  </div>
</div>
```

**Modal**
```html
<div class="modal fade" id="exampleModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modal title</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Content
      </div>
    </div>
  </div>
</div>
```

---

## 🎨 Bootstrap Utilities

### Spacing

**Margin & Padding**
```html
<!-- Margins: m-{size} -->
<div class="m-0">No margin</div>
<div class="m-3">Medium margin (1rem)</div>
<div class="m-5">Large margin (3rem)</div>

<!-- Directional: mt/mb/ms/me -->
<div class="mt-3 mb-4">Top 1rem, Bottom 1.5rem</div>

<!-- Padding: p-{size} -->
<div class="p-4">Padding 1.5rem all sides</div>

<!-- Responsive spacing -->
<div class="mt-3 mt-md-5">Small on mobile, large on desktop</div>
```

**Spacing Scale:**
- `0` = 0
- `1` = 0.25rem (4px)
- `2` = 0.5rem (8px)
- `3` = 1rem (16px) - **Default for most spacing**
- `4` = 1.5rem (24px)
- `5` = 3rem (48px)

### Display

```html
<!-- Responsive display -->
<div class="d-none d-md-block">Hidden on mobile, visible on tablet+</div>
<div class="d-block d-md-none">Visible on mobile, hidden on tablet+</div>

<!-- Flex utilities -->
<div class="d-flex justify-content-between align-items-center">
  <div>Left</div>
  <div>Right</div>
</div>
```

### Typography

```html
<!-- Headings -->
<h1 class="display-1">Display 1</h1>
<h2 class="h3">H2 styled as H3</h2>

<!-- Text utilities -->
<p class="text-center">Centered text</p>
<p class="text-muted">Muted text</p>
<p class="fw-bold">Bold text</p>
<p class="fst-italic">Italic text</p>

<!-- Text size -->
<p class="fs-1">Larger text</p>
<p class="fs-6">Smaller text</p>
```

### Colors

```html
<!-- Background -->
<div class="bg-primary text-white">Primary background</div>
<div class="bg-light">Light background</div>

<!-- Text colors -->
<p class="text-primary">Primary color text</p>
<p class="text-success">Success color text</p>
<p class="text-danger">Danger color text</p>
```

---

## 🎨 TritonLED Color Palette

### Primary Colors

```css
/* Bootstrap variables (set in theme) */
--bs-primary: #0066CC;      /* TritonLED Blue */
--bs-secondary: #6C757D;    /* Gray */
--bs-success: #28A745;      /* Green */
--bs-danger: #DC3545;       /* Red */
--bs-warning: #FFC107;      /* Yellow */
--bs-info: #17A2B8;         /* Cyan */
```

### Usage

```html
<!-- Buttons -->
<button class="btn btn-primary">Primary Action</button>
<button class="btn btn-outline-secondary">Secondary Action</button>

<!-- Backgrounds -->
<div class="bg-primary text-white p-4">Primary section</div>

<!-- Text -->
<p class="text-primary">Important text</p>
```

---

## 📐 Breakpoints

### Drupal Theme Breakpoints

**tritonled_radix.breakpoints.yml:**
```yaml
tritonled_radix.mobile:
  label: Mobile
  mediaQuery: 'all and (max-width: 767px)'
  weight: 0
  multipliers:
    - 1x
    - 2x

tritonled_radix.tablet:
  label: Tablet
  mediaQuery: 'all and (min-width: 768px) and (max-width: 1199px)'
  weight: 1
  multipliers:
    - 1x
    - 2x

tritonled_radix.desktop:
  label: Desktop
  mediaQuery: 'all and (min-width: 1200px)'
  weight: 2
  multipliers:
    - 1x
    - 2x
```

### Bootstrap Breakpoints

| Breakpoint | Class | Dimensions |
|------------|-------|------------|
| X-Small | (none) | <576px |
| Small | `sm` | ≥576px |
| Medium | `md` | ≥768px |
| Large | `lg` | ≥992px |
| X-Large | `xl` | ≥1200px |
| XX-Large | `xxl` | ≥1400px |

### Usage

```html
<!-- Responsive columns -->
<div class="col-12 col-md-6 col-lg-4">
  <!-- Full width mobile, half on tablet, third on desktop -->
</div>

<!-- Responsive spacing -->
<div class="mt-3 mt-lg-5">
  <!-- Small margin mobile, large margin desktop -->
</div>

<!-- Responsive display -->
<div class="d-none d-lg-block">
  <!-- Hidden on mobile/tablet, visible on desktop -->
</div>
```

---

## 🖼️ Responsive Images

### Image Styles (Drupal Config)

**Structure → Media → Image styles:**

```yaml
# tritonled_large
- Effect: Scale and crop
- Width: 1200px
- Height: 675px (16:9)

# tritonled_medium  
- Effect: Scale and crop
- Width: 800px
- Height: 600px (4:3)

# tritonled_thumbnail
- Effect: Scale and crop
- Width: 400px
- Height: 400px (1:1)

# tritonled_hero
- Effect: Scale and crop
- Width: 1920px
- Height: 600px (16:5)
```

### Responsive Image Mapping

**Mobile (<768px):**
- Hero: 768x400 (16:9)
- Card: 400x400 (1:1)
- Product: 600x600 (1:1)

**Tablet (768-1199px):**
- Hero: 1200x675 (16:9)
- Card: 600x450 (4:3)
- Product: 800x800 (1:1)

**Desktop (≥1200px):**
- Hero: 1920x600 (16:5)
- Card: 800x600 (4:3)
- Product: 1200x1200 (1:1)

### Implementation

```yaml
# Configure via Admin UI
Structure → Content types → Article → Manage display
  field_image:
    Type: Responsive image
    Responsive image style: responsive_image_style_name
```

---

## 🎯 Component Patterns

### Product Card

```html
<div class="card h-100">
  <img src="..." class="card-img-top" alt="Product name">
  <div class="card-body d-flex flex-column">
    <h5 class="card-title">Product Name</h5>
    <p class="card-text">Short description</p>
    <div class="mt-auto">
      <p class="h4 text-primary mb-3">2,995 kr</p>
      <a href="#" class="btn btn-primary w-100">Request Quote</a>
    </div>
  </div>
</div>
```

### Hero Section

```html
<div class="hero position-relative">
  <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
      <div class="carousel-item active">
        <img src="..." class="d-block w-100" alt="...">
        <div class="carousel-caption">
          <h1 class="display-3">TritonLED</h1>
          <p class="lead">Professional LED Lighting</p>
        </div>
      </div>
    </div>
  </div>
</div>
```

### 50/50 Content Section

```html
<section class="py-5">
  <div class="container">
    <div class="row g-4 align-items-center">
      <div class="col-md-6">
        <img src="..." class="img-fluid rounded" alt="...">
      </div>
      <div class="col-md-6">
        <h2>Section Title</h2>
        <p class="lead">Lead paragraph</p>
        <p>Body text...</p>
        <a href="#" class="btn btn-primary">Learn More</a>
      </div>
    </div>
  </div>
</section>
```

### Product Specifications

```html
<div class="accordion" id="productSpecs">
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#technical">
        Technical Specifications
      </button>
    </h2>
    <div id="technical" class="accordion-collapse collapse show">
      <div class="accordion-body">
        <table class="table table-bordered">
          <tbody>
            <tr>
              <th>Watt</th>
              <td>20W</td>
            </tr>
            <tr>
              <th>CCT</th>
              <td>4000K</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
```

---

## 🚫 Custom CSS Guidelines

### When to Use Custom CSS

**ONLY when:**
- Bootstrap utilities genuinely insufficient
- Specific brand styling required
- Fine-tuning responsive behavior

### Structure

```
css/
├── style.css              # Global overrides (minimal!)
├── components/
│   ├── media.css          # Component-specific
│   └── product-card.css   # Component-specific
└── print.css              # Print styles
```

### Writing Custom CSS

```css
/* GOOD - Scoped to component */
.product-card__custom-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: var(--bs-primary);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 0.25rem;
}

/* BAD - Global override */
.card {
  /* Don't override Bootstrap base classes! */
}

/* GOOD - Use Bootstrap variables */
.custom-element {
  margin: var(--bs-gutter-x);
  padding: var(--bs-spacer);
}

/* BAD - Magic numbers */
.custom-element {
  margin: 23px;  /* Where does 23px come from? */
}
```

---

## ✅ Implementation Checklist

### Before Adding Custom CSS

- [ ] Checked Bootstrap utilities
- [ ] Checked Bootstrap components
- [ ] Verified no existing class does this
- [ ] Confirmed genuinely needed
- [ ] Scoped to component (not global)
- [ ] Uses Bootstrap variables
- [ ] Documented reason for custom CSS

### Before Creating SDC

- [ ] Bootstrap components tested
- [ ] Core formatters tested
- [ ] Views tested
- [ ] Layout Builder tested
- [ ] Custom CSS tested
- [ ] Confirmed modular component needed
- [ ] Approved by Stefan

---

## 📚 Bootstrap Resources

**Official Documentation:**
- Components: https://getbootstrap.com/docs/5.3/components/
- Utilities: https://getbootstrap.com/docs/5.3/utilities/
- Layout: https://getbootstrap.com/docs/5.3/layout/grid/
- Customize: https://getbootstrap.com/docs/5.3/customize/overview/

**Examples:**
- https://getbootstrap.com/docs/5.3/examples/

**Icons:**
- Bootstrap Icons: https://icons.getbootstrap.com/

---

## 🎓 Design System Principles

### 1. Consistency
- Use same spacing scale everywhere (Bootstrap spacing)
- Use same colors (Bootstrap theme colors)
- Use same typography (Bootstrap headings)
- Use same components (Bootstrap components)

### 2. Simplicity
- Fewer custom classes = easier maintenance
- Bootstrap provides 90% of needs
- Custom code only when necessary

### 3. Accessibility
- Bootstrap components are accessible by default
- Test with keyboard navigation
- Test with screen readers
- Maintain WCAG 2.1 AA compliance

### 4. Performance
- Bootstrap loaded from CDN (cached)
- Minimal custom CSS
- Optimize images (responsive image styles)
- Lazy load images

### 5. Maintainability
- Standard patterns = easy to understand
- Bootstrap updates = automatic improvements
- Less custom code = fewer bugs
- Clear documentation

---

**Version**: 1.0  
**Författare**: Stefan + Claude  
**Nästa review**: 2025-02-10
