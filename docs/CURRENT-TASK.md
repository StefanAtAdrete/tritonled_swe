# Aktuell Task

**Task**: TASK-018 (Cart-sida layout)
**Status**: Påbörjad
**Senast uppdaterad**: 2026-03-17

---

## Vad som gjordes idag (2026-03-17)

### Produktarkitektur — Analys och dokumentation
- Hämtat alla konfigurator-scheman från triton-solutions.co via browser automation (12 modeller, 3 serier)
- Sparat i `/docs/product-schemas/` (max, opti, srow)
- Dokumenterat arkitektur i `task-015-konfigurator-arkitektur.md`
- Skapat TASK-020 (Produktarkitektur Rebuild) med sub-tasks

### Viktiga insikter
- SROW är fundamentalt annorlunda: `chips`/`ip_class` istället för `cri`/`sensor`
- Behövs 2 Commerce Product Types: `led_luminaire_max_opti` och `led_luminaire_srow`
- Konfiguratorn (TASK-015) behöver lager-modellen: Commerce för data + JSON-schema + JS för UX
- dependsOn-logiken kräver custom konfigurator — Commerce klarar det inte nativt

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
