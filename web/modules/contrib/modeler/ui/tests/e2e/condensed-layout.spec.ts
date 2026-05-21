/**
 * E2E tests for condensed layout behavior.
 *
 * Verifies that:
 * 1. Auto-layout places successors below their parents (not beside them).
 * 2. Edges with conditions get more vertical space than edges without.
 * 3. Adding a condition to an edge shifts the target node downward.
 */

import { test, expect } from '@playwright/test';
import { ModelerPage } from './pages/ModelerPage';
import { setupMocks } from './fixtures/mocks';

test.describe('Workflow Modeler - Condensed Layout', () => {
  let modeler: ModelerPage;

  test.beforeEach(async ({ page }) => {
    await setupMocks(page);
    modeler = new ModelerPage(page);
    await modeler.goto();
  });

  test.describe('Auto-Layout Node Placement', () => {
    test('should place successors below their parent after auto-layout', async ({ page }) => {
      // The mock model has: event_1 -> action_1 -> action_2
      // After auto-layout they should form a vertical chain.
      await modeler.autoLayout();
      await page.waitForTimeout(500);

      const eventBox = await modeler.getNode('event_1').boundingBox();
      const action1Box = await modeler.getNode('action_1').boundingBox();
      const action2Box = await modeler.getNode('action_2').boundingBox();

      expect(eventBox).toBeDefined();
      expect(action1Box).toBeDefined();
      expect(action2Box).toBeDefined();

      // Each successor should be positioned below its parent
      expect(action1Box!.y).toBeGreaterThan(eventBox!.y);
      expect(action2Box!.y).toBeGreaterThan(action1Box!.y);
    });

    test('should give more vertical space to edges with conditions', async ({ page }) => {
      // In the mock model, edge_1 (event_1 -> action_1) has a condition,
      // while edge_2 (action_1 -> action_2) does not.
      await modeler.autoLayout();
      await page.waitForTimeout(500);

      const eventBox = await modeler.getNode('event_1').boundingBox();
      const action1Box = await modeler.getNode('action_1').boundingBox();
      const action2Box = await modeler.getNode('action_2').boundingBox();

      expect(eventBox).toBeDefined();
      expect(action1Box).toBeDefined();
      expect(action2Box).toBeDefined();

      // Gap between event_1 and action_1 (edge with condition)
      const gapWithCondition = action1Box!.y - eventBox!.y;
      // Gap between action_1 and action_2 (edge without condition)
      const gapWithoutCondition = action2Box!.y - action1Box!.y;

      // The conditioned edge should have a larger vertical gap
      expect(gapWithCondition).toBeGreaterThan(gapWithoutCondition);
    });
  });

  test.describe('Dynamic Spacing on Condition Add', () => {
    test('should shift target node down when adding a condition to an edge', async ({ page }) => {
      // First trigger auto-layout so nodes are in a predictable arrangement
      await modeler.autoLayout();
      await page.waitForTimeout(500);

      // Record the position of action_2 before adding a condition to edge_2
      const action2Before = await modeler.getNode('action_2').boundingBox();
      expect(action2Before).toBeDefined();

      // Add a condition to edge_2 (action_1 -> action_2), which has no condition
      await modeler.openQuickAddConditionPopup('edge_2');
      await modeler.selectQuickAddComponent('Entity is New');
      await page.waitForTimeout(500);

      // action_2 should have moved down to make room for the condition card
      const action2After = await modeler.getNode('action_2').boundingBox();
      expect(action2After).toBeDefined();
      expect(action2After!.y).toBeGreaterThan(action2Before!.y);
    });
  });
});
