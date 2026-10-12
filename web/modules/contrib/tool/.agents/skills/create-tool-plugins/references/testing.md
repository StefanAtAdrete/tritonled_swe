# Testing a Tool — mandatory

Two tests are **required** for every Tool. You don't write them from a blank
file: `drush generate plugin:tool` scaffolds both stubs next to the plugin (see
the skill workflow, Step 2). Your job is to **flesh them out** so they test the
real tool — and never to delete them. No exceptions, not even for "simple" tools.

1. A **unit test** for `checkAccess()` — `tests/src/Unit/Tool/`.
2. A **kernel test** for `doExecute()` — `tests/src/Kernel/Tool/`.

The generated stubs use PHP attributes — `#[CoversClass]`, `#[Group]`, and
`#[RunTestsInSeparateProcesses]` on kernel tests — not the `@covers`/`@group`
docblock annotations (deprecated in PHPUnit 10+). Keep that style when you
extend them.

If the tool makes HTTP/API calls, the kernel test mocks them (Guzzle) — never
hit a live endpoint.

Namespaces follow PSR-4 test conventions:
`Drupal\Tests\{module}\Unit\Tool\…` and `Drupal\Tests\{module}\Kernel\Tool\…`.

---

## 1. Unit test for `checkAccess()`

`checkAccess()` is `protected`, and the public `access()` path needs the typed
data container — too heavy for a unit test. So instantiate the plugin directly
(the constructor is public) and invoke `checkAccess()` via reflection with a
hand-built `$values` array and a mocked account. No container, fast, isolated.

### What the generator gives you

Out of the scaffold, `checkAccess()` is fail-closed (it throws until access is
real), so the generated unit test simply asserts the throw:

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\my_module\Unit\Tool;

use Drupal\Core\Session\AccountInterface;
use Drupal\my_module\Plugin\tool\Tool\DoThing;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests access control for the Do Thing tool.
 */
#[CoversClass(DoThing::class)]
#[Group('my_module')]
final class DoThingAccessTest extends UnitTestCase {

  /**
   * Invokes the protected checkAccess() on a directly-built plugin.
   */
  private function checkAccess(array $values, AccountInterface $account): bool {
    // Constructor: (configuration, plugin_id, plugin_definition, currentUser).
    // currentUser is unused here because checkAccess receives its own account.
    $tool = new DoThing([], 'my_module:do_thing', [], $account);
    $method = new \ReflectionMethod(DoThing::class, 'checkAccess');
    return $method->invoke($tool, $values, $account, FALSE);
  }

  /**
   * The generated checkAccess() is fail-closed: it throws until access is real.
   */
  public function testAccessIsFailClosed(): void {
    $this->expectException(\LogicException::class);
    $this->checkAccess(['subject' => 'x'], $this->createMock(AccountInterface::class));
  }

}
```

That stub is green (the body does throw) and documents that access is undefined.
It stays as a standing reminder until you replace the fail-closed body.

### After access is approved — replace it with real cases

Once Step 4 of the skill has settled the real access check, replace
`testAccessIsFailClosed()` with allowed/denied cases (keep the helper and the
class attributes):

```php
/**
 * Allowed when the account has the required permission.
 */
public function testAllowedWithPermission(): void {
  $account = $this->createMock(AccountInterface::class);
  $account->method('hasPermission')
    ->willReturnMap([['a real, confirmed permission', TRUE]]);

  $this->assertTrue($this->checkAccess(['subject' => 'x'], $account));
}

/**
 * Denied without the permission.
 */
public function testDeniedWithoutPermission(): void {
  $account = $this->createMock(AccountInterface::class);
  $account->method('hasPermission')->willReturn(FALSE);

  $this->assertFalse($this->checkAccess(['subject' => 'x'], $account));
}
```

Notes:

- `AccessResult::allowedIfHasPermission()` works without a container, so
  permission-based checks unit-test cleanly.
- **Entity access checks**: put a mocked entity in `$values` and stub its
  `access()` to return an `AccessResult`:
  ```php
  $entity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
  $entity->method('access')->willReturn(\Drupal\Core\Access\AccessResult::allowed());
  $this->checkAccess(['entity' => $entity], $account);
  ```
- **Service-dependent checks** (e.g. uses `$this->entityTypeManager`): set the
  protected property by reflection, since you cannot call `create()` here:
  ```php
  $tool = new DoThing([], 'my_module:do_thing', [], $account);
  (new \ReflectionProperty(DoThing::class, 'entityTypeManager'))
    ->setValue($tool, $entityTypeManagerMock);
  ```

---

## 2. Kernel test for `doExecute()`

The generator scaffolds a kernel test that gets the tool from
`plugin.manager.tool`, sets inputs, `execute()`s, and asserts on the result and
outputs (model is the module's own `tests/src/Kernel/Tool/ToolBaseTest.php`).
Flesh it out for the real contract: the happy path, invalid/missing input, and
any side effects. Note the class attributes — `#[CoversClass]`, `#[Group]`, and
`#[RunTestsInSeparateProcesses]` (required for kernel tests in Drupal 11.3+).

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\my_module\Kernel\Tool;

