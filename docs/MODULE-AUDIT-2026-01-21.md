# Module Audit - TritonLED Production Optimization

**Date:** 2026-01-21  
**Purpose:** Identify modules to remove for lean, performant production site  
**Current Total:** ~140 modules enabled

---

## Audit Methodology

Following DRUPAL-FACTORY v1.0 principles:
- **Production efficiency** - Remove development/research modules
- **Performance** - Minimize overhead
- **Maintainability** - Keep only what's actively used

---

## Module Categories

### ✅ CRITICAL - Keep for Production (Core E-commerce)

**Commerce (14 modules):**
- ✅ commerce
- ✅ commerce_cart
- ✅ commerce_checkout
- ✅ commerce_log
- ✅ commerce_number_pattern
- ✅ commerce_order
- ✅ commerce_price
- ✅ commerce_product
- ✅ commerce_promotion
- ✅ commerce_stock + commerce_stock_field + commerce_stock_local + commerce_stock_ui
- ✅ commerce_store
- ✅ commerce_tax

**Design & Layout (6 modules):**
- ✅ layout_builder
- ✅ layout_builder_styles
- ✅ layout_discovery
- ✅ bootstrap_layout_builder
- ✅ bootstrap_styles
- ✅ block_class

**SEO & URLs (3 modules):**
- ✅ pathauto
- ✅ redirect
- ✅ token

**Media & Images (5 modules):**
- ✅ media
- ✅ media_library
- ✅ media_library_form_element
- ✅ imageapi_optimize
- ✅ imageapi_optimize_webp
- ✅ imageapi_optimize_webp_responsive
- ✅ responsive_image

**Forms & Entity (4 modules):**
- ✅ inline_entity_form
- ✅ entity
- ✅ entity_reference_revisions
- ✅ state_machine (Commerce dependency)

**Performance (3 modules):**
- ✅ big_pipe
- ✅ dynamic_page_cache
- ✅ page_cache

**Essential Core (30+ modules):**
- ✅ All standard Drupal core: node, taxonomy, field, user, views, system, file, image, text, datetime, link, options, etc.

**Total Critical: ~70 modules**

---

### ⚠️ DEVELOPMENT ONLY - Remove in Production

**AI Ecosystem (8 modules) - NOT NEEDED IN PRODUCTION:**
- ❌ ai
- ❌ ai_agents
- ❌ ai_agents_explorer
- ❌ ai_assistant_api
- ❌ ai_automators
- ❌ ai_chatbot
- ❌ ai_provider_openai
- ❌ modeler_api
**Reason:** Only used for Claude development workflow, not site functionality

**MCP Tools (16 modules) - DEVELOPMENT ONLY:**
- ❌ mcp_tools (base)
- ❌ mcp_tools_analysis
- ❌ mcp_tools_cache
- ❌ mcp_tools_config
- ❌ mcp_tools_content
- ❌ mcp_tools_image_styles
- ❌ mcp_tools_jsonapi
- ❌ mcp_tools_layout_builder
- ❌ mcp_tools_media
- ❌ mcp_tools_pathauto
- ❌ mcp_tools_redirect
- ❌ mcp_tools_remote
- ❌ mcp_tools_stdio
- ❌ mcp_tools_structure
- ❌ mcp_tools_theme
- ❌ mcp_tools_views
**Reason:** Used by Claude for site building, not needed when site is running

**Tool Modules (7 modules) - DEVELOPMENT ONLY:**
- ❌ tool
- ❌ tool_ai_connector
- ❌ tool_content
- ❌ tool_content_translation
- ❌ tool_entity
- ❌ tool_explorer
- ❌ tool_system
- ❌ tool_user
**Reason:** Development framework, not production functionality

**JSON-RPC (3 modules) - DEVELOPMENT ONLY:**
- ❌ jsonrpc
- ❌ jsonrpc_core
- ❌ jsonrpc_discovery
**Reason:** Used by MCP/Tool communication

**Admin UI (5 modules) - DEVELOPMENT ONLY:**
- ❌ field_ui
- ❌ views_ui
- ❌ contextual
- ❌ toolbar (if Claro not used)
- ❌ shortcut
**Reason:** Only needed for site building, not for visitors

**Custom Development Module:**
- ❌ tritonled_commerce_agent
**Reason:** Likely a test/research module, verify if needed

**Total to Remove: ~40 modules**

---

### 🤔 EVALUATE - Discuss Usage

**Content Management:**
- ❓ content_moderation + workflows
  - **Question:** Planning editorial workflow or content approval?
  - **If NO:** Remove both
  - **If YES:** Keep both

- ❓ comment
  - **Question:** Product reviews/comments needed?
  - **If NO:** Remove
  - **If YES:** Keep (or use contrib review module)

- ❓ webform
  - **Question:** Contact forms, surveys needed?
  - **If NO:** Remove
  - **If YES:** Keep

**Search:**
- ❓ search
  - **Question:** Internal site search needed?
  - **If NO:** Remove (use external search or Views filters)
  - **If YES:** Keep (or upgrade to Search API later)

