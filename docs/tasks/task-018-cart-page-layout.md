# TASK-018: Cart-sida / Offertförfrågan — Layout & Styling

**Status:** TODO  
**Prioritet:** Medium  
**Område:** Commerce Cart / Theming

---

## Problem (observerat)

Cart-sidan (`/sv/cart`) ser ostylad ut:
- "Item"-kolumnen är tom — produktnamn visas inte
- Två separata tabeller (= två separata orders i test-data)
- Dubbla "Uppdatera varukorgen" + "Kassan"-knappar (en per order)
- Knappar ser generiska ut, ej Bootstrap-stylade
- Sidtitel är "Varukorg" — ska döpas om

---

## Mål

- Produktnamn synligt i Item-kolumnen
- Ren, flat Bootstrap-tabell (`table table-borderless` eller liknande)
- "Uppdatera varukorgen" → sekundär knapp (`btn btn-outline-secondary`)
- "Kassan" → primär knapp (`btn btn-primary`) med text "Skicka offertförfrågan"
- Sidtitel: "Offertförfrågan" (SV) / "Quote Request" (EN)

---

## Research att göra

1. Vilka templates styr cart-sidan?
   - `commerce-cart.html.twig`?
   - `commerce-order-item-table.html.twig`?
   - Kör `ddev drush twig:debug` eller kolla Twig debug-kommentarer i källkoden
2. Varför är Item-kolumnen tom?
   - Sannolikt saknas ett fält i cart view mode för produktvariationen
   - Kolla: `/admin/commerce/config/order-item-types` → fält/view modes
3. Är "Kassan"-knappen konfigurerbar utan template-override?
   - Kolla Commerce Checkout-inställningar

---

## Approach

1. Aktivera Twig debug lokalt och inspektera template-namn på cart-sidan
2. Skapa template-override(s) i `templates/commerce/`
3. Styla med Bootstrap-klasser
4. Döp om sidan via översättningar eller Commerce config

---

## Kopplat till

- TASK-017: Cart block-knapp (topbar) — redan klar
- TASK-016: Navigation styling
