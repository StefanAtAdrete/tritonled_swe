# Design Testing Framework

**Skapad**: 2025-01-10  
**Syfte**: Systematisk testning av design-implementationer

---

## 🎯 Design Implementation Hierarchy

**KRITISK ORDNING - följ ALLTID:**

### 1. Bootstrap klasser FÖRST
- Använd Bootstrap 5.3 utility classes
- Grid system (row, col-*)
- Spacing (m-*, p-*)
- Typography (h1-h6, lead, text-*)
- Components (card, carousel, accordion)

**Exempel:**
```html
<!-- GÖR DETTA -->
<div class="row g-4">
  <div class="col-md-6">
    <div class="card">...</div>
  </div>
</div>

<!-- INTE custom CSS/SDC om Bootstrap räcker -->
```

### 2. Drupal Core funktioner
**Responsive Media:**
- Responsive Image module (core)
- Image styles (core)
- Breakpoints (theme definition)
- View modes (core)
- Media entities (core)

**Innan SDC - kolla:**
- Finns view mode för detta? (teaser, full, card)
- Finns image style? (large, medium, thumbnail)
- Kan formatters lösa det? (image formatter, media formatter)

### 3. Kan Core klara det utan kodning?
**Frågor att ställa:**
- Kan **Layout Builder** placera detta? (förstagansval för page layout)
- Kan **Display Suite** formattera det?
- Kan **Views** lista det?
- Kan **Field Groups** gruppera det?
- Kan **Block Class** lägga klasser?

**Layout Builder är primär layoutverktyg:**
- Drag-and-drop sektioner
- Placera blocks & fields visuellt
- Per-page eller per-content-type layouts
- Bootstrap-kompatibelt via Block Class

**OM JA → Använd Core/Moduler. INGEN kod!**

### 4. Views + Templates (sista utväg)
**Endast OM:**
- Bootstrap klasser inte räcker
- Core formatters inte räcker
- Modularitet måste bibehållas
- Återanvändbarhet krävs

**DÅ:**
1. Skapa View för data
2. Eventuellt SDC-template för rendering
3. Testa att modularitet behålls

---

## ✅ Design Testing Checklist

