import { test, expect } from '@playwright/test';
import { ModelerPage } from './pages/ModelerPage';
import { setupMocks, mockReplayEntries, mockTestReplayData } from './fixtures/mocks';

test.describe('Workflow Modeler - Replay System', () => {
  let modeler: ModelerPage;

  test.beforeEach(async ({ page }) => {
    await setupMocks(page);
    modeler = new ModelerPage(page);
    await modeler.goto();
  });

  test.describe('Loading Replay Data', () => {
    test('should show replay load button when event node is selected', async () => {
      // Select the event node
      await modeler.selectNode('event_1');
      await modeler.page.waitForTimeout(300);

      // The replay load button should be visible in the property panel header
      const replayBtn = modeler.getReplayLoadButton();
      await expect(replayBtn).toBeVisible();
    });

    test('should not show replay load button for action nodes', async () => {
      // Select the action node (not an event)
      await modeler.selectNode('action_1');
      await modeler.page.waitForTimeout(300);

      // The replay load button should NOT be visible
      const replayBtn = modeler.getReplayLoadButton();
      await expect(replayBtn).not.toBeVisible();
    });

    test('should load replay data and show replay panel when button is clicked', async ({ page }) => {
      // Select event node
      await modeler.selectNode('event_1');
      await page.waitForTimeout(300);

      // Click the load replay button
      await modeler.loadReplayData();

      // Wait for the replay panel to appear
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });
    });

    test('should show replay entries in the replay panel after loading', async ({ page }) => {
      // Select event node and load replay data
      await modeler.selectNode('event_1');
      await page.waitForTimeout(300);
      await modeler.loadReplayData();

      // Wait for replay panel to appear
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });

      // The entry selector toggle should be visible
      const toggle = modeler.getReplayEntryToggle();
      await expect(toggle).toBeVisible();
    });

    test('should auto-select the first entry after loading', async ({ page }) => {
      // Select event node and load replay data
      await modeler.selectNode('event_1');
      await page.waitForTimeout(300);
      await modeler.loadReplayData();
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });

      // The toggle label should show the first entry's timestamp (not "Select an execution...")
      const toggleLabel = modeler.replayPanel.locator('.replay-entry-toggle-label');
      await expect(toggleLabel).not.toHaveText('Select an execution...');
    });
  });

  test.describe('Replay Entry Selector', () => {
    test.beforeEach(async ({ page }) => {
      // Load replay data for each test in this group
      await modeler.selectNode('event_1');
      await page.waitForTimeout(300);
      await modeler.loadReplayData();
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });
    });

    test('should open entry dropdown when toggle is clicked', async () => {
      await modeler.openReplayEntryDropdown();

      // The dropdown list should be visible
      const dropdownList = modeler.replayPanel.locator('.replay-entry-list');
      await expect(dropdownList).toBeVisible();
    });

    test('should show correct number of entries in dropdown', async () => {
      await modeler.openReplayEntryDropdown();

      const entries = modeler.getReplayEntryItems();
      await expect(entries).toHaveCount(mockReplayEntries.length);
    });

    test('should display entry details (timestamp, user, IP, URL) in dropdown items', async () => {
      await modeler.openReplayEntryDropdown();

      // Check first entry has expected content
      const firstEntry = modeler.getReplayEntryItems().first();
      // It should contain the IP address
      await expect(firstEntry.locator('.entry-value').nth(2)).toHaveText('192.168.1.100');
      // It should contain the URL
      await expect(firstEntry.locator('.entry-value.entry-url')).toHaveText('/node/42/edit');
    });

    test('should mark the selected entry in the dropdown', async () => {
      await modeler.openReplayEntryDropdown();

      // First entry should be selected (auto-selected on load)
      const firstEntry = modeler.getReplayEntryItems().first();
      await expect(firstEntry).toHaveClass(/selected/);
    });

    test('should switch replay data when a different entry is selected', async ({ page }) => {
      // First entry has 3 steps, second entry has 1 step
      // Verify we start with 3 steps from the first entry
      const steps = modeler.getReplaySteps();
      await expect(steps).toHaveCount(3);

      // Select the second entry (which has only 1 step)
      await modeler.selectReplayEntry(1);
      await page.waitForTimeout(300);

      // Now should show 1 step
      await expect(steps).toHaveCount(1);
    });

    test('should close dropdown when clicking outside', async ({ page }) => {
      await modeler.openReplayEntryDropdown();
      const dropdownList = modeler.replayPanel.locator('.replay-entry-list');
      await expect(dropdownList).toBeVisible();

      // Click on the panel header (outside the dropdown) to trigger outside-click handler
      const panelHeader = modeler.replayPanel.locator('.replay-panel-header');
      await panelHeader.dispatchEvent('mousedown');
      await page.waitForTimeout(300);

      await expect(dropdownList).not.toBeVisible();
    });

    test('should update toggle label when entry changes', async ({ page }) => {
      const toggleLabel = modeler.replayPanel.locator('.replay-entry-toggle-label');

      // Get the initial label text (first entry)
      const firstLabelText = await toggleLabel.textContent();

      // Select the second entry
      await modeler.selectReplayEntry(1);
      await page.waitForTimeout(300);

      // Label should change to reflect the second entry
      const secondLabelText = await toggleLabel.textContent();
      expect(secondLabelText).not.toBe(firstLabelText);
    });
  });

  test.describe('Replay Panel UI', () => {
    test.beforeEach(async ({ page }) => {
      await modeler.selectNode('event_1');
      await page.waitForTimeout(300);
      await modeler.loadReplayData();
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });
    });

    test('should display the Execution Replay header', async () => {
      const header = modeler.replayPanel.locator('.replay-panel-header h3');
      await expect(header).toContainText('Execution Replay');
    });

    test('should show step count in header', async () => {
      const stepCount = modeler.replayPanel.locator('.replay-count');
      await expect(stepCount).toBeVisible();
      // First entry has 3 steps — rendered as "(3 steps)" by Drupal.t()
      await expect(stepCount).toContainText('3 steps');
    });

    test('should display replay steps', async () => {
      const steps = modeler.getReplaySteps();
      // First entry has 3 steps
      await expect(steps).toHaveCount(3);
    });

    test('should display playback controls', async () => {
      const playBtn = modeler.replayPanel.locator('button[aria-label="Play"]');
      const stopBtn = modeler.replayPanel.locator('button[aria-label="Stop & Reset"]');
      const nextBtn = modeler.replayPanel.locator('button[aria-label="Next Step"]');
      const prevBtn = modeler.replayPanel.locator('button[aria-label="Previous Step"]');

      await expect(playBtn).toBeVisible();
      await expect(stopBtn).toBeVisible();
      await expect(nextBtn).toBeVisible();
      await expect(prevBtn).toBeVisible();
    });

    test('should display speed control', async () => {
      const speedControl = modeler.getSpeedControl();
      await expect(speedControl).toBeVisible();
    });

    test('should display progress bar', async () => {
      const progressBar = modeler.replayPanel.locator('.progress-bar');
      await expect(progressBar).toBeVisible();
    });

    test('should show "Ready" in progress label initially', async () => {
      const label = modeler.getProgressLabel();
      await expect(label).toHaveText('Ready');
    });
  });

  test.describe('Replay Step Navigation', () => {
    test.beforeEach(async ({ page }) => {
      await modeler.selectNode('event_1');
      await page.waitForTimeout(300);
      await modeler.loadReplayData();
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });
    });

    test('should navigate to next step', async ({ page }) => {
      await modeler.nextReplayStep();
      await page.waitForTimeout(200);

      const label = modeler.getProgressLabel();
      await expect(label).toHaveText('Step 1 of 3');
    });

    test('should navigate through all steps with next button', async ({ page }) => {
      // Step to first
      await modeler.nextReplayStep();
      await page.waitForTimeout(200);
      await expect(modeler.getProgressLabel()).toHaveText('Step 1 of 3');

      // Step to second
      await modeler.nextReplayStep();
      await page.waitForTimeout(200);
      await expect(modeler.getProgressLabel()).toHaveText('Step 2 of 3');
    });

    test('should navigate back with previous button', async ({ page }) => {
      // Go forward two steps
      await modeler.nextReplayStep();
      await page.waitForTimeout(200);
      await modeler.nextReplayStep();
      await page.waitForTimeout(200);

      // Now go back
      await modeler.previousReplayStep();
      await page.waitForTimeout(200);
      await expect(modeler.getProgressLabel()).toHaveText('Step 1 of 3');
    });

    test('should highlight current step in step list', async ({ page }) => {
      await modeler.nextReplayStep();
      await page.waitForTimeout(200);

      const firstStep = modeler.getReplaySteps().first();
      await expect(firstStep).toHaveClass(/current/);
    });

    test('should mark earlier steps as completed', async ({ page }) => {
      // Navigate to second step
      await modeler.nextReplayStep();
      await page.waitForTimeout(200);
      await modeler.nextReplayStep();
      await page.waitForTimeout(200);

      // First step should be marked as completed
      const firstStep = modeler.getReplaySteps().first();
      await expect(firstStep).toHaveClass(/completed/);

      // Second step should be current
      const secondStep = modeler.getReplaySteps().nth(1);
      await expect(secondStep).toHaveClass(/current/);
    });

    test('should jump to step when step is clicked', async ({ page }) => {
      // Wait for any prior sync to settle before clicking
      await page.waitForTimeout(500);

      // Click on the third step directly (action step)
      const thirdStep = modeler.getReplaySteps().nth(2);
      await thirdStep.click();

      await expect(modeler.getProgressLabel()).toHaveText('Step 3 of 3', { timeout: 5000 });
      await expect(thirdStep).toHaveClass(/current/);
    });

    test('should reset when stop button is clicked', async ({ page }) => {
      // Navigate forward
      await modeler.nextReplayStep();
      await page.waitForTimeout(200);

      // Stop/Reset
      await modeler.stopReplay();
      await page.waitForTimeout(200);

      await expect(modeler.getProgressLabel()).toHaveText('Ready');
    });
  });

  test.describe('Replay Playback', () => {
    test.beforeEach(async ({ page }) => {
      await modeler.selectNode('event_1');
      await page.waitForTimeout(300);
      await modeler.loadReplayData();
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });
    });

    test('should start playback when play is clicked', async ({ page }) => {
      await modeler.startReplay();
      await page.waitForTimeout(200);

      // Play button should become a Pause button
      const pauseBtn = modeler.replayPanel.locator('button[aria-label="Pause"]');
      await expect(pauseBtn).toBeVisible();
    });

    test('should pause playback when pause is clicked', async ({ page }) => {
      await modeler.startReplay();
      await page.waitForTimeout(200);

      await modeler.pauseReplay();
      await page.waitForTimeout(200);

      // Should show Play button again
      const playBtn = modeler.replayPanel.locator('button[aria-label="Play"]');
      await expect(playBtn).toBeVisible();
    });

    test('should change playback speed', async ({ page }) => {
      const speedControl = modeler.getSpeedControl();

      // Default should be 1x
      await expect(speedControl).toHaveValue('1');

      // Change to 2x
      await speedControl.selectOption('2');
      await expect(speedControl).toHaveValue('2');
    });

    test('play button should have playing class while playing', async ({ page }) => {
      await modeler.startReplay();
      await page.waitForTimeout(200);

      const playBtn = modeler.replayPanel.locator('.play-btn');
      await expect(playBtn).toHaveClass(/playing/);
    });
  });

  test.describe('Step Data Display', () => {
    test.beforeEach(async ({ page }) => {
      await modeler.selectNode('event_1');
      await page.waitForTimeout(300);
      await modeler.loadReplayData();
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });
    });

    test('should show "Select a step" message when no step is selected', async () => {
      const noData = modeler.replayPanel.locator('.no-data');
      await expect(noData).toContainText('Select a step');
    });

    test('should show step data section header', async () => {
      const dataHeader = modeler.replayPanel.locator('.data-header');
      await expect(dataHeader).toContainText('Step Data');
    });

    test('should show step data when a step with data is selected', async ({ page }) => {
      // Navigate to second step (action_1 which has entity data)
      await modeler.nextReplayStep();
      await page.waitForTimeout(200);
      await modeler.nextReplayStep();
      await page.waitForTimeout(200);

      // The data content section should show the step data
      const dataContent = modeler.replayPanel.locator('.data-content');
      await expect(dataContent).toBeVisible();
    });
  });

  test.describe('Replay Panel Info Popup', () => {
    test.beforeEach(async ({ page }) => {
      await modeler.selectNode('event_1');
      await page.waitForTimeout(300);
      await modeler.loadReplayData();
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });
    });

    test('should show info button when a step is selected', async ({ page }) => {
      // Navigate to a step
      await modeler.nextReplayStep();
      await page.waitForTimeout(200);

      const infoBtn = modeler.replayPanel.locator('button[aria-label="Show metadata"]');
      await expect(infoBtn).toBeVisible();
    });

    test('should open info popup when info button is clicked', async ({ page }) => {
      // Navigate to a step
      await modeler.nextReplayStep();
      await page.waitForTimeout(200);

      // Click the info button
      const infoBtn = modeler.replayPanel.locator('button[aria-label="Show metadata"]');
      await infoBtn.click();

      // Info popup should appear
      const infoPopup = modeler.replayPanel.locator('.info-popup');
      await expect(infoPopup).toBeVisible();
    });
  });

  test.describe('Replay Condition Step Selection', () => {
    test.beforeEach(async ({ page }) => {
      await modeler.selectNode('event_1');
      await page.waitForTimeout(300);
      await modeler.loadReplayData();
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });
    });

    test('should select edge in canvas when condition step is clicked', async ({ page }) => {
      // The second step (index 1) is an "add successor" with conditionId
      const conditionStep = modeler.getReplaySteps().nth(1);
      await conditionStep.click();
      await page.waitForTimeout(500);

      // The edge should have replay highlight styling (animated green/red path)
      const replayPath = page.locator('path.replay-highlighted');
      await expect(replayPath).toBeVisible({ timeout: 5000 });
    });

    test('should show condition in property panel when condition step is clicked', async ({ page }) => {
      // Click the condition step
      const conditionStep = modeler.getReplaySteps().nth(1);
      await conditionStep.click();
      await page.waitForTimeout(500);

      // The property panel should show the condition label field
      const conditionLabel = modeler.propertyPanel.locator('#modeler-condition-label');
      await expect(conditionLabel).toBeVisible({ timeout: 5000 });
    });

    test('should select node when action step is clicked after condition step', async ({ page }) => {
      // First click the condition step
      const conditionStep = modeler.getReplaySteps().nth(1);
      await conditionStep.click();
      await page.waitForTimeout(500);

      // Then click the action step (index 2)
      const actionStep = modeler.getReplaySteps().nth(2);
      await actionStep.click();
      await page.waitForTimeout(500);

      // The action node should be selected (not the edge)
      const actionNode = modeler.getNode('action_1');
      await expect(actionNode).toHaveClass(/selected/);

      // The edge should no longer have replay highlight
      const replayPath = page.locator('.react-flow__edge[data-id="edge_1"] path.replay-highlighted');
      await expect(replayPath).not.toBeVisible();
    });
  });
});

