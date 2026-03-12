# TASK-016: Navigation & Mobil meny

## Status: COMPLETED

## Mål
Snygga till navbar och mobilmeny — konsekvent design, rätt Bootstrap-klasser, bra UX på mobil.

---

## Genomfört denna session

### Block Layout — Topbar right
- **Get a quote** (Commerce Cart-block) — Pages: Show only `product/*`
- **Contact us - button** (Custom content block) — Pages: Hide for `product/*`
- **Language switcher** — Topbar right + Navbar left 3

**Viktigt:** Drupal Pages-villkor matchar mot `currentPath` UTAN språkprefix och UTAN inledande `/`.
Rätt mönster: `product/*` (inte `/sv/product/*`)

### Contact us - button
- Nytt Custom content block med länk till `/sv/form/contact`
- HTML: `<a href="/sv/form/contact" class="btn btn-primary">Kontakta oss</a>`
- Skapad via Admin → Content → Block content

### Block class-klasser
- User account menu: `d-none d-lg-flex align-items-center gap-3`
- Language switcher (Topbar right): `d-none d-lg-flex align-items-center`

### CSS-ändringar i style.css
- `.navbar.sticky-top { z-index: 400 }` — under Drupal admin contextual (600+)
- `.topbar .cart-block--link__expand` — override btn-sm till standard btn-storlek
- `.block--tritonled-radix-account-menu .navbar-nav` — flex-direction: row för samma rad

### Offcanvas-template (page.html.twig + page--front.html.twig)
- Språkväljare (navbar_left_3) i offcanvas-header bredvid logotyp
- Topbar right renderas i offcanvas-body (språkväljare + CTA-knappar)
- **Begränsning:** Drupal renderar inte samma block två gånger — User account menu
  syns därför inte i offcanvas (renderas redan i topbar_left på desktop)

### Öppna frågor / att sova på
- **User account menu i offcanvas** — behövs det?
  - Alternativ A: Lägg till andra block-instans i Header-region, rendera i offcanvas
  - Alternativ B: Sätt Roles-villkor (bara visa för authenticated) — förenklar allt
  - Alternativ C: Lämna som det är (B2B-besökare loggar sällan in på mobil)

- **Get a quote-block titeln** — översätts via Interface Translation
  `/en/admin/config/regional/translate` → sök "Get a quote"

---

## Kvarvarande arbete
- [x] Besluta om User account menu på mobil — lämnas som det är (C)
- [x] Interface Translation för "Get a quote" → "Få offert" — klar
- [x] Testa mobilvy som anonym användare (inkognito) — klar
- [ ] Kontrollera offcanvas på riktiga mobila enheter

---

## Tekniska noter

### Templatefiler
- `web/themes/custom/tritonled_radix/templates/page/page.html.twig`
- `web/themes/custom/tritonled_radix/templates/page/page--front.html.twig`

### Block-ID:n
- Cart/offert-block: `tritonled_radix_cart`
- Contact us button: content block (skapad manuellt)
- Language switcher desktop: `tritonled_radix_languageswitcher` (Topbar right)
- Language switcher offcanvas: `tritonled_radix_languageswitcher_2` (Navbar left 3)

### Regioner i TritonLED Radix
topbar_left, topbar_right, navbar_branding, navbar_left, navbar_left_2,
navbar_left_3, navbar_right, header, content, sidebar_first, sidebar_second, footer
