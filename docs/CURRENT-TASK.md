# Aktuell Task

**Task**: TASK-015 (Produktkonfigurator)
**Status**: In Progress — SESSION 1 planerad
**Senast uppdaterad**: 2026-03-18

---

## Sessionsplan

Se `/docs/tasks/task-015-session-plan.md` för fullständig plan.

### SESSION 1 (nästa) — Backend-grund
**Mål**: `tritonled_configurator` modul + `configurator_item` order item type

Steg:
1. Verifiera att 12 produkter finns och att `field_configurator_schema` är ifyllt
2. Skapa modul med `.info.yml`, grundstruktur
3. Skapa `commerce_order_item_type.configurator_item.yml`
4. Skapa fält: `field_configurator_sku`, `field_configurator_data`
5. `ddev drush en tritonled_configurator -y`

### SESSION 2 — JSON:API + Cart API
### SESSION 3 — JS: rendering + dependsOn
### SESSION 4 — JS: SKU-byggare + cart-POST
### SESSION 5 — Styling + produktsida-integration
### SESSION 6 — Schema-fyllning (om behövs)

---

## Öppna frågor inför SESSION 1

1. Är `field_configurator_schema` ifyllt på de 12 produkterna?
2. Har varje produkt en dummy-variation? (Commerce-krav)
3. Hur placeras konfiguratorn på produktsidan? (Block/Layout Builder rekommenderas)
4. Prislösning för offert-flöde?

---

## Öppna tasks

| Task | Status | Fil |
|------|--------|-----|
| TASK-015 | 🔄 In Progress | task-015-session-plan.md |
| TASK-016 | ✅ Completed | task-016-navigation-styling.md |
| TASK-017 | Planned | task-017-cart-block-styling.md |
| TASK-018 | In Progress | task-018-cart-page-layout.md |
| TASK-013 | In Progress | task-013-attribut-cleanup.md |
| TASK-020 | ✅ Completed | task-020-produktarkitektur-rebuild.md |