**Logging:**
- ❓ dblog vs syslog
  - **Current:** Both enabled
  - **Recommendation:** Production should use syslog only (better performance)
  - **Action:** Remove dblog in production

**Other:**
- ❓ contact
  - **Question:** Contact form module needed?
  - **If NO:** Remove (use webform or custom)
  
- ❓ help
  - **Production:** Not needed
  - **Action:** Remove

- ❓ announcements_feed
  - **Production:** Not needed (shows Drupal.org news)
  - **Action:** Remove

- ❓ navigation (Drupal 11 new)
  - **Question:** Using new navigation vs toolbar?
  - **Action:** Evaluate after testing

**API/External:**
- ❓ jsonapi
  - **Question:** Exposing content via JSON:API?
  - **If NO:** Remove
  - **If YES:** Keep + secure properly

- ❓ rest + serialization + basic_auth
  - **Question:** External API integrations?
  - **If NO:** Remove all three
  - **If YES:** Keep + secure

**Internationalization:**
- ❓ language + content_translation
  - **Question:** Multilingual site?
  - **If NO:** Remove both
  - **If YES:** Keep

---

## Themes Evaluation

**Current Themes:**
- olivero (Drupal 11 default frontend)
- claro (Admin theme)
- radix (Base theme)
- tritonled_radix (Custom theme based on Radix)

**Recommendation:**
- ✅ Keep: claro (admin), tritonled_radix (production)
- ❓ Radix: Keep if tritonled_radix extends it
- ❌ Remove: olivero (not used if custom theme active)

---

## Immediate Actions

### Phase 1: Safe Removals (No Impact)

```bash
# Remove AI/MCP/Tool modules (development only)
ddev drush pm:uninstall ai ai_agents ai_agents_explorer ai_assistant_api ai_automators ai_chatbot ai_provider_openai modeler_api -y

ddev drush pm:uninstall mcp_tools mcp_tools_analysis mcp_tools_cache mcp_tools_config mcp_tools_content mcp_tools_image_styles mcp_tools_jsonapi mcp_tools_layout_builder mcp_tools_media mcp_tools_pathauto mcp_tools_redirect mcp_tools_remote mcp_tools_stdio mcp_tools_structure mcp_tools_theme mcp_tools_views -y

ddev drush pm:uninstall tool tool_ai_connector tool_content tool_content_translation tool_entity tool_explorer tool_system tool_user -y

ddev drush pm:uninstall jsonrpc jsonrpc_core jsonrpc_discovery -y

# Remove admin UI modules (keep for dev, remove for production)
# ddev drush pm:uninstall field_ui views_ui contextual shortcut -y

# Remove unused utilities
ddev drush pm:uninstall help announcements_feed -y

# Export config
ddev drush cex -y
```

### Phase 2: Evaluate & Decide

**Stefan needs to decide on:**
1. content_moderation/workflows - Editorial workflow needed?
2. comment - Product reviews needed?
3. webform - Forms needed?
4. search - Internal search needed?
5. jsonapi/rest - External API needed?
6. language/content_translation - Multilingual needed?

**Based on answers, remove unnecessary modules.**

### Phase 3: Production Optimization

```bash
# Replace dblog with syslog in production
ddev drush pm:uninstall dblog -y
# (syslog already enabled)

# Remove development tools
ddev drush pm:uninstall field_ui views_ui contextual -y

# Clear all caches
ddev drush cr
```

---

## Performance Impact Estimate

**Current:** ~140 modules  
**After Phase 1:** ~100 modules (-40 modules, -30%)  
**After Phase 2+3:** ~65-75 modules (depending on decisions, -45-50%)

**Expected improvements:**
- 🚀 Faster page loads (less code to bootstrap)
- 🚀 Smaller database (fewer tables/config)
- 🚀 Faster cache rebuilds
- 🚀 Easier updates (fewer modules to maintain)
- 🚀 Better security (smaller attack surface)

---

## Documentation Strategy

**Create Environment-Specific Configs:**

`/docs/DEVELOPMENT.md`:
- List of dev-only modules
- How to enable them locally
- MCP/AI tools setup

`/docs/PRODUCTION.md`:
- Production module list
- Performance settings
- Monitoring requirements

---

## Next Steps

1. **Stefan answers evaluation questions** (Phase 2 modules)
2. **Execute Phase 1 removals** (safe, no site impact)
3. **Test site functionality** after removals
4. **Execute Phase 2+3** based on decisions
5. **Document final production module list**
6. **Update methodology** with lessons learned

---

## Module Summary

| Category | Count | Status |
|----------|-------|--------|
| Critical (Keep) | ~70 | ✅ Required |
| Development (Remove) | ~40 | ❌ Safe to remove |
| Evaluate (Decide) | ~15 | ❓ Stefan decides |
| **Total Enabled** | **~140** | Current |
| **Target Production** | **65-75** | Goal |

---

**Created:** 2026-01-21  
**Status:** Awaiting Stefan's decisions on evaluation modules  
**Next Review:** After Phase 1 execution
