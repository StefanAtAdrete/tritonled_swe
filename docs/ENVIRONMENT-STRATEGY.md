# Environment Strategy - TritonLED

**Created:** 2026-01-21  
**Workflow:** Hybrid C - Code in local, content from production

---

## Architecture Overview

```
┌─────────────────────────────────────┐
│  LOCAL/DEV (Master)                 │
│  - Git repository (code + config)   │
│  - ALL modules (dev + runtime)      │
│  - Claude works here via MCP Tools  │
│  - Content via Backup & Migrate     │
└──────────────┬──────────────────────┘
               │
               │ git push/deploy
               ↓
┌─────────────────────────────────────┐
│  PRODUCTION                         │
│  - Code from git                    │
│  - Runtime modules ONLY             │
│  - Content creation by editors      │
│  - Cron jobs for import/export      │
│  - Backup & Migrate for downloads   │
└─────────────────────────────────────┘
               │
               │ backup download
               ↑
               └──────────────────────┘
```

---

## Module Distribution

### ✅ PRODUCTION Modules (Runtime Only)

**AI & Integration (for site functionality):**
- ✅ ai (base)
- ✅ ai_chatbot (customer chatbot)
- ✅ ai_provider_openai (AI backend)
- ✅ jsonapi (serve data to other platforms)
- ✅ rest (API endpoints)
- ✅ serialization (API support)
- ✅ basic_auth (API authentication)

**Content Management:**
- ✅ content_moderation (editorial workflow)
- ✅ workflows (moderation support)
- ✅ comment (product reviews)
- ✅ webform (contact forms)

**Commerce (full stack):**
- ✅ All commerce_* modules (14 modules)

**Design & Layout:**
- ✅ layout_builder
- ✅ layout_builder_styles
- ✅ bootstrap_layout_builder
- ✅ bootstrap_styles
- ✅ block_class

**SEO & Performance:**
- ✅ pathauto
- ✅ redirect
- ✅ metatag (if installed)
- ✅ imageapi_optimize + webp variants
- ✅ page_cache, dynamic_page_cache, big_pipe

**Search:**
- ✅ search (internal site search)

**Backup:**
- ✅ backup_migrate (for content export to local)

**Logging:**
- ✅ syslog (production logging)
- ❌ dblog (removed for performance)

**Language:**
- ✅ language
- ✅ content_translation

**Core essentials:**
- ✅ All standard Drupal core modules

**Total Production: ~80 modules**

---

### 🔧 LOCAL/DEV ONLY Modules (Development Tools)

**Claude's Development Tools:**
- 🔧 mcp_tools + all mcp_tools_* submodules (16 modules)
- 🔧 tool + all tool_* submodules (7 modules)
- 🔧 ai_agents, ai_agents_explorer (Claude taskability)
- 🔧 ai_assistant_api, ai_automators (dev helpers)
- 🔧 jsonrpc, jsonrpc_core, jsonrpc_discovery (MCP transport)

**Admin UI:**
- 🔧 field_ui (structure building)
- 🔧 views_ui (views building)
- 🔧 contextual (quick edit links)
- 🔧 toolbar / navigation (admin navigation)
- 🔧 shortcut (admin shortcuts)

**Development Utilities:**
- 🔧 dblog (local debugging)
- 🔧 devel (if installed)
- 🔧 update (check module updates locally)

**Custom Development:**
- 🔧 tritonled_commerce_agent (testing/research)

**Total Dev-Only: ~35 modules**

---

## Content & Media Strategy

### Content Flow

**Production → Local:**
```bash
# On production: Create backup
drush backup-migrate:backup --destination=private_files --source=db

# Download backup file from production
# (via SFTP, admin UI, or automated sync)

# On local: Import backup
ddev drush backup-migrate:restore --source=private_files
```

**Config Flow (Code/Structure):**
```bash
# On local: After structural changes
ddev drush cex -y
git add config/
git commit -m "Structure: Added product taxonomy"
git push

# On production: Deploy
git pull
drush cim -y
drush cr
```

### Media Files Strategy

**Options for media sync:**

**A) No Sync (Recommended for dev):**
- Local uses placeholder images
- Faster, smaller local environment
- Generate dummy media: `drush generate:media`

**B) Partial Sync (Optimized images only):**
- Sync specific product images needed for testing
- Manual SFTP or rsync specific folders
- Keep local media small

**C) Full Sync (Only when necessary):**
```bash
# From local, sync media from production
rsync -avz --progress \
  production:/var/www/html/web/sites/default/files/ \
  ./web/sites/default/files/
```

**Recommendation:** Start with A, move to B only when testing specific features that require real images.

---

## Deployment Workflow

### 1. Local Development

```bash
# Claude helps via MCP Tools
# Stefan makes changes via UI or code
ddev drush cex -y              # Export config
git add -A
git commit -m "Feature: ..."
git push origin main
```

