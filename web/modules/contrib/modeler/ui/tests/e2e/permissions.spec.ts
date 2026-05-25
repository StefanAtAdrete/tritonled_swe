import { test, expect } from '@playwright/test';
import { ModelerPage } from './pages/ModelerPage';
import { setupMocks } from './fixtures/mocks';

/**
 * E2E tests for the granular permissions system.
 *
 * The modeler API provides `drupalSettings.modeler_api.permissions` with
 * boolean flags that control feature availability.  All flags default to
 * `true` when not specified.
 *
 * Each permission is tested in both states: enabled (true) and disabled (false).
 */

// ---------------------------------------------------------------------------
// edit metadata
// ---------------------------------------------------------------------------
test.describe('Permission: edit metadata', () => {
  test.describe('enabled (default)', () => {
    let modeler: ModelerPage;

    test.beforeEach(async ({ page }) => {
      await setupMocks(page, { permissions: { 'edit metadata': true } });
      modeler = new ModelerPage(page);
      await modeler.goto();
    });

    test('should allow editing metadata fields', async ({ page }) => {
      await modeler.openSettings();

      // Label field should be editable
      const labelInput = modeler.getMetadataField('label');
      await expect(labelInput).toBeVisible();
      await expect(labelInput).not.toHaveAttribute('readonly');

      // Version field should be editable
      const versionInput = modeler.getMetadataField('version');
      await expect(versionInput).not.toHaveAttribute('readonly');

      // Documentation textarea should be editable
      const docField = modeler.getMetadataField('documentation');
      await expect(docField).not.toHaveAttribute('readonly');

      // Tags input should be editable
      const tagsInput = modeler.getMetadataField('tags');
      await expect(tagsInput).not.toHaveAttribute('readonly');

      // Changelog textarea should be editable
      const changelogField = modeler.getMetadataField('changelog');
      await expect(changelogField).not.toHaveAttribute('readonly');

      // Storage select should be enabled
      const storageSelect = modeler.getMetadataField('storage');
      await expect(storageSelect).toBeEnabled();
    });

    test('should show Save button', async () => {
      await modeler.openSettings();
      const saveBtn = modeler.getMetadataSaveButton();
      await expect(saveBtn).toBeVisible();
    });
  });

  test.describe('disabled', () => {
    let modeler: ModelerPage;

    test.beforeEach(async ({ page }) => {
      await setupMocks(page, { permissions: { 'edit metadata': false } });
      modeler = new ModelerPage(page);
      await modeler.goto();
    });

    test('should make metadata fields read-only for existing models', async ({ page }) => {
      await modeler.openSettings();

      // Label field should be read-only
      const labelInput = modeler.getMetadataField('label');
      await expect(labelInput).toHaveAttribute('readonly');

      // Version field should be read-only
      const versionInput = modeler.getMetadataField('version');
      await expect(versionInput).toHaveAttribute('readonly');

      // Documentation textarea should be read-only
      const docField = modeler.getMetadataField('documentation');
      await expect(docField).toHaveAttribute('readonly');

      // Tags input should be read-only
      const tagsInput = modeler.getMetadataField('tags');
      await expect(tagsInput).toHaveAttribute('readonly');

      // Changelog textarea should be read-only
      const changelogField = modeler.getMetadataField('changelog');
      await expect(changelogField).toHaveAttribute('readonly');

      // Storage select should be disabled
      const storageSelect = modeler.getMetadataField('storage');
      await expect(storageSelect).toBeDisabled();
    });

    test('should hide Save button and show Close instead of Cancel', async () => {
      await modeler.openSettings();

      // Save button should not exist
      const saveBtn = modeler.getMetadataSaveButton();
      await expect(saveBtn).toHaveCount(0);

      // The remaining button should say "Close" (not "Cancel")
      const closeBtn = modeler.getMetadataModal().locator('button.btn-secondary');
      await expect(closeBtn).toHaveText('Close');
    });

    test('should still allow editing for new models despite permission being false', async ({ page }) => {
      // New models should always be editable
      await setupMocks(page, {
        isNew: true,
        permissions: { 'edit metadata': false },
      });
      modeler = new ModelerPage(page);
      await modeler.goto('new-model');

      // Metadata modal opens automatically for new models
      await page.waitForSelector('.metadata-modal', { state: 'visible', timeout: 5000 });

      // Label field should be editable even though permission is false
      const labelInput = modeler.getMetadataField('label');
      await expect(labelInput).not.toHaveAttribute('readonly');

      // Save button should be present
      const saveBtn = modeler.getMetadataSaveButton();
      await expect(saveBtn).toBeVisible();
    });
  });
});

