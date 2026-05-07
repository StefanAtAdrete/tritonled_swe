# CURRENT TASK

## TASK-038 — Mega menu + produktkategori-landningssidor (PÅGÅENDE)

### Gjort:
- `product_categories`-taxonomin har termer med SV/EN-översättningar
- Alla produkter kopplade till rätt term via `field_product_categories`
- `tritonled_product_cards` view: Page display med contextual filter
- SROW form display fixad — matchar nu MAX/OPTI
- Pathauto SV/EN för produkter, kategorier och noder
- `taxonomy_menu_ui` installerad, menylänkar med hierarki skapade
- Desktop mega menu template + CSS (full-bredd, CSS grid, WCAG)
- Block display i `tritonled_product_cards` med filter-override
- Block placerat på taxonomy term-sidor

### Återstår:
- [ ] Taxonomy term-sida: layout + styling
- [ ] Mega menu: visuell finjustering
- [ ] Testa SV/EN språkväxling för kategori-URL:er

---

## Gjort tidigare denna session:
- **config_split / cim prod**: Fixat orphan `devel.settings`, config_split local `status: false`, `settings.local.php`, `development.services.yml`
- **TASK-036** (Kontaktformulär): Klar
- **TASK-037** (Feature-block SVG-ikoner): Klar
- **Deploy**: git pull + cim --partial + cr på prod
- **Filsynk**: rsync av web/sites/default/files/ till prod

---

## Backlog:
- **TASK-039** — Pathauto URL-struktur SV/EN
- **TASK-040** — Robot/AI-agentoptimering
- **TASK-041** — SEO och innehåll
