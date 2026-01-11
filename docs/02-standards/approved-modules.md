# Godkända Moduler - TritonLED

## 🎯 Modullista (Prioritetsordning)

**Principen**: Använd alltid högst prioriterad modul för ett problem.

---

## Layout & Display

### 1. Layout Builder (Core) ⭐ PRIORITET 1
**Vad**: Flexibel page builder för landing pages och custom layouts
**Använd för**: 
- Landing pages
- Per-content layouts
- Block-placering per sida

**Installation**: Ingår i Drupal Core
```bash
drush en layout_builder -y
```

### 2. Bootstrap Layout Builder ⭐ PRIORITET 2
**Vad**: Bootstrap Grid integration med Layout Builder
**Använd för**:
- Responsive grid layouts
- Bootstrap columns via UI
- Pre-made Bootstrap sections

**Installation**:
```bash
composer require drupal/bootstrap_layout_builder
drush en bootstrap_layout_builder -y
```

### 3. Display Suite ⭐ PRIORITET 2
**Vad**: Field placement och layout manager
**Använd för**:
- Field ordering
- CSS classes på fields
- Field groups
- Custom view modes

**Installation**:
```bash
composer require drupal/ds
drush en ds -y
```

**⚠️ VARNING**: Kan blockera Commerce AJAX om felanvänt. Testa alltid!

### 4. Block Class ⭐ PRIORITET 3
**Vad**: Lägg Bootstrap-klasser på blocks via UI
**Använd för**:
- `mb-4`, `shadow`, `card` etc på blocks
- Undvik custom CSS

**Installation**:
```bash
composer require drupal/block_class
drush en block_class -y
```

### 5. Field Group
**Vad**: Gruppera fields visuellt
**Använd för**:
- Fieldsets
- Accordions
- Tabs
- Collapsible groups

**Installation**:
```bash
composer require drupal/field_group
drush en field_group -y
```

---

## Commerce (E-handel)

### 1. Commerce (Suite) ⭐ OBLIGATORISK
**Vad**: Komplett e-handelssystem
**Moduler**:
- commerce
- commerce_cart
- commerce_checkout
- commerce_order
- commerce_payment
- commerce_price
- commerce_product

**Installation**:
```bash
composer require drupal/commerce
drush en commerce commerce_product commerce_cart commerce_order -y
```

### 2. Commerce Shipping
**Använd för**: Fraktberäkning (om applicable)
```bash
composer require drupal/commerce_shipping
drush en commerce_shipping -y
```

### 3. Commerce Tax
**Använd för**: Momshantering
```bash
composer require drupal/commerce_tax
drush en commerce_tax -y
```

### 4. Commerce Promotion
**Använd för**: Rabatter, kampanjer
```bash
composer require drupal/commerce_promotion
drush en commerce_promotion -y
```

---

## Media & Images

### 1. Focal Point ⭐ PRIORITET 1
**Vad**: Kontrollera crop-punkt på bilder
**Använd för**: 
- Produktbilder
- Hero images
- Responsive images med konsistent fokus

**Installation**:
```bash
composer require drupal/focal_point
drush en focal_point -y
```

**Se**: `/docs/03-solutions/responsive-images.md`

### 2. Slick Carousel
**Vad**: Image/media sliders
**Använd för**:
- Produktbildsgallerier
- Hero carousels
- Multiple images på produkter

**Installation**:
```bash
composer require drupal/slick drupal/slick_views
drush en slick slick_ui -y
```

### 3. Media (Core)
**Vad**: Centraliserad media hantering
**Använd för**:
- Images
- Videos
- Documents
- Remote media (YouTube, Vimeo)

**Installation**: Ingår i Core
```bash
drush en media media_library -y
```

### 4. Image Widget Crop (optional)
**Vad**: Crop images direkt vid upload
**Använd för**: När Focal Point inte räcker

```bash
composer require drupal/image_widget_crop
drush en image_widget_crop -y
```

---

## SEO & Performance

### 1. Metatag ⭐ PRIORITET 1
**Vad**: Meta tags (title, description, Open Graph, etc)
**Använd för**: SEO på alla content types

**Installation**:
```bash
composer require drupal/metatag
drush en metatag -y
```

### 2. Pathauto ⭐ PRIORITET 1
**Vad**: Automatiska URL-alias
**Använd för**: `/produkter/[product-name]` istället för `/product/123`

**Installation**:
```bash
composer require drupal/pathauto
drush en pathauto -y
```

**Exempel pattern:**
```
Product: /produkter/[commerce_product:title]
Node (article): /artiklar/[node:title]
```

### 3. Redirect
**Vad**: 301-redirects
**Använd för**: 
- URL-ändringar
- Automatiska redirects vid URL-change

**Installation**:
```bash
composer require drupal/redirect
drush en redirect -y
```

### 4. Simple Sitemap
**Vad**: XML sitemap för Google
**Använd för**: SEO crawling

**Installation**:
```bash
composer require drupal/simple_sitemap
drush en simple_sitemap -y
```

---

## Content Management

### 1. Views (Core) ⭐ OBLIGATORISK
**Vad**: Listor, filters, sortering
**Använd för**:
- Produktlistor
- Blogglistor
- Dynamiska listor
- Exposed filters

