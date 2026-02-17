# Quote System - Decisions & Implementation
**Date:** 2026-02-15  
**Session:** Quote Request Checkout Flow Configuration  
**Status:** ✅ Core Complete | ⏳ Template Override in Progress

---

## 🎯 BUSINESS MODEL BESLUT

### Fas 1: Quote-Based System (CURRENT)
**Target Users:** B2B Electricians requesting quotes

**Key Decisions:**
- ✅ NO public pricing display in Quote checkout UI
- ✅ "Request Quote" workflow (not direct purchase)
- ✅ Phone/email primary channels initially
- ✅ Internal price tracking for calculations (retained in data)
- ✅ Quote-based checkout flow without payment
- ✅ **CRITICAL:** Prices retained in product data for future JSON/API exports

### Future (Fas 2): Hybrid E-commerce + API Distribution
**Expansion Plan:**
- Multi-tier partner access with different pricing levels
- Direct e-commerce for specific products/partners
- CSV product imports (daily via cron)
- **JSON/API product exports with list prices** (för partners/distributörer)
- Separate checkout flows per order type

---

## 🏗️ TECHNICAL ARCHITECTURE DECISIONS

### Decision: Two Order Types Approach (Approach B)

**Rationale:**
- Allows separate workflows for quote vs e-commerce
- Different pricing strategies per order type
- Clean separation of concerns
- Future-proof for multi-channel expansion

**Implementation:**
```
Order Type: "Quote" (current)
├─ Checkout Flow: quote_request
├─ No payment required
├─ No prices in UI
└─ Custom contact fields

Order Type: "Default" (reserved for future)
├─ Checkout Flow: Multistep - Default
├─ Payment gateway integration
├─ Prices visible
└─ Standard e-commerce workflow
```

---

## ✅ COMPLETED IMPLEMENTATION

### 1. Quote Order Type Created
**Path:** `/admin/commerce/config/order-types/quote/edit`

**Configuration:**
- Label: "Quote"
- Machine name: `quote`
- Workflow: Default
- Checkout flow: `quote_request`

---

### 2. Custom Contact Fields Added

**Path:** `/admin/commerce/config/order-types/quote/edit/fields`

**Fields Created:**
| Field Name | Machine Name | Type | Required |
|------------|-------------|------|----------|
| Company Name | `field_company_name` | Text (plain) | ✅ Yes |
| Contact Person | `field_contact_person` | Text (plain) | ✅ Yes |
| Phone | `field_phone` | Telephone | ✅ Yes |
| Contact Email | `mail` | Email (core) | ✅ Yes |
| Project Description | `field_project_description` | Text (long) | ❌ No |
| Desired Delivery Date | `field_desired_delivery_date` | Date | ❌ No |

---

### 3. Checkout Form Display Mode Configured

**Issue Encountered:** "Checkout" form display mode missing initially

**Solution:** 
1. Created custom form display mode via `/admin/structure/display-modes/form/add`
   - Entity type: Commerce Order
   - Machine name: `checkout`
2. Enabled for Quote order type via "Custom display settings"

**Path:** `/admin/commerce/config/order-types/quote/edit/form-display/checkout`

**Enabled Fields:**
- ✅ Company name
- ✅ Contact person
- ✅ Phone
- ✅ Contact email
- ✅ Project description
- ✅ Desired delivery date
- ✅ Order items (product display)

**Disabled Fields:**
- ❌ Cart, Coupons, Adjustments, Billing information, Order number (system fields)

---

### 4. Quote Request Checkout Flow Created

**Path:** `/admin/commerce/config/checkout-flows/quote_request/edit`

**Configuration:**
- Machine name: `quote_request`
- Label: "Quote Request"

**Steps Configured:**
| Step | Panes | Status |
|------|-------|--------|
| Login | Log in or continue as guest | ✅ Enabled |
| Order information | Contact info, Order fields checkout | ✅ Enabled |
| Review | Order summary | ✅ Enabled |
| Payment | (no panes) | ✅ Disabled |
| Complete | Completion message, Guest registration | ✅ Enabled |

**Panes Disabled:**
- ❌ Coupon redemption (moved to Disabled section)

---

### 5. commerce_checkout_order_fields Module Integration

**Modules Installed:**
```bash
drupal/feeds ^4.0
drupal/commerce_feeds ^1.0@alpha
drupal/feeds_tamper (dependency)
drupal/commerce_checkout_order_fields ^3.0
```

**Purpose:** 
- Displays custom order fields as checkout panes
- No custom code required for field display in checkout

**Issue Encountered:** "Order fields: Checkout" pane not appearing initially

**Solution:**
- Pane requires checkout form display mode to exist first
- After form display mode created, pane appeared automatically in "Disabled" section
- Moved pane from "Disabled" to "Order information" step

**Pane Configuration:**
- Wrapper element: fieldset
- Label: "Order fields: Checkout" (customizable)
- Displays all custom quote fields in checkout

