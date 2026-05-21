import { Page, Route } from '@playwright/test';

/**
 * Mock data for workflow modeler API responses.
 * These simulate Drupal backend responses for isolated E2E testing.
 */

/**
 * Mock components in the format sent by the backend (drupalSettings.modeler.components).
 * Components carry `componentType` (integer) — the frontend resolves the string
 * `type` at load time via the typeMap.
 *
 * Each type needs >= 15 items so the search field is visible in
 * quick-add popups (see THRESHOLDS.SEARCH_VISIBILITY_MIN_COMPONENTS).
 */

// Generate filler components for a given componentType
function fillers(prefix: string, componentType: number, count: number) {
  const typeName = { 1: 'start', 4: 'element', 5: 'link', 6: 'gateway' }[componentType] ?? 'element';
  return Array.from({ length: count }, (_, i) => ({
    plugin: `${prefix}:filler_${i}`,
    label: `${typeName} Filler ${i}`,
    componentType,
    provider: 'eca_test',
    description: `Filler ${typeName} component ${i}`,
  }));
}

export const mockComponents = [
  // Events (3 real + 12 fillers = 15)
  { plugin: 'content_entity:insert', label: 'Content Entity Insert', componentType: 1, provider: 'eca_content', description: 'Triggered when a content entity is inserted' },
  { plugin: 'cron', label: 'Cron Run', componentType: 1, provider: 'eca_base', description: 'Triggered on cron execution' },
  { plugin: 'user:login', label: 'User Login', componentType: 1, provider: 'eca_user', description: 'Triggered when a user logs in' },
  ...fillers('event', 1, 12),
  // Actions (4 real + 11 fillers = 15)
  { plugin: 'entity:save', label: 'Save Entity', componentType: 4, provider: 'eca_content', description: 'Saves the current entity' },
  { plugin: 'message:set', label: 'Set Message', componentType: 4, provider: 'eca_base', description: 'Displays a message to the user' },
  { plugin: 'email:send', label: 'Send Email', componentType: 4, provider: 'eca_base', description: 'Sends an email' },
  { plugin: 'entity:delete', label: 'Delete Entity', componentType: 4, provider: 'eca_content', description: 'Deletes an entity' },
  ...fillers('action', 4, 11),
  // Conditions (2 real + 13 fillers = 15)
  { plugin: 'entity:is_new', label: 'Entity is New', componentType: 5, provider: 'eca_content', description: 'Checks if entity is new' },
  { plugin: 'user:has_role', label: 'User Has Role', componentType: 5, provider: 'eca_user', description: 'Checks if user has a specific role' },
  ...fillers('condition', 5, 13),
  // Gateways
  { plugin: 'gateway', label: 'Gateway', componentType: 6, provider: 'modeler', description: 'Gateway for conditional branching' }
];

export const mockModel = {
  id: 'test-model-1',
  label: 'Test Workflow',
  version: '1.0.0',
  status: true,
  documentation: 'A test workflow for E2E testing',
  nodes: [
    {
      id: 'event_1',
      type: 'start',
      position: { x: 100, y: 200 },
      data: {
        label: 'On Entity Insert',
        pluginId: 'content_entity:insert',
        configuration: {}
      }
    },
    {
      id: 'action_1',
      type: 'element',
      position: { x: 400, y: 200 },
      data: {
        label: 'Save Entity',
        pluginId: 'entity:save',
        configuration: {}
      }
    },
    {
      id: 'action_2',
      type: 'element',
      position: { x: 700, y: 200 },
      data: {
        label: 'Send Email',
        pluginId: 'email:send',
        configuration: {}
      }
    }
  ],
  edges: [
    {
      id: 'edge_1',
      source: 'event_1',
      target: 'action_1',
      condition: 'entity:is_new',
      conditionId: 'eca_entity_is_new_10j5tps',
      conditionLabel: 'Entity is New',
      conditionConfiguration: { negate: false, entity: '' },
    },
    {
      id: 'edge_2',
      source: 'action_1',
      target: 'action_2',
    }
  ]
};

export const mockEmptyModel = {
  id: 'new-model',
  label: 'New Workflow',
  version: '1.0.0',
  status: false,
  documentation: '',
  nodes: [],
  edges: []
};

export const mockTokens = [
  { token: '[node:title]', label: 'Node Title', description: 'The title of the node' },
  { token: '[node:nid]', label: 'Node ID', description: 'The node ID' },
  { token: '[current-user:name]', label: 'Current User Name', description: 'Name of current user' },
  { token: '[current-user:mail]', label: 'Current User Email', description: 'Email of current user' },
  { token: '[site:name]', label: 'Site Name', description: 'The name of the site' }
];

/**
 * Mock replay entries in the ReplayEntry[] format expected by useReplayLoader.
 * Each entry represents a single workflow execution with its history of steps.
 */
