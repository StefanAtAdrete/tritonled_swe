# CURRENT-TASK: TASK-032 — Product Card

## FDT-steg: 5 — VERIFY

## Aktuellt läge
Kortet renderar korrekt — bild, titel, badge, short_description, inga dubbletter, ingen add-to-cart.
Behöver nu verifieras mot ursprunglig mockup och data fyllas in på fler produkter.

## Nästa steg
1. Fyll in `field_installation_environment` och `field_short_description` på fler produkter i admin
2. Stefan verifierar visuellt mot mockup från steg 0
3. Godkänd → `ddev drush cex -y` + commit
4. Beslut om steg 6 (API)

## Viktiga lärdomar (FDT-tillägg)
- Commerce: `{{ product.field_xxx }}` INTE `{{ content.field_xxx }}`
- `string_long` → formatter `basic_string`
- Views Rendered Entity → lägg till språkfilter annars dubbletter
- `variation__layout_builder` + `variation_price` måste in i `hidden`

## Filer att committa
- `config/sync/core.entity_view_display.commerce_product.*.card.yml` (alla 10)
- `config/sync/core.entity_view_mode.commerce_product.card.yml`
- `config/sync/field.field.commerce_product.*.field_installation_environment.yml` (10 st)
- `config/sync/field.field.commerce_product.*.field_short_description.yml` (8 st)
- `config/sync/field.storage.commerce_product.field_installation_environment.yml`
- `config/sync/field.field.commerce_product_variation.ex_hazardous_variation.attribute_watt.yml`
- `config/sync/field.field.commerce_product_variation.street_area_variation.attribute_watt.yml`
- `config/sync/taxonomy.vocabulary.installation_environment.yml`
- `config/sync/views.view.tritonled_product_cards.yml`
- `web/themes/custom/tritonled_radix/templates/commerce/commerce-product--card.html.twig`
- `docs/skills/fdt/SKILL.md`
- `docs/tasks/task-032-product-card.md`

## Aktiv task-fil
`/docs/tasks/task-032-product-card.md`