// ---------------------------------------------------------------------------
// changelog visibility (hidden for new models)
// ---------------------------------------------------------------------------
test.describe('MetadataModal: changelog visibility', () => {
  test('should hide changelog field when creating a new model', async ({ page }) => {
    await setupMocks(page, { isNew: true });
    const modeler = new ModelerPage(page);
    await modeler.goto('new-model');

    // Metadata modal opens automatically for new models
    await page.waitForSelector('.metadata-modal', { state: 'visible', timeout: 5000 });

    // Changelog field should NOT be present
    const changelogField = modeler.getMetadataField('changelog');
    await expect(changelogField).toHaveCount(0);
  });

  test('should show changelog field when editing an existing model', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    await modeler.openSettings();

    // Changelog field should be visible
    const changelogField = modeler.getMetadataField('changelog');
    await expect(changelogField).toBeVisible();
  });
});

// ---------------------------------------------------------------------------
// switch context
// ---------------------------------------------------------------------------
test.describe('Permission: switch context', () => {
  test.describe('enabled (default)', () => {
    test('should show context dropdown when contexts are available', async ({ page }) => {
      await setupMocks(page, {
        withContexts: true,
        permissions: { 'switch context': true },
      });
      const modeler = new ModelerPage(page);
      await modeler.goto();

      const contextSelect = modeler.getContextSelect();
      await expect(contextSelect).toBeVisible();
    });

    test('should list context options', async ({ page }) => {
      await setupMocks(page, {
        withContexts: true,
        permissions: { 'switch context': true },
      });
      const modeler = new ModelerPage(page);
      await modeler.goto();

      const contextSelect = modeler.getContextSelect();
      const options = contextSelect.locator('option');
      // "No Context" + 2 mock contexts = 3 options
      await expect(options).toHaveCount(3);
    });
  });

  test.describe('disabled', () => {
    test('should hide context dropdown even when contexts exist', async ({ page }) => {
      await setupMocks(page, {
        withContexts: true,
        permissions: { 'switch context': false },
      });
      const modeler = new ModelerPage(page);
      await modeler.goto();

      const contextSelect = modeler.getContextSelect();
      await expect(contextSelect).not.toBeVisible();
    });
  });
});

// ---------------------------------------------------------------------------
// create template
// ---------------------------------------------------------------------------
test.describe('Permission: create template', () => {
  test.describe('enabled (default)', () => {
    let modeler: ModelerPage;

    test.beforeEach(async ({ page }) => {
      await setupMocks(page, { permissions: { 'create template': true } });
      modeler = new ModelerPage(page);
      await modeler.goto();
    });

    test('should allow toggling template checkbox', async () => {
      await modeler.openSettings();

      const templateCheckbox = modeler.getTemplateCheckbox();
      await expect(templateCheckbox).toBeEnabled();
    });
  });

  test.describe('disabled', () => {
    let modeler: ModelerPage;

    test.beforeEach(async ({ page }) => {
      await setupMocks(page, { permissions: { 'create template': false } });
      modeler = new ModelerPage(page);
      await modeler.goto();
    });

    test('should disable template checkbox', async () => {
      await modeler.openSettings();

      const templateCheckbox = modeler.getTemplateCheckbox();
      await expect(templateCheckbox).toBeDisabled();
    });

    test('should keep other metadata fields editable', async () => {
      await modeler.openSettings();

      // The label field should still be editable
      const labelInput = modeler.getMetadataField('label');
      await expect(labelInput).not.toHaveAttribute('readonly');

      // The first checkbox (executable/enabled) should still work
      const executableCheckbox = modeler.getMetadataModal().locator('input[type="checkbox"]').first();
      await expect(executableCheckbox).toBeEnabled();
    });
  });
});

// ---------------------------------------------------------------------------
// test (execution)
// ---------------------------------------------------------------------------
test.describe('Permission: test', () => {
  test.describe('enabled (default)', () => {
    test('should show Test button when test_url is configured', async ({ page }) => {
      await setupMocks(page, {
        withTestUrl: true,
        permissions: { test: true },
      });
      const modeler = new ModelerPage(page);
      await modeler.goto();

      // Replay panel should appear (test_url enables it)
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });

      // Test button should be visible
      const testBtn = modeler.getTestButton();
      await expect(testBtn).toBeVisible();
    });
  });

  test.describe('disabled', () => {
    test('should hide Test button even when test_url is configured', async ({ page }) => {
      await setupMocks(page, {
        withTestUrl: true,
        permissions: { test: false },
      });
      const modeler = new ModelerPage(page);
      await modeler.goto();

      // Test button should NOT be visible
      const testBtn = modeler.getTestButton();
      await expect(testBtn).not.toBeVisible();
    });

    test('should still show replay panel if replay data is available', async ({ page }) => {
      // The test permission does not affect the replay panel availability
      await setupMocks(page, {
        permissions: { test: false },
      });
      const modeler = new ModelerPage(page);
      await modeler.goto();

      // Select event node and load replay data
      await modeler.selectNode('event_1');
      await page.waitForTimeout(300);

      // Replay load button should still work (replay permission is separate)
      const replayBtn = modeler.getReplayLoadButton();
      await expect(replayBtn).toBeVisible();
    });
  });
});

