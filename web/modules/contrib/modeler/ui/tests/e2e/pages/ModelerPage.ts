import { Page, Locator, expect } from '@playwright/test';

/**
 * Page Object for the Workflow Modeler.
 * Provides a clean API for interacting with modeler elements in tests.
 */
export class ModelerPage {
  readonly page: Page;

  // Main layout elements
  readonly canvas: Locator;
  readonly propertyPanel: Locator;
  readonly toolbar: Locator;
  readonly replayPanel: Locator;

  // Toolbar buttons (main toolbar)
  readonly saveButton: Locator;
  readonly settingsButton: Locator;
  readonly searchButton: Locator;
  readonly exportButton: Locator;
  readonly kebabMenuTrigger: Locator;

  // Canvas toolbar buttons (secondary toolbar on the canvas)
  readonly undoButton: Locator;
  readonly redoButton: Locator;
  readonly autoLayoutButton: Locator;
  readonly copyButton: Locator;
  readonly pasteButton: Locator;
  // Canvas elements
  readonly nodes: Locator;
  readonly edges: Locator;
  readonly minimap: Locator;
  readonly controls: Locator;

  constructor(page: Page) {
    this.page = page;

    // Main layout - use actual CSS classes from the app
    this.canvas = page.locator('.react-flow');
    this.propertyPanel = page.locator('.workflow-property-panel');
    this.toolbar = page.locator('.workflow-toolbar');
    this.replayPanel = page.locator('.replay-panel');

    // Main toolbar buttons
    this.saveButton = page.locator('button[title="Save Model"]');
    // Settings and Export are now inside the kebab menu (ToolbarMenu)
    this.kebabMenuTrigger = page.locator('.toolbar-menu-trigger');
    this.settingsButton = page.locator('.toolbar-menu-item:has(.toolbar-menu-item-label:text("Model Settings"))');
    this.searchButton = page.locator('.toolbar-search-inline');
    this.exportButton = page.locator('.toolbar-menu-item:has(.toolbar-menu-item-label:text("Export Model"))');

    // Canvas toolbar buttons (secondary toolbar above the canvas)
    this.undoButton = page.locator('button[title*="Undo"]');
    this.redoButton = page.locator('button[title*="Redo"]');
    this.autoLayoutButton = page.locator('.canvas-toolbar-dropdown-item:has(.canvas-toolbar-dropdown-label:text("Auto Layout"))');
    this.copyButton = page.locator('button[title*="Copy Selected"]');
    this.pasteButton = page.locator('button[title*="Paste Elements"]');

    // Canvas
    this.nodes = page.locator('.react-flow__node');
    this.edges = page.locator('.react-flow__edge');
    this.minimap = page.locator('.react-flow__minimap');
    this.controls = page.locator('.react-flow__controls');
  }

  /**
   * Navigate to the modeler page.
   */
  async goto(modelId = 'test-model-1') {
    await this.page.goto(`/modeler/${modelId}`);
    await this.waitForLoad();
  }

  /**
   * Wait for the modeler to fully load.
   */
  async waitForLoad() {
    await this.canvas.waitFor({ state: 'visible', timeout: 10000 });
    // Wait for React Flow to initialize
    await this.page.waitForSelector('.react-flow__viewport', { timeout: 10000 });
  }

  /**
   * Get a specific node by its ID.
   */
  getNode(nodeId: string): Locator {
    return this.page.locator(`[data-id="${nodeId}"]`);
  }

  /**
   * Get a specific edge by its ID.
   * React Flow creates edge path elements with the edge ID.
   * We look for the edge path or its parent group.
   */
  getEdge(edgeId: string): Locator {
    // Try to find edge by path ID first (React Flow puts edge ID on the path element)
    return this.page.locator(`.react-flow__edge path#${edgeId}, .react-flow__edge[data-id="${edgeId}"]`);
  }

  /**
   * Select a node on the canvas.
   */
  async selectNode(nodeId: string) {
    const node = this.getNode(nodeId);
    await node.click();
  }

  /**
   * Select multiple nodes (Shift+Click).
   */
  async selectMultipleNodes(nodeIds: string[]) {
    for (let i = 0; i < nodeIds.length; i++) {
      const node = this.getNode(nodeIds[i]);
      if (i === 0) {
        await node.click();
      } else {
        await node.click({ modifiers: ['Shift'] });
      }
    }
  }