### Pre-Implementation Check
- [ ] **Exportera Drupal logs** (se [Log Export](#-drupal-status--logs))
- [ ] **Claude läser summary**: Inga errors? ✅ Errors? Fix först! ❌
- [ ] Analyserat design
- [ ] Identifierat Bootstrap-komponenter
- [ ] Listat vilka core funktioner som används
- [ ] Verifierat att SDC INTE behövs
- [ ] Plan godkänd av Stefan

### Visual Testing
- [ ] **Desktop** (≥1200px)
- [ ] **Tablet** (768-1199px)  
- [ ] **Mobile** (320-767px)
- [ ] **Retina/HiDPI** displays

### Browser Testing
- [ ] **Chrome** (latest)
- [ ] **Firefox** (latest)
- [ ] **Safari** (latest - Mac/iOS)
- [ ] **Edge** (latest)

### Responsive Media Testing
- [ ] Images använder responsive image styles
- [ ] Correct image style per breakpoint
- [ ] Aspect ratios maintained
- [ ] Focal Point respected (if used)
- [ ] Lazy loading active
- [ ] No layout shift (CLS < 0.1)

### Responsive Behavior
- [ ] Text scales appropriately
- [ ] Navigation collapses on mobile
- [ ] Buttons accessible (min 44x44px)
- [ ] Forms usable on touch devices
- [ ] No horizontal scroll
- [ ] Content reflows naturally

### Bootstrap Integration
- [ ] Only Bootstrap classes used (no custom CSS if possible)
- [ ] Grid system used correctly
- [ ] Spacing utilities consistent (m-*, p-*)
- [ ] Components used as-is (card, carousel, etc)
- [ ] Responsive utilities (d-none, d-md-block)

### Accessibility (WCAG 2.1 AA)
- [ ] Color contrast ≥4.5:1 (text)
- [ ] Color contrast ≥3:1 (UI components)
- [ ] Keyboard navigation works
- [ ] Focus indicators visible
- [ ] Screen reader compatible
- [ ] Alt text on all images
- [ ] Proper heading hierarchy (h1→h2→h3)
- [ ] Form labels associated
- [ ] ARIA labels where needed

### Performance
- [ ] **LCP** < 2.5s (Largest Contentful Paint)
- [ ] **CLS** < 0.1 (Cumulative Layout Shift)
- [ ] **FID** < 100ms (First Input Delay)
- [ ] Images optimized (WebP if possible)
- [ ] Images lazy-loaded
- [ ] CSS/JS minified
- [ ] No render-blocking resources
- [ ] Proper caching headers

---

## 🤖 Automated Visual Testing (Claude)

**Claude har tillgång till Puppeteer (headless Chrome) för automated testing.**

### Workflow: Efter Design Implementation

**1. Navigate to page:**
```javascript
puppeteer_navigate → http://tritonled.ddev.site/[page]
```

**2. Screenshot all breakpoints:**
```javascript
// Desktop
puppeteer_screenshot(width: 1920, height: 1080, name: 'desktop-[feature]')

// Tablet  
puppeteer_screenshot(width: 768, height: 1024, name: 'tablet-[feature]')

// Mobile
puppeteer_screenshot(width: 375, height: 667, name: 'mobile-[feature]')
```

**3. Verify JavaScript loads:**
```javascript
puppeteer_evaluate → Check Bootstrap, jQuery, Drupal loaded
```

**4. Check for errors:**
```javascript
// Console errors
// Missing resources
// Failed AJAX requests
```

### Example: Homepage Test

```javascript
// 1. Navigate
puppeteer_navigate('http://tritonled.ddev.site')

// 2. Screenshots
puppeteer_screenshot(1920, 1080, 'desktop-homepage')
puppeteer_screenshot(768, 1024, 'tablet-homepage')
puppeteer_screenshot(375, 667, 'mobile-homepage')

// 3. Verify libraries
puppeteer_evaluate(`
  const results = {
    hasBootstrap: typeof bootstrap !== 'undefined',
    bootstrapVersion: bootstrap?.Tooltip?.VERSION,
    hasDrupal: typeof Drupal !== 'undefined',
    hasErrors: window.console?.errors
  };
  results;
`)
```

### Visual Test Report Template

```markdown
## Visual Test: [Feature Name]
**Date**: YYYY-MM-DD
**URL**: http://tritonled.ddev.site/[path]

### Screenshots
- Desktop: ✅ [link]
- Tablet: ✅ [link]
- Mobile: ✅ [link]

### JavaScript Libraries
- Bootstrap: ✅ 5.3.x loaded
- jQuery: ✅ 3.x loaded  
- Drupal: ✅ loaded

### Issues Found
- [ ] None ✅
- [ ] Bootstrap JS missing ⚠️
- [ ] Console errors ❌
- [ ] Layout breaks on mobile ❌

### Recommendation
[Action items if issues found]
```

### Integration with Design Workflow

**After every design implementation:**

1. **Stefan:** Implement design (Bootstrap + Layout Builder)
2. **Stefan:** "Claude, test [page] visually"
3. **Claude:** Runs automated tests
4. **Claude:** Shows screenshots + reports issues
5. **Stefan:** Reviews + approves OR requests fixes
6. **Claude:** Re-tests after fixes

### Benefits

- **Fast**: Screenshots in seconds
- **Consistent**: Same test every time
- **Multi-breakpoint**: Desktop, tablet, mobile
- **Error detection**: JS errors, missing libraries
- **Documentation**: Screenshots saved for reference

---

## 🧪 Testing Tools

### Browser DevTools
```bash
# Chrome DevTools
F12 → Device Mode → Test all breakpoints

# Firefox Responsive Design Mode
Ctrl+Shift+M → Test devices

# Safari Web Inspector
Cmd+Option+I → Responsive Design Mode
```

### Lighthouse (Chrome)
```bash
1. Open DevTools (F12)
2. Lighthouse tab
3. Run audit:
   - Performance
   - Accessibility
   - Best Practices
   - SEO

Target scores:
- Performance: >90
- Accessibility: 100
- Best Practices: >90
- SEO: 100
```

### Accessibility Testing
**Browser Extensions:**
- **axe DevTools** (most comprehensive)
- **WAVE** (visual feedback)
- **Lighthouse** (built-in Chrome)

**Screen Readers:**
- **NVDA** (Windows, free)
- **JAWS** (Windows, commercial)
- **VoiceOver** (Mac/iOS, built-in)
- **TalkBack** (Android, built-in)

### Visual Regression (Future)
```bash
# BackstopJS (to be implemented)
npm install -g backstopjs
backstop init
backstop test
```

---

## 📸 Design Screenshot Protocol

**When providing design screenshots to Claude:**

### Required Information
1. **Full page context** (not just isolated sections)
2. **Breakpoint variants** (mobile, tablet, desktop versions)
3. **State variations** (hover, active, focus, disabled)
4. **Spacing annotations** (if not following Bootstrap defaults)
5. **Color values** (hex codes if custom)

### Good Screenshot Example
```
✅ Shows full page
✅ Annotations for spacing (if custom)
✅ Includes mobile variant
✅ Notes which Bootstrap components to use
```

### Bad Screenshot Example
```
❌ Only shows one section
❌ No mobile variant
❌ Unclear about spacing
❌ Suggests custom CSS instead of Bootstrap
```

---

## 🎨 Design Analysis Process

### Claude's Analysis Template

When receiving design:

```markdown
## 🔍 Design Analysis

### Bootstrap Components Identified
- Hero: Bootstrap carousel (native)
- Cards: Bootstrap card component (native)
- Grid: Bootstrap row/col system

### Core Drupal Functions Needed
- Responsive images: image styles (large, medium, thumbnail)
- View modes: teaser, full
- Views: product listing
- Layout Builder: section placement

### Implementation Strategy
1. Configure image styles (Admin UI)
2. Configure view modes (Admin UI)
3. Create Views (Admin UI)
4. Use Layout Builder for placement
5. Apply Bootstrap classes via Block Class module

### Code Needed
- NONE ✅ (All via config + Bootstrap)

OR

- Views template ONLY if custom layout required
- Minimal preprocess hook IF attribute removal needed

### Testing Requirements
- [ ] Desktop/Tablet/Mobile
- [ ] All browsers
- [ ] Lighthouse score >90
- [ ] WCAG AA compliant
```

---

## 🔄 Testing Workflow

### After Implementation

**1. Visual Check**
```bash
# Open site
ddev launch

# Test each breakpoint
- Desktop: 1920px, 1440px, 1280px
- Tablet: 1024px, 768px
- Mobile: 414px, 375px, 320px
```

**2. Functionality Check**
- [ ] All links work
- [ ] Forms submit
- [ ] Navigation usable
- [ ] Search works
- [ ] Media loads

**3. Performance Check**
```bash
# Lighthouse audit
- Open DevTools
- Lighthouse tab
- Generate report

# Fix issues until:
- Performance: >90
- Accessibility: 100
```

**4. Cross-browser Check**
- [ ] Chrome (primary dev browser)
- [ ] Firefox (test rendering differences)
- [ ] Safari (test WebKit quirks)
- [ ] Mobile Safari (iOS testing)
- [ ] Chrome Android (Android testing)

**5. Accessibility Check**
```bash
# Keyboard navigation
- Tab through entire page
- All interactive elements reachable
- Focus indicators visible
- No keyboard traps

# Screen reader
- Enable VoiceOver/NVDA
- Navigate page
- All content announced
- Images have alt text
```

---

## 📊 Testing Matrix

### Breakpoint Testing Matrix

| Element | Mobile (320-767) | Tablet (768-1199) | Desktop (≥1200) |
|---------|------------------|-------------------|-----------------|
| Grid | 1 column | 2 columns | 3-4 columns |
| Navigation | Collapsed | Collapsed | Expanded |
| Images | 1:1 ratio | 4:3 ratio | 16:9 ratio |
| Font size | 14-16px base | 16px base | 16-18px base |
| Spacing | Compact (2-3) | Normal (3-4) | Spacious (4-5) |

### Browser Support Matrix

| Browser | Desktop | Mobile | Testing Priority |
|---------|---------|--------|------------------|
| Chrome | ✅ Latest | ✅ Latest | 🔴 High |
| Firefox | ✅ Latest | ✅ Latest | 🔴 High |
| Safari | ✅ Latest | ✅ Latest | 🔴 High |
| Edge | ✅ Latest | ✅ Latest | 🟡 Medium |
| Samsung Internet | N/A | ✅ Latest | 🟡 Medium |

---

## 🚨 Common Issues & Solutions

### Issue 1: Layout breaks on mobile
**Solution:**
- Use Bootstrap responsive classes (d-none d-md-block)
- Check grid col-* breakpoints
- Verify spacing utilities are responsive

### Issue 2: Images cause layout shift
**Solution:**
- Use responsive image styles
- Set aspect ratio in image style config
- Use CSS aspect-ratio on containers
- Remove hardcoded width/height attributes

### Issue 3: Bootstrap conflicts with custom CSS
**Solution:**
- DON'T write custom CSS
- Use Bootstrap utilities instead
- If absolutely needed: scope custom CSS to component

### Issue 4: Component not reusable
**Solution:**
- Use Views for data
- Use View modes for display
- Use Layout Builder for placement
- Last resort: SDC template

---

## 📝 Test Report Template

```markdown
## Design Testing Report

**Page**: [Page name/URL]
**Date**: YYYY-MM-DD
**Tested by**: [Name]

### Implementation Summary
- Bootstrap components used: [list]
- Core Drupal functions: [list]
- Custom code needed: [NONE/minimal preprocess/etc]

### Test Results

#### Visual ✅/❌
- Desktop: ✅
- Tablet: ✅
- Mobile: ✅

#### Browsers ✅/❌
- Chrome: ✅
- Firefox: ✅
- Safari: ✅

#### Performance
- Lighthouse Performance: 94
- LCP: 2.1s
- CLS: 0.05

#### Accessibility
- Lighthouse Accessibility: 100
- axe DevTools: 0 issues
- Keyboard navigation: ✅

### Issues Found
[List any issues with severity]

### Recommendations
[Any improvements needed]
```

---

## 📊 Drupal Status & Logs

**Claude kan analysera Drupal logs för att hitta problem före visual testing.**

### Export Logs (Stefan kör detta)

```bash
# Gör scriptet körbart (en gång)
chmod +x scripts/export-drupal-status.sh

# Exportera logs
ddev exec /var/www/html/scripts/export-drupal-status.sh
```

**Exporteras:**
- Watchdog errors (senaste 50)
- Watchdog warnings (senaste 50)
- PHP errors (senaste 50)
- System status (core:requirements)
- Cache info
- Module status
- Config sync status
- PHP error log (senaste 100 rader)

### Claude Analyserar

**Stefan säger:** "Claude, kolla logs och test"

**Claude kör:**
```javascript
// 1. Läs summary
Filesystem:read_text_file('/Users/steffes/Projekt/tritonled/web/sites/default/files/logs/summary.json')

// 2. Analysera errors
Filesystem:read_text_file('/Users/steffes/Projekt/tritonled/web/sites/default/files/logs/watchdog-errors.json')

// 3. Kolla PHP errors
Filesystem:read_text_file('/Users/steffes/Projekt/tritonled/web/sites/default/files/logs/php-errors.json')

// 4. Rapporterar issues
"Found 3 errors:
- Views plugin warning (low priority)
- Media field undefined (medium priority)
- Missing JS library (high priority - blocks testing)"

// 5. Om HIGH priority: Vänta med visual test
// 6. Om LOW/MEDIUM: Kör visual test, notera issues
```

### Workflow: Testing Med Log Analysis

**Före implementation:**
1. Stefan exporterar logs
2. Claude läser summary
3. **Om errors:** Identifiera & prioritera
4. **Om clean:** Proceed med implementation

**Efter implementation:**
1. Stefan: Code changes + cache clear
2. Stefan: Export logs igen
3. Claude jämför nya vs gamla
4. Claude: Visual test
5. Claude: Rapporterar resultat

### Benefits

- **Proaktiv**: Hittar errors före visual testing
- **Prioritering**: Claude kan triage (high/medium/low)
- **Regression**: Jämför före/efter
- **Dokumentation**: Logs sparade för analys

**Full dokumentation:** `/scripts/README.md`

---

## 📚 Resources

**Bootstrap:**
- Components: https://getbootstrap.com/docs/5.3/components/
- Utilities: https://getbootstrap.com/docs/5.3/utilities/
- Grid: https://getbootstrap.com/docs/5.3/layout/grid/

**Drupal:**
- Responsive Images: https://www.drupal.org/docs/8/mobile-guide/responsive-images-in-drupal-8
- Image Styles: https://www.drupal.org/docs/user_guide/en/structure-image-styles.html
- View Modes: https://www.drupal.org/docs/8/api/entity-api/display-modes-view-modes-and-form-modes

**Testing:**
- Lighthouse: https://developers.google.com/web/tools/lighthouse
- WCAG Guidelines: https://www.w3.org/WAI/WCAG21/quickref/
- axe DevTools: https://www.deque.com/axe/devtools/

---

**Version**: 1.0  
**Författare**: Stefan + Claude  
**Nästa review**: 2025-02-10
