import { test, expect } from '@playwright/test';
import { ModelerPage } from './pages/ModelerPage';
import { setupMocks } from './fixtures/mocks';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));

/**
 * Screenshot generation for mkdocs documentation.
 *
 * These tests capture screenshots of the modeler in various states
 * and save them to docs/assets/screenshots/ for use in the documentation.
 *
 * Run with:
 *   npx playwright test --config tests/playwright.config.ts tests/e2e/screenshots.spec.ts
 *
 * All screenshots use a 1280×800 viewport for consistency.
 */

// Screenshots are saved into ui/tests/screenshots/ so that the remote-npm
// skill syncs them back.  A post-generation step copies them to the final
// docs/assets/screenshots/ directory.
const SCREENSHOT_DIR = join(__dirname, '../screenshots');

/** Helper: take a full-page screenshot and save to the docs screenshots dir. */
async function screenshot(page: ModelerPage['page'], name: string) {
  await page.screenshot({
    path: join(SCREENSHOT_DIR, name),
    type: 'png',
  });
}

/** Helper: take a screenshot of a specific element. */
async function elementScreenshot(
  locator: ReturnType<ModelerPage['page']['locator']>,
  name: string,
) {
  await locator.screenshot({
    path: join(SCREENSHOT_DIR, name),
    type: 'png',
  });
}

// Use a consistent wide viewport so all toolbar buttons are visible
test.use({ viewport: { width: 1280, height: 800 } });

// ─── Overview & Getting Started ──────────────────────────────────────────────

