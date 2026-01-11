# Testing Checklist - TritonLED

## 🎯 Efter VARJE ändring

Oavsett hur liten ändringen är, kör alltid dessa grundläggande tester.

---

## 1️⃣ Cache & Errors

### Cache Rebuild
```bash
# ALLTID först efter ändringar
ddev drush cr

# Specifika caches om behövs
ddev drush cc css-js  # CSS/JS aggregates
ddev drush cc render  # Rendered output
```

### Error Logs
```bash
# Kolla senaste errors
ddev drush watchdog:show --severity=Error --count=20

# Följ log live
ddev logs -f

# Rensa watchdog (om mycket noise)
ddev drush watchdog:delete all
```

### PHP Errors
```
Kolla: /admin/reports/dblog
Filter: Type = php, Severity = Error/Warning
```

---

## 2️⃣ Browser Testing

### Browsers (minst 2)

**Primary:**
- [ ] **Firefox** (latest) - Utvecklingsbrowser

**Secondary:**
- [ ] **Chrome** (latest) - Vanligaste användarbrowser

**Om tid finns:**
- [ ] Safari (Mac)
- [ ] Edge (Windows)

---

### Responsive Breakpoints

**Testa ALLA tre:**

#### Mobile (<768px)
```
Device toolbar: iPhone SE (375px)
eller: 414px (iPhone Pro Max)
```
- [ ] Layout stacks vertikalt
- [ ] Text läsbar (inte för liten)
- [ ] Buttons klickbara (44x44px minimum)
- [ ] Bilder fyller bredden
- [ ] Meny fungerar (hamburger?)
- [ ] Ingen horisontell scroll

#### Tablet (768px-1199px)
```
Device toolbar: iPad (768px)
eller: 1024px (iPad Pro)
```
- [ ] Layout använder 2 kolumner (där applicable)
- [ ] Navigation anpassad
- [ ] Bilder skalas korrekt
- [ ] Forms användbara

#### Desktop (≥1200px)
```
1920px (Full HD) or 1440px (standard desktop)
```
- [ ] Full layout visas
- [ ] Max-width på content (ingen endless text-rad)
- [ ] Images fyller utan distortion
- [ ] All funktionalitet tillgänglig

**Tools:**
```
Chrome/Firefox: F12 → Toggle device toolbar (Ctrl+Shift+M)
```

---

## 3️⃣ JavaScript Console

**Öppna Console (F12):**

### Inga Errors
```
❌ DÅLIGT:
Uncaught TypeError: Cannot read property...
Failed to load resource: 404

✅ BRA:
(inget eller harmlösa warnings)
```

### Vanliga errors att kolla:

- [ ] jQuery undefined (drupal.js laddade?)
- [ ] Commerce AJAX errors (variation switching)
- [ ] 404 på CSS/JS files (aggregation problem?)
- [ ] Mixed content (HTTPS page loading HTTP resource)

**Fix:**
```bash
# Om 404 på aggregates
ddev drush cc css-js
ddev drush cr

# Om mixed content
# Fixa URL i config (använd //, inte http://)
```

---

## 4️⃣ Network Performance

**Chrome DevTools → Network:**

### Sidladdning
- [ ] **Total load time**: <3 sekunder (lokal DDEV)
- [ ] **DOMContentLoaded**: <1 sekund
- [ ] **First Contentful Paint**: <1 sekund

### Resource Sizes
- [ ] **HTML**: <100kb
- [ ] **CSS** (aggregated): <200kb
- [ ] **JS** (aggregated): <300kb
- [ ] **Images**: <500kb each (optimera om större)

### Requests
- [ ] Total requests: <50 (idealiskt <30)
- [ ] Inga 404s
- [ ] Inga 500s

**Optimera om behövs:**
```bash
# Image optimization (om mycket stora bilder)
# Använd ImageMagick, TinyPNG, eller liknande

# CSS/JS aggregation (production)
ddev drush config-set system.performance css.preprocess 1 -y
ddev drush config-set system.performance js.preprocess 1 -y
```

---

## 5️⃣ Commerce-Specifikt (om applicable)

### Produkter med Varianter

#### AJAX Variation Switching
- [ ] Välj variant → Pris uppdateras omedelbart
- [ ] Välj variant → Bild uppdateras (om applicable)
- [ ] Välj variant → SKU visas korrekt
- [ ] Välj variant → Tillgänglighet uppdateras
- [ ] Console: Inga AJAX errors

**Test:**
```
1. Öppna produktsida med varianter
2. F12 → Network tab
3. Välj annan variant
4. Kolla:
   - POST request till /product/{id}/ajax
   - Status: 200 OK
   - Response: JSON data
   - DOM uppdateras visuellt
```

