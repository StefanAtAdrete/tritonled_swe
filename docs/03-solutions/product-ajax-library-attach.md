# Lösning: hook_page_attachments i modul, inte i tema

**Datum**: 2026-03-07  
**Relaterad task**: TASK-010b (commerce_variation_blocks AJAX)  
**Problem**: `product-ajax` biblioteket laddades aldrig på produktsidor

---

## Problemet

`tritonled_radix_page_attachments()` i `.theme`-filen anropades aldrig av Drupal 11,
trots att:
- Filen inkluderades (`get_included_files()` bekräftade det)
- Ingen syntaxfel hittades (`php -l`)
- Temat var aktivt

`Drupal.behaviors.tritonledProductAjax` saknades på sidan — biblioteket
`tritonled_radix/product-ajax` laddades aldrig.

## Rotkaus

`hook_page_attachments()` i **teman** är opålitlig i Drupal 11. Drupal laddar
modulers hooks före temats hooks, och i vissa bootstrap-scenarion (t.ex. AJAX-requests,
cached responses) kan temats `.theme`-fil inte vara fullt initierad när hooken körs.

## Lösningen

Flytta `hook_page_attachments()` för kritiska bibliotek till en **modul** —
i detta projekt `tritonled_compat.module` som alltid är aktiv i produktion.

```php
// tritonled_compat.module
function tritonled_compat_page_attachments(array &$attachments) {
  $route = \Drupal::routeMatch()->getRouteName();
  if ($route === 'entity.commerce_product.canonical') {
    $attachments['#attached']['library'][] = 'tritonled_radix/product-ajax';
  }
}
```

## Regel framåt

> **Lägg aldrig kritiska `hook_page_attachments()`-implementationer i `.theme`-filer.**
> Använd alltid en modul för detta i Drupal 11+.

Teman kan använda `.info.yml` `libraries:` för globala bibliotek,
men villkorlig attachering av bibliotek ska alltid ske i moduler.

## Påverkar produktion?

Nej — `tritonled_compat` är redan markerad som obligatorisk i produktion.
Kräver ingen ny modul, inga nya beroenden. Följer med git automatiskt.
