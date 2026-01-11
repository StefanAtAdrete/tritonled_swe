# Commerce Beslutsträd

## 🎯 Commerce-specifika problem

Detta träd kompletterar `/docs/DRUPAL-DECISION-TREE.md` med Commerce-specifika lösningar.

**Läs alltid huvudträdet först, sedan detta.**

---

## Problem: Produktvisning & Layout

### ❓ Behövs AJAX-funktionalitet? (varianter)

#### ✅ JA - Produkter med varianter (watt, CCT, etc)

**KRITISKT**: Använd INTE custom product templates!

**Rätt approach:**
1. Layout Builder för sidstruktur
2. Bootstrap Layout Builder för grids
3. Display Suite för field placering
4. Event Subscribers för custom AJAX-beteende

**Varför?**
Commerce AJAX kräver specifik DOM-struktur:
- Klasser: `.product--variation-field--variation_field_[name]__[id]`
- Field injection via JavaScript
- Custom templates förstör denna struktur

**Se lösning**: `/docs/03-solutions/commerce-ajax-solution.md`

#### ❌ NEJ - Enkla produkter utan varianter

**Då är det OK med:**
- Custom templates (ingen AJAX att förstöra)
- Display Suite layouts
- Twig template overrides

---

## Problem: Produktlistor

### Steg 1: Försök Views först

**Views kan:**
- Filtrera på produktattribut
- Visa varianter
- Sortera på pris/titel/datum
- Paginering
- Exposed filters
- Relationer till variations

**Exempel-view:**
```
Structure → Views → Add new view
- View: Commerce Products
- Display: Page or Block
- Filter: Product type = "luminaire"
- Sort: Created date DESC
- Fields: Title, Image, Price, Add to cart
```

### Steg 2: Om Views inte räcker

**Contrib-moduler:**
- **Search API + Facets**: Avancerad filtrering
- **Commerce Product Display**: Förbättrad produktvisning
- **Views Bulk Operations**: Bulk-hantering

**Custom View Mode:**
- Create new view mode: `Structure → Display modes → View modes`
- Configure fields för view mode
- Använd i View

---

## Problem: Checkout & Quote-system

### ⚠️ Vi använder QUOTE-system (EJ direktköp)

**Målgrupp**: Professionella köpare (B2B)
**Flow**: Produkt → Lägg i offertförfrågan → Skicka förfrågan → Manuell offert

### Implementationsalternativ:

#### Alternativ 1: Webform Integration (REKOMMENDERAT)
```
1. Install Webform + Commerce Webform
2. Skapa "Request Quote" webform
3. Koppla till produkter
4. Email till säljare vid submission
```

#### Alternativ 2: Custom Order Type
```
1. Commerce → Configuration → Order types → Add order type
2. Type: "quote_request"  
3. Workflow: draft → submitted → quoted → accepted
4. Event Subscribers för email-notifikationer
```

#### Alternativ 3: Commerce Quote Module
```
composer require drupal/commerce_quote
drush en commerce_quote -y
```
**OBS**: Kolla om modulen finns och är D11-kompatibel först!

### ❌ ALDRIG:
- Ändra Commerce Cart templates direkt
- Förstör checkout-flödet
- Hårdkoda order-logik i templates

---

## Problem: Produktattribut

### Skapa attribut via UI (FÖREDRA)

```
Commerce → Configuration → Attributes → Add attribute

Exempel:
- Watt: 10W, 20W, 30W, 40W
- CCT: 3000K, 4000K, 6000K
- CRI: >80, >90
- IP-rating: IP20, IP44, IP65
```

### Använd attribut i Views
```
Views → Add relationship → Product Variation
Views → Add filter → Attribute: Watt
Views → Exposed filter → User can select
```

### Custom attribut-hantering

**Endast om UI inte räcker:**
- Event Subscriber för validering
- Custom formatters för display
- **ALDRIG** hårdkoda attributvärden i templates

---

## Problem: Produktimport (JSON)

### TritonLED-specifikt: JSON import ~2x dagligen

**Använd:**
- **Feeds module**: `composer require drupal/feeds`
- **Migrate API**: För komplex data-transformation

**Import-flow:**
```
1. JSON endpoint/fil → Feeds/Migrate
2. Map JSON-fält till Commerce-fält
3. Skapa/uppdatera produkter + varianter
4. Cron kör import automatiskt
```

