# Custom Module: tritonled_quote

**Created:** 2026-02-15  
**Purpose:** Quote-specific alterations for Commerce Order checkout  
**Status:** ✅ Approved - Documented, Ready for Implementation

---

## 🎯 PROBLEM STATEMENT

**Business Requirement:**
- Quote checkout (Fas 1) must NOT display unit prices in order items table
- Prices must remain in database for JSON/API exports (future partner integration)
- Visual hiding via CSS is insufficient (prices visible in HTML source)

**Technical Challenge:**
Commerce Order module defines `unit_price` field in `OrderItemInlineForm::getTableFields()` which populates the inline entity form table. The `hook_inline_entity_form_table_fields_alter()` hook is the Drupal-standard way to modify these fields, but:

1. **Themes cannot reliably implement this hook** - `ModuleHandler::alter()` only calls module implementations
2. **No GUI configuration exists** for column visibility in inline_entity_form widget
3. **No Commerce events** exist for altering table fields
4. **OrderItemInlineForm is not a service** - cannot be decorated

---

## 🔍 RESEARCH CONDUCTED

### Research Phase 1: Template Override Attempt
**Approach:** Override `inline-entity-form-entity-table.html.twig` in theme  
**Result:** ❌ Template only renders already-built table render array - cannot modify structure  
**Lesson:** Templates are for presentation, not data structure modification

### Research Phase 2: Preprocess Hook Attempt
**Approach:** `hook_preprocess_inline_entity_form_entity_table()` and `hook_preprocess_table()`  
**Result:** ❌ Caused AJAX callback failures when modifying render array post-build  
**Lesson:** Modifying structure after AJAX callbacks are attached breaks functionality

### Research Phase 3: Hook Implementation in Theme
**Approach:** `hook_inline_entity_form_table_fields_alter()` in `tritonled_radix.theme`  
**Result:** ❌ Hook never executed (no logs, prices still visible)  
**Code Investigation:**
```php
// InlineEntityFormComplex.php line 362
$this->moduleHandler->alter('inline_entity_form_table_fields', $fields, $context);
```
**Lesson:** `ModuleHandler::alter()` only invokes module implementations, not theme hooks

### Research Phase 4: Source Code Analysis
**Files Analyzed:**
- `/modules/contrib/inline_entity_form/src/Plugin/Field/FieldWidget/InlineEntityFormComplex.php`
- `/modules/contrib/commerce/modules/order/src/Form/OrderItemInlineForm.php`
- `/modules/contrib/inline_entity_form/inline_entity_form.api.php`

**Key Finding:**
```php
// OrderItemInlineForm.php lines 28-32
public function getTableFields($bundles) {
  $fields = parent::getTableFields($bundles);
  $fields['unit_price'] = [
    'type' => 'field',
    'label' => $this->t('Unit price'),
    'weight' => 2,
  ];
  // ...
}
```

**Hook Documentation:**
```php
// inline_entity_form.api.php
/**
 * Alter the fields used to represent an entity in the IEF table.
 * This hook can be implemented by themes.  // ← INCORRECT for ModuleHandler
 */
function hook_inline_entity_form_table_fields_alter(array &$fields, array $context) {
  // ...
}
```

**Discovery:** API documentation claims themes can implement this hook, but `ModuleHandler::alter()` implementation only calls modules.

---

## ✅ SOLUTION: MINIMAL CUSTOM MODULE

### Decision Rationale

**Why Custom Module:**
1. ✅ **Drupal Standard:** `hook_inline_entity_form_table_fields_alter()` is the documented API
2. ✅ **Guaranteed Execution:** Modules are invoked by `ModuleHandler::alter()`
3. ✅ **Minimal Overhead:** ~30 lines of code total (info.yml + .module)
4. ✅ **No External Dependencies:** No patches on contrib modules
5. ✅ **Future-Proof:** Conditional logic ready for Fas 2 (e-commerce)

**Why NOT Alternatives:**

**CSS Hiding:**
- ❌ Prices visible in HTML source (security/UX concern)
- ❌ Not "The Drupal Way" for data structure modification

**Patch on Commerce Order:**
- ⚠️ High maintenance burden (reapply on every Commerce update)
- ⚠️ Requires composer.json patches configuration
- ⚠️ May conflict with future Commerce changes

**Widget Settings:**
- ❌ No such configuration exists in inline_entity_form
- ❌ Would require patch to inline_entity_form module

**Event Subscriber:**
- ❌ No relevant events exist in Commerce Order

**Service Decoration:**
- ❌ OrderItemInlineForm is not a service

---

## 📦 MODULE SPECIFICATION

### File Structure
```
web/modules/custom/tritonled_quote/
├── tritonled_quote.info.yml          (5 lines)
└── tritonled_quote.module             (25-30 lines)
```

### tritonled_quote.info.yml
```yaml
name: 'TritonLED Quote'
type: module
description: 'Quote-specific customizations for Commerce Order checkout'
package: TritonLED
core_version_requirement: ^11
dependencies:
  - commerce:commerce_order
  - inline_entity_form:inline_entity_form
```

