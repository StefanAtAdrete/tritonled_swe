# Offcanvas meny + Deploy-lärdomar (2026-03-15)

Tillägg till `00-START-HERE.md` — dessa lärdomar ska integreras vid nästa större uppdatering.

---

### Custom block content → Views för deploy-säkerhet (2026-03-15)
- ❌ `block_content`-entiteter följer INTE med i `cim` — är innehåll, inte config
- ✅ Använd **Views med Global: Custom text** för statiska block (knappar, länkar)
- ✅ Views är ren config — exporteras med `cex`, importeras med `cim`
- ✅ Views Custom text kan översättas via Drupals översättningssystem
- ✅ Menyer är alternativet om redaktörer behöver redigera via UI
- Se: `03-solutions/production-deploy.md`

### Offcanvas mobilmeny — collapse-struktur (2026-03-15)
- ✅ Separat meny-block i ny region `navbar_offcanvas` — separerar desktop/mobil
- ✅ `hook_theme_suggestions_menu_alter` + `preprocess_block` krävs för block-ID suggestions på menu-hook
- ✅ Drupal genererar INTE block-ID suggestions för menu-hook automatiskt (skiljer sig från block-hook)
- ✅ Förälderlänk med undermeny = `<button>` med collapse — inte `<a href="">` (orsakar sidladdning)
- ✅ Samma block kan inte renderas två gånger — desktop och offcanvas måste ha separata block-instanser
- Se: `03-solutions/offcanvas-mobile-menu.md`

### JS-cache på prod (2026-03-15)
- `drush cr` rensar server-cache men INTE browser-cache
- Ny JavaScript syns inte förrän hård reload: `Ctrl+Shift+R` / `Cmd+Shift+R`
- Views Custom text strippar `onclick`-attribut — använd CSS-klass + JS-behavior istället

### Cart z-index (2026-03-15)
- Commerce Cart's `cart-block--contents` har `z-index: 300` (från `commerce_cart.layout.css`)
- Sticky navbar har `z-index: 400` → cart-dropdown hamnar under navbar
- Fix: `.cart-block--contents { z-index: 999 !important }` i `style.css`