**Konfiguration:**
```
Structure → Feeds → Add Feed Type
- Parser: JSON
- Processor: Product/Variation
- Mappings: JSON-keys → Drupal fields
```

**Exempel mapping:**
```
sku        → SKU (product variation)
title      → Title (product)
watt       → Attribute: Watt
cct        → Attribute: CCT
price      → Price
images[]   → Image (multiple)
```

---

## Problem: Pris-display

### Standard pris-formattering

**Drupal Commerce hanterar:**
- Valuta (SEK, EUR, etc)
- Moms (inkl/exkl)
- Olika priser per variation

**Konfigurera:**
```
Commerce → Configuration → Currencies
Commerce → Configuration → Tax types
```

### Custom pris-display

**Via Field Formatter:**
```
Manage Display → Price field → Format → Commerce Price
- Show/hide currency
- Number of decimals
- Calculate taxes
```

**Event Subscriber för komplex logik:**
```php
// Endast för avancerade behov (volymrabatt, medlemspris, etc)
ProductEvents::FILTER_VARIATIONS
```

---

## Problem: Produktbilder & Media

### Standard approach

**Commerce + Media:**
```
Product → Add field → Reference: Media (Image)
- Multiple values
- Required: No
- Default: First image blir huvudbild
```

**Focal Point för crop:**
- Se: `/docs/03-solutions/responsive-images.md`

### Bildkarusell

**Använd:**
- **Slick Carousel**: `composer require drupal/slick`
- Konfigurera field formatter → Slick Carousel
- Multiple images visas automatiskt i slider

---

## Commerce Modules - Prioritet

### Core Commerce Suite (ALLTID)
- commerce
- commerce_cart
- commerce_checkout
- commerce_order
- commerce_payment
- commerce_price
- commerce_product

### Recommended (OFTAST)
- commerce_shipping (om fysiska produkter)
- commerce_tax (moms)
- commerce_promotion (kampanjer/rabatter)

### Optional (VID BEHOV)
- commerce_stock (lagerhantering)
- commerce_wishlist (önskelistor)
- commerce_recurring (prenumerationer - troligen EJ för TritonLED)

---

## ⚠️ Commerce VARNINGAR

### GÖR INTE:

❌ **Override Commerce templates** utan att förstå AJAX-systemet
❌ **Ändra order workflow** utan att testa checkout-flödet
❌ **Hårdkoda priser** i templates (använd price fields)
❌ **Gör custom cart** (använd Commerce Cart)
❌ **Custom checkout-steg** utan Event Subscribers

### GÖR:

✅ **Använd Commerce's field system**
✅ **Event Subscribers för custom logik**
✅ **Views för produktlistor**
✅ **Rules module** för enkel automation (om behövs)
✅ **Testa checkout-flödet** efter varje ändring

---

## 🧪 Testing - Commerce-specifikt

**Efter Commerce-ändringar:**

### 1. Variation Switching (om AJAX)
- [ ] Välj variant → Pris uppdateras
- [ ] Välj variant → Bild uppdateras
- [ ] Välj variant → Tillgänglighet uppdateras
- [ ] Console: Inga AJAX-errors

### 2. Cart/Quote Flow
- [ ] Lägg produkt i cart/quote
- [ ] Quantity fungerar
- [ ] Remove fungerar
- [ ] Totalsumma korrekt

### 3. Checkout (om applicable)
- [ ] Alla steg visas
- [ ] Validering fungerar
- [ ] Order skapas korrekt
- [ ] Email skickas

### 4. Product Display
- [ ] Alla fält visas
- [ ] Bilder laddar
- [ ] Layout responsiv
- [ ] Add to cart/quote button synlig

---

## 📚 Commerce Resources

**Dokumentation:**
- https://docs.drupalcommerce.org/
- https://www.drupal.org/docs/commerce

**Modules:**
- https://www.drupal.org/project/commerce (main)
- https://www.drupal.org/project/project_module?f[2]=commerce

**Community:**
- Drupal Commerce Slack: #commerce
- Commerce issue queue

---

**Version**: 1.0  
**Skapad**: 2025-01-10  
**Författare**: Stefan + Claude
