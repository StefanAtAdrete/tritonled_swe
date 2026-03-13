# Aktuell Task

**Task**: TASK-016 (Completed)
**Status**: Navbar/mobil nästan klar, ett par öppna frågor
**Senast uppdaterad**: 2026-03-11
**Fil**: `/docs/tasks/task-016-navigation-styling.md`

---

## Vad som gjordes idag (2026-03-11)

- CTA-knappar i topbar: "Få offert" på produktsidor, "Kontakta oss" på övriga
- Pages-villkor: mönstret är `product/*` (utan språkprefix, utan inledande /)
- Contact us-block skapades som custom content block med btn btn-primary
- User account menu: flex-direction row via CSS
- z-index på sticky navbar sänkt till 400 (under Drupal admin contextual)
- Cart-knapp (btn-sm) overridad till standard btn-storlek via CSS
- Offcanvas: språkväljare i header, topbar_right i body

## Nästa steg

1. **Besluta**: User account menu i offcanvas — extra block-instans eller Roles-villkor?
2. **Interface Translation**: "Get a quote" → "Få offert" via `/en/admin/config/regional/translate`
3. **Testa** som anonym användare i inkognito

---

## Exportera config

Kör efter varje session:
```bash
ddev drush cex -y
git add -A && git commit -m "TASK-016: navbar CTA-knappar, offcanvas, CSS-justeringar"
```

---

## Öppna tasks

| Task | Status | Fil |
|------|--------|-----|
| TASK-016 | Completed | task-016-navigation-styling.md |
| TASK-017 | Planned | task-017-cart-block-styling.md |
| TASK-018 | Planned | task-018-cart-page-layout.md |
| TASK-013 | In Progress | task-013-attribut-cleanup.md |
