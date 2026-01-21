#!/bin/bash
#
# Export Drupal Status & Logs for Claude Analysis
# 
# Usage: 
#   ddev exec /var/www/html/scripts/export-drupal-status.sh
#   OR from host: ddev ssh -c "/var/www/html/scripts/export-drupal-status.sh"
#

set -e

PROJECT_ROOT="/var/www/html"
LOG_DIR="$PROJECT_ROOT/web/sites/default/files/logs"

# Create log directory if not exists
mkdir -p "$LOG_DIR"

echo "🔍 Exporting Drupal status and logs..."

# 1. Watchdog Errors (last 50)
echo "  → Watchdog errors..."
drush watchdog:show --severity=Error --count=50 --format=json > "$LOG_DIR/watchdog-errors.json" 2>&1 || echo "[]" > "$LOG_DIR/watchdog-errors.json"

# 2. Watchdog Warnings (last 50)
echo "  → Watchdog warnings..."
drush watchdog:show --severity=Warning --count=50 --format=json > "$LOG_DIR/watchdog-warnings.json" 2>&1 || echo "[]" > "$LOG_DIR/watchdog-warnings.json"

# 3. PHP Errors (last 50)
echo "  → PHP errors..."
drush watchdog:show --type=php --count=50 --format=json > "$LOG_DIR/php-errors.json" 2>&1 || echo "[]" > "$LOG_DIR/php-errors.json"

# 4. System Status (core:requirements)
echo "  → System status..."
drush core:requirements --format=json > "$LOG_DIR/system-status.json" 2>&1 || echo "{}" > "$LOG_DIR/system-status.json"

# 5. Cache Info (cache tags, bins)
echo "  → Cache statistics..."
drush cache:get system.theme --format=json > "$LOG_DIR/cache-info.json" 2>&1 || echo "{}" > "$LOG_DIR/cache-info.json"

# 6. Module Status
echo "  → Module status..."
drush pm:list --status=enabled --format=json > "$LOG_DIR/modules-enabled.json" 2>&1 || echo "{}" > "$LOG_DIR/modules-enabled.json"

# 7. Config Status
echo "  → Configuration status..."
drush config:status --format=json > "$LOG_DIR/config-status.json" 2>&1 || echo "[]" > "$LOG_DIR/config-status.json"

# 8. Last 100 lines of PHP error log (if exists)
echo "  → PHP error log..."
if [ -f "/var/log/php/error.log" ]; then
    tail -100 /var/log/php/error.log > "$LOG_DIR/php-error-log.txt" 2>&1
else
    echo "No PHP error log found" > "$LOG_DIR/php-error-log.txt"
fi

# 9. Generate summary
echo "  → Generating summary..."
cat > "$LOG_DIR/summary.json" <<EOF
{
  "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
  "drupal_version": "$(drush core:status drupal-version --format=string 2>/dev/null || echo 'unknown')",
  "site_name": "$(drush config:get system.site name --format=string 2>/dev/null || echo 'unknown')",
  "files": {
    "watchdog_errors": "$LOG_DIR/watchdog-errors.json",
    "watchdog_warnings": "$LOG_DIR/watchdog-warnings.json",
    "php_errors": "$LOG_DIR/php-errors.json",
    "system_status": "$LOG_DIR/system-status.json",
    "cache_info": "$LOG_DIR/cache-info.json",
    "modules_enabled": "$LOG_DIR/modules-enabled.json",
    "config_status": "$LOG_DIR/config-status.json",
    "php_error_log": "$LOG_DIR/php-error-log.txt"
  }
}
EOF

echo ""
echo "✅ Logs exported to: $LOG_DIR"
echo "📊 Summary: $LOG_DIR/summary.json"
echo ""
echo "Claude can now read these logs with:"
echo "  Filesystem:read_text_file('$LOG_DIR/summary.json')"
echo ""