  /**
   * Delete selected elements using keyboard shortcut.
   */
  async deleteSelected() {
    await this.page.keyboard.press('Delete');
  }

  /**
   * Copy selected elements using keyboard shortcut.
   */
  async copySelected() {
    await this.page.keyboard.press('Control+c');
  }

  /**
   * Paste elements using keyboard shortcut.
   */
  async paste() {
    await this.page.keyboard.press('Control+v');
  }

  /**
   * Undo last action using keyboard shortcut.
   */
  async undo() {
    await this.page.keyboard.press('Control+z');
  }

  /**
   * Redo last undone action using keyboard shortcut.
   */
  async redo() {
    await this.page.keyboard.press('Control+Shift+z');
  }

  /**
   * Open search using keyboard shortcut.
   */
  async openSearch() {
    await this.page.keyboard.press('Control+f');
  }

  /**
   * Save the model using keyboard shortcut.
   */
  async saveModel() {
    await this.page.keyboard.press('Control+s');
  }

  /**
   * Click a toolbar button, falling back to the overflow menu when the
   * button has been collapsed by the toolbar overflow system.
   */
  private async clickToolbarButton(inlineButton: Locator, overflowLabel: string) {
    if (await inlineButton.isVisible().catch(() => false)) {
      await inlineButton.click();
      return;
    }
    // Button is overflowed — open the "..." menu and click the item there.
    const overflowTrigger = this.page.locator('.toolbar-overflow-trigger');
    await overflowTrigger.click();
    const menuItem = this.page.locator(`.toolbar-overflow-item:has(.toolbar-overflow-item-label:text("${overflowLabel}"))`);
    await menuItem.click();
  }

  /**
   * Click the auto-layout button via the View dropdown in the canvas toolbar.
   */
  async autoLayout() {
    // Open the View dropdown in the canvas toolbar
    const viewTrigger = this.page.locator('.canvas-toolbar-view-trigger');
    await viewTrigger.click();
    // Click "Auto Layout" in the dropdown
    await this.autoLayoutButton.waitFor({ state: 'visible', timeout: 2000 });
    await this.autoLayoutButton.click();
  }

  /**
   * Open the settings modal via the kebab menu.
   */
  async openSettings() {
    await this.kebabMenuTrigger.click();
    await this.settingsButton.waitFor({ state: 'visible', timeout: 2000 });
    await this.settingsButton.click();
    await this.page.waitForSelector('.metadata-modal', { state: 'visible' });
  }

  /**
   * Close any open modal.
   */
  async closeModal() {
    await this.page.keyboard.press('Escape');
  }

  /**
   * Get the node count on the canvas.
   */
  async getNodeCount(): Promise<number> {
    return await this.nodes.count();
  }

  /**
   * Get the edge count on the canvas.
   */
  async getEdgeCount(): Promise<number> {
    return await this.edges.count();
  }

  /**
   * Connect two nodes by creating an edge.
   */
  async connectNodes(sourceNodeId: string, targetNodeId: string) {
    const sourceNode = this.getNode(sourceNodeId);
    const targetNode = this.getNode(targetNodeId);

    // Find the source handle (output)
    const sourceHandle = sourceNode.locator('.react-flow__handle-right, .react-flow__handle-bottom');
    // Find the target handle (input)
    const targetHandle = targetNode.locator('.react-flow__handle-left, .react-flow__handle-top');

    await sourceHandle.dragTo(targetHandle);
  }

  /**
   * Drag a node to a new position.
   */
  async moveNode(nodeId: string, deltaX: number, deltaY: number) {
    const node = this.getNode(nodeId);
    const box = await node.boundingBox();
    if (!box) throw new Error(`Node ${nodeId} not found`);

    await this.page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
    await this.page.mouse.down();
    await this.page.mouse.move(box.x + box.width / 2 + deltaX, box.y + box.height / 2 + deltaY);
    await this.page.mouse.up();
  }

  /**
   * Wait for a node to appear on the canvas.
   */
  async waitForNode(nodeId: string) {
    await this.getNode(nodeId).waitFor({ state: 'visible' });
  }

  /**
   * Check if a node exists on the canvas.
   */
  async nodeExists(nodeId: string): Promise<boolean> {
    return await this.getNode(nodeId).isVisible();
  }

