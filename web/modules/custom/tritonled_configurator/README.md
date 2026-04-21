# tritonled_configurator

Custom Drupal module for TritonLED. Provides a JavaScript-driven product configurator for Category A products (LED luminaires with 55,000+ variants). Replaces Drupal Commerce's standard attribute dropdowns.

---

## When to use

Use this module for product types that store variants in a JSON schema (`field_configurator_schema`) instead of Commerce attributes. Currently used by:

- `led_luminaire_max_opti`
- `led_luminaire_srow`

Standard Commerce products (Category B/C) do NOT use this module.

---

## How it works

1. **Schema**: Each product has `field_configurator_schema` (JSON) defining steps, options, SKU logic, and image mapping.
2. **Preprocess hook**: `tritonled_configurator_preprocess_commerce_product()` reads the schema, finds the `CONFIGURATOR-{id}` dummy variation, builds image picture data, and passes everything to `drupalSettings.tritonConfigurator`.
3. **JavaScript**: `js/configurator.js` reads `drupalSettings.tritonConfigurator` and renders Bootstrap 5 dropdowns, SKU bar, quantity field, and submit button. All UI strings use `Drupal.t()`.
4. **Cart**: Submissions POST to `triton/configurator/add-to-cart` (defined in `tritonled_configurator.routing.yml`) with the built SKU and selections object.

---

## Schema structure (`field_configurator_schema`)

```json
{
  "productName": "SROW-ED Gen 3",
  "skuPrefix": "SED-",
  "imagePrefix": "srow-ed",
  "steps": [
    {
      "id": "length",
      "skuPart": "middle",
      "visual": false,
      "options": [
        {
          "code": "A5",
          "label": "0,8m",
          "dependsOn": []
        }
      ]
    }
  ]
}
```

### Step fields

| Field | Description |
|-------|-------------|
| `id` | Step identifier — used in `getLabelForStep()` in JS |
| `skuPart` | `"middle"` or `"end"` — controls SKU assembly order |
| `visual` | `true` if this step affects product image |
| `options[].code` | Short code appended to SKU |
| `options[].label` | Display label shown in dropdown |
| `options[].dependsOn` | Array of `{stepId, codes}` — option hidden unless all conditions met |
| `options[].dependsOnAny` | Array of condition groups — option visible if ANY group fully matches |

---

## Image switching

Images are stored as media entities on `field_configurator_media`. Naming convention: `{imagePrefix}-{code1}{code2}` for combinations, `{imagePrefix}-default` for fallback.

Steps with `"visual": true` contribute their selected code to the image lookup.

---

## Dummy variation

Each configurator product requires exactly one variation with SKU starting `CONFIGURATOR-`. This variation holds no real data — it exists only so Commerce allows the product to be saved, and its ID is passed to the cart endpoint.

---

## Translations

All UI strings in `js/configurator.js` use `Drupal.t()`. Swedish translations are in `translations/sv.po`.

### Re-importing translations

```bash
ddev drush locale:import sv /var/www/html/web/modules/custom/tritonled_configurator/translations/sv.po --type=customized
ddev drush cr
```

### Why translations may appear in English

If the logged-in user has English as their account language preference, Drupal's `User` language detection takes priority over URL detection — causing JS translations to load in English even on Swedish pages.

**Fix**: Disable `User` in `/admin/config/regional/language/detection`. Use `Account administration pages` instead — this applies English only to admin pages, not the frontend.

---

## Files

```
tritonled_configurator/
├── js/
│   └── configurator.js          # Main configurator UI
├── css/
│   └── components/
│       └── configurator-print.css  # Print/PDF styles
├── src/
│   └── Controller/
│       └── ConfiguratorController.php  # Cart endpoint
├── translations/
│   └── sv.po                    # Swedish translations
├── tritonled_configurator.info.yml
├── tritonled_configurator.libraries.yml
├── tritonled_configurator.module   # Preprocess hook + image helpers
├── tritonled_configurator.routing.yml
└── README.md
```

---

## Session history

| Session | What was built |
|---------|---------------|
| Session 1–4 | Core configurator, schema, SKU builder, cart POST |
| Session 5 | Bootstrap 5 dropdowns, image switching, quantity field, auto-select |
| TASK-022 | `Drupal.t()` on all hardcoded strings |
| TASK-023 | Bootstrap custom dropdowns replace native `<select>` |
| TASK-024 | Live specs block, print/PDF button |
| TASK-025 | Layout tweaks (watt/optic wider columns) |
| TASK-026 | Document title set to SKU before `window.print()` |
