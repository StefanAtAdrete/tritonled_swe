# Session: MCP Tools Research & Methodology Creation

**Date:** 2026-01-21  
**Duration:** ~3 hours  
**Participants:** Stefan + Claude  
**Status:** Completed - Methodology v1.0 established

---

## Objectives

1. Understand why MCP structure tools weren't accessible
2. Research MCP/Tool/AI Agents architecture
3. Design systematic approach for building Drupal sites with AI
4. Create methodology documentation v1.0

---

## Key Discoveries

### MCP Tools Architecture

Discovered there are MULTIPLE MCP-related systems in Drupal:

1. **`drupal/mcp`** - Main MCP implementation (150 tools)
2. **`mcp_tools`** - Tool plugins system
3. **`Tool` module** - Generic tool plugin framework
4. **`AI Agents`** - Makes Drupal "taskable" by AI
5. **`mcp_tools_mcp_server`** - Bridge module (requires `mcp_server` dependency)

### Tool Accessibility Issue

**Problem:** 150 tools listed in `drush mcp:tools` but only 123 accessible via function calls.

**Root Cause:** Tools exist in multiple layers:
- **Tool Explorer UI** - Can see and execute all tools manually
- **MCP STDIO transport** - Only exposes subset of tools
- **MCP function calls** - What Claude can actually invoke

**Structure write tools** (create_vocabulary, create_term, etc.) are:
- ✅ Defined as Tool plugins
- ✅ Visible in Tool Explorer
- ✅ Listed in `mcp:tools`
- ❌ NOT exposed as callable function calls

### Attempted Solutions

1. **Tried activating `mcp_tools_mcp_server` bridge**
   - Result: Requires `drupal/mcp_server` module
   - Problem: `mcp_server` has dependency on `simple_oauth_21` 
   - Issue: Composer can't resolve (package name mismatch)

2. **Tried switching from `mcp-tools:server` to `mcp:serve`**
   - Result: Connection failed
   - Needs further research on proper configuration

3. **Attempted direct tool invocation**
   - Tried: `mcp-server-drupal:mcp_structure_setup_taxonomy`
   - Result: "Tool not found"

---

## Strategic Decision: Build Methodology First

Stefan made critical insight:
> "Det är nästan viktigare än sajten i sig. Arbetssättet behöver kunna förändras, uppdateras och byta ut delar allt eftersom Claude uppdateras, Drupals moduler uppdateras, AI-stöd uppdateras och annat."

### Agreed Principles

1. **E-commerce first** - If we can build complex e-commerce, we can build anything
2. **Production-ready definition:**
   - All functions working
   - Design implemented
   - Foundation content
   - SEO/indexing built-in from start
   - Clear CI/CD procedures
3. **Control over all parts** - Build, evaluate, develop, maintain
4. **Methodology as product** - Version controlled, evolvable, documented

---

## Methodology v1.0 Created

### Core Components

1. **DRUPAL-FACTORY-v1.0.md** - Main methodology document
   - Philosophy: "Design för människor, metadata för datorer, struktur för underhåll"
   - Roles: Stefan (Architect) + Claude (Orchestrator)
   - Five-phase workflow
   - Tool selection matrix with decision trees
   - Quality standards
   - Version control process

2. **CHANGELOG.md** - Version history
   - Semantic versioning
   - Tracks all changes to methodology
   - Documents decisions and research areas

3. **README.md** - Usage guide
   - How to use methodology
   - When to update
   - Contributing process

### Architecture Design

```
Stefan (Design Authority)
    ↓
Claude (Orchestrator)
    ↓
┌─────────────┬─────────────┐
│ ANALYZE     │  EXECUTE    │
│ (MCP Tools) │ (UI+Drush)  │
└─────────────┴─────────────┘
    ↓
Production-Ready Site
```

### Tool Selection Strategy

**READ operations:**
- Use MCP Tools (they work!)
- Examples: Get vocabularies, list content types, search content

**WRITE operations:**
- Use UI (Browser automation) + Config export
- Priority: Config > Contrib > Custom code
- Always export config: `drush cex`

**Content:**
- Demo: `drush generate`
- Real: UI or bulk import

**Design:**
- SDC components
- Layout Builder
- Bootstrap classes
- Minimal custom CSS

---

## Current Status: TritonLED Project

### Completed
- ✅ Product structure defined (`/docs/PRODUCT-STRUCTURE.md`)
- ✅ Design references documented
- ✅ Methodology v1.0 created

### Pending
- ⏳ Create 4 taxonomies (35 terms total)
- ⏳ Create Commerce Product content type
- ⏳ Create Product Variation types
- ⏳ Implement homepage layout
- ⏳ Implement product page layout

### Blocked/Research
- 🔬 How to access all 150 MCP tools
- 🔬 Optimal drupal/mcp vs mcp_tools configuration
- 🔬 AI Agents integration potential

---

## Next Steps

1. **Apply methodology immediately** - Create taxonomies using UI automation
2. **Document process** - Track what works/doesn't in practice
3. **Iterate methodology** - Update based on real-world usage
4. **Continue TritonLED** - Build complete e-commerce site as proof of concept

---

## Lessons Learned

1. **Research before rushing** - Understanding architecture saves time
2. **Documentation is infrastructure** - Methodology enables scaling
3. **Version control everything** - Including processes, not just code
4. **Test assumptions** - Just because tools are listed doesn't mean they're callable
5. **Ask when uncertain** - Stefan's patience with research pays off

---

## Technical Notes

### MCP Tools Commands Discovered

```bash
# Show status and configuration
drush mcp:status

# List all 150 available tools
drush mcp:tools

# Development profile (enables more modules)
drush mcp:dev-profile

# Serve MCP over STDIO
drush mcp-tools:server --uid=2    # Currently used
drush mcp:serve                    # Alternative (needs research)
```

### Config Locations

- MCP Tools settings: `/config/sync/mcp_tools.settings.yml`
- Scopes enabled: read, write, admin
- Rate limiting: disabled (dev mode)

---

## Files Created This Session

```
/docs/methodology/
├── DRUPAL-FACTORY-v1.0.md
├── CHANGELOG.md
└── README.md

/docs/sessions/
└── 2026-01-21-mcp-tools-research-methodology.md  (this file)
```

---

## Quotes

Stefan:
> "Vi behöver precis som med sajten bygga vårat arbetssätt 1.0 för att utvärdera, utveckla och underhålla detta."

> "Arbetssättet behöver kunna förändras, uppdateras och byta ut delar allt eftersom Claude uppdateras."

Claude:
> "Detta är grunden för allt. Låt mig skapa en komplett dokumentationsstruktur."

---

**Session End:** Methodology v1.0 established and documented  
**Next Session:** Apply methodology to create TritonLED taxonomies
