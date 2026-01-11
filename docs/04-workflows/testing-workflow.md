# Testing Workflow - För Claude

## 🎯 När Claude ska köra tester

### Grundregel
**ALLTID testa efter ändringar som påverkar:**
- Config (YAML-filer, drush cim)
- Databas (entity updates, schema changes)
- PHP-kod (modules, services, hooks)
- Templates (Twig-filer)
- Commerce (produkter, variations, checkout)

---

## 📋 Test-kommandon (inne i DDEV)

```bash
# SSH in i DDEV först
ddev ssh

# Snabba tester (30 sekunder) - KÖR ALLTID
composer test:quick

# Alla unit tests (1 min)
composer test:unit

# Alla functional tests (2-3 min)
composer test:functional

# Alla Behat tests (3-5 min)
composer test:behat

# Bara smoke tests (grundfunktionalitet)
composer test:behat:smoke

# Bara Commerce tests
composer test:commerce

# Allt (5-10 min)
composer test:all
```

---

## 🔄 Claudes Test-Workflow

### 1. FÖRE ändring (Baseline)
```bash
ddev ssh
composer test:quick
# ✅ = Fortsätt
# ❌ = Fixa först innan du ändrar något
```

### 2. Efter Config-ändring
```bash
# Import config
ddev drush cim -y
ddev drush cr

# Testa
ddev ssh
composer test:quick

# Om ❌ → Läs felmeddelande, fixa, testa igen
```

### 3. Efter Code-ändring
```bash
# Clear cache
ddev drush cr

# Testa relevanta tester
ddev ssh
composer test:unit  # PHP kod
# eller
composer test:commerce  # Om Commerce-kod
```

### 4. Efter Template-ändring
```bash
# Clear cache
ddev drush cr

# Testa UI/frontend
ddev ssh
composer test:behat
```

### 5. FÖRE Commit
```bash
# ALLTID full test suite
ddev ssh
composer test:all

# ✅ → OK att committa
# ❌ → Fixa ALLT först
```

---

## 🚦 Test-resultat tolkning

### ✅ Success
```
PHPUnit 10.5.4 by Sebastian Bergmann

.....                                                               5 / 5 (100%)

Time: 00:01.234, Memory: 10.00 MB

OK (5 tests, 12 assertions)
```
**→ Fortsätt!**

---

### ❌ Failure
```
PHPUnit 10.5.4 by Sebastian Bergmann

....F                                                               5 / 5 (100%)

Time: 00:01.234, Memory: 10.00 MB

There was 1 failure:

1) Drupal\Tests\tritonled\Functional\CommerceAjaxTest::testVariationSwitch
Failed asserting that '2995' is not present in response
/var/www/html/tests/Functional/CommerceAjaxTest.php:45

FAILURES!
Tests: 5, Assertions: 12, Failures: 1.
```

**→ STOPPA!**
**→ Läs felmeddelande**
**→ Identifiera problem**
**→ Fixa**
**→ Kör test igen**

---

### ⚠️ Error
```
PHPUnit 10.5.4 by Sebastian Bergmann

E....                                                               5 / 5 (100%)

Time: 00:00.234, Memory: 10.00 MB

There was 1 error:

1) Drupal\Tests\tritonled\Unit\ExampleUnitTest::testBasic
Error: Call to undefined function validateSku()
```

**→ STOPPA!**
**→ Syntax error eller missing dependency**
**→ Fixa kod**
**→ Kör test igen**

---

## 🎯 Test-strategi per ändring

| Ändring | Test | Tid | Kommando |
|---------|------|-----|----------|
| YAML config | Quick | 30s | `composer test:quick` |
| PHP service | Unit | 1min | `composer test:unit` |
| Commerce entity | Commerce | 2min | `composer test:commerce` |
| Template | Behat | 3min | `composer test:behat` |
| Full refactor | All | 5-10min | `composer test:all` |

---

## 🤖 Claudes Beslutträd

