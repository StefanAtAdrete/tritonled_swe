# Aktuell Task

**Task**: TASK-011 (In Progress)
**Status**: In Progress
**Senast uppdaterad**: 2026-03-01
**Fil**: `/docs/tasks/task-011-specifications-tabs.md`

## Vad som gjordes denna session

### Template → Layout Builder migration
- `commerce-product--default.html.twig` reducerad till minimal wrapper
- All layout hanteras nu av Layout Builder (ingen dubbel layout)
- `lb_tabs` aktiverad och används för Specifications-tabs

### lb_tabs — Specifications
- Electrical, Mechanical, Certifications placerade som block i lb_tabs-sektion
- AJAX fungerar via befintlig EventSubscriber (ReplaceCommand på CSS-klasser)

### Splide thumbnail-fix
- Första tumnageln klipptes av `overflow: hidden` på `.splide__track`
- Fix: `.splide--nav .splide__track { overflow: visible !important; }`
- Aktiv-markering bytt från `border` till `outline` (påverkar ej Splide layoutberäkning)
- `trimSpace: move` satt i product_nav optionset

## Nästa steg

- Layout Builder för variation view modes (Electrical/Mechanical/Certifications)
  → Aktivera Layout Builder per view mode för kolumnlayout inuti tabs
- Committa och cex