#### Add to Cart/Quote
- [ ] Button synlig och enabled
- [ ] Click → Läggs till (feedback visas)
- [ ] Cart count uppdateras
- [ ] Rätt variant läggs till

---

### Checkout Flow (om applicable)
- [ ] Alla checkout-steg visas
- [ ] Formulär validerar input
- [ ] Fel visas tydligt
- [ ] Order skapas vid completion
- [ ] Confirmation email skickas (kolla logs)

---

## 6️⃣ Forms & Input

### Alla formulär

#### Validation
- [ ] Required fields markerade (asterisk)
- [ ] Submit utan required → Error visas
- [ ] Error messages tydliga
- [ ] Invalid email/format → Error

#### Submission
- [ ] Submit med valid data → Success
- [ ] Success message visas
- [ ] Form rensar (eller behåller data om edit)
- [ ] Sparad data korrekt i DB

#### Edge Cases
- [ ] Mycket lång text (>1000 chars)
- [ ] Special characters (&, <, >, ", ')
- [ ] HTML injection attempt (<script>)
- [ ] SQL injection attempt (' OR 1=1)

**Drupal hanterar automatiskt**, men testa ändå!

---

## 7️⃣ Tillgänglighet (A11y)

### Lighthouse Audit

```
Chrome DevTools → Lighthouse
→ Select: Accessibility
→ Generate report

Mål: Score ≥90
```

**Fixa vanliga problem:**
- Missing alt text → Lägg till på bilder
- Low color contrast → Justera färger
- Missing form labels → Koppla labels till inputs
- Missing ARIA attributes → Lägg till där semantik saknas

---

### Keyboard Navigation

**Tab through page:**
- [ ] Alla interaktiva element nåbara (links, buttons, inputs)
- [ ] Focus outline synlig (inget `outline: none` utan ersättning)
- [ ] Skip to main content link (optional men bra)
- [ ] Modals/dropdowns öppnas med Enter/Space
- [ ] Esc stänger modals

**Test:**
```
Unplugg mouse
Navigera endast med:
- Tab (framåt)
- Shift+Tab (bakåt)
- Enter (aktivera)
- Space (aktivera checkbox/button)
- Esc (stäng modal)
```

---

### Screen Reader (Basic)

**Om tid finns (advanced):**

**Mac: VoiceOver**
```
Cmd+F5 (aktivera)
Ctrl+Option+Right arrow (nästa element)
```

**Windows: NVDA (gratis)**
```
Download: https://www.nvaccess.org/
Insert+Down arrow (läs nästa)
```

**Testa:**
- [ ] Sidrubrik läses upp
- [ ] Länkar har meningsfulla texter (ej "click here")
- [ ] Bilder läses upp (alt text)
- [ ] Form labels läses upp

---

## 8️⃣ Content Editor UX

### Redaktörsperspektiv

**Logga in som editor (ej admin):**

#### Content Creation
- [ ] Kan skapa content utan förvirring
- [ ] Field labels tydliga
- [ ] Help text finns där behövs
- [ ] WYSIWYG fungerar
- [ ] Image upload fungerar

#### Layout Builder (om använt)
- [ ] Kan lägga till sections
- [ ] Kan lägga till blocks
- [ ] Kan redigera blocks
- [ ] Kan ta bort blocks
- [ ] Preview visar korrekt

#### Saving
- [ ] Save fungerar
- [ ] Revision skapas (om enabled)
- [ ] Kan ångra ändringar

---

## 🚨 Kritiska Problem (Stoppa release)

**Om något av dessa inträffar, FIXA INNAN deploy:**

- ❌ **500 Internal Server Error** på någon sida
- ❌ **PHP Fatal Error** i logs
- ❌ **Checkout fungerar inte** (om e-handel)
- ❌ **Data loss** (content försvinner)
- ❌ **Security vulnerability** (XSS, SQL injection fungerar)
- ❌ **Site inaccessible** på mobil
- ❌ **Search fungerar inte** (om critical för site)

---

## ⚠️ Viktiga Problem (Fixa snart)

**Inte akut men bör åtgärdas:**

- ⚠️ **Lighthouse score <80** (performance, a11y)
- ⚠️ **JavaScript errors** i console
- ⚠️ **Slow page load** (>5 sekunder)
- ⚠️ **CSS-layout bruten** på en breakpoint
- ⚠️ **Form validation saknas** på inputs
- ⚠️ **404 errors** på resources (CSS, JS, images)

---

## 📊 Pre-Deploy Checklist

**Innan VARJE deploy till staging/production:**

### Config
```bash
# Export config
ddev drush cex -y

# Verify config in git
git status config/sync/
git diff config/sync/

# Commit config
git add config/sync/
git commit -m "Config export: [what changed]"
```

### Database
```bash
# Create backup
ddev snapshot

# Note snapshot name for rollback
ddev snapshot --list
```

### Code Quality
```bash
# Run coding standards check (om phpcs setup)
vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/custom/ web/themes/custom/

# Run security check
ddev drush pm:security
```

### Testing Matrix

| Test | Passed? | Notes |
|------|---------|-------|
| Cache cleared | ☐ | ddev drush cr |
| No PHP errors | ☐ | ddev logs |
| Firefox OK | ☐ | All pages |
| Chrome OK | ☐ | All pages |
| Mobile (<768px) | ☐ | Layout correct |
| Tablet (768px) | ☐ | Layout correct |
| Desktop (1200px) | ☐ | Layout correct |
| JS Console clean | ☐ | No errors |
| Forms work | ☐ | Validation + submit |
| Commerce AJAX | ☐ | Variants switch |
| A11y score ≥90 | ☐ | Lighthouse |
| Config exported | ☐ | git status clean |
| DB backed up | ☐ | ddev snapshot |

---

## 🔄 Regression Testing

**Efter större ändringar (modul-install, config-ändringar):**

### Full Site Smoke Test

**30-minuters snabbtest:**

1. **Homepage**
   - [ ] Laddar utan errors
   - [ ] Hero section visar korrekt
   - [ ] Navigation fungerar
   
2. **Product page** (random)
   - [ ] Bild visas
   - [ ] Pris visas
   - [ ] Varianter fungerar (AJAX)
   - [ ] Add to cart fungerar
   
3. **Product listing**
   - [ ] Produkter visas
   - [ ] Filters fungerar
   - [ ] Pagination fungerar
   
4. **Cart/Quote**
   - [ ] Items visas
   - [ ] Quantity ändras
   - [ ] Remove fungerar
   - [ ] Checkout startar (om applicable)
   
5. **Contact form**
   - [ ] Formulär visas
   - [ ] Validation fungerar
   - [ ] Submit fungerar
   - [ ] Email skickas (kolla logs)

6. **Search** (om applicable)
   - [ ] Sökning fungerar
   - [ ] Resultat relevanta
   - [ ] Filters fungerar

---

## 🛠️ Testing Tools

### Browser Extensions

**Firefox/Chrome:**
- **WAVE** - Accessibility checker
- **axe DevTools** - A11y testing
- **Wappalyzer** - Tech stack detection
- **Lighthouse** (built-in Chrome)

### Drupal Modules (Development)

```bash
# Devel module (debugging)
composer require drupal/devel --dev
drush en devel kint webprofiler -y

# Stage File Proxy (avoid downloading all files)
composer require drupal/stage_file_proxy --dev
drush en stage_file_proxy -y
```

### Command-line

```bash
# Check for security updates
ddev drush pm:security

# Check for available updates
ddev drush pm:updatestatus

# Show recent log entries
ddev drush watchdog:show --count=50

# Clear specific cache
ddev drush cc [cache-type]
```

---

## 📝 Bug Report Template

**Om du hittar bug, dokumentera:**

```markdown
## Bug: [Kort beskrivning]

**Severity**: Critical / High / Medium / Low

**Steps to reproduce:**
1. Go to /path
2. Click button X
3. Observe error

**Expected behavior:**
Button should do Y

**Actual behavior:**
Error message: "..."

**Environment:**
- Browser: Firefox 120
- Screen size: 1920x1080
- User role: Authenticated
- Date: 2025-01-10

**Screenshots:**
[Attach if applicable]

**Error logs:**
```
[Paste from watchdog or browser console]
```

**Temporary workaround:**
[If any]
```

---

## ✅ Definition of Done

**En feature/uppgift är KLAR när:**

- [ ] Funktionalitet fungerar som specificerat
- [ ] Testad i Firefox + Chrome
- [ ] Testad på mobil + desktop
- [ ] Inga JavaScript errors i console
- [ ] Inga PHP errors i logs
- [ ] Lighthouse score ≥90 (a11y)
- [ ] Code följer Drupal coding standards
- [ ] Config exporterad och committad
- [ ] Dokumentation uppdaterad (om ny feature)
- [ ] Peer review OK (om tillämpligt)
- [ ] Godkänd av Stefan

---

**Version**: 1.0  
**Skapad**: 2025-01-10  
**Författare**: Stefan + Claude
