# Design → Drupal Workflow

## 🎯 Mål

Översätta en design (Figma, PDF, sketch) till fungerande Drupal-implementation med Layout Builder och Bootstrap.

**Princip**: Bootstrap först, custom CSS sist.

---

## 📋 Fas 1: Analys (INNAN kod)

### Steg 1.1: Dela upp designen

**Identifiera sektioner:**
```
┌─────────────────────┐
│ HEADER (global)     │ ← Navbar, logo, meny
├─────────────────────┤
│ HERO                │ ← H1, bild, CTA
├─────────────────────┤
│ CONTENT             │ ← Huvudinnehåll (varierar per sida)
│ - Feature grid      │
│ - Text + Image      │
│ - Testimonials      │
├─────────────────────┤
│ FOOTER (global)     │ ← Copyright, länkar, social
└─────────────────────┘
```

**Dokumentera i Google Doc / Notes:**
```markdown
## Sektioner
1. Header (global) - Navbar with logo + menu
2. Hero - Full-width bg image, H1, subtitle, 2 buttons
3. Features - 3-column grid (cards)
4. About - 2-column (image left, text right)
5. CTA - Centered text + button
6. Footer (global) - 4-column links + copyright
```

---

### Steg 1.2: Identifiera komponenter

**För varje sektion, lista komponenter:**

| Sektion | Komponenter | Bootstrap? | Custom? |
|---------|-------------|------------|---------|
| Hero | H1, H2, Button, Background image | ✅ Yes (display, btn) | ⚠️ BG image CSS |
| Features | Card (image, title, text, link) | ✅ Yes (card) | ❌ No |
| About | Image, Text block | ✅ Yes (row, col) | ❌ No |

**Resultat**: Lista vad som FINNS i Bootstrap vs vad som behöver custom CSS.

---

### Steg 1.3: Identifiera återanvändning

**Globala komponenter** (samma på alla sidor):
- Header/Navbar
- Footer
- Breadcrumbs (om används)

**Återanvända block** (flera sidor men ej alla):
- Hero-sektion (olika text/bild per sida)
- CTA-block (samma layout, olika text)
- Feature cards (samma struktur)

**Sidunika**:
- Specifikt innehåll per content type

**Dokumentera:**
```markdown
## Återanvändning
- Header: Global (Block)
- Footer: Global (Block)
- Hero: Layout Builder section (varierar per sida)
- Feature cards: Custom block type (återanvändbar struktur)
```

---

### Steg 1.4: Bootstrap Components Check

