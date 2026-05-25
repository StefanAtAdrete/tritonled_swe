import { test, expect } from '@playwright/test';
import { ModelerPage } from './pages/ModelerPage';
import { setupMocks } from './fixtures/mocks';

/**
 * E2E tests for condition data preservation across save round-trips.
 *
 * These tests verify that condition IDs and their associated data
 * (plugin, label, configuration) survive the load → edit → save cycle.
 * This prevents a regression where the modeler would regenerate condition
 * IDs on every save, breaking references in the ECA YAML config.
 */
test.describe('Condition Round-trip Preservation', () => {
  let modeler: ModelerPage;

  test.beforeEach(async ({ page }) => {
    await setupMocks(page);
    modeler = new ModelerPage(page);
  });

  test('should load model with condition edge and display it correctly', async ({ page }) => {
    await modeler.goto();

    // The model has 3 nodes and 1 condition edge
    const nodeCount = await modeler.getNodeCount();
    expect(nodeCount).toBe(3);

    const edgeCount = await modeler.getEdgeCount();
    expect(edgeCount).toBe(2);

    // The condition edge should be rendered (edge_1 has condition data)
    const conditionEdge = page.locator('.react-flow__edge').first();
    await expect(conditionEdge).toBeVisible();
  });

  test('should preserve conditionId in save payload', async ({ page }) => {
    await modeler.goto();

    // Set up request interception BEFORE triggering save.
    // The mock Drupal.ajax in test-server.ts performs a real fetch()
    // to the save endpoint, which Playwright can intercept.
    const saveRequestPromise = page.waitForRequest(request =>
      request.url().includes('modeler-api/model') &&
      request.method() === 'POST'
    );

    // Make a change to enable save, then trigger save
    await modeler.autoLayout();
    await page.waitForTimeout(300);
    await expect(modeler.saveButton).toBeEnabled();

    // Click the save button — the mock Drupal.ajax sends a real fetch
    await modeler.saveButton.click();

    const saveRequest = await saveRequestPromise;
    const payload = JSON.parse(saveRequest.postData() || '{}');

    // Find the condition edge in the saved payload
    const conditionEdge = payload.edges?.find(
      (e: Record<string, unknown>) => e.source === 'event_1' && e.target === 'action_1'
    );

    expect(conditionEdge).toBeDefined();

    // The original condition ID must be preserved — not regenerated
    expect(conditionEdge.conditionId).toBe('eca_entity_is_new_10j5tps');

    // Condition plugin and label must also survive
    expect(conditionEdge.condition).toBe('entity:is_new');
    expect(conditionEdge.conditionLabel).toBe('Entity is New');

    // Configuration must be preserved
    expect(conditionEdge.conditionConfiguration).toEqual(
      expect.objectContaining({ negate: false })
    );
  });

  test('should preserve conditionId after node drag and save', async ({ page }) => {
    await modeler.goto();

    // Set up request interception BEFORE triggering save
    const saveRequestPromise = page.waitForRequest(request =>
      request.url().includes('modeler-api/model') &&
      request.method() === 'POST'
    );

    // Drag a node to make the model dirty
    await modeler.moveNode('action_1', 50, 30);
    await page.waitForTimeout(300);
    await expect(modeler.saveButton).toBeEnabled();

    await modeler.saveButton.click();
    const saveRequest = await saveRequestPromise;
    const payload = JSON.parse(saveRequest.postData() || '{}');

    const conditionEdge = payload.edges?.find(
      (e: Record<string, unknown>) => e.conditionId === 'eca_entity_is_new_10j5tps'
    );

    expect(conditionEdge).toBeDefined();
    expect(conditionEdge.condition).toBe('entity:is_new');
  });
});
