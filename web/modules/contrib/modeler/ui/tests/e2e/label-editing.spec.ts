import { test, expect } from '@playwright/test';
import { ModelerPage } from './pages/ModelerPage';
import { setupMocks } from './fixtures/mocks';

/**
 * E2E tests for label editing functionality.
 * These tests verify that labels can be edited in the property panel
 * and that changes are reflected on the canvas.
 */
test.describe('Workflow Modeler - Label Editing', () => {
  let modeler: ModelerPage;

  test.beforeEach(async ({ page }) => {
    await setupMocks(page);
    modeler = new ModelerPage(page);
    await modeler.goto();
  });

  test.describe('Node Label Editing', () => {
    test('should display node label in property panel when node is selected', async ({ page }) => {
      // Select the event node
      await modeler.selectNode('event_1');
      
      // Wait for property panel to show the node
      await expect(modeler.propertyPanel).toBeVisible();
      
      // Check that the label input exists and has the correct value
      const labelInput = page.locator('#modeler-component-label');
      await expect(labelInput).toBeVisible();
      await expect(labelInput).toHaveValue('On Entity Insert');
    });

    test('should update node label on canvas when edited in property panel', async ({ page }) => {
      // Select the event node
      await modeler.selectNode('event_1');
      
      // Find the label input
      const labelInput = page.locator('#modeler-component-label');
      await expect(labelInput).toBeVisible();
      
      // Clear and type new label
      await labelInput.clear();
      await labelInput.fill('My Updated Event');
      
      // Blur to trigger save
      await labelInput.blur();
      
      // Wait for debounce and update
      await page.waitForTimeout(400);
      
      // Verify the node label updated on canvas
      const node = modeler.getNode('event_1');
      await expect(node).toContainText('My Updated Event');
    });

    test('should persist node label after selecting different node and returning', async ({ page }) => {
      // Select the event node
      await modeler.selectNode('event_1');
      
      // Edit the label
      const labelInput = page.locator('#modeler-component-label');
      await labelInput.clear();
      await labelInput.fill('Renamed Event');
      await labelInput.blur();
      await page.waitForTimeout(400);
      
      // Select a different node
      await modeler.selectNode('action_1');
      await page.waitForTimeout(200);
      
      // Select the original node again
      await modeler.selectNode('event_1');
      await page.waitForTimeout(200);
      
      // Verify the label persisted
      await expect(labelInput).toHaveValue('Renamed Event');
      
      // Verify it's still on the canvas
      const node = modeler.getNode('event_1');
      await expect(node).toContainText('Renamed Event');
    });

    test('should mark model as having unsaved changes when label is edited', async ({ page }) => {
      // Select the event node
      await modeler.selectNode('event_1');
      
      // Edit the label
      const labelInput = page.locator('#modeler-component-label');
      await labelInput.clear();
      await labelInput.fill('Changed Label');
      await labelInput.blur();
      await page.waitForTimeout(400);
      
      // Check for unsaved changes indicator (the save button or title change)
      // The toolbar should show unsaved state
      const saveButton = page.locator('button[title="Save Model"]');
      // The button should be enabled when there are unsaved changes
      await expect(saveButton).toBeEnabled();
    });
  });

  test.describe('Condition Label Editing', () => {
    test('should display condition label input when edge with condition is selected', async ({ page }) => {
      // First, add a condition to an edge using quick-add
      // Click directly on the quick-add condition button (it's always present, just with low opacity)
      const quickAddConditionBtn = page.locator('button[title="Add condition"]').first();
      
      // Force click since the button may have low opacity
      await quickAddConditionBtn.click({ force: true });
      
      // Wait for popup
      await page.waitForSelector('.quick-add-popup', { state: 'visible', timeout: 5000 });
      
      // Select a condition
      const conditionItem = page.locator('.quick-add-component-item:has-text("Entity is New")');
      await conditionItem.click();
      
      // Wait for popup to close and edge to be selected
      await page.waitForSelector('.quick-add-popup', { state: 'hidden', timeout: 5000 });
      await page.waitForTimeout(300);
      
      // The edge should now be selected and show condition label input
      const conditionLabelInput = page.locator('#modeler-condition-label');
      await expect(conditionLabelInput).toBeVisible();
    });

    test('should update condition label on canvas when edited', async ({ page }) => {
      // Add a condition to an edge using quick-add
      const quickAddConditionBtn = page.locator('button[title="Add condition"]').first();
      await quickAddConditionBtn.click({ force: true });
      await page.waitForSelector('.quick-add-popup', { state: 'visible', timeout: 5000 });
      
      const conditionItem = page.locator('.quick-add-component-item:has-text("Entity is New")');
      await conditionItem.click();
      await page.waitForSelector('.quick-add-popup', { state: 'hidden', timeout: 5000 });
      await page.waitForTimeout(300);
      
      // Find the condition label input
      const conditionLabelInput = page.locator('#modeler-condition-label');
      await expect(conditionLabelInput).toBeVisible();
      
      // Edit the condition label
      await conditionLabelInput.clear();
      await conditionLabelInput.fill('Is New Entity?');
      await conditionLabelInput.blur();
      
      // Wait for debounce
      await page.waitForTimeout(400);
      
      // Verify the label was saved by deselecting and reselecting
      // Click elsewhere to deselect
      await modeler.selectNode('event_1');
      await page.waitForTimeout(200);
      
      // Reselect the edge (edge_2 — the one we added the condition to)
      const edge = page.locator('.react-flow__edge[data-testid="rf__edge-edge_2"], .react-flow__edge[data-id="edge_2"]').first();
      await edge.click({ force: true });
      await page.waitForTimeout(200);
      
      // Verify the label persisted in the input
      const conditionLabelInputAfter = page.locator('#modeler-condition-label');
      await expect(conditionLabelInputAfter).toHaveValue('Is New Entity?');
    });

    test('should persist condition label after deselecting and reselecting edge', async ({ page }) => {
      // Add a condition to an edge (edge_2, the one without a condition)
      const quickAddConditionBtn = page.locator('button[title="Add condition"]').first();
      await quickAddConditionBtn.click({ force: true });
      await page.waitForSelector('.quick-add-popup', { state: 'visible', timeout: 5000 });
      
      const conditionItem = page.locator('.quick-add-component-item:has-text("Entity is New")');
      await conditionItem.click();
      await page.waitForSelector('.quick-add-popup', { state: 'hidden', timeout: 5000 });
      await page.waitForTimeout(300);
      
      // Edit the condition label
      const conditionLabelInput = page.locator('#modeler-condition-label');
      await conditionLabelInput.clear();
      await conditionLabelInput.fill('Custom Condition Label');
      await conditionLabelInput.blur();
      await page.waitForTimeout(400);
      
      // Click elsewhere to deselect
      await modeler.selectNode('event_1');
      await page.waitForTimeout(200);
      
      // Reselect the edge (edge_2 — the one we modified)
      const edge = page.locator('.react-flow__edge[data-testid="rf__edge-edge_2"], .react-flow__edge[data-id="edge_2"]').first();
      await edge.click({ force: true });
      await page.waitForTimeout(200);
      
      // Verify the label persisted
      const conditionLabelInputAfter = page.locator('#modeler-condition-label');
      await expect(conditionLabelInputAfter).toHaveValue('Custom Condition Label');
    });
  });

  test.describe('Action Node Label Editing', () => {
    test('should update action node label', async ({ page }) => {
      // Select the action node
      await modeler.selectNode('action_1');
      
      // Find the label input
      const labelInput = page.locator('#modeler-component-label');
      await expect(labelInput).toBeVisible();
      await expect(labelInput).toHaveValue('Save Entity');
      
      // Edit the label
      await labelInput.clear();
      await labelInput.fill('Custom Save Action');
      await labelInput.blur();
      await page.waitForTimeout(400);
      
      // Verify the node label updated on canvas
      const node = modeler.getNode('action_1');
      await expect(node).toContainText('Custom Save Action');
    });
  });

  test.describe('Label Input Behavior', () => {
    test('should update label on blur without waiting for debounce', async ({ page }) => {
      // Select a node
      await modeler.selectNode('event_1');
      
      const labelInput = page.locator('#modeler-component-label');
      
      // Type a new label
      await labelInput.clear();
      await labelInput.fill('Immediate Update Test');
      
      // Immediately blur (don't wait for debounce)
      await labelInput.blur();
      
      // Small wait for the blur handler
      await page.waitForTimeout(100);
      
      // The label should already be updated
      const node = modeler.getNode('event_1');
      await expect(node).toContainText('Immediate Update Test');
    });

    test('should handle rapid typing with debounce', async ({ page }) => {
      // Select a node
      await modeler.selectNode('event_1');
      
      const labelInput = page.locator('#modeler-component-label');
      
      // Type character by character rapidly
      await labelInput.clear();
      await labelInput.pressSequentially('Rapid', { delay: 50 });
      
      // Wait for debounce to complete
      await page.waitForTimeout(400);
      
      // The final value should be on the canvas
      const node = modeler.getNode('event_1');
      await expect(node).toContainText('Rapid');
    });

  });
});
