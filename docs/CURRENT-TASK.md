# CURRENT TASK

## TASK-038 — Mega menu + produktkategori-landningssidor (PÅGÅENDE)

### Gjort:
- `product_categories`-taxonomin har termer med SV/EN-översättningar:
  - 34: Strålkastare / Floodlight
  - 35: Industriarmatur / Highbay
  - 44: Linjär LED / Linear LED (förälder för MAX/OPTI/SROW)
  - 13: MAX, 14: OPTI, 15: SROW (barn under Linjär LED)
  - 37: Gatu- & Områdesbelysning / Street & Area Lighting
  - 38: Höghållsbelysning / High Mast Lighting
  - 47: Explosionsskyddad / Ex Hazardous
  - 40: Tillbehör / Accessories
  - 41: Överspänningsskydd / Surge Protection
- Alla produkter kopplade till rätt term via `field_product_categories`
- `tritonled_product_cards` view: Page display med contextual filter på `field_product_categories` tillagt
- SROW form display fixad — matchar nu MAX/OPTI (media_library_widget, entity_reference_autocomplete, dolda fält, korrekt vikter)
- SROW widgets för field_configurator_media, field_hero_media, field_product_media → media_library_widget
- SROW widgets för field_producers, field_product_categories → entity_reference_autocomplete
- field_product_type och variations dolda i SROW (matchar MAX/OPTI)
- field_product_categories translatable: true behålls på alla bundles — Drupal kräver minst ett translatbart fält per variation-bundle. Termen är översatt vilket räcker i praktiken.

### Återstår:
- [ ] Pathauto-mönster för taxonomy term-sidor (`product_categories`)
- [ ] Taxonomy term-sidan: styling/layout (beskrivning + produktlista)
- [ ] Mega menu under "Produkter" med kategori-länkar
- [ ] Testa SV/EN-språkväxling för kategori-URL:er

---

## Gjort tidigare denna session:
- **config_split / cim prod**: Fixat orphan `devel.settings`, config_split local `status: false`, `settings.local.php`, `development.services.yml`
- **TASK-036** (Kontaktformulär): Klar
- **TASK-037** (Feature-block SVG-ikoner): Klar

---

## Backlog:
- **TASK-039** — Pathauto URL-struktur SV/EN
- **TASK-040** — Robot/AI-agentoptimering
- **TASK-041** — SEO och innehåll
