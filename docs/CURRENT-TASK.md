# Aktuell Task

**Task**: TASK-020 (Produktarkitektur Rebuild)
**Status**: In Progress
**Senast uppdaterad**: 2026-03-18

---

## Vad som gjordes idag (2026-03-18)

### TASK-020 — Arkitekturbeslut (KRITISKT)
- TASK-020-01 till 03 klara: Product Types, attribut, field_configurator_schema, rensning av felaktig data
- Försökte importera 15 840 varianter per modell via Feeds — **fel approach, avfärdat**
- **Nytt beslut:** Varje modell = EN Commerce-produkt med JSON-schema i `field_configurator_schema`
- Konfiguratorn (TASK-015) läser schemat, genererar SKU dynamiskt, lägger i cart som custom line item
- Inga tusentals Commerce-varianter behövs

### Nästa steg
- TASK-020-08: Skapa 12 Commerce-produkter (en per modell) via Drush
- TASK-020-09: Lägg in JSON-schema i `field_configurator_schema` per produkt

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
| TASK-020 | Not Started | task-020-produktarkitektur-rebuild.md |