  /**
   * Get the property panel title (selected element type).
   */
  async getPropertyPanelTitle(): Promise<string | null> {
    const title = this.propertyPanel.locator('.component-type');
    return await title.textContent();
  }

  /**
   * Enter a value in a property field.
   */
  async setPropertyValue(fieldName: string, value: string) {
    const field = this.propertyPanel.locator(`[name="${fieldName}"]`);
    await field.fill(value);
  }

  /**
   * Zoom the canvas.
   */
  async zoom(delta: number) {
    await this.canvas.click();
    await this.page.mouse.wheel(0, delta);
  }

  /**
   * Fit the view to show all nodes using the Canvas Toolbar's View dropdown.
   */
  async fitView() {
    const viewButton = this.page.locator('.canvas-toolbar-view-trigger');
    await viewButton.click();
    const fitViewItem = this.page.locator('.canvas-toolbar-dropdown-item:has(.canvas-toolbar-dropdown-label:text("Fit View"))');
    await fitViewItem.click();
    // Wait for the viewport animation to complete (500ms duration in CanvasToolbar).
    await this.page.waitForTimeout(600);
  }

  /**
   * Click the "Load replay data" button in the property panel header.
   * This triggers a fetch to the replay_url endpoint.
   */
  async loadReplayData() {
    const replayBtn = this.propertyPanel.locator('button[aria-label="Load replay data"]');
    await replayBtn.click();
  }

  /**
   * Get the replay load button in the property panel header.
   */
  getReplayLoadButton(): Locator {
    return this.propertyPanel.locator('button[aria-label="Load replay data"]');
  }

  /**
   * Get the replay entry selector toggle button.
   */
  getReplayEntryToggle(): Locator {
    return this.replayPanel.locator('.replay-entry-toggle');
  }

  /**
   * Open the replay entry dropdown.
   */
  async openReplayEntryDropdown() {
    const toggle = this.getReplayEntryToggle();
    await toggle.click();
    await this.page.waitForSelector('.replay-entry-list', { state: 'visible', timeout: 5000 });
  }

  /**
   * Get replay entry items in the dropdown.
   */
  getReplayEntryItems(): Locator {
    return this.replayPanel.locator('.replay-entry-item');
  }

  /**
   * Select a replay entry by index from the dropdown.
   */
  async selectReplayEntry(index: number) {
    await this.openReplayEntryDropdown();
    const items = this.getReplayEntryItems();
    await items.nth(index).click();
    // Wait for dropdown to close
    await this.page.waitForTimeout(200);
  }

  /**
   * Get replay steps in the replay panel.
   */
  getReplaySteps(): Locator {
    return this.replayPanel.locator('.replay-step');
  }

  /**
   * Click the play/pause button in replay controls.
   */
  async startReplay() {
    const playBtn = this.replayPanel.locator('button[aria-label="Play"]');
    await playBtn.click();
  }

  /**
   * Pause replay.
   */
  async pauseReplay() {
    const pauseBtn = this.replayPanel.locator('button[aria-label="Pause"]');
    await pauseBtn.click();
  }

  /**
   * Click the stop button in replay controls.
   */
  async stopReplay() {
    const stopBtn = this.replayPanel.locator('button[aria-label="Stop & Reset"]');
    await stopBtn.click();
  }

  /**
   * Click the next step button in replay controls.
   */
  async nextReplayStep() {
    const nextBtn = this.replayPanel.locator('button[aria-label="Next Step"]');
    await nextBtn.click();
  }

  /**
   * Click the previous step button in replay controls.
   */
  async previousReplayStep() {
    const prevBtn = this.replayPanel.locator('button[aria-label="Previous Step"]');
    await prevBtn.click();
  }

  /**
   * Get the progress label text (e.g. "Step 1 of 2" or "Ready").
   */
  getProgressLabel(): Locator {
    return this.replayPanel.locator('.progress-label');
  }

  /**
   * Get the speed control select element.
   */
  getSpeedControl(): Locator {
    return this.replayPanel.locator('select[aria-label="Playback Speed"]');
  }

  /**
   * Check if a node is highlighted during replay.
   */
  async isNodeHighlighted(nodeId: string): Promise<boolean> {
    const node = this.getNode(nodeId);
    const classList = await node.getAttribute('class');
    return classList?.includes('replay-highlighted') ?? false;
  }