**Gå igenom [Bootstrap 5.3 Components](https://getbootstrap.com/docs/5.3/components/):**

**Finns i design:**
- [ ] Accordion
- [ ] Alerts
- [ ] Badges
- [ ] Breadcrumb
- [ ] Buttons ← ✅ Används
- [ ] Button group
- [ ] Card ← ✅ Används (feature cards)
- [ ] Carousel
- [ ] Close button
- [ ] Collapse
- [ ] Dropdowns
- [ ] List group
- [ ] Modal
- [ ] Navbar ← ✅ Används
- [ ] Navs & tabs
- [ ] Offcanvas
- [ ] Pagination
- [ ] Placeholders
- [ ] Popovers
- [ ] Progress
- [ ] Scrollspy
- [ ] Spinners
- [ ] Toasts
- [ ] Tooltips

**Resultat**: Lista exakt vilka Bootstrap-komponenter som ska användas.

---

## 🗺️ Fas 2: Planering

### Steg 2.1: Skapa Implementation Plan

**Google Doc: "Design Implementation Plan - [Projekt]"**

```markdown
# Hero Section Implementation

## Layout
- Bootstrap: container-fluid (full-width background)
- Inner: container (max-width 1200px för text)
- Flexbox: center content vertically

## Komponenter
- Background image: CSS (background-image, cover)
- Heading (H1): Drupal field (title)
- Subheading (H2): Drupal field (field_subtitle)
- Button (primary): Drupal field (field_cta_link)
- Button (secondary): Drupal field (field_cta_secondary)

## Bootstrap Classes
- `.container-fluid` (outer)
- `.d-flex`, `.align-items-center`, `.justify-content-center`
- `.btn`, `.btn-primary`, `.btn-lg`
- `.text-center`, `.text-white`

## Custom CSS Needed
- Background image overlay (rgba gradient)
- Min-height: 80vh
- Background: cover, center, no-repeat

## Fields Needed (Content Type: Landing Page)
- Title (default node title)
- field_subtitle (Text - Plain)
- field_hero_image (Media - Image)
- field_cta_link (Link)
- field_cta_secondary (Link)

## Layout Builder Structure
Section 1: Hero
  Region: Main
    Block: Node Title
    Block: Node field_subtitle
    Block: Node field_cta_link (rendered as button)
```

**Gör detta för VARJE sektion i designen.**

---

### Steg 2.2: Content Type Planning

**Vilka content types behövs?**

| Content Type | Syfte | Fields | Layout |
|--------------|-------|--------|--------|
| Landing Page | Marketing pages | Hero fields, sections via LB | Layout Builder enabled |
| Product (Commerce) | LED luminaires | Price, variations, specs | Fixed layout (DS) |
| Article | Blogg/news | Body, image, author | Standard |

**Skapa lista:**
```markdown
## Content Types att skapa
1. Landing Page
   - Title
   - field_subtitle
   - field_hero_image
   - Layout Builder enabled
   
2. (Product redan finns via Commerce)
```

---

### Steg 2.3: Prioritera Implementation

**1. Kritiskt (gör FÖRST):**
- Basic layout structure
- Global header/footer
- Main content display

**2. Viktigt (nästa):**
- Responsive behavior
- Forms & CTAs
- Navigation

**3. Nice-to-have (sist):**
- Animationer
- Hover effects
- Extra polish

**Dokumentera:**
```markdown
## Implementation Priorities

### Sprint 1 (Vecka 1)
- [ ] Create Landing Page content type
- [ ] Setup Layout Builder
- [ ] Implement Header block
- [ ] Implement Footer block

### Sprint 2 (Vecka 2)
- [ ] Hero section layout
- [ ] Feature cards (Bootstrap cards)
- [ ] Responsive testing

### Sprint 3 (Vecka 3)
- [ ] Custom CSS for hero BG
- [ ] CTA animations (if needed)
- [ ] Final polish
```

---

## 🏗️ Fas 3: Implementation

### Steg 3.1: Content Type Setup

**Skapa content type:**
```
Structure → Content types → Add content type

Name: Landing Page
Machine name: landing_page

Settings:
☑ Published by default
☑ Promoted to front page: No
☐ Sticky at top of lists: No
☑ Create new revision
```

**Lägg till fields:**
```
Manage fields → Add field

1. field_subtitle
   Type: Text (plain)
   Label: Subtitle
   
2. field_hero_image  
   Type: Reference → Media
   Label: Hero Image
   Allowed: Image
   
3. field_cta_link
   Type: Link
   Label: Call to Action
```

---

### Steg 3.2: Layout Builder Setup

**Aktivera Layout Builder:**
```
Structure → Content types → Landing Page → Manage Display

☑ Enable Layout Builder
☑ Allow each content item to have its layout customized
□ Disable discard changes

Save
```

**Skapa default layout:**
```
Manage display → Manage layout

1. Add section → Bootstrap Grid (2-column 50/50)
   Left: field_hero_image
   Right: Title + field_subtitle + field_cta_link
   
2. Add section → Bootstrap Grid (3-column 33/33/33)
   [Will be filled per-content]

Save layout
```

---

### Steg 3.3: Bootstrap Layout Builder

**Använd Bootstrap Grid:**
```
Layout Builder → Add section
→ Bootstrap Grid

Layouts:
- One column
- Two column (50/50, 67/33, 33/67, 75/25, 25/75)
- Three column (33/33/33, 25/50/25)
- Four column (25/25/25/25)
```

**Column settings:**
```
Click column → Configure
→ Column class: col-md-6 col-lg-4
→ Additional classes: mb-4 d-flex
```

---

### Steg 3.4: Display Suite (Field Styling)

**Field classes:**
```
Manage Display → [Field]
→ Settings (gear icon)
→ CSS classes: btn btn-primary btn-lg
```

**Field wrapper:**
```
→ Wrapper element: div
→ Classes: mb-3 text-center
```

---

### Steg 3.5: Custom CSS (Minimal!)

**Endast för:**
- Brand colors
- Specific spacing Bootstrap saknar
- Background images/overlays
- Custom animations

**themes/tritonled/css/components/hero-section.css:**
```css
/**
 * Hero Section
 * Full-width background image med overlay
 */

.hero-section {
  position: relative;
  min-height: 80vh;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

/* Overlay för läsbarhet */
.hero-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    to bottom,
    rgba(0, 0, 0, 0.3) 0%,
    rgba(0, 0, 0, 0.6) 100%
  );
}

/* Innehåll ovanför overlay */
.hero-section .container {
  position: relative;
  z-index: 1;
}

/* Responsive */
@media (max-width: 767px) {
  .hero-section {
    min-height: 60vh;
  }
}
```

**Ladda CSS:**
```yaml
# themes/tritonled/tritonled.libraries.yml
hero-section:
  css:
    theme:
      css/components/hero-section.css: {}
```

**Attach via preprocess:**
```php
// themes/tritonled/tritonled.theme
function tritonled_preprocess_node__landing_page(&$variables) {
  $variables['#attached']['library'][] = 'tritonled/hero-section';
}
```

---

### Steg 3.6: Responsive Testing

**Browser DevTools (F12 → Toggle device toolbar):**

```
Test breakpoints:
□ 375px (iPhone SE) - Mobile
□ 768px (iPad) - Tablet  
□ 1200px (Desktop)
□ 1920px (Large desktop)
```

**Checklist per breakpoint:**
- [ ] Layout stacks korrekt (columns → rows)
- [ ] Text läsbart (font-size, line-height)
- [ ] Buttons klickbara (min 44x44px touch target)
- [ ] Bilder skalas korrekt
- [ ] Horisontell scroll ALDRIG förekommer

**Bootstrap responsive utilities:**
```html
<!-- Visa bara på desktop -->
<div class="d-none d-lg-block">Desktop only</div>

<!-- Visa bara på mobile -->
<div class="d-block d-lg-none">Mobile only</div>

<!-- Stack på mobile, row på desktop -->
<div class="flex-column flex-lg-row">...</div>
```

---

## 🧪 Fas 4: Testing

### Steg 4.1: Funktionalitet

**Layout:**
- [ ] Alla sektioner visas korrekt
- [ ] Columns rätt antal och bredd
- [ ] Spacing konsekvent (Bootstrap spacing)

**Content:**
- [ ] Alla fields visas
- [ ] Fields kan redigeras
- [ ] Bilder laddar
- [ ] Länkar fungerar

**Forms (om applicable):**
- [ ] Submit fungerar
- [ ] Validering visar errors
- [ ] Success-meddelande visas

---

### Steg 4.2: Prestanda

```bash
# Drupal cache
ddev drush cr

# Check page load time
# Chrome DevTools → Network → Page load
# Mål: <3 sekunder på 3G
```

**Optimeringar:**
- [ ] Images optimerade (<500kb)
- [ ] CSS/JS minifierade (production)
- [ ] Lazy loading på images
- [ ] Kritisk CSS inline (om behövs)

---

### Steg 4.3: Tillgänglighet (A11y)

**Lighthouse audit:**
```
Chrome DevTools → Lighthouse → Accessibility
Mål: Score >90
```

**Manual checks:**
- [ ] Semantic HTML (header, main, footer, nav)
- [ ] Alt-text på ALLA bilder
- [ ] Form labels kopplade till inputs
- [ ] Keyboard navigation (Tab, Enter, Space)
- [ ] Color contrast ≥4.5:1 (WCAG AA)
- [ ] Focus states synliga

**Verktyg:**
- WAVE browser extension
- axe DevTools
- Color contrast checker

---

### Steg 4.4: Cross-Browser

**Test i minst:**
- [ ] Firefox (latest)
- [ ] Chrome (latest)
- [ ] Safari (om Mac tillgänglig)
- [ ] Edge (latest)

**IE11?** ❌ Nej (end of life 2022)

---

## 📝 Fas 5: Dokumentation

### Steg 5.1: Uppdatera projektdokumentation

**Om ny sektion/komponent skapades:**

Lägg till i `/docs/03-solutions/[namn].md`:

```markdown
# Hero Section - Implementation

## Problem
Behövde full-width background med centered content.

## Lösning
- Layout Builder section
- Bootstrap container-fluid + container
- Custom CSS för background-image

## Fields
- field_hero_image (Media)
- field_subtitle (Text)

## CSS
/themes/tritonled/css/components/hero-section.css

## Testing
✅ Responsive (<768px, ≥768px, ≥1200px)
✅ A11y score: 95/100
✅ Cross-browser: Firefox, Chrome

## Datum: 2025-01-10
```

---

### Steg 5.2: Git Commit

**Commit message template:**
```
feat: Add hero section to landing page content type

- Created field_hero_image, field_subtitle
- Configured Layout Builder with Bootstrap Grid
- Added custom CSS for background overlay
- Tested responsive (mobile, tablet, desktop)
- A11y score: 95/100

Resolves: #123
```

**Include:**
```bash
git add config/sync/  # Config changes
git add web/themes/custom/tritonled/css/  # CSS
git add web/themes/custom/tritonled/tritonled.theme  # Preprocess
git commit -m "..."
```

---

### Steg 5.3: Exportera Config

```bash
# Export configuration
ddev drush cex -y

# Verify diff
git diff config/sync/

# Commit
git add config/sync/
git commit -m "Config: Landing page content type and layout"
```

---

## 🎓 Best Practices Sammanfattning

### Bootstrap First
1. Kolla Bootstrap components
2. Använd Bootstrap classes via UI
3. Custom CSS endast om Bootstrap saknar det

### Mobile-First CSS
```css
/* Base: Mobile */
.hero { padding: 1rem; }

/* Tablet up */
@media (min-width: 768px) {
  .hero { padding: 3rem; }
}

/* Desktop up */
@media (min-width: 1200px) {
  .hero { padding: 5rem; }
}
```

### Layout Builder Struktur
```
Page
└── Section 1 (Hero)
    └── Region: Main
        ├── Block: Title
        └── Block: Image
└── Section 2 (Features)
    └── Region: Main (3-col Bootstrap grid)
        ├── Block: Feature 1
        ├── Block: Feature 2
        └── Block: Feature 3
```

### Reusability
- Global blocks → Header, Footer
- Layout Builder → Per-page structure
- Display Suite → Field layouts
- Custom blocks → Återanvändbara komponenter

---

## 🔗 Relaterade Filer

- Theming guide: `/docs/01-decision-trees/theming-decision-tree.md`
- Bootstrap moduler: `/docs/02-standards/approved-modules.md`
- Testing checklist: `/docs/04-workflows/testing-checklist.md`

---

**Version**: 1.0  
**Skapad**: 2025-01-10  
**Författare**: Stefan + Claude