test.describe('Documentation Screenshots', () => {
  test('modeler-overview — full modeler with workflow', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Select the action node so the Property Panel is populated
    await modeler.selectNode('action_1');
    await page.waitForTimeout(500);

    await screenshot(page, 'modeler-overview.png');
  });

  test('modeler-empty — empty canvas for a new model', async ({ page }) => {
    await setupMocks(page, { isNew: true });
    const modeler = new ModelerPage(page);
    await page.goto('/modeler/new-model');
    await modeler.waitForLoad();

    // Wait for the metadata modal that auto-opens for new models (100ms delay in Flow.tsx)
    const modal = page.locator('.metadata-modal');
    await expect(modal).toBeVisible({ timeout: 5000 });

    // Close it via the close button (more reliable than Escape key)
    await modal.locator('.close-btn').click();
    await expect(modal).not.toBeVisible({ timeout: 3000 });

    await screenshot(page, 'modeler-empty.png');
  });

  test('add-event — adding an event node via toolbar', async ({ page }) => {
    await setupMocks(page, { isNew: true });
    const modeler = new ModelerPage(page);
    await page.goto('/modeler/new-model');
    await modeler.waitForLoad();

    // Wait for the metadata modal that auto-opens for new models (100ms delay in Flow.tsx)
    const modal = page.locator('.metadata-modal');
    await expect(modal).toBeVisible({ timeout: 5000 });

    // Close it via the close button (more reliable than Escape key)
    await modal.locator('.close-btn').click();
    await expect(modal).not.toBeVisible({ timeout: 3000 });

    // Open the quick-add event popup from the toolbar
    await modeler.openQuickAddEventPopup();
    await page.waitForTimeout(300);

    await screenshot(page, 'add-event.png');
  });

  // ─── Interface ───────────────────────────────────────────────────────────

  test('interface-overview — all four panels visible', async ({ page }) => {
    await setupMocks(page, { withTestUrl: true });
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Select the event node and load replay data so all panels are populated
    await modeler.selectNode('event_1');
    await page.waitForTimeout(300);
    await modeler.loadReplayData();
    await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });
    await page.waitForTimeout(500);

    await screenshot(page, 'interface-overview.png');
  });

  test('multi-selection — multiple nodes selected', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Select both nodes
    await modeler.selectMultipleNodes(['event_1', 'action_1']);
    await page.waitForTimeout(500);

    await screenshot(page, 'multi-selection.png');
  });

  test('quick-add — quick-add event popup with categories', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Show the quick-add event popup which displays component categories
    await modeler.openQuickAddEventPopup();
    await page.waitForTimeout(300);

    await screenshot(page, 'component-panel.png');
  });

  test('toolbar — toolbar with all buttons', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();
    await page.waitForTimeout(500);

    // Capture just the toolbar area
    await elementScreenshot(modeler.toolbar, 'toolbar.png');
  });

  test('property-panel — config form for selected action', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Select the action node to show its configuration
    await modeler.selectNode('action_1');
    await page.waitForTimeout(500);

    // Wait for the property panel to fully render
    await expect(modeler.propertyPanel).toBeVisible();

    await screenshot(page, 'property-panel.png');
  });

  test('multi-selection-panel — bulk operations for multi-select', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Select both nodes to trigger the multi-selection panel
    await modeler.selectMultipleNodes(['event_1', 'action_1']);
    await page.waitForTimeout(500);

    await expect(modeler.propertyPanel).toBeVisible();

    await screenshot(page, 'multi-selection-panel.png');
  });

  test('replay-panel — replay panel with execution steps and controls', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Select event node and load replay data
    await modeler.selectNode('event_1');
    await page.waitForTimeout(300);
    await modeler.loadReplayData();
    await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });

    // Navigate to the second step so we can show active step + completed step
    await modeler.nextReplayStep();
    await page.waitForTimeout(200);
    await modeler.nextReplayStep();
    await page.waitForTimeout(500);

    await screenshot(page, 'replay-panel.png');
  });

  // ─── Working with Models ──────────────────────────────────────────────────

  test('quick-add — quick-add popup on a node', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Hover over the event node to reveal the quick-add button, then click it
    const eventNode = modeler.getNode('event_1');
    await eventNode.hover();
    await page.waitForTimeout(300);

    // Open the quick-add popup
    await modeler.openQuickAddPopup('event_1');
    await page.waitForTimeout(300);

    await screenshot(page, 'quick-add.png');
  });

  test('quick-add-filter — quick-add popup with type filter expanded', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Open the quick-add popup on the event node
    await modeler.openQuickAddPopup('event_1');
    await page.waitForTimeout(300);

    // Expand the filter panel
    const filterToggle = page.locator('.quick-add-filter-toggle');
    if (await filterToggle.count() > 0) {
      await filterToggle.click();
      await page.waitForTimeout(200);
    }

    await screenshot(page, 'quick-add-filter.png');
  });

  test('placeholder-node — placeholder node from condition-first authoring', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Open the quick-add popup on the event node
    await modeler.openQuickAddPopup('event_1');
    await page.waitForTimeout(300);

    // Use the filter to show only conditions
    const filterToggle = page.locator('.quick-add-filter-toggle');
    if (await filterToggle.count() > 0) {
      await filterToggle.click();
      await page.waitForTimeout(200);

      // Click the condition filter option
      const conditionFilter = page.locator('.quick-add-filter-option').filter({ hasText: /Links|Conditions/ });
      if (await conditionFilter.count() > 0) {
        await conditionFilter.first().click();
        await page.waitForTimeout(200);
      }
    }

    // Select the first condition component to create a placeholder node
    const conditionItem = page.locator('.quick-add-component-item').first();
    if (await conditionItem.count() > 0) {
      await conditionItem.click();
      await page.waitForTimeout(500);
    }

    // Click on empty canvas to deselect the condition edge, then take screenshot
    await modeler.canvas.click({ position: { x: 50, y: 50 } });
    await page.waitForTimeout(300);

    await screenshot(page, 'placeholder-node.png');
  });

  // ─── Components ───────────────────────────────────────────────────────────

  test('event-node — event node on the canvas', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Click on empty canvas to deselect everything
    await modeler.canvas.click({ position: { x: 50, y: 50 } });
    await page.waitForTimeout(300);

    // Get the event node and take an element screenshot of it
    const eventNode = modeler.getNode('event_1');
    await expect(eventNode).toBeVisible();

    // Take a full screenshot but cropped around the event node area
    await screenshot(page, 'event-node.png');
  });

  test('edge-condition — quick-add condition button on an edge', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Hover over the edge to reveal the quick-add condition button
    // First, find the edge path element
    const edgePath = page.locator('.react-flow__edge').first();
    await edgePath.hover({ force: true });
    await page.waitForTimeout(500);

    // Try to open the condition popup
    try {
      await modeler.openQuickAddConditionPopup('edge_2');
      await page.waitForTimeout(300);
    } catch {
      // The button may not appear in all configurations — take screenshot anyway
    }

    await screenshot(page, 'edge-condition.png');
  });

  test('configuration-form — config form in property panel', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Select the action node to display its configuration form
    await modeler.selectNode('action_1');
    await page.waitForTimeout(500);

    await expect(modeler.propertyPanel).toBeVisible();

    await screenshot(page, 'configuration-form.png');
  });

  // ─── Features ─────────────────────────────────────────────────────────────

  test('search — search bar with results', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Open search via keyboard shortcut
    await modeler.openSearch();
    await page.waitForTimeout(300);

    // Type a search query to show results
    const searchInput = page.locator('input[placeholder*="Search"]').first();
    if (await searchInput.isVisible()) {
      await searchInput.fill('Entity');
      await page.waitForTimeout(500);
    }

    await screenshot(page, 'search.png');
  });

  test('dark-mode — modeler in dark mode', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Dark mode toggle is now inside the kebab menu (ToolbarMenu)
    const kebabTrigger = page.locator('.toolbar-menu-trigger');
    await kebabTrigger.click();
    const darkModeItem = page.locator('.toolbar-menu-item:has(.toolbar-menu-item-label:text("Switch to Dark Mode"))');
    await darkModeItem.click();
    await page.waitForTimeout(500);

    // Select a node so the property panel is visible
    await modeler.selectNode('action_1');
    await page.waitForTimeout(300);

    await screenshot(page, 'dark-mode.png');
  });

  // Skipped: The "Show All Annotations" toggle was removed from the toolbar.
  // Annotations are visible per-node when annotation data exists, but there is
  // no global toggle in the current UI to show all annotations at once.
  test.skip('annotations — canvas with visible annotations', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();
    await page.waitForTimeout(500);

    await screenshot(page, 'annotations.png');
  });

  test('flow-filter — flow filter dropdown', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Look for the flow filter dropdown in the toolbar
    const filterDropdown = page.locator('.flow-filter, .toolbar-flow-filter, select[aria-label*="flow"], select[aria-label*="Flow"]');
    if (await filterDropdown.count() > 0) {
      await filterDropdown.first().click();
      await page.waitForTimeout(300);
    } else {
      // Try button-based filter
      const filterBtn = page.locator('button[title*="Flow"], button[title*="filter"], button[aria-label*="Flow"], button[aria-label*="filter"]');
      if (await filterBtn.count() > 0) {
        await filterBtn.first().click();
        await page.waitForTimeout(300);
      }
    }

    await screenshot(page, 'flow-filter.png');
  });

  // ─── Replay ───────────────────────────────────────────────────────────────

  test('replay-load — loading replay data for an event node', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Select event node
    await modeler.selectNode('event_1');
    await page.waitForTimeout(300);

    // Click the load replay button
    await modeler.loadReplayData();

    // Wait for the replay panel to appear
    await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });
    await page.waitForTimeout(500);

    await screenshot(page, 'replay-load.png');
  });

  test('test-waiting — test waiting state with spinner', async ({ page }) => {
    // Use a high poll count so the waiting state stays visible
    await setupMocks(page, { withTestUrl: true, testPollWaitCount: 100 });
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Wait for replay panel (visible because test_url is configured)
    await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });

    // Click the Test button
    const testBtn = modeler.getTestButton();
    await testBtn.click();

    // Wait for the waiting state to appear
    const waitingState = modeler.getTestWaitingState();
    await expect(waitingState).toBeVisible({ timeout: 5000 });
    await page.waitForTimeout(500);

    await screenshot(page, 'test-waiting.png');
  });

  test('step-data — step data showing token values', async ({ page }) => {
    await setupMocks(page);
    const modeler = new ModelerPage(page);
    await modeler.goto();

    // Select event node and load replay data
    await modeler.selectNode('event_1');
    await page.waitForTimeout(300);
    await modeler.loadReplayData();
    await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });

    // Navigate to the second step (action_1 which has entity data)
    await modeler.nextReplayStep();
    await page.waitForTimeout(200);
    await modeler.nextReplayStep();
    await page.waitForTimeout(500);

    // The data content section should be visible
    const dataContent = modeler.replayPanel.locator('.data-content');
    await expect(dataContent).toBeVisible();

    await screenshot(page, 'step-data.png');
  });
});