  // ============== Test Button Methods ==============

  /**
   * Get the Test button in the replay panel header.
   */
  getTestButton(): Locator {
    return this.replayPanel.locator('.header-test-btn');
  }

  /**
   * Click the Test button to initiate a test run.
   */
  async startTest() {
    const testBtn = this.getTestButton();
    await testBtn.click();
  }

  /**
   * Get the test waiting state container (shown during polling).
   */
  getTestWaitingState(): Locator {
    return this.replayPanel.locator('.replay-test-waiting');
  }

  /**
   * Get the cancel button shown during test polling.
   */
  getTestCancelButton(): Locator {
    return this.replayPanel.locator('.replay-test-waiting button');
  }

  /**
   * Cancel a running test by clicking the cancel button.
   */
  async cancelTest() {
    const cancelBtn = this.getTestCancelButton();
    await cancelBtn.click();
  }

  /**
   * Get the empty state message in the replay panel.
   */
  getReplayEmptyState(): Locator {
    return this.replayPanel.locator('.empty-state');
  }

  /**
   * Get the replay panel collapse/expand toggle button.
   */
  getReplayPanelToggle(): Locator {
    return this.replayPanel.locator('.collapse-toggle, .panel-collapse-widget');
  }

  // ============== Quick Add Methods ==============

  /**
   * Get the quick-add button for a node.
   */
  getQuickAddButton(nodeId: string): Locator {
    const node = this.getNode(nodeId);
    return node.locator('.quick-add-button');
  }

  /**
   * Get the quick-add condition button for an edge.
   * Note: The button is rendered via EdgeLabelRenderer portal, positioned at edge center.
   * The button has title="Add condition".
   */
  getQuickAddConditionButton(edgeId: string): Locator {
    // The quick-add-condition-button is rendered in EdgeLabelRenderer, which is a portal
    // It's positioned at the edge center. Use button title to find it.
    return this.page.locator('button[title="Add condition"]').first();
  }

  /**
   * Click the quick-add button on a node to open the popup.
   */
  async openQuickAddPopup(nodeId: string) {
    const quickAddBtn = this.getQuickAddButton(nodeId);
    await quickAddBtn.click();
    // Wait for popup to appear
    await this.page.waitForSelector('.quick-add-popup', { state: 'visible', timeout: 5000 });
  }

  /**
   * Click the quick-add condition button on an edge to open the popup.
   */
  async openQuickAddConditionPopup(edgeId: string) {
    // The quick-add-condition-button is rendered via EdgeLabelRenderer portal
    // Find it by title and click directly (it may have low opacity but should be clickable)
    const quickAddBtn = this.getQuickAddConditionButton(edgeId);
    
    // Hover to make button fully visible (it has opacity transition on hover)
    await quickAddBtn.hover({ force: true });
    await this.page.waitForTimeout(200);
    
    await quickAddBtn.click();
    // Wait for popup to appear
    await this.page.waitForSelector('.quick-add-popup', { state: 'visible', timeout: 5000 });
  }

  /**
   * Get the quick-add popup.
   */
  getQuickAddPopup(): Locator {
    return this.page.locator('.quick-add-popup');
  }

  /**
   * Get a component in the quick-add popup by label.
   */
  getQuickAddComponent(label: string): Locator {
    return this.getQuickAddPopup().locator(`.quick-add-component-item:has-text("${label}")`);
  }

  /**
   * Search for components in the quick-add popup.
   */
  async searchQuickAddComponents(query: string) {
    const searchInput = this.getQuickAddPopup().locator('input[type="text"]');
    await searchInput.fill(query);
    await this.page.waitForTimeout(250); // Wait for debounce
  }

  /**
   * Select a component from the quick-add popup by clicking it.
   */
  async selectQuickAddComponent(label: string) {
    const component = this.getQuickAddComponent(label);
    await component.click();
    // Wait for popup to close
    await this.page.waitForSelector('.quick-add-popup', { state: 'hidden', timeout: 5000 });
  }

  /**
   * Close the quick-add popup by clicking outside.
   */
  async closeQuickAddPopup() {
    await this.canvas.click({ position: { x: 10, y: 10 } });
    await this.page.waitForSelector('.quick-add-popup', { state: 'hidden', timeout: 5000 });
  }

