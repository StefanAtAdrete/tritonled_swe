# TritonLED Test Suite

## Quick Start
```bash
# After config changes
ddev drush cim -y && ddev drush cr && ddev composer test:quick

# After code changes
ddev drush cr && ddev composer test:unit

# Before commit
ddev composer test:all
```

## Test Commands
```bash
composer test:quick          # Unit + @smoke tests (30s) ✅
composer test:unit           # All unit tests (1min)
composer test:functional     # Functional tests (2-3min)
composer test:behat          # All Behat tests (3-5min)
composer test:behat:smoke    # @smoke tagged only
composer test:commerce       # @commerce tagged
composer test:all            # Full suite (5-10min)
```

## Current Status

### ✅ Working Tests
- **PHPUnit Unit Tests:** 4 tests, 10 assertions
- **Behat @smoke:** 2 scenarios passing
  - Product page loads with AJAX
  - Add to cart button visible

### 🚧 Pending Tests (undefined steps)
- Variation switching (needs implementation)
- Price updates (needs implementation)
- SKU changes (needs implementation)

## Behat "Undefined Steps" Prompt

When running Behat, you'll see:
```
>> default suite has undefined steps. Please choose the context:
  [0] None
  [1] FeatureContext
  ...
```

**Always press `0`** - this skips code generation for steps we haven't implemented yet.

## Infrastructure

- **PHPUnit:** 11.5.46 on PHP 8.3.17
- **Behat:** With DrupalExtension
- **Selenium:** seleniarm/standalone-chromium (ARM64)
- **noVNC Viewer:** http://localhost:7900 (password: secret)

## Writing Tests

### Add @smoke tag for fast tests
```gherkin
@commerce @smoke
Scenario: Quick verification
  Given I am on "/product/1"
  Then I should see the button "Add to cart"
```

### Add @javascript for browser tests
```gherkin
@javascript
Scenario: AJAX interaction
  Given I am on "/product/1"
  And I wait for AJAX to finish
  Then I should see the button "Add to cart"
```

## Debugging

### View Chrome automation
http://localhost:7900 (password: secret)

### Check test output
```bash
ddev composer test:behat -- --format=pretty
```

### Run specific scenario
```bash
ddev composer test:behat -- features/product-variations.feature:11
```

## Next Steps

1. ✅ Basic infrastructure working
2. 🚧 Implement Commerce variation steps
3. 🚧 Add quote cart flow tests
4. 🚧 Set up CI/CD