---

### 6. String Overrides Configuration

**Module:** `drupal/stringoverrides ^2.0`

**Status:** ✅ INSTALLED & CONFIGURED

**Path:** `/admin/config/regional/stringoverrides`

**Configured Strings:**
| Original | Override | Status |
|----------|----------|--------|
| `Add to cart` | `Request Quote` | ✅ Active |
| `Shopping cart` | `Quote Request` | ✅ Active |
| `View cart` | `View Quote` | ✅ Active |
| `Order summary` | `Quote summary` | ✅ Active |
| `Continue to review` | `Continue to review quote` | ✅ Active |

**Rationale:**
- ✅ No code required
- ✅ GUI-based configuration
- ✅ Update-safe
- ✅ Acceptable for Fas 1 (Quote only)
- ⚠️ Future: Switch to custom module with `hook_form_alter()` when e-commerce is added (conditional logic per order type)

---

### 7. Price Hiding Implementation - PARTIAL

**Decision:** Separate Data Retention from UI Display

**CRITICAL REQUIREMENT:**
- ✅ Prices MUST remain in product variation data
- ✅ Prices MUST be available for JSON/API exports (för partners)
- ❌ Prices MUST NOT be visible in Quote checkout UI
- ❌ Prices MUST NOT be visible in HTML source (security/UX)

**Completed:**

**A) Cart View:**
- Path: `/admin/structure/views/view/commerce_cart_form`
- Price fields: Excluded from display

**B) Checkout Order Summary View:**
- Path: `/admin/structure/views/view/commerce_checkout_order_summary`
- Price fields: Excluded from display

**C) Product Variation Display:**
- Path: `/admin/commerce/config/product-variation-types/luminaire_variation/edit/display`
- Price field: Moved to "Disabled"

**Pending (see section 8):**
- ⏳ Order Items Table in Checkout (requires template override)

---

### 8. Order Item Type Configuration

**Path:** `/admin/commerce/config/order-item-types/default/edit`

**Decision:** All products use Quote order type

**Configuration:**
- Order type: **Quote** (changed from "Default")
- All cart additions create Quote orders
- All products use `quote_request` checkout flow
- Simplifies initial implementation (no conditional logic needed)

**Future:** When e-commerce is added, create separate "Quote" order item type for B2B products.

---

## ⏳ IN PROGRESS: Custom Template Override

### Decision: Template Override for Order Items Table

**Issue:**
- Order items table in checkout displays "Unit price" column
- Inline entity form widget doesn't expose column configuration in GUI
- CSS hiding would leave prices visible in HTML source (security concern)
- Disabling field globally would break future JSON/API exports

**Approved Solution:** Custom Twig template with conditional logic

**Rationale:**
1. **Data Integrity:** Prices remain in database and API responses
2. **Security:** Prices not rendered in HTML for Quote checkout
3. **Flexibility:** Same data serves both Quote UI and API exports
4. **Future-Proof:** Conditional logic allows e-commerce prices later

**Implementation Plan:**

**Template:** `themes/tritonled_radix/templates/commerce-order-item-table.html.twig`

**Logic:**
```twig
{# Conditional price display based on order type and context #}
{% set is_quote_checkout = order.bundle == 'quote' %}
{% set hide_prices = is_quote_checkout %}

{# Table structure with conditional price column #}
```

**Affected Locations:**
- Checkout "Order information" step (order items inline form)
- Checkout "Review" step (order summary)

**Why This Approach:**
- ✅ Server-side logic (secure)
- ✅ Prices never rendered for Quote orders
- ✅ Prices available for API/export
- ✅ Conditional - works for future e-commerce
- ✅ Follows Drupal templating best practices

**Methodology Alignment:**
- Decision Tree Step: Custom template (step 5)
- Approved: Stefan explicit approval given
- Reason: No contrib module solution exists for conditional column display in inline entity forms
- Alternative: CSS (rejected - security concern)

**Status:** ⏳ AWAITING TEMPLATE CREATION

---

## 🔄 VERIFICATION COMMANDS

```bash
# Verify Quote order type checkout flow
ddev drush config:get commerce_order.commerce_order_type.quote third_party_settings

# Verify checkout flow configuration
ddev drush config:get commerce_checkout.commerce_checkout_flow.quote_request

# Verify commerce_checkout_order_fields module
ddev drush pm:list | grep commerce_checkout_order_fields

# Verify String Overrides module
ddev drush pm:list | grep stringoverrides
```

---

## 📋 COMPLETE CHECKOUT FLOW TESTING

**Test Checklist:**
1. [✅] Add product to cart - Button shows "Request Quote"
2. [✅] Cart page shows "Quote Request" heading
3. [✅] No prices in cart view
4. [✅] Checkout uses `quote_request` flow
5. [✅] Quote fields appear in "Order information" step
6. [✅] No coupon code field in checkout
7. [ ] No prices in order items table (pending template)
8. [ ] Complete quote request submission
9. [ ] Verify admin receives quote data
10. [ ] Test guest checkout flow
11. [ ] Test logged-in user checkout flow