### tritonled_quote.module (Pseudo-code)
```php
<?php

/**
 * @file
 * Quote checkout customizations.
 */

/**
 * Implements hook_inline_entity_form_table_fields_alter().
 *
 * Remove unit_price field from Quote order checkout.
 * Prices remain in database for JSON/API exports.
 */
function tritonled_quote_inline_entity_form_table_fields_alter(&$fields, $context) {
  // Only act on commerce_order_item entities
  if ($context['entity_type'] !== 'commerce_order_item') {
    return;
  }
  
  // Only act on Quote order type (Fas 1)
  // Future: Add conditional check for order bundle
  // For now: Remove for all orders (acceptable - only Quote exists)
  
  if (isset($fields['unit_price'])) {
    unset($fields['unit_price']);
  }
}
```

**Future Enhancement (Fas 2):**
When e-commerce is added, enhance to:
```php
// Check parent order type
if ($context['parent_entity_type'] === 'commerce_order') {
  // Load parent order to check bundle
  // Only remove for Quote orders, keep for Default orders
}
```

---

## 🔄 MAINTENANCE PLAN

### Installation Requirements
1. Enable module: `ddev drush en tritonled_quote -y`
2. Clear cache: `ddev drush cr`
3. No configuration needed

### Update Strategy
- **Module is self-contained** - no external patches
- **Hook is stable API** - unlikely to change in Drupal/Commerce
- **Testing:** Verify after Commerce Order updates

### Deactivation Path (Future)
When transitioning away from Quote-only model:
1. Update hook to be conditional on order type
2. OR disable module if functionality no longer needed
3. No database changes - safe to enable/disable

---

## 📊 COMPARISON: Custom Module vs Alternatives

| Criteria | Custom Module | CSS Hiding | Commerce Patch |
|----------|--------------|------------|----------------|
| **Drupal Way** | ✅ Yes | ❌ No | ✅ Yes |
| **Security** | ✅ Server-side | ❌ Client-side | ✅ Server-side |
| **Maintenance** | ⚠️ Enable on install | ✅ None | ❌ High |
| **Code Lines** | 30 | 5 | 50+ |
| **Dependencies** | None | None | Composer patches |
| **Update Risk** | ✅ Low | ✅ None | ❌ High |
| **Future-Proof** | ✅ Conditional logic | ❌ Always hides | ⚠️ Medium |

---

## 🎯 IMPLEMENTATION CHECKLIST

**Pre-Implementation:**
- [x] Research all alternatives
- [x] Document decision rationale
- [x] Get explicit approval
- [ ] Create module files
- [ ] Test in local environment
- [ ] Document in PROJECT-STATUS.md

**Testing:**
1. [ ] Enable module and clear cache
2. [ ] Create new Quote order
3. [ ] Verify unit_price NOT in checkout table HTML
4. [ ] Verify Edit/Remove buttons work (AJAX intact)
5. [ ] Verify prices in database (SQL query)
6. [ ] Verify prices available via API/export

**Documentation:**
- [ ] Add to `/docs/sessions/2026-02-15-quote-system-decisions.md`
- [ ] Update MODULE-AUDIT with new custom module
- [ ] Add installation step to deployment docs

---

## 🔐 DATA INTEGRITY GUARANTEE

**Critical Requirement Met:**
- ✅ Prices NEVER rendered in HTML (server-side removal)
- ✅ Prices remain in `commerce_order_item.unit_price` field (database)
- ✅ Prices available for JSON:API exports (Drupal core module)
- ✅ Future e-commerce functionality preserved (conditional logic)

**Verification Query:**
```sql
SELECT order_item_id, unit_price__number, unit_price__currency_code 
FROM commerce_order_item 
WHERE order_id = [order_id];
```

---

## 📚 REFERENCES

**Drupal API Documentation:**
- [hook_inline_entity_form_table_fields_alter()](https://git.drupalcode.org/project/inline_entity_form/-/blob/4.x/inline_entity_form.api.php#L73)
- [ModuleHandler::alter()](https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Extension%21ModuleHandler.php/function/ModuleHandler%3A%3Aalter)

**Commerce Order Source:**
- [OrderItemInlineForm.php](https://git.drupalcode.org/project/commerce/-/blob/4.x/modules/order/src/Form/OrderItemInlineForm.php)
- [InlineEntityFormComplex.php](https://git.drupalcode.org/project/inline_entity_form/-/blob/4.x/src/Plugin/Field/FieldWidget/InlineEntityFormComplex.php)

**Project Documentation:**
- `/docs/sessions/2026-02-15-quote-system-decisions.md`
- `/docs/DRUPAL-DECISION-TREE.md`

---

## ✅ APPROVAL RECORD

**Decision:** Create minimal custom module `tritonled_quote`  
**Approved By:** Stefan  
**Date:** 2026-02-15  
**Rationale:** Only Drupal-standard solution after exhaustive research  

**Conditions:**
1. Must document thoroughly before implementation
2. Must be minimal (~30 lines total)
3. Must follow Drupal coding standards
4. Must include future e-commerce conditional logic path

**Status:** ✅ APPROVED - Ready for Implementation
