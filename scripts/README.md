# Drupal Status Export for Claude

**Syfte**: Exportera Drupal logs och status så Claude kan analysera dem.

---

## 🚀 Quick Start

### 1. Gör scriptet körbart (en gång)
```bash
chmod +x scripts/export-drupal-status.sh
```

### 2. Kör från DDEV container
```bash
# Inne i DDEV
ddev ssh
/var/www/html/scripts/export-drupal-status.sh

# Eller från host
ddev exec /var/www/html/scripts/export-drupal-status.sh
```

### 3. Claude läser logs
```javascript
// Claude kör:
Filesystem:read_text_file('/Users/steffes/Projekt/tritonled/web/sites/default/files/logs/summary.json')

// Eller specifika logs:
Filesystem:read_text_file('/Users/steffes/Projekt/tritonled/web/sites/default/files/logs/watchdog-errors.json')
```

---

## 📊 Vad exporteras

| Fil | Innehåll | Storlek |
|-----|----------|---------|
| `summary.json` | Översikt av alla logs | ~1 KB |
| `watchdog-errors.json` | Senaste 50 errors från watchdog | ~10 KB |
| `watchdog-warnings.json` | Senaste 50 warnings | ~10 KB |
| `php-errors.json` | Senaste 50 PHP-fel | ~10 KB |
| `system-status.json` | Drupal system requirements | ~5 KB |
| `cache-info.json` | Cache status | ~2 KB |
| `modules-enabled.json` | Aktiverade moduler | ~20 KB |
| `config-status.json` | Config sync status | ~5 KB |
| `php-error-log.txt` | Senaste 100 rader PHP error log | ~10 KB |

**Total**: ~73 KB

---

## 🔄 Workflow: Claude Testing

### Före visual test:
```bash
# 1. Stefan exporterar logs
ddev exec /var/www/html/scripts/export-drupal-status.sh

# 2. Stefan: "Claude, kolla logs och kör visual test"

# 3. Claude analyserar:
- Läser summary.json
- Kollar watchdog-errors.json
- Noterar PHP errors
- Kör puppeteer visual test
- Rapporterar issues
```

### Efter code changes:
```bash
# 1. Stefan: Gör ändringar + cache clear
ddev drush cr

# 2. Stefan: Exportera nya logs
ddev exec /var/www/html/scripts/export-drupal-status.sh

# 3. Stefan: "Claude, verify fix"

# 4. Claude:
- Jämför nya vs gamla logs
- Verifierar att errors försvunnit
- Kör visual regression test
- Bekräftar fix
```

---

## 🎯 Integration med Test-ramverk

### Lägg till i design-testing.md

**Pre-Implementation Check:**
- [ ] Exportera logs: `ddev exec /var/www/html/scripts/export-drupal-status.sh`
- [ ] Claude läser summary: **Inga errors?** ✅ **Errors?** Fix först! ❌

**Post-Implementation:**
- [ ] Exportera logs igen
- [ ] Claude verifierar inga nya errors
- [ ] Visual test
- [ ] Approve/reject

---

## 📁 Log Location

Alla logs sparas i:
```
/web/sites/default/files/logs/
```

**OBS:** Denna mapp är i `.gitignore` (bör vara). Logs är temporära.

---

## 🔧 Troubleshooting

### Script kan inte köras
```bash
# Kontrollera permissions
ls -la scripts/export-drupal-status.sh

# Ska visa: -rwxr-xr-x (executable)

# Om inte:
chmod +x scripts/export-drupal-status.sh
```

### Logs inte skapas
```bash
# Kolla log directory permissions
ddev exec ls -la /var/www/html/web/sites/default/files/
ddev exec mkdir -p /var/www/html/web/sites/default/files/logs
ddev exec chmod 775 /var/www/html/web/sites/default/files/logs
```

### Claude kan inte läsa logs
```bash
# Verifiera att filer finns
ddev exec ls -la /var/www/html/web/sites/default/files/logs/

# Verifiera innehåll
ddev exec cat /var/www/html/web/sites/default/files/logs/summary.json
```

---

## 🚀 Framtida Förbättringar

### Option 1: Automatisk export vid cache clear
```bash
# I ~/.ddev/commands/host/drush-cr-with-logs
#!/bin/bash
ddev drush cr
ddev exec /var/www/html/scripts/export-drupal-status.sh
```

### Option 2: MCP Tool för Drupal
```typescript
// Skapa MCP tool i mcp-tritonled server
export_drupal_status: {
  description: "Export Drupal logs for analysis",
  handler: async () => {
    await exec('ddev exec /var/www/html/scripts/export-drupal-status.sh');
    const summary = await readFile('web/sites/default/files/logs/summary.json');
    return JSON.parse(summary);
  }
}
```

### Option 3: Real-time Log Streaming
- Tail watchdog in real-time
- WebSocket integration
- Claude får updates automatiskt

---

## 📖 Related Documentation

- `/docs/04-workflows/design-testing.md` - Visual testing workflow
- `/docs/SESSION-2025-01-11.md` - Automated testing setup

---

**Skapad**: 2025-01-11  
**Författare**: Claude + Stefan  
**Version**: 1.0