test.describe('Workflow Modeler - Test Feature', () => {
  let modeler: ModelerPage;

  test.describe('Test Button Visibility', () => {
    test('should show Test button when test_url is configured and event is auto-detected', async ({ page }) => {
      await setupMocks(page, { withTestUrl: true });
      modeler = new ModelerPage(page);
      await modeler.goto();

      // The model has exactly one event node (event_1) so it should be auto-detected.
      // The replay panel should appear (because test_url is configured = hasAnyReplayCapability).
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });

      // The Test button should be visible
      const testBtn = modeler.getTestButton();
      await expect(testBtn).toBeVisible();
    });

    test('should not show Test button when test_url is not configured', async ({ page }) => {
      await setupMocks(page); // no withTestUrl
      modeler = new ModelerPage(page);
      await modeler.goto();

      // The replay panel may or may not be visible (depends on replay_url).
      // But the Test button should not be visible.
      const testBtn = modeler.getTestButton();
      await expect(testBtn).not.toBeVisible();
    });
  });

  test.describe('Test Execution', () => {
    test.beforeEach(async ({ page }) => {
      await setupMocks(page, { withTestUrl: true, testPollWaitCount: 1 });
      modeler = new ModelerPage(page);
      await modeler.goto();
      // Wait for replay panel (visible because test_url is configured)
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });
    });

    test('should show waiting state after clicking Test', async ({ page }) => {
      const testBtn = modeler.getTestButton();
      await testBtn.click();

      // Should show the waiting/polling state
      const waitingState = modeler.getTestWaitingState();
      await expect(waitingState).toBeVisible({ timeout: 5000 });
    });

    test('should show cancel button during test polling', async ({ page }) => {
      const testBtn = modeler.getTestButton();
      await testBtn.click();

      const cancelBtn = modeler.getTestCancelButton();
      await expect(cancelBtn).toBeVisible({ timeout: 5000 });
    });

    test('should load replay data after test completes', async ({ page }) => {
      const testBtn = modeler.getTestButton();
      await testBtn.click();

      // Wait for test to complete (1 poll wait then data returns)
      // The replay steps should appear with the test replay data
      const steps = modeler.getReplaySteps();
      await expect(steps).toHaveCount(mockTestReplayData.length, { timeout: 10000 });
    });

    test('should cancel test when cancel button is clicked', async ({ page }) => {
      // Use a high wait count so the test stays in polling
      await setupMocks(page, { withTestUrl: true, testPollWaitCount: 100 });
      modeler = new ModelerPage(page);
      await modeler.goto();
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });

      const testBtn = modeler.getTestButton();
      await testBtn.click();

      // Wait for waiting state to appear
      const waitingState = modeler.getTestWaitingState();
      await expect(waitingState).toBeVisible({ timeout: 5000 });

      // Cancel the test
      await modeler.cancelTest();

      // Waiting state should disappear
      await expect(waitingState).not.toBeVisible({ timeout: 5000 });
    });
  });

  test.describe('Test Error Handling', () => {
    test('should show error when test initiation fails', async ({ page }) => {
      await setupMocks(page, { withTestUrl: true, testInitError: 'Event not supported' });
      modeler = new ModelerPage(page);
      await modeler.goto();
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });

      const testBtn = modeler.getTestButton();
      await testBtn.click();

      // Wait a moment for the error to be processed
      await page.waitForTimeout(1000);

      // The test should not enter the waiting/polling state
      const waitingState = modeler.getTestWaitingState();
      await expect(waitingState).not.toBeVisible();
    });

    test('should show error when polling fails', async ({ page }) => {
      await setupMocks(page, { withTestUrl: true, testPollError: 'Execution timed out' });
      modeler = new ModelerPage(page);
      await modeler.goto();
      await expect(modeler.replayPanel).toBeVisible({ timeout: 5000 });

      const testBtn = modeler.getTestButton();
      await testBtn.click();

      // Wait for the poll to happen and return error
      await page.waitForTimeout(3000);

      // The waiting state should disappear once the error is received
      const waitingState = modeler.getTestWaitingState();
      await expect(waitingState).not.toBeVisible({ timeout: 5000 });
    });
  });
});