**Installation**: Ingår i Core
```bash
drush en views views_ui -y
```

### 2. Webform ⭐ PRIORITET 1
**Vad**: Formulär (contact, quote request, etc)
**Använd för**:
- Kontaktformulär
- Quote requests
- Lead generation

**Installation**:
```bash
composer require drupal/webform
drush en webform webform_ui -y
```

### 3. Paragraphs (UNDVIK - använd Layout Builder)
**Varför UNDVIK**: Layout Builder är bättre för TritonLED's behov

**Använd endast om**: 
- Mycket komplex nested content
- Efter diskussion med Stefan

### 4. Entity Reference Revisions
**Vad**: Referenced content med revisioner
**Använd för**: När Paragraphs verkligen behövs

---

## Admin & UX

### 1. Admin Toolbar (Core i D11)
**Vad**: Dropdown admin menu
**Installation**: Ingår i Core
```bash
drush en admin_toolbar admin_toolbar_tools -y
```

### 2. Gin Admin Theme ⭐ REKOMMENDERAD
**Vad**: Modern admin theme
**Installation**:
```bash
composer require drupal/gin
drush en gin -y
drush config-set system.theme admin gin -y
```

### 3. Gin Toolbar
**Vad**: Modern toolbar för Gin
**Installation**:
```bash
composer require drupal/gin_toolbar
drush en gin_toolbar -y
```

---

## Development (endast lokal DDEV)

### 1. Devel ⭐ OBLIGATORISK (lokal)
**Vad**: Debugging, kint(), development helpers
**Installation**:
```bash
composer require drupal/devel --dev
drush en devel kint -y
```

**⚠️ ALDRIG på production!**

### 2. Stage File Proxy
**Vad**: Proxy files från production (undvik download hela files/)
**Installation**:
```bash
composer require drupal/stage_file_proxy --dev
drush en stage_file_proxy -y
drush config-set stage_file_proxy.settings origin "https://tritonled.se" -y
```

### 3. Twig Tweak
**Vad**: Extra Twig-funktioner
**Använd för**: Debugging, custom rendering

**Installation**:
```bash
composer require drupal/twig_tweak
drush en twig_tweak -y
```

---

## Import & Migration

### 1. Feeds ⭐ REKOMMENDERAD
**Vad**: Import från JSON, CSV, XML
**Använd för**: TritonLED product import

**Installation**:
```bash
composer require drupal/feeds
drush en feeds -y
```

### 2. Migrate API (Core)
**Vad**: Advanced migrations
**Använd för**: Komplex data transformation

**Installation**: Ingår i Core
```bash
drush en migrate migrate_tools -y
```

---

## ⛔ UNDVIK / ERSÄTT MED

### Föråldrade eller redundanta moduler:

| Modul | Varför undvika | Använd istället |
|-------|----------------|-----------------|
| Panels | Föråldrad | Layout Builder |
| Context | Redundant | Layout Builder + Block visibility |
| Display Suite Layouts | Begränsad | Layout Builder + Bootstrap Layout Builder |
| Page Manager | Komplex | Layout Builder |
| Custom Breadcrumbs | Ofta onödigt | Easy Breadcrumb (om behövs) |

---

## 🔍 Innan du installerar ny modul

**Checklist:**

- [ ] Finns modulen på drupal.org?
- [ ] Drupal 11 kompatibel?
- [ ] Aktivt underhållen? (senaste commit <6 månader)
- [ ] Många installationer? (>10,000)
- [ ] Finns säkerhetsuppdateringar?
- [ ] Finns dokumentation?
- [ ] Testat i dev/DDEV först?

**Sök:**
```
https://www.drupal.org/project/project_module
Filter: Drupal 11
Sort: Most installed
```

**Verifiera säkerhet:**
```bash
ddev drush pm:security
```

---

## 📦 Composer Best Practices

### Installera alltid via Composer:
```bash
# GÖR (Composer)
composer require drupal/[module_name]

# GÖR INTE (manual download)
# Ladda aldrig ned .zip och packa upp manuellt
```

### Version constraints:
```bash
# Rekommenderat: Compatible releases
composer require drupal/[module]:^2.0

# Undvik: Specifik version (svårt att uppdatera)
composer require drupal/[module]:2.0.1
```

### Dev-dependencies:
```bash
# Development only (devel, stage_file_proxy)
composer require drupal/devel --dev
```

---

## 🧪 Testing ny modul

**Process:**

1. **Installera i DDEV**
```bash
composer require drupal/[module]
drush en [module] -y
drush cr
```

2. **Testa funktionalitet**
- Gör vad modulen ska göra?
- Konflikt med befintliga moduler?
- Påverkar prestanda?

3. **Exportera config**
```bash
drush cex -y
git add config/sync/
git commit -m "Add [module] module"
```

4. **Dokumentera**
- Uppdatera denna fil om modulen blir standard
- Lägg till i `04-workflows/` om special workflow

---

**Version**: 1.0  
**Skapad**: 2025-01-10  
**Uppdaterad**: 2025-01-10  
**Författare**: Stefan + Claude
