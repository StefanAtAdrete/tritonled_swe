# Aktuell Task

**Task**: TASK-005  
**Fil**: `/docs/tasks/task-005-views-unique-products.md`  
**Status**: ✅ Löst  
**Senast uppdaterad**: 2026-02-22

## Vad som gjordes idag (2026-02-22)

### TASK-005 — Unika produkter i Views ✅
- Lösning: Filter `Product: Variations:delta (= 0)` på Hero och Featured Products views
- Varje produkt visas nu exakt en gång
- Ingen relationship behövdes — enkelt filter löste det

### Image style nginx-problem ✅
- Problem: nginx genererade inte image style-derivat on-demand
- Root cause: `@rewrite` skickade inte med URI till Drupal
- Lösning: Fixade `@rewrite` i `.ddev/nginx_full/nginx-site.conf`
- Vald stack: nginx (LEMP) — matchar Hostinger VPS produktion
- Dokumenterat i `/docs/03-solutions/image-style-nginx-problem.md`

## Startpunkt nästa session

Hero-karusellen och Featured Products visas korrekt med unika produkter och bilder.

**Nästa steg:**
1. Styling av Featured Products-korten
2. Styling av Hero-karusellen (layout, text-overlay, ratio)
3. Kontrollera att hero-bilden fyller hela bredden korrekt på alla breakpoints

## Kvarstående städning
- Ta bort `/docs/03-solutions/theme-and-views-debugging.md.bak`
- Kör `ddev drush cex -y` för att exportera Views-config
