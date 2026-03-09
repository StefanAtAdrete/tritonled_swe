# TASK-016: Navigation & Mobil meny

## Status: OPEN

## Mål
Snygga till navbar och mobilmeny — konsekvent design, rätt Bootstrap-klasser, bra UX på mobil.

## Att undersöka
- Nuvarande navbar-struktur (desktop + mobil)
- Hamburger-meny på mobil — fungerar den? Ser den bra ut?
- Aktiv länk-styling
- Dropdown för "Produkter"
- Topbar (Get a quote / språkväxlare)
- Färger, typografi, spacing

## Filer att kolla
- Navbar-template i tritonled_radix (page.html.twig eller liknande)
- `css/style.css` — befintlig navbar-CSS
- `tritonled_radix.libraries.yml`

## Beslut att ta
- Vilka Bootstrap navbar-klasser används?
- Custom CSS eller enbart Bootstrap?
- Mobilmenyns utseende (fullskärm overlay vs slide-in vs standard collapse)