// ---------------------------------------------------------------------------
// replay
// ---------------------------------------------------------------------------
test.describe('Permission: replay', () => {
  test.describe('enabled (default)', () => {
    test('should show replay load button in property panel for event nodes', async ({ page }) => {
      await setupMocks(page, { permissions: { replay: true } });
      const modeler = new ModelerPage(page);
      await modeler.goto();

      await modeler.selectNode('event_1');
      await page.waitForTimeout(300);

      const replayBtn = modeler.getReplayLoadButton();
      await expect(replayBtn).toBeVisible();
    });

    test('should allow loading replay data', async ({ page }) => {
      await setupMocks(page, { permissions: { replay: true } });
      const modeler = new ModelerPage(page);
      await modeler.goto();

      await modeler.selectNode('event_1');
      await page.waitForTimeout(300);
      await modeler.loadReplayData();

      // Replay panel should show entries
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });
      const toggle = modeler.getReplayEntryToggle();
      await expect(toggle).toBeVisible();
    });
  });

  test.describe('disabled', () => {
    test('should hide replay load button in property panel', async ({ page }) => {
      await setupMocks(page, { permissions: { replay: false } });
      const modeler = new ModelerPage(page);
      await modeler.goto();

      await modeler.selectNode('event_1');
      await page.waitForTimeout(300);

      const replayBtn = modeler.getReplayLoadButton();
      await expect(replayBtn).not.toBeVisible();
    });

    test('should not show replay panel when replay is denied', async ({ page }) => {
      // Without replay permission, hasReplayUrl becomes false
      // and without test permission either, no replay capability
      await setupMocks(page, {
        permissions: { replay: false, test: false },
      });
      const modeler = new ModelerPage(page);
      await modeler.goto();

      // The replay panel should not be visible at all (no replay capability)
      // Give it a moment to see if it appears
      await page.waitForTimeout(500);
      await expect(modeler.replayPanel).not.toBeVisible();
    });

    test('should hide test button even if test permission is granted (replay implies test)', async ({ page }) => {
      // Replay denied but test allowed — test button should still be hidden
      // because testing is pointless without replay capability
      await setupMocks(page, {
        withTestUrl: true,
        permissions: { replay: false, test: true },
      });
      const modeler = new ModelerPage(page);
      await modeler.goto();

      // Replay panel should not be visible (no replay capability)
      await page.waitForTimeout(500);
      await expect(modeler.replayPanel).not.toBeVisible();

      // Test button should also be hidden (replay: false implies test: false)
      const testBtn = modeler.getTestButton();
      await expect(testBtn).not.toBeVisible();
    });
  });
});

