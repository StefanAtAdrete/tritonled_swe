# Drupal Factory Methodology v1.0

**Created:** 2026-01-21  
**Status:** Active  
**Purpose:** Systematic approach for building production-ready Drupal sites with AI orchestration

---

## Philosophy

"Design för människor, metadata för datorer, struktur för underhåll"

This methodology prioritizes:
1. **Maintainability** - Config-driven, documented decisions
2. **Scalability** - Patterns that work for simple and complex sites  
3. **Evolvability** - Version-controlled, updatable processes
4. **Quality** - Production-ready output with SEO, accessibility, performance

---

## Roles & Responsibilities

### Stefan (Architect + Design Authority)
- Provides design references and requirements
- Approves architectural decisions
- Evaluates results
- Has control over all parts: build, evaluate, develop, maintain

### Claude (Orchestrator + Decision Engine)
- Interprets design → feature list
- Selects appropriate tools per task
- Documents all decisions
- **ASKS when uncertain** (never guesses on architecture)
- Follows DRUPAL-DECISION-TREE.md strictly

---

## Architecture Layers

```
┌─────────────────────────────────────────────┐
│ Stefan (Architect + Design Authority)       │
└─────────────────┬───────────────────────────┘
                  ↓
┌─────────────────────────────────────────────┐
│ Claude (Orchestrator)                        │
│ - Read via MCP Tools                         │
│ - Write via UI + Drush                       │
│ - Document everything                        │
└─────────────────┬───────────────────────────┘
                  ↓
      ┌───────────┴───────────┐
      ↓                       ↓
┌─────────────┐         ┌─────────────┐
│  ANALYZE    │         │  EXECUTE    │
└─────────────┘         └─────────────┘
      ↓                       ↓
┌─────────────────────────────────────────────┐
│       Production-Ready Drupal Site           │
│ ├─ Structure (exported config)               │
│ ├─ Design (theme + SDC components)           │
│ ├─ Content (nodes + taxonomy + media)        │
│ ├─ Metadata (SEO + schema.org + accessibility)│
│ └─ CI/CD (procedures + documentation)        │
└─────────────────────────────────────────────┘
```

---

## Tool Selection Matrix

### Available Tools (v1.0)

| Tool Category | Tool Name | Use Case | Status |
|--------------|-----------|----------|--------|
| **Analysis** | MCP Tools (read) | Get site status, list structures | ✅ Working |
| **Structure Creation** | Browser automation | Create content types, fields, taxonomies via UI | ✅ Working |
| **Structure Export** | `drush cex` | Export config for version control | ✅ Working |
| **Content Creation** | `drush generate` | Create demo/test content | ✅ Working |
| **Design Implementation** | Code files + Layout Builder | SDC components, CSS, layouts | ✅ Working |
| **File Operations** | Filesystem tools | Read/write files, create directories | ✅ Working |

### Tool Selection Decision Tree

```
Need to READ Drupal structure/content?
├─ YES → Use MCP Tools (mcp-server-drupal:*)
└─ NO → Continue

Need to WRITE structure (content types, fields, taxonomies)?
├─ YES → Can it be exported as config?
│   ├─ YES → Use UI (Browser automation) + drush cex
│   │         Priority: Config > Contrib > Custom code
│   └─ NO → Discuss with Stefan first
└─ NO → Continue

Need to create CONTENT?
├─ Demo/test → drush generate
├─ Real content → UI or bulk import
└─ Media → Upload + optimization

Need to implement DESIGN?
├─ Layout → Layout Builder
├─ Components → SDC (Single Directory Components)
├─ Styling → Bootstrap classes + minimal custom CSS
└─ Templates → Only as last resort (breaks AJAX, etc)

UNCERTAIN about approach?
└─ STOP and ASK Stefan before proceeding
```

---

## Workflow: Design → Production

### Phase 1: Requirements Analysis

**Input:** Design references (screenshots, Figma, description)

**Process:**
1. Claude analyzes design for:
   - Visual components → Layout Builder sections
   - Data structures → Content Types + Fields
   - Navigation → Menus + Views
   - Functions → Contrib modules needed
