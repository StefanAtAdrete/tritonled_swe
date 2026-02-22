# Task 005-B: Remote Video Styling på Produktsida

**Status**: ✅ LÖST 2026-02-22  
**Session**: 2026-02-22  

---

## Problem

YouTube-embed i produktgalleri visade med fel ratio — iframe 150px hög med stort tomrum under i Splide-karusellen.

## Root Causes (3 st)

### 1. Fel tema
CSS redigerades i `tritonled` men aktivt tema är `tritonled_radix`. Kontrollera alltid aktivt tema via DevTools → Network innan CSS-arbete.

### 2. Blazy utan internet
Blazy hämtar ratio-info från YouTube's oEmbed API. DDEV saknar internet → API-anrop misslyckas → ingen `media--ratio--43`-klass → iframe fastnar på 150px default.

### 3. Splide fast höjd
`splide.optionset.product_main` hade `autoHeight: false` och `heightRatio: 0.75` → karusellen satte fast 470px höjd oberoende av innehållet.

## Lösning

**CSS i rätt tema** (`tritonled_radix/css/components/product-gallery.css`):
```css
.splide--main .media--youtube {
  position: relative !important;
  width: 100% !important;
  aspect-ratio: 16 / 9 !important;
  height: auto !important;
  background: #000;
}
.splide--main .media--youtube iframe {
  position: absolute !important;
  top: 0 !important; left: 0 !important;
  width: 100% !important; height: 100% !important;
  border: 0;
}
```

**Bibliotek länkat** i `tritonled_radix.libraries.yml` under `global-styling`.

**Splide optionset** (`config/sync/splide.optionset.product_main.yml`):
- `autoHeight: true`
- `heightRatio: '0'`

## Filer ändrade

- `themes/custom/tritonled_radix/css/components/product-gallery.css` (ny)
- `themes/custom/tritonled_radix/css/components/media.css` (ny)
- `themes/custom/tritonled_radix/tritonled_radix.libraries.yml`
- `config/sync/splide.optionset.product_main.yml`
- `config/sync/core.entity_view_display.media.remote_video.splide.yml` (ny)

## Se även

- `/docs/03-solutions/active-theme-css-checklist.md`
