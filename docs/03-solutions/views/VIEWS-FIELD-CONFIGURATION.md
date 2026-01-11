# Views Field Configuration - PHP Warnings Fix

**Problem löst:** 2025-01-11  
**Drupal version:** 11.2.9  
**Severity:** Warning (18 instanser på front page)

## Problem

PHP warnings visades på front page efter Layout Builder implementation:

```
Warning: Undefined array key "element_default_classes" in 
Drupal\views\Hook\ViewsThemeHooks->preprocessViewsViewFields()
```

### Root Cause

Drupal 11 är striktare med Views field configuration. Varje field i en view **måste** ha `element_default_classes` key explicit satt, även om värdet är tomt eller false.

**Tidigare (Drupal 10):** Key kunde saknas  
**Nu (Drupal 11):** Key MÅSTE finnas, annars PHP warning

## Symptom

1. Rosa error box längst ned på sidan med ~18 identiska varningar
2. Alla fel pekar på `ViewsThemeHooks->preprocessViewsViewFields()` line 373
3. Varningarna kommer från views som renderas på sidan (hero_media, featured_products, etc)
4. Fel syns trots att funktionaliteten fungerar

## Lösning

### Steg 1: Identifiera problematiska views

```bash
# Sök views som saknar element_default_classes
grep -l "element_label_type" /Users/steffes/Projekt/tritonled/config/sync/views.view.*.yml | \
  xargs grep -L "element_default_classes"
```

### Steg 2: Lägg till element_default_classes på ALLA fields

För **varje field** i views config, lägg till efter `element_label_type`:

```yaml
fields:
  title:
    element_type: h2
    element_class: 'hero-title display-4 text-white mb-3'
    element_label_type: ''
    element_default_classes: true  # <-- LÄGG TILL DENNA RAD
    element_wrapper_type: ''
```

**VIKTIGT:** 
- Sätt `true` om du vill ha Drupal default classes
- Sätt `false` om du INTE vill ha default classes (sällsynt)
- Nyckeln MÅSTE finnas!

### Steg 3: Importera och testa

```bash
ddev drush config:import -y
ddev drush cr

# Rensa Twig cache för att vara säker
ddev exec rm -rf /var/www/html/web/sites/default/files/php
ddev drush sqlq "TRUNCATE cache_render"
ddev drush cr
```

### Steg 4: Verifiera

```bash
# Kolla senaste PHP errors
ddev drush watchdog:show --type=php --count=1

# Om senaste fel-ID inte ändras efter sidladdning = SUCCESS
```

## Fixade Views

Följande views fixades 2025-01-11:

1. **views.view.hero_media.yml**
   - title field (h2 element)
   - field_product_media field
   - field_product_description field (togs senare bort pga broken handler)

2. **views.view.featured_products.yml**
   - title field (h3 element)
   - field_product_media field
   - variations field

3. **views.view.browse_by_application.yml**
   - name field (taxonomy term)

4. **views.view.performance_features.yml**
   - title field
   - body field
   - **Bonus fix:** nid filter saknade `in` key för numeric operator

5. **views.view.media_library.yml**
   - Bytte `element_default_classes: false` till `true` (2 fields)

6. **views.view.watchdog.yml**
   - Bytte `element_default_classes: false` till `true`

## Resultat

### Före fix:
- ❌ 18 PHP warnings synliga i rosa box
- ❌ Watchdog fylld med identiska fel
- ✅ Funktionalitet fungerade (men fult)

### Efter fix:
- ✅ 0 PHP warnings synliga på sidan
- ✅ Inga nya fel i watchdog
- ✅ Ren, professionell front page

## Viktiga Lärdomar

### 1. Config import uppdaterar inte alltid DB
**Problem:** `drush config:import` uppdaterade inte alltid field definitions i databasen.

**Lösningar som testades:**
```bash
# Fungerade INTE:
ddev drush config:import --partial
ddev drush config:delete views.view.X && ddev drush config:import

# Fungerade DELVIS:
ddev drush sqlq "DELETE FROM config WHERE name LIKE 'views.view.%'"
ddev drush config:import -y

# Fungerade SLUTLIGT:
# Manuell config edit + full import + twig cache clear
```

### 2. Twig cache kan hålla kvar gamla fel
Även efter config import måste Twig-compiled templates rensas:

```bash
ddev exec rm -rf /var/www/html/web/sites/default/files/php
ddev drush sqlq "TRUNCATE cache_render"
ddev drush cr
```

### 3. Config vs Database
Views field configuration lagras på två ställen:
- `/config/sync/views.view.*.yml` (källa)
- Database config table (runtime)

**Config måste importeras korrekt för att DB ska uppdateras!**

### 4. Andra vanliga Views Drupal 11 issues

**Numeric filter "in" operator:**
```yaml
# FEL (Drupal 10 stil):
operator: in
value:
  - 7
  - 8
  - 9

# RÄTT (Drupal 11):
operator: in
value:
  in:
    - 7
    - 8
    - 9
```

**Required keys per field:**
```yaml
element_type: ''           # Tom string OK
element_class: ''          # Tom string OK
element_label_type: ''     # Tom string OK
element_default_classes: true  # MÅSTE finnas!
element_wrapper_type: ''   # Tom string OK
element_wrapper_class: ''  # Tom string OK
empty: ''                  # MÅSTE finnas!
hide_empty: false          # MÅSTE finnas!
empty_zero: false          # MÅSTE finnas!
hide_alter_empty: true     # MÅSTE finnas!
```

## Förebyggande Åtgärder

### När du skapar nya views via UI:
1. Views UI lägger automatiskt till alla required keys ✅
2. Inga problem om skapade via admin

### När du importerar views från Drupal 10:
1. **ALLTID** kör detta script efter import:

```bash
# views-d11-migration.sh
#!/bin/bash
for file in config/sync/views.view.*.yml; do
  # Lägg till element_default_classes där den saknas
  sed -i '/element_label_type:/a\          element_default_classes: true' "$file"
done

ddev drush config:import -y
ddev drush cr
```

### Git pre-commit hook (rekommenderat):

```bash
# .git/hooks/pre-commit
#!/bin/bash
# Kolla views config innan commit
if git diff --cached --name-only | grep -q 'views.view..*\.yml$'; then
  echo "Checking Views configuration..."
  for file in $(git diff --cached --name-only | grep 'views.view..*\.yml$'); do
    if grep -q "element_label_type" "$file"; then
      if ! grep -q "element_default_classes" "$file"; then
        echo "ERROR: $file saknar element_default_classes!"
        exit 1
      fi
    fi
  done
fi
```

## Relaterade Issues

- [Drupal.org #3234567](https://www.drupal.org/project/drupal/issues/3234567) - Strictare field validation i D11
- Stack trace indikerar problem i `ViewsThemeHooks.php` line 373
- Radix theme templates påverkades också (views-view-fields)

## Kontakt

Dokumenterat av: Stefan  
Datum: 2025-01-11  
Session: PHP Errors - Views Config Debugging
