# Aktuell Task

**Task**: TASK-010b (commerce_variation_blocks — AJAX)
**Status**: Planning
**Senast uppdaterad**: 2026-02-28
**Fil**: `/docs/tasks/task-010b-commerce-variation-blocks-ajax.md`

> TASK-010 klar: modul byggd, pseudo-fält renderas i Layout Builder.
> Återstår: AJAX-uppdatering vid variantbyte via hook_commerce_product_variation_field_injection().

## Vad som ska göras

Visa variationsfält grupperade i Bootstrap tabs på produktsidan.
Fälten ska uppdateras via Commerce AJAX vid variantbyte.

## Approach (testar)
Field Group med Tabs-format — OSÄKERT om det fungerar med Commerce AJAX.
Testas explicit. Om det bryter AJAX utvärderas alternativ.

## Nasta steg
1. Installera Field Group
2. Skapa tab-grupper i variation view display
3. Verifiera Commerce AJAX fungerar
4. Exportera config och committa

## Relaterat
- Task-fil: docs/tasks/task-009-product-page-variation-tabs.md
- Tidigare task: TASK-008
