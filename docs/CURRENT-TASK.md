# CURRENT TASK

## TASK-039 — Startsida: Tre ikoner-block (TODO)

### Gjort:
- Task-dokument skapat: `/docs/tasks/task-039-startsida-ikoner-block.md`
- HTML-färdig text för affarsfabriken.se/node/12 klar (SV + EN)
- Ankarlänkar definierade: `#modulart`, `#kostnad`, `#installation`
- Stefan klistrar in texterna i CKEditor på affarsfabriken.se/node/12

### Återstår:
- [ ] Bygga tre ikoner-block på tritonled.se startsidan
- [ ] Ankarlänkarna peka på affarsfabriken.se/node/12#modulart osv
- [ ] SV + EN-versioner av blocktexterna

---

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
- **SROW form display**: Widgets matchar MAX/OPTI, field_product_type + variations dolda
- **Pathauto**: SV/EN-mönster för produkter, kategorier, noder. Alla alias genererade
- **taxonomy_menu_ui**: Installerad lokalt + prod (composer require på servern)
- **Mega menu**: Desktop template + CSS (full-bredd, CSS grid, WCAG)
- **tritonled_product_cards**: Block display med filter-override (Triton/surgy-filter borttagna)
- **Block**: Placerat på taxonomy term-sidor med contextual filter
- **Deploy**: composer require taxonomy_menu_ui på prod, cim --partial, cr
- **Varning på prod**: views_block block_2 plugin not found — åtgärdat, fungerar

---

## Backlog:
- **TASK-039** — Pathauto URL-struktur SV/EN
- **TASK-040** — Robot/AI-agentoptimering
- **TASK-041** — SEO och innehåll
