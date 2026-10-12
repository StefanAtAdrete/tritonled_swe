<?php

declare(strict_types=1);

namespace Drupal\Tests\tool_ai_connector\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\tool\Event\ToolInputTransformEvent;
use Drupal\tool\FailureCategory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the connector checks the declared permission first.
 *
 * An account without the declared permission is denied before any
 * model-provided value is processed: no input transform events fire, so no
 * handles resolve and no refiners run for it (#3583077).
 *
 * @group tool_ai_connector
 */
#[Group('tool_ai_connector')]
#[RunTestsInSeparateProcesses]
final class DeclaredPermissionDenialTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'key',
    'serialization',
    'ai',
    'tool',
    'tool_test',
    'tool_ai_connector',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    // The first account gets uid 1, which passes every permission check, so
    // it must exist before any account under test is created.
    $this->createUser();
  }

  /**
   * Tests the denial happens before any input is processed.
   */
  public function testDeniedBeforeInputProcessing(): void {
    $transforms = 0;
    $this->container->get('event_dispatcher')
      ->addListener(ToolInputTransformEvent::class, function () use (&$transforms): void {
        $transforms++;
      });
    $this->setCurrentUser($this->createUser());

    $function_call = $this->container->get('plugin.manager.ai.function_calls')
      ->createInstance('tool:declared_permission_tool');
    $function_call->setContextValue('subject', 'probe');
    $function_call->execute();

    $tool = $function_call->getPluginInstance();
    $this->assertFalse($tool->hasExecuted());
    $result = $tool->getResult();
    $this->assertFalse($result->isSuccess());
    $this->assertStringContainsString('Tool plugin access denied', (string) $result->getMessage());
    $this->assertSame(FailureCategory::Access, $result->getFailureCategory());
    $this->assertSame(0, $transforms, 'The denial happened before any input transform event fired.');

    // The structured denial matches the access() denial shape: a failure
    // with no outputs and no input-schema echo, since an access denial is
    // not correctable by the model (#3583077).
    $output = $function_call->getStructuredOutput();
    $this->assertFalse($output['success']);
    $this->assertSame([], $output['outputs']);
    $this->assertArrayNotHasKey('input_schema', $output);
    $this->assertStringStartsWith('Tool plugin access denied: ', $output['message']);
  }

  /**
   * Tests an account with the declared permissions executes the tool.
   */
  public function testGrantedAccountExecutes(): void {
    $transforms = 0;
    $this->container->get('event_dispatcher')
      ->addListener(ToolInputTransformEvent::class, function () use (&$transforms): void {
        $transforms++;
      });
    $this->setCurrentUser($this->createUser(['operate test tools', 'audit test tools']));

    $function_call = $this->container->get('plugin.manager.ai.function_calls')
      ->createInstance('tool:declared_permission_tool');
    $function_call->setContextValue('subject', 'probe');
    $function_call->execute();

    $tool = $function_call->getPluginInstance();
    $this->assertTrue($tool->hasExecuted());
    $this->assertTrue($tool->getResult()->isSuccess());
    $this->assertGreaterThan(0, $transforms, 'A granted run fires input transform events, proving the listener is wired.');
  }

}
