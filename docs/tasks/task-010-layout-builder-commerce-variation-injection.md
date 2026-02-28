# Task 010: Layout Builder + Commerce Variation Field Injection

**Created**: 2026-02-28
**Status**: DELVIS KLAR — Subtask 010b för AJAX
**Priority**: KRITISK — Arkitekturbeslut
**Related Tasks**: TASK-009, TASK-010b

---

## 1. BAKGRUND

### Problemet vi löste
Variation-fält på Commerce produktsidan gick inte att gruppera via Drupal UI:
- Field Group fungerar inte med Commerce variation injection
- Commerce exponerar variation-rendering som ETT samlat block
- Separata view modes exponeras inte automatiskt som block i Layout Builder

### Lösningen vi byggde
Modulen `commerce_variation_blocks` (web/modules/custom/commerce_variation_blocks/)
exponerar varje custom view mode på `commerce_product_variation` som ett pseudo-fält
på `commerce_product` — placerbart i Layout Builder som vanliga block.

---

## 2. TESTER OCH LÄRDOMAR

### Test 1: Layout Builder på Commerce ✓
- Layout Builder fungerar på Commerce produktsidor utan krasch
- Variation AJAX-väljare fungerar med Layout Builder aktivt

### Test 2: Variation fields som individuella block ✗
- Commerce exponerar bara "Default variation" och "Variations" som block
- Inga individuella fält eller view modes exponeras automatiskt

### Test 3: commerce_variation_blocks modulen ✓ DELVIS
- Pseudo-fält skapas dynamiskt från alla variation view modes
- Block syns i Layout Builder block-biblioteket under "Product fields"
- Fälten renderas korrekt på produktsidan (Voltage, Lumens visas)
- **MEN**: AJAX uppdaterar INTE pseudo-fälten vid variantbyte ✗

### Kritisk lärdom: Layout Builder + getComponents()
När Layout Builder är aktivt på en entity lagras block i **sections**, inte i
`$display->getComponents()`. Hooken måste alltid rendera alla view modes —
Layout Builder styr synligheten via block-placering, inte via components.

### Kritisk lärdom: Commerce AJAX-systemet
Commerce's AJAX (`replaceRenderedFields`) skickar `ReplaceCommand` per fält
via CSS-klassen `product--variation-field--variation_{field}__{product_id}`.
Pseudo-fält på produktentiteten ingår INTE i detta system automatiskt —
de kräver en egen AJAX-implementation.

---

## 3. NULÄGE

### Vad som fungerar
- `commerce_variation_blocks` modul installerad och aktiv
- Electrical view mode exponeras som block i Layout Builder ✓
- Electrical-blocket placerat i produktens Layout Builder layout ✓
- Voltage och Lumens renderas på produktsidan ✓
- Statisk rendering fungerar perfekt ✓

### Vad som saknas
- AJAX-uppdatering av pseudo-fälten vid variantbyte ✗
- Hook: `hook_commerce_product_variation_field_injection()` behöver implementeras
- Commerce måste informeras om att ersätta hela pseudo-fält-containern vid AJAX

---

## 4. NÄSTA STEG → TASK-010b

Se: `/docs/tasks/task-010b-commerce-variation-blocks-ajax.md`

**Godkänt av Stefan**: ✓ 2026-02-28
