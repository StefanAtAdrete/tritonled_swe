# TASK-017: Cart Block Styling — "Få offert"-knapp

**Status:** IN PROGRESS  
**Prioritet:** Medium  
**Område:** Theming / Commerce Cart

---

## Beslut (godkända)

- Knapptext SV: **"Få offert"**
- Knapptext EN: **"Get a quote"** (via Drupal t()-filter)
- Knappstil: **btn-primary**
- Cart-sidan döps om: **Ja** (till "Offertförfrågan" / "Quote request")

---

## Mål

- `btn btn-primary` för hela cart-länken
- Bootstrap `badge rounded-pill` för antal-siffran (bara siffran, ingen "inlägg"-text)
- Dölj dropdown-innehållet i topbaren
- Översättningsbar text via Drupal t()-filter

---

## Lösning

Template override: `templates/commerce/commerce-cart-block.html.twig`

Variabler som används:
- `{{ url }}` — länk till cart-sidan
- `{{ count }}` — bara siffran (inte `count_text` som renderar "X inlägg")

---

## Implementation-steg

1. [x] Research — template-variabler kartlagda
2. [ ] Skapa `web/themes/custom/tritonled_radix/templates/commerce/` katalog
3. [ ] Skapa template-override
4. [ ] `drush cr`
5. [ ] Döp om Cart-sidan i Drupal admin
6. [ ] Lägg till översättning "Få offert" under `/admin/config/regional/translate`
7. [ ] Verifiera mobil + desktop
