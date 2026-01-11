# Responsive Images - Focal Point Lösning ✅

**Problem löst**: 2025-01-08  
**Status**: Verifierad lösning  
**Relevans**: Alla bilder (produkter, media, content)

---

## 🔴 Problemet

Focal Point fungerade **inte konsekvent** över olika breakpoints på grund av olika aspect ratios.

### Symptom:

- ❌ Focal Point visade rätt på desktop men fel på mobil
- ❌ Bilder croppade bort viktiga delar (ansikten, produkter)
- ❌ Olika crop på tablet vs desktop vs mobile
- ❌ Designen såg inkonsistent ut

### Root Cause:

**Olika aspect ratios över breakpoints:**
```
Desktop: 16:9 (1200x675)
Tablet:  4:3  (768x576)
Mobile:  1:1  (576x576)
```

**Problem**: Focal Point är en *punkt* (X/Y koordinater). När aspect ratio ändras, flyttas focal point relativt till crop-området.

**Exempel:**
- Focal Point: 50% X, 30% Y (personens ansikte)
- Desktop 16:9: Ansikte centrerat ✅
- Mobile 1:1: Ansikte toppen av bild (cropar bort kropp) ❌

---

## ✅ Lösningen

### Använd SAMMA aspect ratio över ALLA breakpoints

**Fördelar:**
- Focal Point stannar konsistent
- Förutsägbart crop-beteende
- Enklare för redaktörer att förstå

**Rekommenderat**: 4:3 aspect ratio (passar produktbilder bra)

---

## 🖼️ Image Styles - Implementation

### Steg 1: Skapa Image Styles

```
Configuration → Media → Image styles
```

**Skapa tre styles:**

#### Mobile (576x432) - 4:3
- Width: 576px
- Height: 432px (576 × 3 ÷ 4)
- Effect: Focal Point Scale and Crop

#### Tablet (768x576) - 4:3
- Width: 768px
- Height: 576px (768 × 3 ÷ 4)
- Effect: Focal Point Scale and Crop

#### Desktop (1200x900) - 4:3
- Width: 1200px
- Height: 900px (1200 × 3 ÷ 4)
- Effect: Focal Point Scale and Crop

**Viktigt**: ALLA har aspect ratio 4:3!

### Steg 2: Responsive Image Style

```
Configuration → Media → Responsive image styles
→ Add responsive image style
```

**Name**: Product Images  
**Breakpoint group**: Responsive image  
**Fallback**: Desktop (1200x900)

**Mappings:**
```
Breakpoint: 1x
  <768px:  Mobile (576x432)
  ≥768px:  Tablet (768x576)
  ≥1200px: Desktop (1200x900)
```

---

## 🎨 CSS Implementation

### Preprocess Hook - Ta bort width/height

**themes/tritonled/tritonled.theme:**
```php
<?php

/**
 * Implements hook_preprocess_HOOK() for responsive_image.
 *
 * Removes width/height attributes to allow CSS aspect-ratio to control size.
 */
function tritonled_preprocess_responsive_image(&$variables) {
  // Remove hardcoded dimensions.
  // This allows CSS aspect-ratio to work properly.
  unset($variables['img_element']['#attributes']['width']);
  unset($variables['img_element']['#attributes']['height']);
}
```

**Varför?**  
Drupal lägger automatiskt till `width="1200" height="900"` vilket förhindrar CSS från att styra storlek.

### CSS - Container med aspect-ratio

**themes/tritonled/css/components/responsive-images.css:**
```css
/**
 * Responsive Image Container
 * 
 * Maintains 4:3 aspect ratio and allows Focal Point to work consistently.
 */

/* Container får aspect-ratio */
.image-wrapper {
  position: relative;
  width: 100%;
  aspect-ratio: 4 / 3; /* SAMMA som image styles */
  overflow: hidden;
  background-color: #f0f0f0; /* Fallback medan bild laddar */
}

/* Bilden fyller container */
.image-wrapper img,
.image-wrapper video {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover; /* Crop for att fylla utan distortion */
  object-position: center; /* Focal Point override detta */
}

/* För lazy-loading placeholders */
.image-wrapper.loading {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
}

@keyframes loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
```

### HTML Structure (template)

**templates/field/field--field-image.html.twig:**
```twig
{#
/**
 * @file
 * Image field with responsive wrapper.
 */
#}
{% for item in items %}
  <div class="image-wrapper">
    {{ item.content }}
  </div>
{% endfor %}
```

---

## 🧪 Testing

### Visual Test

#### Desktop (≥1200px)
- [ ] Bild fyller container
- [ ] Focal Point centrerad på viktigt innehåll
- [ ] Ingen distortion
- [ ] 4:3 aspect ratio

#### Tablet (768px-1199px)
- [ ] SAMMA focal point som desktop
- [ ] Fortfarande 4:3 aspect ratio
- [ ] Responsiv storlek

#### Mobile (<768px)
- [ ] FORTFARANDE samma focal point
- [ ] 4:3 aspect ratio bibehålls
- [ ] Inget onödigt whitespace

### Focal Point Test