use Drupal\KernelTests\KernelTestBase;
use Drupal\my_module\Plugin\tool\Tool\DoThing;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests execution of the Do Thing tool.
 */
#[CoversClass(DoThing::class)]
#[Group('my_module')]
#[RunTestsInSeparateProcesses]
final class DoThingTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['tool', 'my_module', 'user'];

  /**
   * The tool plugin manager.
   *
   * @var \Drupal\tool\Tool\ToolManager
   */
  protected $toolManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // If the tool touches entities, install their schema here, e.g.:
    // $this->installEntitySchema('node');
    $this->toolManager = $this->container->get('plugin.manager.tool');
  }

  /**
   * Tests a successful execution with valid input.
   */
  public function testSuccessfulExecution(): void {
    $tool = $this->toolManager->createInstance('my_module:do_thing');
    $tool->setInputValue('subject', 'widget');
    $tool->execute();

    $result = $tool->getResult();
    $this->assertTrue($result->isSuccess());
    $this->assertStringContainsString('widget', (string) $result->getMessage());
    // Output keys must match output_definitions to be populated.
    $this->assertSame(['subject' => 'widget'], $tool->getOutputValue('result'));
  }

  /**
   * Tests that missing required input produces a failure result.
   */
  public function testInvalidInputFails(): void {
    $tool = $this->toolManager->createInstance('my_module:do_thing');
    // Missing the required 'subject' → execute() catches the validation error.
    $tool->execute();

    $this->assertFalse($tool->getResult()->isSuccess());
  }

}
```

`execute()` does **not** call `checkAccess()` (access is a separate path enforced
by callers), so these execution tests pass even while `checkAccess()` is still
fail-closed — that is why the generated kernel stub is green out of the box.

Assert side effects explicitly for `Write`/`Trigger` tools (entity saved/blocked/
deleted, message logged, etc.) by reloading the affected state after `execute()`.

---

## 3. Mocking an external API (Guzzle)

If `doExecute()` calls an HTTP API (via an injected `http_client`), the kernel
test must replace the real client with a Guzzle `MockHandler` so the test is
hermetic. Set the service **before** you `createInstance()` the tool, so the
mocked client is what `create()` injects.

```php
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * The queue of mocked HTTP responses.
 */
protected MockHandler $mockHandler;

protected function setUp(): void {
  parent::setUp();
  $this->mockHandler = new MockHandler();
  $stack = HandlerStack::create($this->mockHandler);
  // Override the core http_client with one backed by the mock handler.
  $this->container->set('http_client', new Client(['handler' => $stack]));
  $this->toolManager = $this->container->get('plugin.manager.tool');
}

public function testApiSuccess(): void {
  // Queue the response the tool will receive.
  $this->mockHandler->append(new Response(200, [], json_encode(['ok' => TRUE])));

  $tool = $this->toolManager->createInstance('my_module:call_api');
  $tool->setInputValue('query', 'hello');
  $tool->execute();

  $this->assertTrue($tool->getResult()->isSuccess());
}

public function testApiFailureIsHandled(): void {
  // Queue an error so you can assert the tool returns ExecutableResult::failure.
  $this->mockHandler->append(new Response(500, [], 'boom'));

  $tool = $this->toolManager->createInstance('my_module:call_api');
  $tool->setInputValue('query', 'hello');
  $tool->execute();

  $this->assertFalse($tool->getResult()->isSuccess());
}
```

- `append()` more responses to assert retry/pagination behavior; the handler
  dequeues one per request.
- To assert the **outgoing** request (URL, method, headers, body), add a
  `GuzzleHttp\Middleware::history($container)` middleware to the stack and
  inspect `$container` after `execute()`.
- **Alternative — middleware service**: register a service tagged
  `http_client_middleware` in a small test module. Use this when the tool must
  go through the real `http_client` service stack (e.g. it reads other handlers/
  options). For most tools the `MockHandler` container override above is simpler
  and sufficient.

Never let a test perform a real network request: it is slow, flaky, and may have
real side effects.
