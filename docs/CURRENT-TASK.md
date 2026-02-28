# Aktuell Task

**Task**: TASK-010 (Layout Builder + Commerce Variation Field Injection)
**Status**: Research / Planning — KRITISKT ARKITEKTURBESLUT
**Senast uppdaterad**: 2026-02-28
**Fil**: `/docs/tasks/task-010-layout-builder-commerce-variation-injection.md`

> Detta test avgör hela produktsidans arkitektur.
> Genomför Test 1-4 i task-filen innan något byggs.

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
