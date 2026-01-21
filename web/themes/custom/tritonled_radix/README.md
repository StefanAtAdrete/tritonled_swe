# TritonLED Radix Theme

Custom Drupal 11 theme for TritonLED based on Radix with Bootstrap 5.3.

## Structure

```
tritonled_radix/
├── components/          # Single Directory Components (SDC)
│   └── [component-name]/
│       ├── component-name.component.yml
│       ├── component-name.twig
│       ├── component-name.css (optional)
│       └── component-name.js (optional)
├── config/             # Theme configuration
├── css/                # Global & component CSS
│   ├── style.css       # Global styles
│   ├── hero.css        # Hero carousel
│   └── components/     # Component-specific CSS
│       ├── media.css
│       └── product-gallery.css
├── js/                 # JavaScript files
│   ├── global.js       # Global behaviors
│   ├── hero-carousel.js
│   └── product-ajax.js
├── images/             # Theme images
├── templates/          # Twig templates (minimal!)
└── tritonled_radix.theme  # Theme functions

```

## Key Features

### Bootstrap 5.3
- Provided by Radix base theme
- Use Bootstrap utilities FIRST before custom CSS
- Grid system: `row`, `col-*`
- Spacing: `m-*`, `p-*`
- Components: `card`, `carousel`, `accordion`, etc.

### Responsive Images
- Breakpoints defined in `tritonled_radix.breakpoints.yml`
- Mobile: <768px
- Tablet: 768-1199px
- Desktop: ≥1200px

### SDC Support
- Components auto-discovered from `components/` directory
- Available in Layout Builder under "Theme Components"
- Follow SDC workflow documented in `/docs/04-workflows/sdc-workflow.md`

## Development Guidelines

### Design Implementation Hierarchy

**Follow this order ALWAYS:**

1. **Bootstrap classes** - 80% can be solved here
2. **Core Drupal functions** - Responsive images, view modes, formatters
3. **Views + minimal templates** - Only if necessary
4. **SDC components** - Last resort (almost never needed)

### CSS

**Preferred:**
```html
<div class="row g-4">
  <div class="col-md-6">
    <div class="card">...</div>
  </div>
</div>
```

**Avoid:**
```css
/* Custom CSS when Bootstrap utilities exist */
.my-grid {
  display: flex;
  gap: 1.5rem;
}
```

### When to Create Custom CSS

- Bootstrap utilities genuinely insufficient
- Brand-specific styling required
- Fine-tuning responsive behavior

**Always:**
- Scope to component (`.product-card__badge`)
- Use Bootstrap variables (`var(--bs-primary)`)
- Document why custom CSS needed

### When to Create SDC

**ONLY if:**
- Bootstrap components insufficient
- Core formatters insufficient
- Modular/reusable component required
- Complex logic needed (slots, conditionals)

**See:** `/docs/04-workflows/sdc-workflow.md`

## Migration from Old Theme

Files to migrate from `web/themes/custom/tritonled/`:

**Keep:**
- `css/hero.css` → `css/hero.css`
- `css/components/*.css` → `css/components/*.css`
- `js/hero-carousel.js` → `js/hero-carousel.js`
- `js/product-ajax.js` → `js/product-ajax.js`
- `images/*` → `images/*`

**Evaluate before migrating:**
- `templates/*` - Check if still needed with Radix + Layout Builder
- Custom CSS - Can Bootstrap utilities replace it?

**Don't migrate:**
- `tritonled.info.yml` (we have new one)
- `tritonled.libraries.yml` (we have new one)
- Bootstrap CDN references (Radix provides it)

## Activation

```bash
# Clear cache
ddev drush cr

# Enable theme
ddev drush theme:enable tritonled_radix -y

# Set as default
ddev drush config:set system.theme default tritonled_radix -y

# Clear cache again
ddev drush cr
```

## Testing

After activation, test:
- [ ] Frontend loads correctly
- [ ] Bootstrap components work (cards, carousel)
- [ ] Layout Builder works
- [ ] Responsive breakpoints work
- [ ] No console errors
- [ ] No missing CSS/JS

## Resources

- Radix Documentation: https://www.drupal.org/project/radix
- Bootstrap 5.3 Docs: https://getbootstrap.com/docs/5.3/
- Design System: `/docs/02-standards/design-system.md`
- SDC Workflow: `/docs/04-workflows/sdc-workflow.md`
- Design Testing: `/docs/04-workflows/design-testing.md`

---

**Version:** 1.0.0  
**Created:** 2025-01-10  
**Base Theme:** Radix 6.0.2  
**Bootstrap:** 5.3 (from Radix)