```
Gjorde jag ändringar?
├─ JA → Vilken typ?
│  ├─ Config/YAML
│  │  └─ ddev drush cim -y && ddev drush cr && ddev composer test:quick
│  ├─ PHP kod
│  │  └─ ddev drush cr && ddev composer test:unit
│  ├─ Commerce
│  │  └─ ddev drush cr && ddev composer test:commerce
│  ├─ Templates
│  │  └─ ddev drush cr && ddev composer test:behat
│  └─ Flera typer
│     └─ ddev composer test:all
└─ NEJ → Inget test behövs
```

---

## 📝 Exempel: Claude gör Commerce-ändring

**Scenario:** Uppdatera Commerce product variation AJAX

**1. Baseline**
```bash
ddev ssh
composer test:commerce
# ✅ 3 scenarios (5 steps) - OK
```

**2. Gör ändring**
```bash
# Ändra event subscriber
ddev drush cr
```

**3. Testa**
```bash
ddev ssh
composer test:commerce
```

**4. Resultat**
```
--- Failed scenarios:

    tests/Behat/features/product-variations.feature:12

1 scenario (1 failed)
5 steps (1 failed, 4 passed)
```

**5. Läs detaljer**
```
Then the product variation should have changed
  Product variation container not found after AJAX
  (Behat\Mink\Exception\ElementNotFoundException)
```

**6. Identifiera problem**
- AJAX svarar inte korrekt
- DOM element saknas efter uppdatering

**7. Fixa kod**
```php
// Lägg till rätt CSS-klass i event subscriber
```

**8. Testa igen**
```bash
composer test:commerce
# ✅ 3 scenarios (5 steps) - PASS!
```

**9. Full test suite före commit**
```bash
composer test:all
# ✅ Alla tester går igenom
```

**10. Commit**
```bash
git add .
git commit -m "Fix: Commerce variation AJAX event subscriber"
```

---

## 🎓 Best Practices

### DOs
- ✅ Kör `test:quick` efter VARJE ändring
- ✅ Kör `test:all` före VARJE commit
- ✅ Läs felmeddelanden noga
- ✅ Fixa ett test i taget
- ✅ Clear cache före test (`ddev drush cr`)

### DON'Ts
- ❌ Skippa tester "för det borde funka"
- ❌ Committa kod med failing tests
- ❌ Ignorera warnings
- ❌ Glömma `ddev drush cim` efter config-ändringar

---

## 🚀 Quick Reference

```bash
# Vanligaste workflow
ddev drush cim -y      # Import config
ddev drush cr          # Clear cache
ddev composer test:quick   # Snabbtest
# ✅ → Fortsätt
# ❌ → Fixa, testa igen

# Före commit
ddev composer test:all
# ✅ → git commit
# ❌ → Fixa allt först
```

---

## 📊 Test Coverage Mål

| Test Type | Coverage | Status |
|-----------|----------|--------|
| Unit | 80%+ | 🟡 Setup |
| Functional | 60%+ | 🟡 Setup |
| Behat | Kritiska flows | 🟡 Setup |

**Nästa steg:** Skriva fler tester för:
- [ ] Commerce product variation switching
- [ ] Quote cart functionality
- [ ] Product import från JSON
- [ ] Responsive image rendering
- [ ] User permissions

---

## 🆘 Troubleshooting

### "Cannot find phpunit"
```bash
ddev composer install
ddev composer require --dev drupal/core-dev
```

### "Behat not found"
```bash
ddev composer install
ddev composer require --dev behat/behat drupal/drupal-extension
```

### "Chrome container not running"
```bash
ddev restart
ddev ssh
curl http://chrome:9515/status
```

### "Database connection error"
```bash
# Kontrollera phpunit.xml
# SIMPLETEST_DB: mysql://db:db@db/db
```

---

**Dokumentversion:** 1.0  
**Senast uppdaterad:** 2025-01-10  
**Nästa review:** 2025-02-10
