<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\tool\Event\ToolInputTransformEvent;
use Drupal\tool\Form\ExecuteToolPluginForm;
use Drupal\tool\Tool\ToolInterface;
use Drupal\tool_explorer\Form\ToolExecuteForm;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the execute form checks the declared permission first.
 *
 * An account without the declared permission gets no widget tree derived
 * from the input definitions, and a submission from it is denied before any
 * submitted value is extracted or transformed (#3583077). An account with
 * the permission still executes the tool through the Explorer's form.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
final class ExecuteToolPluginFormTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'tool',
    'tool_explorer',
    'tool_test',
    'user',
  ];

  /**
   * The number of input transform events fired.
   */
  private int $transforms = 0;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    // The first account gets uid 1, which passes every permission check, so
    // it must exist before any account under test is created.
    $this->createUser();
    $this->container->get('event_dispatcher')
      ->addListener(ToolInputTransformEvent::class, function (): void {
        $this->transforms++;
      });
  }

  /**
   * Builds the execute form object for a fresh tool instance.
   *
   * @return array{0: \Drupal\tool\Form\ExecuteToolPluginForm, 1: \Drupal\tool\Tool\ToolInterface}
   *   The form object and the tool it is for.
   */
  private function createFormObject(): array {
    $tool = $this->container->get('plugin.manager.tool')
      ->createInstance('declared_permission_tool');
    $this->assertInstanceOf(ToolInterface::class, $tool);
    $form_object = ExecuteToolPluginForm::create($this->container);
    $form_object->setPlugin($tool);
    return [$form_object, $tool];
  }

  /**
   * Tests an unauthorized account gets a denial instead of widgets.
   */
  public function testBuildDeniedWithoutPermission(): void {
    $this->setCurrentUser($this->createUser());
    [$form_object] = $this->createFormObject();

    $form = $form_object->buildConfigurationForm([], new FormState());

    $this->assertArrayHasKey('access_denied', $form);
    $this->assertArrayNotHasKey('subject', $form);
  }

  /**
   * Tests an account with the declared permissions gets the widgets.
   */
  public function testBuildWithPermission(): void {
    $this->setCurrentUser($this->createUser(['operate test tools', 'audit test tools']));
    [$form_object] = $this->createFormObject();

    $form = $form_object->buildConfigurationForm([], new FormState());

    $this->assertArrayNotHasKey('access_denied', $form);
    $this->assertArrayHasKey('subject', $form);
  }

  /**
   * Tests a denied submission runs before any value is processed.
   *
   * The form is built for an account that held the permission, so the
   * widget tree exists; the account is then demoted, pinning that the
   * submit-time check itself guards the extraction rather than the build
   * having withheld the widgets.
   */
  public function testSubmitDeniedBeforeInputProcessing(): void {
    $granted = $this->createUser(['operate test tools', 'audit test tools']);
    $this->setCurrentUser($granted);
    [$form_object] = $this->createFormObject();
    $form_state = new FormState();
    $form = $form_object->buildConfigurationForm([], $form_state);
    $this->assertArrayHasKey('subject', $form);

    $this->setCurrentUser($this->createUser());
    $form_object->submitConfigurationForm($form, $form_state);

    $errors = $this->container->get('messenger')->messagesByType('error');
    $this->assertCount(1, $errors);
    $this->assertSame('You do not have access to execute this tool.', (string) reset($errors));
    $this->assertSame(0, $this->transforms, 'The denial happened before any input transform event fired.');
  }

  /**
   * Tests the denied Explorer page offers no Execute button.
   */
  public function testDeniedPageOffersNoExecuteButton(): void {
    $this->setCurrentUser($this->createUser());

    $form = $this->container->get('form_builder')
      ->getForm(ToolExecuteForm::class, 'declared_permission_tool');

    $this->assertArrayHasKey('access_denied', $form);
    $this->assertArrayNotHasKey('submit', $form['actions']);
    $this->assertArrayHasKey('cancel', $form['actions']);
  }

  /**
   * Tests a denied Explorer submission reports the denial once.
   *
   * Without the result guard, the host form read getResult() on the
   * never-executed tool and showed the BadMethodCallException text as a
   * second error (#3583079).
   */
  public function testDeniedSubmitReportsOnce(): void {
    $this->setCurrentUser($this->createUser());

    $form_state = new FormState();
    $this->container->get('form_builder')
      ->submitForm(ToolExecuteForm::class, $form_state, 'declared_permission_tool');

    $errors = array_map('strval', $this->container->get('messenger')->messagesByType('error'));
    $this->assertCount(1, $errors);
    $this->assertSame('You do not have access to execute this tool.', reset($errors));
    $this->assertStringNotContainsString('::execute()', implode(' ', $errors));
  }

  /**
   * Tests an account with the declared permissions executes through submit.
   *
   * Driven through the Explorer's host form via the form builder, so the
   * extraction, access check and execution run as a real submission would.
   * The transform count doubles as the positive control for the listener
   * the denial test relies on.
   */
  public function testSubmitWithPermissionExecutes(): void {
    $this->setCurrentUser($this->createUser(['operate test tools', 'audit test tools']));

    $form_state = new FormState();
    $form_state->setValues(['subject' => ['value' => 'probe']]);
    $this->container->get('form_builder')
      ->submitForm(ToolExecuteForm::class, $form_state, 'declared_permission_tool');

    $this->assertSame([], $form_state->getErrors());
    $tool = $form_state->get('tool_plugin');
    $this->assertInstanceOf(ToolInterface::class, $tool);
    $this->assertTrue($tool->hasExecuted());
    $this->assertGreaterThan(0, $this->transforms, 'A granted run fires input transform events, proving the listener is wired.');
    $messenger = $this->container->get('messenger');
    $this->assertSame([], $messenger->messagesByType('error'));
    $this->assertStringContainsString('Done.', implode(' ', array_map('strval', $messenger->messagesByType('status'))));
  }

}