export const mockReplayEntries = [
  {
    model_id: 'test-model-1',
    component_id: 'event_1',
    history: [
      {
        id: 'event_1',
        type: 'started',
        data: { label: 'On Entity Insert' },
      },
      {
        id: 'event_1',
        type: 'add successor',
        successorId: 'action_1',
        conditionId: 'eca_entity_is_new_10j5tps',
        data: {},
      },
      {
        id: 'action_1',
        type: 'execute',
        data: { label: 'Save Entity', entity: { title: 'Test Article', type: 'node' } },
      },
    ],
    timestamp: '2026-02-09T10:30:00Z',
    user: { name: 'admin', uid: 1 },
    ip: '192.168.1.100',
    url: '/node/42/edit',
  },
  {
    model_id: 'test-model-1',
    component_id: 'event_1',
    history: [
      {
        id: 'event_1',
        type: 'event',
        data: { label: 'On Entity Insert' },
      },
    ],
    timestamp: '2026-02-08T14:15:30Z',
    user: { name: 'editor', uid: 5 },
    ip: '10.0.0.42',
    url: '/admin/content',
  },
  {
    model_id: 'test-model-1',
    component_id: 'event_1',
    history: [
      {
        id: 'event_1',
        type: 'event',
        data: { label: 'On Entity Insert' },
      },
      {
        id: 'action_1',
        type: 'action',
        successorId: 'action_1',
        data: { label: 'Save Entity' },
      },
    ],
    timestamp: '2026-02-07T09:00:00Z',
    user: 'anonymous',
    ip: '203.0.113.50',
    url: '/contact',
  },
];

/**
 * Mock test endpoint responses.
 * The test flow is: POST with {modelId, componentId} → get {jobId}
 * then poll with {jobId} → get {status: "waiting"} or replay data array.
 */
export const mockTestJobId = 'test-job-abc-123';

export const mockTestReplayData = [
  {
    id: 'event_1',
    type: 'event',
    data: { label: 'On Entity Insert' },
  },
  {
    id: 'action_1',
    type: 'action',
    successorId: 'action_1',
    data: { label: 'Save Entity', entity: { title: 'Test Result', type: 'node' } },
  },
];

/**
 * Mock contexts for testing the context switcher dropdown.
 */
export const mockContexts = [
  {
    id: 'ctx_content',
    topic: 'Content Management',
    model_owner: 'node',
    components: {
      start: { plugins: ['content_entity:insert'] },
      element: { plugins: ['entity:save', 'message:set'] },
    },
  },
  {
    id: 'ctx_user',
    topic: 'User Management',
    model_owner: 'user',
    components: {
      start: { plugins: ['user:login'] },
      element: { plugins: ['email:send'] },
    },
  },
];

/**
 * Options for setupMocks function.
 */
export interface SetupMocksOptions {
  /** Whether the model is new (triggers metadata modal) */
  isNew?: boolean;
  /** Whether to include test_url in modeler_api settings (default: false) */
  withTestUrl?: boolean;
  /** Number of poll requests to return "waiting" before returning data (default: 1) */
  testPollWaitCount?: number;
  /** If set, the test endpoint returns this error on initial request */
  testInitError?: string;
  /** If set, the test endpoint returns this warning on initial request */
  testInitWarning?: string;
  /** If set, the test poll endpoint returns this error */
  testPollError?: string;
  /**
   * Override individual permission values in `drupalSettings.modeler_api.permissions`.
   * Missing keys keep their default `true` from the test server HTML.
   */
  permissions?: Partial<Record<
    'create template' | 'edit metadata' | 'edit template' | 'replay' | 'switch context' | 'test',
    boolean
  >>;
  /** Whether to inject mock contexts into `drupalSettings.modeler_api.contexts` */
  withContexts?: boolean;
  /** Whether to set the model metadata as template (`metadata.template: true`) */
  withTemplate?: boolean;
  /** Whether to set the model as read-only (`modeler_api.readOnly: true`) */
  readOnly?: boolean;
}

/**
 * Sets up mock API routes for the modeler.
 * Call this in your test's beforeEach to enable mocked responses.
 */