---

## 🔮 FUTURE ENHANCEMENTS (Fas 2)

### When E-commerce is Added:

**1. Create Separate Order Item Types:**
```
Order Item Type: "Quote Item"
├─ Order Type: Quote
└─ For B2B products

Order Item Type: "Default"
├─ Order Type: Default
└─ For e-commerce products
```

**2. Conditional Price Display in Templates:**
- Template already has conditional logic
- Just change condition to check order type
- Show prices for Default orders, hide for Quote orders

**3. Button Text Logic:**
- Replace String Overrides with custom module
- `hook_form_alter()` with conditional logic
- Different button text per order type

**4. CSV Product Import:**
- Configure Feeds module
- Create import configuration for daily product updates
- Map CSV columns to product fields
- Set up cron job for automated imports

**5. Multi-tier Partner Pricing:**
- Install Price Lists module (or similar)
- Configure partner tiers
- Assign price lists to user roles
- Conditional checkout flows per tier

**6. JSON/API Product Exports:**
- Configure JSON:API module (core in D11)
- Expose product variations with prices
- Partner authentication/access control
- Automated daily exports or real-time API

---

## 📋 CONFIGURATION REFERENCE PATHS

### Order Configuration
- Order types: `/admin/commerce/config/order-types`
- Order item types: `/admin/commerce/config/order-item-types`
- Checkout flows: `/admin/commerce/config/checkout-flows`

### Display Configuration
- Form display modes: `/admin/structure/display-modes/form`
- Product variation types: `/admin/commerce/config/product-variation-types`

### Views
- Cart form: `/admin/structure/views/view/commerce_cart_form`
- Checkout order summary: `/admin/structure/views/view/commerce_checkout_order_summary`

### String Overrides
- Configuration: `/admin/config/regional/stringoverrides`

---

## 🔧 TROUBLESHOOTING NOTES

### Issue: commerce_checkout_order_fields Pane Not Appearing

**Symptoms:**
- Module enabled
- Pane not visible in checkout flow configuration

**Root Cause:**
- Pane requires "Checkout" form display mode to exist
- Form display mode must be enabled for the order type

**Solution:**
1. Create form display mode: `/admin/structure/display-modes/form/add`
2. Enable for order type: `/admin/commerce/config/order-types/quote/edit/display`
3. Configure fields: `/admin/commerce/config/order-types/quote/edit/form-display/checkout`
4. Pane appears automatically in checkout flow "Disabled" section
5. Drag pane to desired step

**Prevention:**
Always create form display modes before installing modules that depend on them.

---

### Issue: Wrong Checkout Flow Being Used

**Symptoms:**
- Checkout form class shows `commerce-checkout-flow-multistep-default`
- Should show `commerce-checkout-flow-quote-request`

**Root Cause:**
- Order Item Type "Default" pointing to wrong Order Type

**Solution:**
1. Go to: `/admin/commerce/config/order-item-types/default/edit`
2. Change "Order type" from "Default" to "Quote"
3. Save
4. Clear cache: `ddev drush cr`
5. Empty cart and re-add products
6. Verify checkout form class in HTML

---

## 📚 METHODOLOGY ALIGNMENT

This implementation follows the established decision tree:

1. ✅ **Configuration First:** All quote fields via Drupal field system
2. ✅ **Contrib Modules:** commerce_checkout_order_fields for pane integration
3. ✅ **String Overrides:** For UI text changes (GUI-based)
4. ⏳ **Custom Template:** APPROVED for order items table (conditional price display)

**Custom Code Used:**
- Template override for order items table (approved)
- Rationale: No GUI solution for conditional column display in inline entity forms
- Security requirement: Prices must not be in HTML for Quote orders
- Data requirement: Prices must remain for JSON/API exports

**Custom Code Deferred to Fas 2:**
- `hook_form_alter()` for button text per order type (conditional logic)
- Conditional view displays or additional templates

---

## 🎯 SESSION OUTCOME

**Completed:**
- ✅ Quote order type with custom fields
- ✅ Quote checkout flow without payment
- ✅ Coupon redemption disabled in Quote flow
- ✅ String Overrides configured for all UI text
- ✅ Price hiding in cart and checkout summary views
- ✅ commerce_checkout_order_fields integration
- ✅ All configuration documented
- ✅ Template override decision documented and approved

**In Progress:**
- ⏳ Custom template for order items table (conditional price display)

**Next Steps:**
1. Create template override for commerce-order-item-table
2. Test template in Quote checkout
3. Verify prices hidden in HTML source
4. Complete full checkout flow testing
5. Document any issues or refinements needed

**Status:** Configuration phase complete. Template implementation approved and ready to proceed.