2. Claude creates prioritized feature list
3. Stefan approves/modifies list

**Output:** Approved feature list in `/docs/PROJECT-PLAN.md`

---

### Phase 2: Structure (Config-First)

**For each Content Type:**
1. Claude defines fields + settings
2. Stefan approves structure
3. Execute via UI (Claude in Chrome)
4. Export config: `drush cex`
5. Commit to git
6. Document in `/docs/structure/content-types/`

**For each Taxonomy:**
1. Same process as Content Type
2. Include term hierarchy if relevant
3. Document term structure

**Critical Rules:**
- ALWAYS export config after structural changes
- NEVER create custom code without explicit approval
- FOLLOW `/docs/DRUPAL-DECISION-TREE.md` strictly

---

### Phase 3: Design (Theme-Driven)

**For each page type:**
1. Break down into SDC components
2. Create component in `/web/themes/custom/{theme}/components/`
3. Configure Layout Builder
4. Apply Bootstrap classes
5. Test responsive: 320px, 768px, 1920px
6. Stefan approves visually

**SEO Integration (Built-in):**
- Meta tags per content type
- Schema.org markup in templates
- Open Graph for social sharing
- JSON-LD structured data
- Clean semantic HTML5
- Alt texts on all images
- Proper heading hierarchy (h1→h6)

---

## Documentation Structure

```
/docs/
├── methodology/
│   ├── DRUPAL-FACTORY-v1.0.md          ← This file
│   ├── CHANGELOG.md                     ← Version history
│   ├── TOOL-MATRIX.md                   ← Detailed tool documentation
│   └── DECISION-TREES/                  ← Specific decision trees
├── sessions/
│   └── YYYY-MM-DD-topic.md             ← Session documentation
├── structure/
│   ├── content-types/
│   ├── taxonomies/
│   ├── fields/
│   └── views/
├── design/
│   ├── components/
│   ├── layouts/
│   └── style-guide.md
├── PRODUCT-STRUCTURE.md
├── DEPLOYMENT.md
├── DEVELOPMENT.md
└── MAINTENANCE.md
```

---

## Quality Standards: Production-Ready Definition

**✅ Structure:**
- All content types defined and exported as config
- All taxonomies with terms created
- All necessary fields configured
- Views for content listings

**✅ Design:**
- Responsive layouts (mobile, tablet, desktop)
- SDC components where appropriate
- Bootstrap-based styling
- Consistent visual design

**✅ Content:**
- Foundation taxonomy terms
- Demo/example content
- Optimized media assets
- Working navigation

**✅ Metadata & SEO:**
- Meta tags per content type
- Schema.org markup
- Open Graph tags
- Alt texts on all images
- Clean semantic HTML
- Proper heading hierarchy

**✅ CI/CD:**
- Config exported and in version control
- Deployment documentation complete
- Rollback procedures documented

---

## Maintenance & Evolution

### Version Control

This methodology uses semantic versioning:
- **Major (1.0 → 2.0):** Fundamental approach changes
- **Minor (1.0 → 1.1):** New tools or significant process updates
- **Patch (1.0.0 → 1.0.1):** Bug fixes or minor clarifications

### Update Triggers

Re-evaluate when:
- Claude capabilities change significantly
- Drupal core/modules major updates
- New AI tools become available
- MCP protocol changes
- Project patterns emerge that need standardization

### Change Process

1. Identify need for change
2. Document proposed change
3. Test new approach
4. Evaluate results together
5. Update methodology document
6. Update version number
7. Log in CHANGELOG.md

---

## Current Status (v1.0)

**What Works:**
- ✅ Reading Drupal via MCP Tools
- ✅ Browser automation for structure creation
- ✅ Config export workflow
- ✅ Design via SDC + Layout Builder
- ✅ Documentation structure

**Known Limitations:**
- ❌ MCP structure write tools not accessible via function calls
- ⚠️ Browser automation requires login credentials

**Active Research:**
- How to expose all 150 MCP tools
- Optimal drupal/mcp vs mcp_tools usage
- AI Agents integration

---

**Next Review Date:** 2026-02-21  
**Last Updated:** 2026-01-21  
**Updated By:** Claude + Stefan
