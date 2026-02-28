# Task 010: Layout Builder + Commerce Variation Field Injection

**Created**: 2026-02-28
**Status**: Research / Planning
**Priority**: KRITISK — Arkitekturbeslut
**Related Tasks**: TASK-009

---

## 1. BAKGRUND

### Problemet vi löser
TASK-009 visade att variation-fält på Commerce produktsidan är svåra att styra via Drupal UI:
- Field Group fungerar inte med Commerce variation injection
- Fälten renderas som en flat lista utan möjlighet att gruppera via GUI
- Enda fungerande alternativet har hittills varit manuell template-kod

### Varför detta är viktigt
Om Layout Builder kan styra produktsidan med fungerande variation field injection ger det:
- Fullständig GUI-kontroll över produktsidans layout
- Drag-and-drop för alla fält inklusive variationsfält
- Field Group fungerar på Layout Builder blocks
- Skalbart: framtida ändringar görs i UI, inte i kod
- Hållbart: ingen teknisk skuld från workarounds

---

## 2. HYPOTES

Layout Builder stöder redan Commerce produktsidor — men variation field injection
kan ha buggar. Om det fungerar behöver vi ingen custom kod alls.

**Känd bugg**: `commerce_product_block_alter` kontrollerar fel `base_plugin_id`
vilket kan krascha extra fields i Layout Builder.
Referens: https://www.drupal.org/project/commerce/issues/3182636

---

## 3. TEST SOM SKA GENOMFÖRAS

### Test 1: Aktivera Layout Builder på Commerce product type
1. Gå till: `/admin/commerce/config/product-types/default/edit/display`
2. Aktivera "Use Layout Builder"
3. Spara
4. Gå till produktsidan `/product/8`
5. Verifiera: renderas sidan korrekt?

### Test 2: Variation field injection med Layout Builder
1. I Layout Builder — lägg till ett variationsfält (t.ex. field_lumens) som block
2. Spara layout
3. Byt variant på produktsidan
4. Verifiera: uppdateras fältet via AJAX?

### Test 3: Field Group i Layout Builder
1. Lägg till en Field Group (tabs) via Layout Builder section
2. Placera variation-fält i Field Group
3. Verifiera: renderas tabs korrekt?
4. Byt variant — uppdateras fälten?

### Test 4: Kontrollera Console
- Inga JS-fel vid sidladdning
- Inga JS-fel vid variantbyte
- AJAX-requests ser korrekta ut (Network tab)

---

## 4. FÖRVÄNTADE UTFALL

### Scenario A: Allt fungerar ✓
→ Aktivera Layout Builder permanent på product type
→ Ta bort custom template-kod (TASK-009)
→ Konfigurera produktsidans layout via GUI
→ Ingen custom kod behövs

### Scenario B: Layout Builder fungerar men AJAX inte uppdaterar ✗
→ Undersök `commerce_product_block_alter` buggen
→ Överväg patch eller workaround i tritonled_compat
→ Referens: https://www.drupal.org/project/commerce/issues/3182636

### Scenario C: Layout Builder kraschar helt ✗
→ Fallback: pseudo-fält via `hook_entity_extra_field_info()`
→ Exponera tab-grupper som pseudo-fält på commerce_product
→ Placerbara i Layout Builder, AJAX via `hook_commerce_product_variation_field_injection()`
→ Ca 100 rader PHP i tritonled_compat

---

## 5. ARKITEKTURELLT BESLUT

Detta test avgör hela produktsidans arkitektur.

**Om Layout Builder fungerar:**
- GUI-baserad layout för alltid
- Inga templates för fältplacering
- Field Group för tab-gruppering

**Om Layout Builder inte fungerar:**
- Pseudo-fält approach (Scenario C)
- Fortfarande GUI-baserat men med initial custom kod
- Hållbart och skalbart

**Det vi INTE gör oavsett:**
- Ingen JavaScript för att flytta DOM-element
- Ingen hårdkodad template för varje fält
- Ingen CSS-position-hacking

---

## 6. TESTRESULTAT

*Fylls i under testet*

### Test 1: Layout Builder aktiverat
- [ ] Sidan renderas korrekt
- [ ] Inga PHP-fel
- Anteckningar:

### Test 2: Variation field injection
- [ ] Fält uppdateras vid variantbyte
- [ ] AJAX fungerar
- Anteckningar:

### Test 3: Field Group i Layout Builder
- [ ] Tabs renderas
- [ ] AJAX uppdaterar fält i tabs
- Anteckningar:

### Test 4: Console
- [ ] Inga JS-fel
- Anteckningar:

---

## 7. BESLUT

*Fattas efter test*

Valt scenario: __
Motivering: __
Godkänt av Stefan: __

---

## 8. LÄRDOMAR

*Fylls i efter completion*
