<?php

declare(strict_types=1);

namespace Drupal\Tests\tool_explorer\Kernel;

use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\tool_explorer\Controller\ToolExplorerController;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests access to the Tool Explorer.
 *
 * Route access and the offered operations are checked without rendering a
 * page.
 *
 * @group tool_explorer
 */
#[Group('tool_explorer')]
#[RunTestsInSeparateProcesses]
class ToolExplorerAccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'tool',
    'tool_explorer',
    'tool_test',
  ];

  /**
   * The parameters of each Explorer route, keyed by route name.
   *
   * @var array
   */
  protected const ROUTE_PARAMETERS = [
    'tool_explorer.list' => [],
    'tool_explorer.view' => ['plugin_id' => 'greeting_tool'],
    'tool_explorer.execute' => ['plugin_id' => 'greeting_tool'],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    // User 1 is granted every permission, so create it first to keep it out
    // of the accounts under test.
    $this->createUser();
  }

  /**
   * Tests that both Tool permissions are marked as restricted.
   */
  public function testPermissionMetadata(): void {
    $permissions = $this->container->get('user.permissions')->getPermissions();

    foreach (['administer tool', 'view tool information'] as $permission) {
      $this->assertArrayHasKey($permission, $permissions);
      $this->assertTrue($permissions[$permission]['restrict access']);
    }
  }

  /**
   * Tests that every Explorer route is gated by the expected permission.
   */
  public function testRoutePermissionGate(): void {
    // Anonymous users are denied everywhere.
    $this->assertRouteAccess(new AnonymousUserSession(), [
      'tool_explorer.list' => FALSE,
      'tool_explorer.view' => FALSE,
      'tool_explorer.execute' => FALSE,
    ]);

    // Authenticated users without either Tool permission are denied
    // everywhere.
    $this->assertRouteAccess($this->createUser(), [
      'tool_explorer.list' => FALSE,
      'tool_explorer.view' => FALSE,
      'tool_explorer.execute' => FALSE,
    ]);

    // Read-only users can browse but cannot execute tools.
    $this->assertRouteAccess($this->createUser(['view tool information']), [
      'tool_explorer.list' => TRUE,
      'tool_explorer.view' => TRUE,
      'tool_explorer.execute' => FALSE,
    ]);

    // Existing administrator access continues to cover every Explorer route.
    $this->assertRouteAccess($this->createUser(['administer tool']), [
      'tool_explorer.list' => TRUE,
      'tool_explorer.view' => TRUE,
      'tool_explorer.execute' => TRUE,
    ]);
  }

  /**
   * Tests that only administrators are offered the Execute operation.
   */
  public function testListOperations(): void {
    // Read-only users are offered the View operation only.
    $this->setCurrentUser($this->createUser(['view tool information']));
    [$build, $row_links] = $this->buildToolList();
    $this->assertContains('greeting_tool', array_column($build['tools_table']['#rows'], 'id'));
    foreach ($row_links as $links) {
      $this->assertArrayHasKey('view', $links);
      $this->assertArrayNotHasKey('execute', $links);
    }

    // The Execute link depends on a permission, so a cached list must vary
    // by permissions.
    $this->assertContains('user.permissions', $build['tools_table']['#cache']['contexts']);

    // Administrators are also offered the Execute operation.
    $this->setCurrentUser($this->createUser(['administer tool']));
    [$build, $row_links] = $this->buildToolList();
    $this->assertContains('greeting_tool', array_column($build['tools_table']['#rows'], 'id'));
    foreach ($row_links as $links) {
      $this->assertArrayHasKey('view', $links);
      $this->assertArrayHasKey('execute', $links);
    }
  }

  /**
   * Tests that the View page shows the permission a tool declares.
   */
  public function testViewShowsDeclaredPermission(): void {
    $this->assertSame('operate test tools,audit test tools', $this->viewToolInformation('declared_permission_tool')['Permission']);
    $this->assertSame('None', $this->viewToolInformation('greeting_tool')['Permission']);
  }

  /**
   * Asserts which Explorer routes an account can access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check.
   * @param bool[] $expected
   *   Whether the account is expected to have access, keyed by route name.
   */
  protected function assertRouteAccess(AccountInterface $account, array $expected): void {
    $access_manager = $this->container->get('access_manager');
    foreach ($expected as $route_name => $allowed) {
      $this->assertSame(
        $allowed,
        $access_manager->checkNamedRoute($route_name, self::ROUTE_PARAMETERS[$route_name], $account),
        sprintf('Unexpected access result for the %s route.', $route_name),
      );
    }
  }

  /**
   * Builds the tool list as the current user.
   *
   * The controller renders the operations links of each row itself, which
   * needs a render context and runs theme templates. A stub renderer records
   * the links instead, so they can be checked before any markup exists.
   *
   * @return array
   *   An array containing the render array the controller returns and the
   *   operations links of each row, in row order.
   */
  protected function buildToolList(): array {
    $row_links = [];
    $renderer = $this->createStub(RendererInterface::class);
    $renderer->method('render')->willReturnCallback(function (array $elements) use (&$row_links): string {
      if (($elements['#type'] ?? NULL) === 'dropbutton') {
        $row_links[] = $elements['#links'];
      }
      return '';
    });
    $this->container->set('renderer', $renderer);

    $build = ToolExplorerController::create($this->container)->listTools();

    // Each row has exactly one set of operations links.
    $this->assertCount(count($build['tools_table']['#rows']), $row_links);
    return [$build, $row_links];
  }

  /**
   * Builds the Tool Information table of a tool's View page.
   *
   * @param string $plugin_id
   *   The tool plugin ID.
   *
   * @return string[]
   *   The values in the table, keyed by their header.
   */
  protected function viewToolInformation(string $plugin_id): array {
    $build = ToolExplorerController::create($this->container)->viewTool($plugin_id);
    $information = [];
    foreach ($build['tool_details']['info']['#rows'] as [$header, $value]) {
      $information[(string) $header['data']] = (string) $value;
    }
    return $information;
  }

}
