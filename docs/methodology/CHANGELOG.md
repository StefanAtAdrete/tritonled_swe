# Drupal Factory Methodology - Changelog

All notable changes to the Drupal Factory methodology will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2026-01-21

### Added
- Initial methodology framework
- Five-phase workflow (Requirements → Structure → Design → Content → CI/CD)
- Tool selection matrix with decision trees
- Quality standards for production-ready definition
- Documentation structure
- Version control process
- Roles and responsibilities definition
- Architecture layers diagram

### Decisions
- Prioritize config-driven approach over custom code
- Use browser automation for structure creation (UI + drush cex)
- Use MCP Tools for read operations only (write operations via UI)
- Bootstrap 5.3 as primary styling framework
- SDC (Single Directory Components) for component architecture
- Layout Builder for page layouts
- Minimal custom CSS approach

### Known Issues
- MCP structure write tools not accessible via function calls (150 tools exist, only 123 accessible)
- Some MCP Tools modules require dependencies not yet installed
- Browser automation requires manual login credentials

### Research Areas
- Optimal way to expose all MCP tools
- Integration between drupal/mcp and mcp_tools
- AI Agents usage for sub-tasks
- Automated testing strategies

---

## Template for Future Versions

## [X.Y.Z] - YYYY-MM-DD

### Added
- New features or capabilities

### Changed  
- Changes to existing functionality

### Deprecated
- Features marked for removal

### Removed
- Features removed from methodology

### Fixed
- Bug fixes or corrections

### Security
- Security-related changes

### Decisions
- Important architectural or process decisions made

### Research Areas
- Active areas of investigation
