# TASK-038 — Mega menu + produktkategori-landningssidor

**Status: PÅGÅENDE**
**Prioritet: Hög**

## Beskrivning
Mega menu för "Produkter" där varje produkttyp har en egen landningssida.
Landningssidorna visar produkter filtrerade efter taxonomi (produktkategori).

---

## Design-referens
**Xcen.se** används som visuell referens för mega menu-beteendet:
- **Desktop**: Full-bredd dropdown-panel under "Produkter", kategorier som kolumnrubriker med underkategorier/produktserier listade under
- **Mobil**: Slide-in offcanvas-panel med accordion-stil för kategorier (chevron-toggle, collapse per kategori)
- Ren typografi, ingen "quick and dirty" Bootstrap-dropdown
- Tydlig hierarki: Kategori (rubrik) → underkategorier (länklista)

## UX-principer (Mobile First)
- **Mobil**: Offcanvas-meny med collapse redan implementerad — BEHÅLLS SOM DEN ÄR
- **Desktop**: Mega menu aktiveras via hover + click på "Produkter"
- Inga hover-only interaktioner (WCAG, touch-devices)
- Keyboard-navigation: Escape stänger, Tab navigerar igenom
- `aria-expanded`, `aria-haspopup`, `aria-controls` på alla toggles
- Tillräcklig färgkontrast (WCAG AA)

## WCAG-krav
- Alla interaktiva element nåbara via tangentbord
- `aria-expanded` uppdateras korrekt vid öppna/stänga
- Focus management: fokus stannar i menyn tills användaren stänger
- Escape stänger mega menu och returnerar fokus till toggle-knappen
- Inga "hover only" triggers — click/Enter/Space öppnar

---

## Teknisk approach

### Menystruktur
```
Huvudmeny (navbar_left block: tritonled_radix_main_menu)
├── Produkter          → mega menu panel (full bredd)
│   ├── Linjär LED     → /produkter/linjar-led
│   │   ├── MAX        → /produkter/max
│   │   ├── OPTI       → /produkter/opti
│   │   └── SROW       → /produkter/srow
│   ├── Strålkastare   → /produkter/stralkastare
│   ├── Industriarmatur → /produkter/industriarmatur
│   ├── Gatu- & Områdesbelysning → /produkter/gatu-omradesbelysning
│   ├── Höghållsbelysning → /produkter/hoghallsbelysning
│   ├── Explosionsskyddad → /produkter/explosionsskyddad
│   ├── Överspänningsskydd → /produkter/overspanningsskydd
│   └── Tillbehör      → /produkter/tillbehor
├── Om oss
├── Kontakt
└── ...
```

### Moduler
- `taxonomy_menu_ui` — skapar menylänkarna från taxonomy terms (installerad)
- Inga mega menu-moduler — Bootstrap 5 + custom template

### Filer
1. `templates/navigation/menu--main--tritonled-radix-main-menu.html.twig` — desktop mega menu template
2. `css/components/mega-menu.css` — positionering, kolumner, animationer
- Block-djup i admin: ändras från `2` → `3`

### Varför INTE Bootstrap dropdown-modul?
- Bootstrap-specifika mega menu-moduler laddar egna CSS/JS som konfliktar med Bootstrap 5 CDN
- Svåra att anpassa utan att överkriva modulens styles
- Custom template + minimal CSS ger full kontroll

---

## Beslut
- ✅ Taxonomy term-sidor (automatiskt via Views contextual filter)
- ✅ Pathauto: SV `/produkter/[term]`, EN `/products/[term]`
- ✅ `taxonomy_menu_ui` för menylänkar
- ✅ Custom template + CSS för mega menu (inga extra moduler)
- ✅ Mobil: behåller befintlig offcanvas-template

---

## Gjort
- [x] Taxonomy `product_categories` med SV/EN-termer
- [x] Alla produkter kopplade till rätt term
- [x] `tritonled_product_cards` view med contextual filter
- [x] SROW form display matchar MAX/OPTI
- [x] Pathauto-mönster SV/EN för produkter, kategorier, noder
- [x] Alla taxonomy term-alias genererade SV/EN
- [x] `taxonomy_menu_ui` installerad
- [x] Menylänkar skapade med korrekt hierarki
- [x] Desktop mega menu template (`menu--main--tritonled-radix-main-menu.html.twig`)
- [x] `mega-menu.css` — full-bredd, CSS grid, WCAG focus-ring
- [x] `mega-menu` library i `tritonled_radix.libraries.yml`
- [x] Block display i `tritonled_product_cards` med filter-override (ta bort title-filter för Triton/surgy)
- [x] Block placerat på taxonomy term-sidor med contextual filter från term-sidan

## Återstår
- [ ] Taxonomy term-sida: layout + styling (rubrik, beskrivning, produktlista)
- [ ] Mega menu: visuell finjustering (bredd, spacing, typografi)
- [ ] Testa SV/EN språkväxling för kategori-URL:er
- [ ] Höghallsbelysning saknas? (kontrollera alla termer har produkter)

---

## Acceptanskriterier
- Mega menu öppnas under "Produkter" på desktop
- Kategorier visas i kolumner, underkategorier (MAX/OPTI/SROW) under Linjär LED
- Fungerar med tangentbord (Escape, Tab, Enter)
- WCAG AA-kompatibel
- Mobilmenyn (offcanvas) fungerar oförändrad
- SV och EN-versioner har korrekta URL:er