### 2. Production Deployment

```bash
# On production server
git pull origin main           # Get latest code
drush updb -y                  # Run database updates
drush cim -y                   # Import config
drush cr                       # Clear cache
drush cron                     # Run cron

# Verify
drush status
drush core:requirements
```

### 3. Content Backup (Regular schedule)

```bash
# Automated via cron (daily/weekly)
drush backup-migrate:backup \
  --destination=private_files \
  --source=db
  
# Keep last 7 daily + 4 weekly backups
```

---

## Module Installation Commands

### Production Setup (First Time)

```bash
# Install production-required modules
composer require drupal/backup_migrate

# Enable production modules only
drush en backup_migrate -y

# Disable dev-only modules
drush pm:uninstall \
  mcp_tools mcp_tools_* \
  tool tool_* \
  ai_agents ai_agents_explorer \
  field_ui views_ui contextual toolbar shortcut \
  dblog -y

# Replace dblog with syslog
drush en syslog -y

# Export config
drush cex -y
```

### Local Setup (First Time)

```bash
# Install ALL modules (dev + production)
composer install

# Enable everything needed for development
drush en \
  mcp_tools mcp_tools_stdio mcp_tools_structure \
  tool tool_explorer \
  ai_agents \
  field_ui views_ui contextual \
  dblog devel -y

# Import production config
drush cim -y

# Download latest content backup
# (manual or automated script)
```

---

## Configuration Management

### Environment-Specific Settings

**Use `settings.local.php` for environment differences:**

```php
// Local: web/sites/default/settings.local.php
$config['system.logging']['error_level'] = 'verbose';
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;
$settings['cache']['bins']['render'] = 'cache.backend.null';

// Production: settings.php
$config['system.logging']['error_level'] = 'hide';
$config['system.performance']['css']['preprocess'] = TRUE;
$config['system.performance']['js']['preprocess'] = TRUE;
// Real cache backend
```

### Config Split (Optional - Future Enhancement)

Consider `config_split` module to manage:
- Dev-only config
- Production-only config
- Environment-specific settings

---

## Security Considerations

### Production Hardening

```yaml
# Disable dangerous permissions
# Remove these from production roles:
- "administer modules"
- "administer software updates"
- "administer site configuration"
- "access site reports"

# Secure API endpoints
# Add authentication to JSON:API
# Use OAuth for external integrations

# Disable development modules
# Verified via drush pm:list --status=enabled
```

### MCP Tools in Local Only

```yaml
# mcp_tools.settings.yml (local only)
mode: development
access:
  allowed_scopes:
    - read
    - write
    - admin
  
# Not present in production config
```

---

## Monitoring & Maintenance

### Production Checklist

**Daily (Automated):**
- ✅ Content backup via Backup & Migrate
- ✅ Cron jobs running
- ✅ Error log monitoring (syslog)

**Weekly:**
- ✅ Security updates check
- ✅ Performance metrics
- ✅ Disk space check

**Monthly:**
- ✅ Full backup test restore
- ✅ Module updates review
- ✅ Performance optimization

### Local Checklist

**Per Session:**
- ✅ Pull latest code from git
- ✅ Import latest config: `ddev drush cim -y`
- ✅ Clear cache: `ddev drush cr`
- ✅ Verify MCP Tools connection

**Weekly:**
- ✅ Download production content backup
- ✅ Update composer dependencies
- ✅ Test deployment process

---

## Performance Optimization

### Production Settings

```php
// Caching
$settings['cache']['default'] = 'cache.backend.database';
$config['system.performance']['cache']['page']['max_age'] = 3600;

// Aggregation
$config['system.performance']['css']['preprocess'] = TRUE;
$config['system.performance']['js']['preprocess'] = TRUE;

// Twig
$settings['twig_debug'] = FALSE;
$settings['twig_cache'] = TRUE;

// Disable unnecessary services
$settings['container_yamls'][] = __DIR__ . '/services.production.yml';
```

**Expected Performance:**
- Page load < 2 seconds (with cache)
- Time to first byte < 500ms
- ~80 modules vs 140 (40% reduction)

---

## Documentation References

- `/docs/methodology/DRUPAL-FACTORY-v1.0.md` - Core methodology
- `/docs/MODULE-AUDIT-2026-01-21.md` - Module decisions
- `/docs/DEPLOYMENT.md` - Deployment procedures (to be created)
- `/docs/DEVELOPMENT.md` - Local setup guide (to be created)

---

## Next Steps

1. ✅ Strategy documented
2. ⏳ Execute production module cleanup
3. ⏳ Install backup_migrate module
4. ⏳ Test backup/restore workflow
5. ⏳ Create deployment scripts
6. ⏳ Document media sync strategy
7. ⏳ Setup production monitoring

---

**Status:** Strategy approved  
**Last Updated:** 2026-01-21  
**Next Review:** After first production deployment