```
1. Ladda upp bild i Media Library
2. Sätt Focal Point (klicka på viktigt objekt)
3. Spara
4. Visa bild på produktsida
5. Ändra browser-storlek från 1920px → 375px
6. Verifiera: Focal Point stannar på samma objekt
```

**Resultat**: Objekt ska vara centrerat över alla breakpoints ✅

### Browser DevTools Check

```javascript
// Console check
const img = document.querySelector('.image-wrapper img');
const wrapper = document.querySelector('.image-wrapper');

// Aspect ratio check
const wrapperRatio = wrapper.offsetWidth / wrapper.offsetHeight;
console.log('Wrapper ratio:', wrapperRatio); // Should be ~1.33 (4/3)

// Object-fit check
console.log('Object fit:', getComputedStyle(img).objectFit); // Should be 'cover'
```

---

## 📐 Alternativa Aspect Ratios

### Om 4:3 inte passar:

**16:9 (Widescreen):**
```
Mobile:  576x324  (16:9)
Tablet:  768x432  (16:9)
Desktop: 1200x675 (16:9)
```

**1:1 (Square):**
```
Mobile:  576x576  (1:1)
Tablet:  768x768  (1:1)
Desktop: 1200x1200 (1:1)
```

**3:2 (Classic photo):**
```
Mobile:  576x384  (3:2)
Tablet:  768x512  (3:2)
Desktop: 1200x800 (3:2)
```

**Viktigt**: Välj EN ratio och håll den över ALLA breakpoints!

---

## 🔧 Focal Point Module - Setup

### Installation

```bash
composer require drupal/focal_point
drush en focal_point -y
drush cr
```

### Konfiguration

```
Configuration → Media → Image styles
→ [Your style]
→ Add effect: Focal Point Scale and Crop
```

**Settings:**
- Width: [as above]
- Height: [as above]
- Focal Point: ✅ Enabled

### På Image Fields

```
Structure → Media types → Image → Manage fields
→ field_media_image
→ Field settings
→ Enable focal point: ✅
```

**Redaktörer ser:**
- Bild upload
- Focal point widget (klicka för att sätta punkt)
- Preview av crop

---

## 📱 Mobile-Specific Considerations

### Portrait vs Landscape

**Om användare vrider telefon:**

```css
/* Landscape mobil */
@media (max-width: 767px) and (orientation: landscape) {
  .image-wrapper {
    aspect-ratio: 16 / 9; /* Bredare på landscape */
  }
}
```

**OBS**: Detta bryter konsistent focal point! Endast om absolut nödvändigt.

### Lazy Loading

```html
<img 
  src="placeholder.jpg" 
  data-src="actual-image.jpg" 
  loading="lazy"
  class="lazy-load"
>
```

**CSS:**
```css
img.lazy-load {
  opacity: 0;
  transition: opacity 0.3s;
}

img.lazy-load.loaded {
  opacity: 1;
}
```

---

## ⚠️ Common Pitfalls

### UNDVIK:

❌ **Olika aspect ratios per breakpoint**
```
Desktop: 16:9
Mobile: 1:1  ← Focal Point blir fel!
```

❌ **Hårdkodade width/height i HTML**
```html
<img src="..." width="1200" height="900">
<!-- CSS aspect-ratio fungerar inte! -->
```

❌ **Object-fit: contain** (istället för cover)
```css
img { object-fit: contain; } /* Skapar whitespace */
```

❌ **Inline styles från WYSIWYG**
```html
<img src="..." style="width: 500px;">
<!-- Override CSS! -->
```

### GÖR:

✅ Samma aspect ratio alla breakpoints  
✅ `unset()` width/height i preprocess  
✅ `object-fit: cover` i CSS  
✅ `aspect-ratio` på CONTAINER, inte img  

---

## 🎓 Lärdomar

### Focal Point = En punkt, inte magisk crop

Focal Point är en **koordinat** (X%, Y%) på originalbilden. När crop-området ändras (olika aspect ratio), flyttas denna punkt relativt till synligt område.

**Lösning**: Håll crop-området konstant (samma aspect ratio).

### Aspect-ratio property vs background-size

```css
/* Modern (2021+) */
.wrapper {
  aspect-ratio: 4 / 3; /* ✅ Enklare */
}

/* Äldre fallback (padding-hack) */
.wrapper {
  padding-top: 75%; /* 3/4 = 0.75 = 75% */
}
```

**Vi använder**: Modern `aspect-ratio` (stöds av alla moderna browsers)

---

## 🔗 Relaterade Filer

- Theming guide: `/docs/01-decision-trees/theming-decision-tree.md`
- Godkända moduler: `/docs/02-standards/approved-modules.md`

---

## 📚 Externa Referenser

**Focal Point module:**
- https://www.drupal.org/project/focal_point
- https://www.drupal.org/docs/extending-drupal/contributed-modules/focal-point

**CSS aspect-ratio:**
- https://developer.mozilla.org/en-US/docs/Web/CSS/aspect-ratio
- https://caniuse.com/mdn-css_properties_aspect-ratio

**Responsive Images:**
- https://www.drupal.org/docs/mobile-guide/responsive-images-in-drupal

---

**Version**: 1.0  
**Skapad**: 2025-01-08  
**Testad**: 2025-01-08  
**Verifierad**: ✅  
**Författare**: Stefan + Claude