export async function setupMocks(page: Page, options: SetupMocksOptions = {}) {
  const {
    isNew = false,
    withTestUrl = false,
    testPollWaitCount = 1,
    testInitError,
    testInitWarning,
    testPollError,
    permissions,
    withContexts = false,
    withTemplate = false,
    readOnly = false,
  } = options;

  // Determine whether we need to intercept and modify the HTML document.
  const needsHtmlPatch = withTestUrl || permissions || withContexts || withTemplate || readOnly;

  if (needsHtmlPatch) {
    await page.route('**/modeler/**', async (route: Route) => {
      // Only intercept HTML page requests (not API/static requests)
      const request = route.request();
      if (request.resourceType() !== 'document') {
        await route.continue();
        return;
      }
      const response = await route.fetch();
      let html = await response.text();

      // Inject test_url into the modeler_api settings object
      if (withTestUrl) {
        html = html.replace(
          "replay_url: '/modeler-api/replay'",
          "replay_url: '/modeler-api/replay',\n        test_url: '/modeler-api/test'"
        );
      }

      // Override individual permissions
      if (permissions) {
        for (const [key, value] of Object.entries(permissions)) {
          // Replace e.g. "'edit metadata': true" with "'edit metadata': false"
          html = html.replace(
            new RegExp(`'${key}':\\s*true`),
            `'${key}': ${String(value)}`,
          );
          html = html.replace(
            new RegExp(`'${key}':\\s*false`),
            `'${key}': ${String(value)}`,
          );
        }
      }

      // Inject contexts array into modeler_api
      if (withContexts) {
        html = html.replace(
          "permissions: {",
          `contexts: ${JSON.stringify(mockContexts)},\n        permissions: {`,
        );
      }

      // Set metadata.template to true in the inline modelData JSON AND
      // inject it into modeler_api.metadata so Flow.tsx can read it.
      if (withTemplate) {
        html = html.replace(
          /"metadata":\s*\{/,
          '"metadata": {\n      "template": true,',
        );
        html = html.replace(
          'isNew:',
          'metadata: { template: true },\n        isNew:',
        );
      }

      // Inject readOnly: true into the modeler_api settings block
      if (readOnly) {
        html = html.replace(
          'isNew:',
          'readOnly: true,\n        isNew:',
        );
      }

      await route.fulfill({
        status: response.status(),
        headers: response.headers(),
        body: html,
      });
    });
  }

  // Mock component library endpoint
  await page.route('**/modeler-api/components**', async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(mockComponents)
    });
  });

  // Mock model load endpoint
  await page.route('**/modeler-api/model/**', async (route: Route) => {
    const url = route.request().url();
    if (url.includes('new-model') || isNew) {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          ...mockEmptyModel,
          settings: { modeler_api: { isNew: true } }
        })
      });
    } else {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          ...mockModel,
          settings: { modeler_api: { isNew: false } }
        })
      });
    }
  });

  // Mock model save endpoint
  await page.route('**/modeler-api/model', async (route: Route) => {
    if (route.request().method() === 'POST' || route.request().method() === 'PUT') {
      const body = route.request().postDataJSON();
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ ...body, saved: true })
      });
    } else {
      await route.continue();
    }
  });

  // Mock token endpoint — serves both CSRF token (plain text GET) and token browser (JSON)
  await page.route('**/modeler-api/tokens**', async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'text/plain',
      body: 'mock-csrf-token'
    });
  });

  // Mock configuration form endpoint
  await page.route('**/modeler-api/config/**', async (route: Route) => {
    const url = route.request().url();
    const pluginId = url.split('/').pop()?.split('?')[0] || 'unknown';

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        pluginId,
        form: '<div class="form-wrapper"><input type="text" name="field1" /></div>',
        values: {}
      })
    });
  });

  // Mock replay data endpoint — returns ReplayEntry[]
  await page.route('**/modeler-api/replay**', async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(mockReplayEntries)
    });
  });

  // Mock test endpoint — handles both initial request and polling
  if (withTestUrl) {
    let pollCount = 0;

    await page.route('**/modeler-api/test**', async (route: Route) => {
      if (route.request().method() !== 'POST') {
        await route.continue();
        return;
      }

      const body = route.request().postDataJSON();

      if (body && body.jobId && body.cancelled) {
        // Cancellation notification — acknowledge it
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ status: 'cancelled' }),
        });
      } else if (body && body.jobId) {
        // This is a poll request
        pollCount++;

        if (testPollError) {
          await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ error: testPollError }),
          });
          return;
        }

        if (pollCount <= testPollWaitCount) {
          // Still waiting
          await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ status: 'waiting' }),
          });
        } else {
          // Return replay data
          await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(mockTestReplayData),
          });
        }
      } else {
        // This is the initial test request
        if (testInitError) {
          await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ error: testInitError }),
          });
          return;
        }

        const response: Record<string, string> = { jobId: mockTestJobId };
        if (testInitWarning) {
          response.warning = testInitWarning;
        }

        pollCount = 0; // Reset poll counter for this test run
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(response),
        });
      }
    });
  }
}

/**
 * Creates a test page with the modeler application and mocked APIs.
 */
export async function createModelerPage(page: Page, modelId = 'test-model-1') {
  await setupMocks(page);

  // Navigate to the modeler page
  // In a real setup, this would be the Drupal page URL
  await page.goto(`/modeler/${modelId}`);

  // Wait for the React app to initialize
  await page.waitForSelector('[data-testid="flow-canvas"]', { timeout: 10000 });
}
