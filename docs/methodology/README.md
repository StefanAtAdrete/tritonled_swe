# Methodology Documentation

This directory contains the Drupal Factory methodology - our systematic approach for building production-ready Drupal sites with AI orchestration.

## Quick Start

**Read first:** [DRUPAL-FACTORY-v1.0.md](DRUPAL-FACTORY-v1.0.md)

This is the core methodology document that defines:
- Philosophy and principles
- Roles and responsibilities  
- Architecture and workflow
- Tool selection and decision trees
- Quality standards
- Maintenance and evolution process

## Files in This Directory

- **DRUPAL-FACTORY-v1.0.md** - Main methodology document (current version)
- **CHANGELOG.md** - Version history and notable changes
- **README.md** - This file

## How to Use This Methodology

### For Stefan (Architect)
1. Review methodology before starting new projects
2. Use decision trees when uncertain about approach
3. Propose changes when patterns emerge
4. Approve architectural decisions based on documented principles

### For Claude (Orchestrator)
1. **ALWAYS read DRUPAL-FACTORY-v1.0.md at session start**
2. Follow tool selection decision tree
3. Document all decisions in session notes
4. Ask Stefan when uncertain (don't guess)
5. Reference this methodology in all architectural discussions

## Version Control

Current Version: **1.0.0**

- **Major versions (X.0.0):** Fundamental approach changes
- **Minor versions (0.X.0):** New tools or significant process updates  
- **Patch versions (0.0.X):** Bug fixes or clarifications

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

## When to Update

Update the methodology when:
- Claude capabilities change significantly
- Drupal core/modules have major updates
- New AI tools become available
- MCP protocol changes
- Project patterns emerge needing standardization

## Contributing to Methodology

1. Identify need for change (Stefan or Claude)
2. Test new approach on non-critical task
3. Evaluate results together
4. Update DRUPAL-FACTORY-vX.Y.md
5. Update version number
6. Log in CHANGELOG.md
7. Update this README if structure changes

## Philosophy

> "Design för människor, metadata för datorer, struktur för underhåll"

This methodology is **itself a product** that must be:
- Built (documented)
- Evaluated (tested on real projects)
- Developed (improved over time)
- Maintained (kept current with ecosystem changes)

The methodology is MORE IMPORTANT than any single site, because it enables building many sites systematically and with consistent quality.

---

**Next Review:** 2026-02-21  
**Last Updated:** 2026-01-21
