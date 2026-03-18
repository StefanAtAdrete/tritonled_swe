# Aktuell Task

**Task**: TASK-015 (Produktkonfigurator)
**Status**: In Progress — SESSION 1 klar, SESSION 2 nästa
**Senast uppdaterad**: 2026-03-18

---

## Var vi är

### SESSION 1 ✅ Klar (2026-03-18)

- ✅ 12 Commerce-produkter verifierade med `field_configurator_schema` ifyllt
- ✅ `tritonled_configurator` modul skapad och aktiverad
- ✅ `configurator_item` order item type registrerad i Drupal
- ✅ Fält skapade: `field_configurator_sku` + `field_configurator_data`
- ✅ Config exporterad, commit: `[TASK-015-01]`

### SESSION 2 — nästa

**Mål:** JSON:API-verifiering + Cart API-integration

1. Testa att `field_configurator_schema` exponeras via JSON:API
2. Testa Cart API POST med `configurator_item`
3. Implementera PriceResolver (returnerar 0 för offert-flöde)

### Kommande sessioner
| Session | Fokus |
|---------|-------|
| SESSION 3 | JS: dropdown-rendering + dependsOn-filtrering |
| SESSION 4 | JS: SKU-byggare + cart-knapp |
| SESSION 5 | Styling + placering i Layout Builder |
| SESSION 6 | Schema-fyllning (klar — ej behövs) |

Se fullständig plan: `/docs/tasks/task-015-session-plan.md`  
Se task-detaljer: `/docs/tasks/task-015-variant-configurator.md`

---

## Öppna tasks

| Task | Status | Fil |
|------|--------|-----|
| TASK-015 | 🔄 SESSION 1 klar | task-015-variant-configurator.md |
| TASK-016 | ✅ Completed | task-016-navigation-styling.md |
| TASK-017 | Planned | task-017-cart-block-styling.md |
| TASK-018 | In Progress | task-018-cart-page-layout.md |
| TASK-013 | In Progress | task-013-attribut-cleanup.md |
| TASK-020 | ✅ Completed | task-020-produktarkitektur-rebuild.md |