// ---------------------------------------------------------------------------
// edit template
// ---------------------------------------------------------------------------
test.describe('Permission: edit template', () => {
  test.describe('enabled (default)', () => {
    test('should allow editing a template model', async ({ page }) => {
      // Model is an existing template, user has edit template permission
      await setupMocks(page, {
        withTemplate: true,
        permissions: { 'edit template': true },
      });
      const modeler = new ModelerPage(page);
      await modeler.goto();

      // Model should load — canvas should be visible
      await expect(modeler.canvas).toBeVisible();

      // Metadata modal should be openable and editable
      await modeler.openSettings();
      const labelInput = modeler.getMetadataField('label');
      await expect(labelInput).not.toHaveAttribute('readonly');
    });
  });

  test.describe('disabled', () => {
    test('should enter read-only mode for existing template models', async ({ page }) => {
      // Model is an existing template, user lacks edit template permission
      // — the entire modeler falls back to read-only mode.
      await setupMocks(page, {
        withTemplate: true,
        permissions: { 'edit template': false },
      });
      const modeler = new ModelerPage(page);
      await modeler.goto();

      // Canvas should still render
      await expect(modeler.canvas).toBeVisible();

      // Save button should be hidden (read-only)
      await expect(modeler.saveButton).not.toBeVisible();

      // Per-element locking has been removed
    });

    test('should make metadata fields read-only for template models without permission', async ({ page }) => {
      await setupMocks(page, {
        withTemplate: true,
        permissions: { 'edit template': false },
      });
      const modeler = new ModelerPage(page);
      await modeler.goto();

      await modeler.openSettings();

      // Label field should be read-only
      const labelInput = modeler.getMetadataField('label');
      await expect(labelInput).toHaveAttribute('readonly');

      // Save button should not exist in modal
      const saveBtn = modeler.getMetadataSaveButton();
      await expect(saveBtn).toHaveCount(0);
    });

    test('should disable property panel fields for template models without permission', async ({ page }) => {
      await setupMocks(page, {
        withTemplate: true,
        permissions: { 'edit template': false },
      });
      const modeler = new ModelerPage(page);
      await modeler.goto();

      // Select a node — property panel should show disabled fields
      await modeler.selectNode('event_1');
      await page.waitForTimeout(300);
      const labelInput = modeler.propertyPanel.locator('#modeler-component-label');
      await expect(labelInput).toBeDisabled();
    });

    test('should not affect non-template models but disable template checkbox', async ({ page }) => {
      // Model is NOT a template, edit template permission is false
      // — metadata stays editable but the template checkbox is disabled
      //   so the user cannot turn a non-template into a template.
      await setupMocks(page, {
        permissions: { 'edit template': false },
      });
      const modeler = new ModelerPage(page);
      await modeler.goto();

      await expect(modeler.canvas).toBeVisible();

      // Save button should still be visible (not read-only)
      await expect(modeler.saveButton).toBeVisible();

      // Metadata should remain editable
      await modeler.openSettings();
      const labelInput = modeler.getMetadataField('label');
      await expect(labelInput).not.toHaveAttribute('readonly');

      // Template checkbox should be disabled
      const templateCheckbox = modeler.getTemplateCheckbox();
      await expect(templateCheckbox).toBeDisabled();
    });
  });
});

// ---------------------------------------------------------------------------
// Combined permission scenarios
// ---------------------------------------------------------------------------
test.describe('Permissions: combined scenarios', () => {
  test('should enforce multiple denied permissions simultaneously', async ({ page }) => {
    await setupMocks(page, {
      withTestUrl: true,
      withContexts: true,
      permissions: {
        'edit metadata': false,
        'switch context': false,
        'create template': false,
        test: false,
        replay: false,
      },
    });
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Context dropdown should be hidden
    const contextSelect = modeler.getContextSelect();
    await expect(contextSelect).not.toBeVisible();

    // Test button should be hidden
    const testBtn = modeler.getTestButton();
    await expect(testBtn).not.toBeVisible();

    // Replay load button should be hidden
    await modeler.selectNode('event_1');
    await page.waitForTimeout(300);
    const replayBtn = modeler.getReplayLoadButton();
    await expect(replayBtn).not.toBeVisible();

    // Open metadata modal — fields should be read-only
    await modeler.openSettings();
    const labelInput = modeler.getMetadataField('label');
    await expect(labelInput).toHaveAttribute('readonly');

    // Template checkbox should be disabled
    const templateCheckbox = modeler.getTemplateCheckbox();
    await expect(templateCheckbox).toBeDisabled();

    // Save button should not exist
    const saveBtn = modeler.getMetadataSaveButton();
    await expect(saveBtn).toHaveCount(0);
  });

  test('should work with all permissions explicitly enabled', async ({ page }) => {
    await setupMocks(page, {
      withTestUrl: true,
      withContexts: true,
      permissions: {
        'edit metadata': true,
        'switch context': true,
        'create template': true,
        'edit template': true,
        test: true,
        replay: true,
      },
    });
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Context dropdown should be visible
    const contextSelect = modeler.getContextSelect();
    await expect(contextSelect).toBeVisible();

    // Replay panel should appear (test_url configured)
    await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });

    // Test button should be visible
    const testBtn = modeler.getTestButton();
    await expect(testBtn).toBeVisible();

    // Replay load button should be visible for event nodes
    await modeler.selectNode('event_1');
    await page.waitForTimeout(300);
    const replayBtn = modeler.getReplayLoadButton();
    await expect(replayBtn).toBeVisible();

    // Open metadata modal — fields should be editable
    await modeler.openSettings();
    const labelInput = modeler.getMetadataField('label');
    await expect(labelInput).not.toHaveAttribute('readonly');

    // Template checkbox should be enabled
    const templateCheckbox = modeler.getTemplateCheckbox();
    await expect(templateCheckbox).toBeEnabled();

    // Save button should be present
    const saveBtn = modeler.getMetadataSaveButton();
    await expect(saveBtn).toBeVisible();
  });
});
