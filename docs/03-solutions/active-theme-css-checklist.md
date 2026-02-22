# Active Theme CSS — Checklista

**Skapad**: 2026-02-22  
**Status**: ✅ Verifierad lösning

---

## Problem

CSS-ändringar slår inte igenom trots att filer finns och `drush cr` körts.

## Vanligaste orsaken

Fel tema redigeras. Projektet har två teman:
- `tritonled` — gammalt tema, **ej aktivt**
- `tritonled_radix` — **aktivt tema**

## Verifiera aktivt tema

```javascript
// DevTools Console på sidan:
Array.from(document.styleSheets).map(s => s.href).filter(Boolean)
// Kolla vilket tema CSS-filerna kommer från
```

eller:

```bash
ddev drush config-get system.theme default
```

## Verifiera att CSS-fil laddas

```javascript
Array.from(document.styleSheets).map(s => s.href)
  .filter(h => h && h.includes('product-gallery'))
  .join('\n') || 'INTE LADDAD';
```

## Lägga till ny CSS-fil i aktivt tema

1. Skapa fil i `themes/custom/tritonled_radix/css/`
2. Länka i `tritonled_radix.libraries.yml`
3. Kör `drush cr`
4. Verifiera via DevTools

## Stänga av CSS-aggregering under dev

```bash
ddev drush config-set system.performance css.preprocess 0 -y
ddev drush cr
```

## Blazy + oEmbed offline (DDEV)

Blazy sätter `media--ratio`-klasser baserat på data från YouTube's oEmbed API. DDEV saknar internet → API misslyckas → ingen ratio-klass → iframe får 150px hårdkodad höjd.

**Lösning:** Sätt `aspect-ratio` direkt via CSS på kända media-klasser:
```css
.media--youtube {
  aspect-ratio: 16 / 9;
  position: relative;
}
.media--youtube iframe {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}
```
