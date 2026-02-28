# Lärdom: Verifiera alltid API:t innan implementation

**Datum**: 2026-02-28  
**Kontext**: commerce_variation_blocks AJAX-integration

---

## Vad hände

Vid implementation av AJAX-uppdatering för pseudo-fält vid variantbyte
antog Claude att Commerce exponerade en hook:

```php
hook_commerce_product_variation_field_injection(AjaxResponse $response, ...)
```

Denna hook **existerar inte**. Commerce använder ett Event-system.

## Rätt approach

Commerce exponerar `ProductEvents::PRODUCT_VARIATION_AJAX_CHANGE` via:

```
commerce/modules/product/src/Event/ProductVariationAjaxChangeEvent.php
commerce/modules/product/src/Event/ProductEvents.php
```

Rätt implementation är en **EventSubscriber**, inte en hook.

## Regel

**ALDRIG anta att ett hook-namn eller API existerar utan att verifiera det i källkoden.**

Innan implementation av integration med contrib-modul:

1. Sök i modulens källkod efter hooks, events, services
2. Läs faktiska PHP-filer — inte dokumentation eller minne
3. Kontrollera `.api.php` om den finns
4. Sök på event-klasser och konstanter

```bash
# Exempel: hitta alla events i Commerce
grep -r "const PRODUCT" web/modules/contrib/commerce/modules/product/src/Event/ProductEvents.php

# Hitta hur AJAX hanteras
find web/modules/contrib/commerce -name "*.php" | xargs grep -l "AjaxResponse"
```

## Kostnad av att gissa

- Implementerar kod som inte fungerar
- Svårt att debugga (ingen felkod — bara "inget händer")
- Gräver ned sig i felspåret

## Principen

> "Drupal och contrib-moduler är ett etablerat ekosystem.  
> Läs källkoden. Gissa aldrig API:t."