  /**
   * Get the section headers in the quick-add popup.
   */
  getQuickAddSectionHeaders(): Locator {
    return this.getQuickAddPopup().locator('.quick-add-section-header');
  }

  /**
   * Close the quick-add popup using the close button.
   */
  async closeQuickAddPopupWithButton() {
    const closeBtn = this.getQuickAddPopup().locator('.quick-add-popup-close');
    await closeBtn.click();
    await this.page.waitForSelector('.quick-add-popup', { state: 'hidden', timeout: 5000 });
  }

  // ============== Metadata Modal Methods ==============

  /**
   * Get the metadata modal element.
   */
  getMetadataModal(): Locator {
    return this.page.locator('.metadata-modal');
  }

  /**
   * Get a form field inside the metadata modal by its id attribute.
   */
  getMetadataField(fieldId: string): Locator {
    return this.getMetadataModal().locator(`#${fieldId}`);
  }

  /**
   * Get the template checkbox in the metadata modal.
   */
  getTemplateCheckbox(): Locator {
    return this.getMetadataModal().locator('input[type="checkbox"]').nth(1);
  }

  /**
   * Get the metadata modal Save button.
   */
  getMetadataSaveButton(): Locator {
    return this.getMetadataModal().locator('button[type="submit"]');
  }

  // ============== Context Switcher Methods ==============

  /**
   * Get the context select dropdown in the toolbar.
   */
  getContextSelect(): Locator {
    return this.page.locator('#toolbar-context-select');
  }

  // ============== Quick Add Event Methods ==============

  /**
   * Get the "New event" toolbar button for adding event/start nodes.
   */
  getQuickAddEventButton(): Locator {
    return this.page.locator('.quick-add-event-button');
  }

  /**
   * Click the "New event" toolbar button to open the event popup.
   */
  async openQuickAddEventPopup() {
    const quickAddBtn = this.getQuickAddEventButton();
    await quickAddBtn.click();
    // Wait for popup to appear
    await this.page.waitForSelector('.quick-add-popup', { state: 'visible', timeout: 5000 });
  }

  /**
   * Select an event from the quick-add event popup by clicking it.
   */
  async selectQuickAddEvent(label: string) {
    const component = this.getQuickAddComponent(label);
    await component.click();
    // Wait for popup to close
    await this.page.waitForSelector('.quick-add-popup', { state: 'hidden', timeout: 5000 });
  }

  // ---------------------------------------------------------------------------
  // Export
  // ---------------------------------------------------------------------------

  /**
   * Get the export menu item from the kebab menu.
   * Note: the kebab menu must be opened first for the item to be visible.
   */
  getExportButton(): Locator {
    return this.exportButton;
  }

  /**
   * Open the kebab menu so menu items become visible.
   */
  async openKebabMenu() {
    await this.kebabMenuTrigger.click();
    // Wait for the dropdown to appear
    await this.page.locator('.toolbar-menu-dropdown').waitFor({ state: 'visible', timeout: 2000 });
  }

  /**
   * Open the export dialog via the kebab menu.
   */
  async openExportDialog() {
    await this.kebabMenuTrigger.click();
    await this.exportButton.waitFor({ state: 'visible', timeout: 2000 });
    await this.exportButton.click();
    await this.page.waitForSelector('.export-dialog', { state: 'visible', timeout: 5000 });
  }

  /**
   * Get the export dialog element.
   */
  getExportDialog(): Locator {
    return this.page.locator('.export-dialog');
  }

  /**
   * Select an export format in the export dialog.
   */
  async selectExportFormat(format: 'recipe' | 'archive' | 'json' | 'svg') {
    const labels: Record<string, string> = {
      recipe: 'Recipe',
      archive: 'Archive',
      json: 'JSON',
      svg: 'SVG',
    };
    const option = this.page.locator(`.export-format-option:has(.export-format-label:text("${labels[format]}"))`);
    await option.click();
  }

  /**
   * Click the Export button inside the export dialog.
   */
  async confirmExport() {
    const exportBtn = this.page.locator('.export-dialog-footer .btn-primary');
    await exportBtn.click();
  }

  /**
   * Close the export dialog via the Cancel button.
   */
  async cancelExport() {
    const cancelBtn = this.page.locator('.export-dialog-footer .btn-secondary');
    await cancelBtn.click();
    await this.page.waitForSelector('.export-dialog', { state: 'hidden', timeout: 5000 });
  }
}
