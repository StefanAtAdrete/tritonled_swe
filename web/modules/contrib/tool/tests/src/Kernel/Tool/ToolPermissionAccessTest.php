<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolManager;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the permission a tool declares on its definition.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
final class ToolPermissionAccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'tool',
    'tool_test',
    'user',
  ];

  /**
   * The tool plugin manager.
   */
  private ToolManager $toolManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    // The first account gets uid 1, which passes every permission check, so
    // it must exist before any account under test is created.
    $this->createUser();
    $this->toolManager = $this->container->get('plugin.manager.tool');
  }

  /**
   * Tests a declaration naming one permission.
   */
  public function testSinglePermission(): void {
    $denied = $this->assertPermissionAccess(FALSE, 'derived_permission_tool:single', $this->createUser());
    $this->assertInstanceOf(AccessResultReasonInterface::class, $denied);
    $this->assertSame("The 'operate test tools' permission is required.", $denied->getReason());
    $this->assertContains('user.permissions', $denied->getCacheContexts());

    $allowed = $this->assertPermissionAccess(TRUE, 'derived_permission_tool:single', $this->createUser(['operate test tools']));
    $this->assertContains('user.permissions', $allowed->getCacheContexts());
  }

  /**
   * Tests that names joined with a comma are all required.
   */
  public function testCommaRequiresEveryPermission(): void {
    $reason = "The following permissions are required: 'operate test tools' AND 'audit test tools'.";
    foreach (['operate test tools', 'audit test tools'] as $permission) {
      $denied = $this->assertPermissionAccess(FALSE, 'declared_permission_tool', $this->createUser([$permission]));
      $this->assertInstanceOf(AccessResultReasonInterface::class, $denied);
      $this->assertSame($reason, $denied->getReason());
    }

    $account = $this->createUser(['operate test tools', 'audit test tools']);
    $allowed = $this->assertPermissionAccess(TRUE, 'declared_permission_tool', $account);
    $this->assertContains('user.permissions', $allowed->getCacheContexts());

    // Spaces around each name are trimmed, so the reason names the real
    // permissions.
    $denied = $this->assertPermissionAccess(FALSE, 'derived_permission_tool:spaced', $this->createUser(['operate test tools']));
    $this->assertInstanceOf(AccessResultReasonInterface::class, $denied);
    $this->assertSame($reason, $denied->getReason());
    $this->assertPermissionAccess(TRUE, 'derived_permission_tool:spaced', $account);
  }

  /**
   * Tests that names joined with a plus sign require any one of them.
   */
  public function testPlusRequiresAnyPermission(): void {
    $this->assertPermissionAccess(TRUE, 'derived_permission_tool:any', $this->createUser(['operate test tools']));
    $this->assertPermissionAccess(TRUE, 'derived_permission_tool:any', $this->createUser(['audit test tools']));

    $denied = $this->assertPermissionAccess(FALSE, 'derived_permission_tool:any', $this->createUser());
    $this->assertInstanceOf(AccessResultReasonInterface::class, $denied);
    $this->assertSame("The following permissions are required: 'operate test tools' OR 'audit test tools'.", $denied->getReason());
    $this->assertContains('user.permissions', $denied->getCacheContexts());
  }

  /**
   * Tests that a string mixing both operators is rejected at discovery.
   *
   * Routes do not combine them either; there the mixed string just fails
   * silently, because each "+" part is read as one literal permission name.
   */
  public function testMixedOperatorsAreRejectedAtDiscovery(): void {
    $definition = clone $this->toolManager->getDefinition('derived_permission_tool:single');
    $definition->setPermission('operate test tools+audit test tools,operate test tools');
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage('mixes "," and "+"');
    $this->toolManager->processDefinition($definition, 'derived_permission_tool:mixed');
  }

  /**
   * Tests that a misspelled permission denies every ordinary account.
   *
   * Only user 1 and accounts with an administrator role pass, as they pass
   * every permission check.
   */
  public function testMisspelledPermissionDeniesOrdinaryAccounts(): void {
    $account = $this->createUser(['operate test tools', 'audit test tools']);
    $denied = $this->assertPermissionAccess(FALSE, 'derived_permission_tool:misspelled', $account);
    $this->assertInstanceOf(AccessResultReasonInterface::class, $denied);
    $this->assertSame("The 'operate test tool' permission is required.", $denied->getReason());
  }

  /**
   * Tests that a tool declaring no permission is allowed without conditions.
   *
   * The result must not vary by permissions, or every listing of such tools
   * would be cached per permission set for nothing.
   */
  public function testNoDeclaredPermission(): void {
    $account = $this->createUser();
    $allowed = $this->assertPermissionAccess(TRUE, 'greeting_tool', $account);
    $this->assertSame([], $allowed->getCacheContexts());
    $this->assertSame(Cache::PERMANENT, $allowed->getCacheMaxAge());

    // The instance gate adds no permission cacheability either.
    $this->setCurrentUser($account);
    $tool = $this->toolManager->createInstance('greeting_tool');
    $tool->setInputValue('name', 'Ada');
    $access = $tool->access(NULL, TRUE);
    $this->assertInstanceOf(AccessResult::class, $access);
    $this->assertTrue($access->isAllowed());
    $this->assertNotContains('user.permissions', $access->getCacheContexts());
  }

  /**
   * Tests that a tool with no access rule at all is rejected at discovery.
   *
   * With checkAccess() concrete, such a tool would silently answer neutral
   * for every account, administrators included; discovery is where it can
   * still fail loudly. Both NULL and '' declarations arrive here as NULL,
   * because getPermission() normalizes the empty string.
   */
  public function testMissingAccessRuleIsRejectedAtDiscovery(): void {
    $definition = clone $this->toolManager->getDefinition('derived_permission_tool:single');
    $definition->setPermission(NULL);
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage('declares no permission and does not override');
    $this->toolManager->processDefinition($definition, 'derived_permission_tool:open');
  }

  /**
   * Tests that an access() override alone satisfies the discovery check.
   *
   * The check accepts either override as an access rule; without this
   * fixture, only the checkAccess() half of the condition is exercised.
   */
  public function testAccessOverrideSatisfiesDiscovery(): void {
    $definition = $this->toolManager->getDefinition('access_override_tool');
    $this->assertNull($definition->getPermission());
    $this->assertTrue($this->toolManager->createInstance('access_override_tool')->access($this->createUser()));
  }

  /**
   * Tests permission-only derivatives against an explicit account.
   */
  public function testDefaultAccessWithPermission(): void {
    $denied_account = $this->createUser();
    $this->setCurrentUser($denied_account);
    $account = $this->createUser(['operate test tools']);
    foreach (['derived_permission_tool:single', 'derived_permission_tool:any'] as $tool_id) {
      $tool = $this->toolManager->createInstance($tool_id);
      $this->assertTrue($tool->access($account), $tool_id);
      $access = $tool->access($account, TRUE);
      $this->assertInstanceOf(AccessResult::class, $access);
      $this->assertTrue($access->isAllowed(), $tool_id);
      $this->assertContains('user.permissions', $access->getCacheContexts());
      $this->assertFalse($tool->access($denied_account), $tool_id);
      $this->assertFalse($tool->access(), $tool_id);

      // The default does not re-check the account: ::access() has already
      // required the permission by the time it runs.
      $check_access = new \ReflectionMethod($tool, 'checkAccess');
      $this->assertTrue($check_access->invoke($tool, [], $account), $tool_id);
      $this->assertTrue($check_access->invoke($tool, [], $denied_account), $tool_id);
    }
  }

  /**
   * Tests that a deriver sets a different permission on each derivative.
   */
  public function testDerivativesDeclareTheirOwnPermission(): void {
    $this->assertSame('operate test tools', $this->toolManager->getDefinition('derived_permission_tool:single')->getPermission());

    $account = $this->createUser();
    $this->assertFalse(ToolManager::checkPermission($this->toolManager->getDefinition('derived_permission_tool:single'), $account)->isAllowed());
    $this->assertTrue(ToolManager::checkPermission($this->toolManager->getDefinition('greeting_tool'), $account)->isAllowed());
  }

  /**
   * Tests that access() checks the declared permission before the inputs.
   *
   * The required input is not set, so reaching input validation would deny
   * with the generic validation reason instead of the permission one.
   */
  public function testAccessChecksDeclaredPermissionFirst(): void {
    $this->setCurrentUser($this->createUser());
    $tool = $this->toolManager->createInstance('declared_permission_tool');

    $this->assertFalse($tool->access());
    $access = $tool->access(NULL, TRUE);
    $this->assertInstanceOf(AccessResultReasonInterface::class, $access);
    $this->assertFalse($access->isAllowed());
    $this->assertSame("The following permissions are required: 'operate test tools' AND 'audit test tools'.", $access->getReason());

    // Valid input cannot bypass the declaration through the default check.
    $tool->setInputValue('subject', 'status');
    $this->assertFalse($tool->access());
  }

  /**
   * Tests a permission-only tool still requires valid input.
   */
  public function testAccessWithDeclaredPermission(): void {
    $this->setCurrentUser($this->createUser(['operate test tools', 'audit test tools']));
    $tool = $this->toolManager->createInstance('declared_permission_tool');

    // Input validation still applies.
    $access = $tool->access(NULL, TRUE);
    $this->assertInstanceOf(AccessResultReasonInterface::class, $access);
    $this->assertTrue($access->isForbidden());
    $this->assertSame('Tool input validation failed.', $access->getReason());

    // The final result keeps the permission cacheability of the gate.
    $tool->setInputValue('subject', 'status');
    $this->assertTrue($tool->access());
    $access = $tool->access(NULL, TRUE);
    $this->assertInstanceOf(AccessResult::class, $access);
    $this->assertTrue($access->isAllowed());
    $this->assertContains('user.permissions', $access->getCacheContexts());
  }

  /**
   * Tests custom access verdicts and their cacheability remain authoritative.
   */
  public function testCustomCheckAccessComposition(): void {
    $denied_account = $this->createUser();
    $this->setCurrentUser($denied_account);
    $account = $this->createUser(['operate test tools', 'audit test tools']);
    $definition = $this->toolManager->getDefinition('declared_permission_tool');
    $runtime_results = [
      AccessResult::allowed(),
      AccessResult::neutral('Additional access required.'),
      AccessResult::forbidden('Denied by runtime check.'),
    ];
    foreach ($runtime_results as $runtime_access) {
      $runtime_access->addCacheContexts(['user'])
        ->addCacheTags(['test:runtime_access'])
        ->setCacheMaxAge(60);
      $tool = $this->getMockBuilder(ToolBase::class)
        ->setConstructorArgs([
          [],
          'declared_permission_tool',
          $definition,
          $this->container->get('current_user'),
          $this->container->get('event_dispatcher'),
          $this->container->get('logger.channel.tool'),
        ])
        ->onlyMethods(['doExecute', 'checkAccess'])
        ->getMock();
      $tool->expects($this->exactly(2))->method('checkAccess')
        ->with(['subject' => 'status'], $account, TRUE)
        ->willReturn($runtime_access);
      $tool->setInputValue('subject', 'status');

      $this->assertSame($runtime_access->isAllowed(), $tool->access($account));
      $access = $tool->access($account, TRUE);
      $this->assertInstanceOf(AccessResult::class, $access);
      $this->assertSame($runtime_access->isAllowed(), $access->isAllowed());
      $this->assertSame($runtime_access->isNeutral(), $access->isNeutral());
      $this->assertSame($runtime_access->isForbidden(), $access->isForbidden());
      $this->assertEqualsCanonicalizing(['user.permissions', 'user'], $access->getCacheContexts());
      $this->assertSame(['test:runtime_access'], $access->getCacheTags());
      $this->assertSame(60, $access->getCacheMaxAge());
      if ($runtime_access instanceof AccessResultReasonInterface) {
        $this->assertInstanceOf(AccessResultReasonInterface::class, $access);
        $this->assertSame($runtime_access->getReason(), $access->getReason());
      }

      // The override is not called when the declared permission fails.
      $this->assertFalse($tool->access($denied_account));
    }
  }

  /**
   * Tests access() when the tool's own check returns a bare bool.
   *
   * A bool carries no cacheability metadata, so the composed result keeps the
   * cache context of the permission gate but must not be cached.
   */
  public function testBooleanCheckAccessComposition(): void {
    $this->setCurrentUser($this->createUser(['operate test tools']));
    $tool = $this->toolManager->createInstance('boolean_access_tool');

    $this->assertFalse($tool->access());
    $access = $tool->access(NULL, TRUE);
    $this->assertInstanceOf(AccessResult::class, $access);
    $this->assertFalse($access->isAllowed());

    $this->setCurrentUser($this->createUser(['operate test tools', 'audit test tools']));
    $tool = $this->toolManager->createInstance('boolean_access_tool');

    $this->assertTrue($tool->access());
    $access = $tool->access(NULL, TRUE);
    $this->assertInstanceOf(AccessResult::class, $access);
    $this->assertTrue($access->isAllowed());
    $this->assertContains('user.permissions', $access->getCacheContexts());
    $this->assertSame(0, $access->getCacheMaxAge());
  }

  /**
   * Asserts the manager's verdict in both of its return modes.
   *
   * @param bool $expected
   *   Whether access is expected to be allowed.
   * @param string $tool_id
   *   The tool plugin ID.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result, for further assertions.
   */
  private function assertPermissionAccess(bool $expected, string $tool_id, AccountInterface $account): AccessResult {
    $definition = $this->toolManager->getDefinition($tool_id);
    $access = ToolManager::checkPermission($definition, $account);
    $this->assertInstanceOf(AccessResult::class, $access);
    $this->assertSame($expected, $access->isAllowed(), $tool_id);
    return $access;
  }

}
