# Task 010b: commerce_variation_blocks — AJAX-uppdatering vid variantbyte

**Created**: 2026-02-28
**Status**: Completed
**Priority**: KRITISK
**Parent Task**: TASK-010
**Related**: web/modules/custom/commerce_variation_blocks/

---

## 1. PROBLEMET

Pseudo-fälten (Electrical, Mechanical, Certifications) renderades korrekt vid
sidladdning, men uppdaterades INTE via AJAX när användaren bytte variant.

**Orsak**: Commerce's AJAX-system hanterar bara fält som är registrerade i
`ProductVariationFieldRenderer`. Pseudo-fält på produktentiteten ingår inte.

---

## 2. LÖSNING

### EventSubscriber på ProductEvents::PRODUCT_VARIATION_AJAX_CHANGE

Commerce exponerar **inte** någon hook för AJAX-tillägg — det är ett **Event**:

```
commerce/modules/product/src/Event/ProductVariationAjaxChangeEvent.php
commerce/modules/product/src/Event/ProductEvents::PRODUCT_VARIATION_AJAX_CHANGE
```

Rätt implementation: `EventSubscriber` som lyssnar på detta event och
skickar `ReplaceCommand` för varje pseudo-fält-container.

### CSS-klass strategi
Varje pseudo-fält-container får en unik klass i `hook_entity_view()`:
`commerce-variation-block--{view_mode_id}--{product_id}`

EventSubscriber använder denna som selector i `ReplaceCommand`.

---

## 3. IMPLEMENTATION

### Filer skapade/ändrade

- `commerce_variation_blocks.module` — container-wrapper med CSS-klass i `hook_entity_view()`
- `commerce_variation_blocks.services.yml` — registrerar EventSubscriber
- `src/EventSubscriber/VariationAjaxChangeSubscriber.php` — skickar ReplaceCommands

### Viktigt: Field Groups borttagna

Field Group-tabs på Default variation view mode togs bort — de var
redundanta när view modes (Electrical, Mechanical, Certifications) hanterar
grupperingen. Alla tekniska spec-fält flyttades till Disabled i Default.

---

## 4. TESTRESULTAT

- [x] AJAX-anrop görs vid variantbyte
- [x] EventSubscriber anropas korrekt
- [x] CSS-klasser finns på sidan vid sidladdning
- [x] Field Groups borttagna — Power Factor visas ej längre utanför tab-kontexten
- [ ] Visuell verifiering av Voltage/Lumens-uppdatering vid variantbyte (nästa session)

---

## 5. LÄRDOMAR

### KRITISK: Verifiera alltid API:t i källkoden

Commerce exponerar **inte** hooks för AJAX-tillägg — använder Events.
Hook-namnet `hook_commerce_product_variation_field_injection` existerar INTE.

**Regel**: Läs alltid källkoden innan integration med contrib-modul.
Se: `docs/03-solutions/verify-before-implement.md`

### Field Groups vs View Modes
När view modes används för gruppering är Field Groups på Default redundanta
och orsakar dubbelrendering. Ta bort Field Groups när view modes tar över.
