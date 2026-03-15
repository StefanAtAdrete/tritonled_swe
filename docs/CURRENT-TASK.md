# Aktuell Task

**Task**: TASK-018 (Cart-sida layout)
**Status**: Påbörjad
**Senast uppdaterad**: 2026-03-15

---

## Vad som gjordes idag (2026-03-15)

### TASK-016 — Avslutad
- Offcanvas collapse-meny med `navbar_offcanvas`-region och custom template
- Cart z-index: 999
- Custom block content → Views (deploy-säkert)
- Klaro cookie-länk via JS-behavior (`.klaro-open`)

### Lärdomar
- Custom block content följer INTE med i `cim` — använd Views med Custom text istället
- JS-cache på prod kräver hård reload efter deploy
- Views Custom text kan översättas

---

## Nästa steg — TASK-018: Cart-sida

### Kända problem
1. **Dubblerade cart-forms** — två separata orders i databasen (order 5 + 6)
   - Lösning: Radera gamla order via admin `/en/admin/commerce/orders`
2. **Innehållet flyter ut 100%** — saknar container-wrapper
   - Lösning: CSS på `.path-cart` eller Views CSS-klass
3. **Artikeltext syns inte** i cart-tabellen

---

## Öppna tasks

| Task | Status | Fil |
|------|--------|-----|
| TASK-016 | ✅ Completed | task-016-navigation-styling.md |
| TASK-017 | Planned | task-017-cart-block-styling.md |
| TASK-018 | In Progress | task-018-cart-page-layout.md |
| TASK-013 | In Progress | task-013-attribut-cleanup.md |
