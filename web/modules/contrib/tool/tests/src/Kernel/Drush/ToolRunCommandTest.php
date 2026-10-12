<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Drush;

use Drupal\tool\Drush\Commands\ToolRunCommand;
use Drupal\tool\Event\ToolInputTransformEvent;
use Drupal\user\Entity\Role;
use Drush\Commands\DrushCommands;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Tests the tool:run Drush command.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class ToolRunCommandTest extends DrushCommandTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    // Create user 1 so the command can switch to it.
    $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create([
        'uid' => 1,
        'name' => 'admin',
        'status' => 1,
      ])
      ->save();
  }

  /**
   * Creates a configured ToolRunCommand instance.
   *
   * @param \Symfony\Component\Console\Input\ArrayInput $input
   *   The input.
   * @param \Symfony\Component\Console\Output\BufferedOutput $output
   *   The output.
   *
   * @return \Drupal\tool\Drush\Commands\ToolRunCommand
   *   The command instance.
   */
  protected function createCommand(ArrayInput $input, BufferedOutput $output): ToolRunCommand {
    $command = new ToolRunCommand(
      $this->toolManager,
      $this->container->get('entity_type.manager'),
      $this->container->get('account_switcher'),
      $this->container->get('current_user'),
    );
    $this->configureCommand($command, $input, $output);
    return $command;
  }

  /**
   * Tests the declared permission denies before any input is processed.
   */
  public function testDeclaredPermissionDeniedBeforeInputProcessing(): void {
    $transforms = 0;
    $this->container->get('event_dispatcher')
      ->addListener(ToolInputTransformEvent::class, function () use (&$transforms): void {
        $transforms++;
      });

    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    // Without --uid the command runs as anonymous, which lacks the declared
    // permissions.
    $result = $command->runTool('declared_permission_tool', [
      'input' => ['subject=probe'],
      'json' => FALSE,
      'uid' => NULL,
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString("Access denied for tool 'declared_permission_tool'", $content);
    $this->assertStringContainsString('Ran as anonymous (uid 0)', $content);
    $this->assertStringNotContainsString('Running tool:', $content);
    $this->assertSame(0, $transforms, 'The denial happened before any input transform event fired.');
  }

  /**
   * Tests an account with the declared permissions runs the tool.
   */
  public function testDeclaredPermissionAllowsExecution(): void {
    $this->enableModules(['system']);
    Role::create(['id' => 'tool_operator', 'label' => 'Tool operator'])
      ->grantPermission('operate test tools')
      ->grantPermission('audit test tools')
      ->save();
    $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create([
        'uid' => 3,
        'name' => 'operator',
        'status' => 1,
        'roles' => ['tool_operator'],
      ])
      ->save();

    // Registered after enableModules(), which rebuilds the container and
    // would discard a listener added to the old dispatcher.
    $transforms = 0;
    $this->container->get('event_dispatcher')
      ->addListener(ToolInputTransformEvent::class, function () use (&$transforms): void {
        $transforms++;
      });

    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('declared_permission_tool', [
      'input' => ['subject=probe'],
      'json' => FALSE,
      'uid' => 3,
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringContainsString('Success:', $content);
    $this->assertGreaterThan(0, $transforms, 'A granted run fires input transform events, proving the listener is wired.');
  }

  /**
   * Tests an account holding only one of two AND-joined permissions.
   */
  public function testDeclaredPermissionPartialGrantDenied(): void {
    $this->enableModules(['system']);
    Role::create(['id' => 'tool_operator', 'label' => 'Tool operator'])
      ->grantPermission('operate test tools')
      ->save();
    $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create([
        'uid' => 3,
        'name' => 'operator',
        'status' => 1,
        'roles' => ['tool_operator'],
      ])
      ->save();

    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('declared_permission_tool', [
      'input' => ['subject=probe'],
      'json' => FALSE,
      'uid' => 3,
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString("Access denied for tool 'declared_permission_tool'", $content);
    // An explicit --uid was passed, so no anonymous hint.
    $this->assertStringNotContainsString('Ran as anonymous', $content);
    $this->assertStringNotContainsString('Running tool:', $content);
  }

  /**
   * Tests running a simple tool with no inputs.
   */
  public function testRunSimpleTool(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('no_requirements_tool');

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringContainsString('Running tool: no_requirements_tool', $content);
    $this->assertStringContainsString('Success:', $content);
    $this->assertStringContainsString('Done.', $content);
  }

  /**
   * Tests running a tool with JSON inputs.
   */
  public function testRunToolWithInputs(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('greeting_tool', [
      'input' => ['{"name":"World","greeting":"Hi"}'],
      'json' => FALSE,
      'uid' => 1,
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringContainsString('Success:', $content);
    $this->assertStringContainsString('Greeting generated.', $content);
    $this->assertStringContainsString('message:', $content);
    $this->assertStringContainsString('Hi, World!', $content);
  }

  /**
   * Tests running a tool with key=value inputs.
   */
  public function testRunToolWithKeyValueInputs(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->runTool('greeting_tool', [
      'input' => ['name=Alice'],
      'json' => FALSE,
      'uid' => 1,
    ]);

    $content = $output->fetch();
    $this->assertStringContainsString('Success:', $content);
    $this->assertStringContainsString('Hello, Alice!', $content);
  }

  /**
   * Tests running a tool with JSON output.
   */
  public function testRunToolJsonOutput(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->runTool('greeting_tool', [
      'input' => ['{"name":"World"}'],
      'json' => TRUE,
      'uid' => 1,
    ]);

    $content = $output->fetch();
    $decoded = json_decode($content, TRUE);
    $this->assertNotNull($decoded);
    $this->assertTrue($decoded['success']);
    $this->assertEquals('Greeting generated.', $decoded['message']);
    $this->assertEquals('Hello, World!', $decoded['outputs']['message']);
  }

  /**
   * Tests that outputs are sent the way their advertised schema describes.
   *
   * The command reads the formatted result, not the PHP values, so a
   * timestamp goes out as a date-time string, a decimal as a string, and an
   * empty map as an object.
   */
  public function testRunToolSendsFormattedOutputs(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->runTool('timestamp_output_tool', ['input' => [], 'json' => TRUE, 'uid' => 1]);
    $decoded = json_decode($output->fetch(), TRUE);
    $timestamp = version_compare(\Drupal::VERSION, '11.1', '>=') ? '2026-08-01T17:55:07+00:00' : 1785606907;
    $this->assertSame($timestamp, $decoded['outputs']['occurred_at']);
    $this->assertSame('19.99', $decoded['outputs']['amount']);
    $this->assertSame('2026-08-01T12:35:07+00:00', $decoded['outputs']['scheduled_for']);

    $command->runTool('empty_map_output_tool', ['input' => [], 'json' => TRUE, 'uid' => 1]);
    $this->assertStringContainsString('"outputs":{"settings":{},"entries":[{}],"options":{},"required_settings":{},"required_options":{},"document":{"settings":{},"options":{},"entries":[{}]}}', $output->fetch());

    $command->runTool('empty_map_output_tool', ['input' => [], 'json' => FALSE, 'uid' => 1]);
    $content = $output->fetch();
    $this->assertStringContainsString('settings: {}', $content);
    $this->assertStringContainsString('entries: [{}]', $content);
  }

  /**
   * Tests that an entity output is printed as a reference, not its fields.
   *
   * A user's fields include the password hash, which must never reach the
   * caller, in either output mode.
   */
  public function testRunToolPrintsEntityOutputAsReference(): void {
    $account = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create([
        'name' => 'sample',
        'pass' => 'sample-password',
        'status' => 1,
      ]);
    $account->save();
    $password_hash = $account->getPassword();
    $this->assertNotEmpty($password_hash);
    $reference = [
      'entity_type' => 'user',
      'id' => $account->id(),
      'uuid' => $account->uuid(),
      'label' => 'sample',
    ];

    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->runTool('account_output_tool', [
      'input' => ['{"account_id":' . $account->id() . '}'],
      'json' => TRUE,
      'uid' => 1,
    ]);
    $content = $output->fetch();
    $this->assertStringNotContainsString($password_hash, $content);
    $this->assertStringNotContainsString('"pass"', $content);
    $decoded = json_decode($content, TRUE);
    $this->assertTrue($decoded['success']);
    $this->assertEquals($reference, $decoded['outputs']['account']);
    $this->assertEquals([$reference], $decoded['outputs']['accounts']);
    // A map keyed from zero serializes as an object (#3583073), and the
    // entity inside it is still replaced by its reference.
    $this->assertStringContainsString('"record":{"0":{"entity_type":"user"', $content);
    $this->assertEquals([$reference], $decoded['outputs']['record']);

    $command->runTool('account_output_tool', [
      'input' => ['{"account_id":' . $account->id() . '}'],
      'json' => FALSE,
      'uid' => 1,
    ]);
    $content = $output->fetch();
    $this->assertStringNotContainsString($password_hash, $content);
    $this->assertStringContainsString('account: {"entity_type":"user","id":', $content);
    $this->assertStringContainsString('"uuid":"' . $account->uuid() . '","label":"sample"}', $content);
    $this->assertStringContainsString('accounts: [{"entity_type":"user"', $content);
    $this->assertStringContainsString('record: {"0":{"entity_type":"user"', $content);
  }

  /**
   * Tests failure messages print as plain text, not HTML entities.
   */
  public function testFailureMessagePrintsPlainText(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->runTool('quoted_failure_tool', [
      'input' => [],
      'json' => FALSE,
      'uid' => 1,
    ]);
    $content = $output->fetch();
    $this->assertStringContainsString('The "subject" value is not usable.', $content);
    $this->assertStringNotContainsString('&quot;', $content);

    $command->runTool('quoted_failure_tool', [
      'input' => [],
      'json' => TRUE,
      'uid' => 1,
    ]);
    $decoded = json_decode($output->fetch(), TRUE);
    $this->assertSame('Tool execution failed due to invalid input: The "subject" value is not usable.', $decoded['message']);
  }

  /**
   * Tests the entity violation names the invoker's workaround.
   *
   * The drush invoker declares no handle capability, so a scalar passed for
   * an entity input gets the hint alongside the violation (#3583046).
   */
  public function testEntityViolationNamesTheWorkaround(): void {
    // The fixture's entity input is node-typed; the data type plugin needs
    // the module, though nothing is saved or loaded.
    $this->enableModules(['field', 'text', 'node']);
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('comprehensive_types_tool', [
      'input' => ['entity=not-an-entity'],
      'json' => FALSE,
      'uid' => 1,
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString('The Entity input must be an entity. The drush invoker cannot pass entities; use a tool that takes an entity type and ID instead.', $content);
  }

  /**
   * Tests running a nonexistent tool.
   */
  public function testRunToolNotFound(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('nonexistent_tool');

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString("Tool 'nonexistent_tool' not found.", $content);
  }

  /**
   * Tests that omitting --uid runs as the anonymous user.
   */
  public function testRunToolNoUidRunsAsAnonymous(): void {
    $accountSwitcher = $this->container->get('account_switcher');
    $admin = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->load(1);
    $this->assertNotNull($admin);
    $accountSwitcher->switchTo($admin);

    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    try {
      $result = $command->runTool('current_user_tool', [
        'input' => [],
        'json' => TRUE,
        'uid' => NULL,
      ]);
    }
    finally {
      $restoredUid = (int) $this->container->get('current_user')->id();
      $accountSwitcher->switchBack();
    }

    $content = $output->fetch();
    $decoded = json_decode($content, TRUE);
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertNotNull($decoded);
    $this->assertTrue($decoded['success']);
    $this->assertEquals(0, $decoded['outputs']['uid']);
    $this->assertSame(1, $restoredUid);
  }

  /**
   * Tests that an explicit --uid=0 runs as the anonymous user.
   *
   * The Drush option layer delivers a literal --uid=0 as boolean FALSE for
   * options whose default is NULL.
   */
  public function testRunToolExplicitZeroUidRunsAsAnonymous(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('current_user_tool', [
      'input' => [],
      'json' => TRUE,
      'uid' => FALSE,
    ]);

    $content = $output->fetch();
    $decoded = json_decode($content, TRUE);
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertNotNull($decoded);
    $this->assertTrue($decoded['success']);
    $this->assertEquals(0, $decoded['outputs']['uid']);
  }

  /**
   * Tests that --uid switches to the given account.
   */
  public function testRunToolUidSwitchesAccount(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('current_user_tool', [
      'input' => [],
      'json' => TRUE,
      'uid' => 1,
    ]);

    $content = $output->fetch();
    $decoded = json_decode($content, TRUE);
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertNotNull($decoded);
    $this->assertTrue($decoded['success']);
    $this->assertEquals(1, $decoded['outputs']['uid']);
  }

  /**
   * Tests that a permission-gated tool denies access without --uid.
   */
  public function testRunToolNoUidPermissionGated(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('permission_gated_tool');

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString("Access denied for tool 'permission_gated_tool': The 'administer users' permission is required.", $content);
    $this->assertStringNotContainsString('Running tool:', $content);
  }

  /**
   * Tests that a denial says it ran as anonymous only when --uid was omitted.
   *
   * An explicit --uid=0 and a denial for a real account keep the plain
   * message.
   */
  public function testRunToolAccessDeniedAnonymousHint(): void {
    $hint = 'Ran as anonymous (uid 0); pass --uid=<id> to run as a user.';
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->runTool('permission_gated_tool', ['input' => [], 'json' => TRUE, 'uid' => NULL]);
    $decoded = json_decode($output->fetch(), TRUE);
    $this->assertSame("Access denied for tool 'permission_gated_tool': The 'administer users' permission is required. $hint", $decoded['error']);
    $this->assertSame('access_denied', $decoded['error_type']);

    $command->runTool('permission_gated_tool', ['input' => [], 'json' => TRUE, 'uid' => FALSE]);
    $decoded = json_decode($output->fetch(), TRUE);
    $this->assertSame("Access denied for tool 'permission_gated_tool': The 'administer users' permission is required.", $decoded['error']);

    $command->runTool('access_denied_tool', ['input' => [], 'json' => FALSE, 'uid' => 1]);
    $this->assertStringNotContainsString($hint, $output->fetch());
  }

  /**
   * Tests that a denial reports the reason the tool gives.
   */
  public function testRunToolAccessDeniedReason(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('access_denied_tool', [
      'input' => [],
      'json' => FALSE,
      'uid' => 1,
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString("Access denied for tool 'access_denied_tool': Denied by design.", $content);
    $this->assertStringNotContainsString('Running tool:', $content);
  }

  /**
   * Tests that a missing required input is reported as such, not as a denial.
   *
   * The command validates before it checks access because access() fails
   * closed with a generic reason on invalid input (#3582961), which would
   * otherwise report the missing input as a permission problem.
   */
  public function testRunToolMissingRequiredInput(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('numeric_input_tool', [
      'input' => [],
      'json' => FALSE,
      'uid' => 1,
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString("Input validation failed for tool 'numeric_input_tool': Input number: This value should not be null.", $content);
    $this->assertStringNotContainsString('Access denied', $content);
    $this->assertStringNotContainsString('Running tool:', $content);
  }

  /**
   * Tests that JSON output names why the command failed.
   */
  #[DataProvider('errorTypeProvider')]
  public function testRunToolJsonErrorType(string $tool_id, array $options, string $expected_type, string $expected_error): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool($tool_id, $options);

    $decoded = json_decode($output->fetch(), TRUE);
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertNotNull($decoded);
    $this->assertFalse($decoded['success']);
    $this->assertSame($expected_error, $decoded['error']);
    $this->assertSame($expected_type, $decoded['error_type']);
    $this->assertArrayNotHasKey('outputs', $decoded);
  }

  /**
   * Provides one failing invocation per error type the command reports.
   */
  public static function errorTypeProvider(): array {
    return [
      'unknown tool' => [
        'nonexistent_tool',
        ['input' => [], 'json' => TRUE, 'uid' => NULL],
        'tool_not_found',
        "Tool 'nonexistent_tool' not found.",
      ],
      'invalid --uid' => [
        'no_requirements_tool',
        ['input' => [], 'json' => TRUE, 'uid' => 'admin'],
        'invalid_option',
        "Invalid --uid value 'admin': expected a non-negative integer user ID.",
      ],
      'invalid --input' => [
        'greeting_tool',
        ['input' => ['bogus'], 'json' => TRUE, 'uid' => NULL],
        'invalid_option',
        "Invalid input 'bogus': expected JSON or a name=value pair.",
      ],
      'unknown user' => [
        'no_requirements_tool',
        ['input' => [], 'json' => TRUE, 'uid' => 99],
        'user_not_found',
        "User '99' not found.",
      ],
      'rejected input' => [
        'locked_input_tool',
        ['input' => ['{"api_mode":"changed"}'], 'json' => TRUE, 'uid' => 1],
        'invalid_input',
        "Error setting input 'api_mode': The api_mode input is locked and cannot be changed.",
      ],
      'missing required input' => [
        'numeric_input_tool',
        ['input' => [], 'json' => TRUE, 'uid' => 1],
        'invalid_input',
        "Input validation failed for tool 'numeric_input_tool': Input number: This value should not be null.",
      ],
      'access denied' => [
        'access_denied_tool',
        ['input' => [], 'json' => TRUE, 'uid' => 1],
        'access_denied',
        "Access denied for tool 'access_denied_tool': Denied by design.",
      ],
      'invalid output' => [
        'invalid_output_tool',
        ['input' => [], 'json' => TRUE, 'uid' => 1],
        'execution_failed',
        'Execution failed: Invalid values given. Values must be represented as an associative array.',
      ],
    ];
  }

  /**
   * Tests that a blocked --uid account is named as such in JSON output.
   */
  public function testRunToolBlockedUidJson(): void {
    $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create([
        'uid' => 2,
        'name' => 'blocked',
        'status' => 0,
      ])
      ->save();

    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('no_requirements_tool', [
      'input' => [],
      'json' => TRUE,
      'uid' => 2,
    ]);

    $decoded = json_decode($output->fetch(), TRUE);
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertNotNull($decoded);
    $this->assertFalse($decoded['success']);
    $this->assertSame("User '2' is blocked.", $decoded['error']);
    $this->assertSame('user_blocked', $decoded['error_type']);
  }

  /**
   * Tests that a tool's own failure result is a result, not an error.
   *
   * That includes an exception thrown inside doExecute(): ToolBase catches
   * it and reports a failed result, so no error_type is involved.
   */
  #[DataProvider('failedResultProvider')]
  public function testRunToolFailedResultJsonHasNoErrorType(string $tool_id): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool($tool_id, [
      'input' => [],
      'json' => TRUE,
      'uid' => 1,
    ]);

    $decoded = json_decode($output->fetch(), TRUE);
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertNotNull($decoded);
    $this->assertFalse($decoded['success']);
    $this->assertArrayHasKey('message', $decoded);
    $this->assertArrayNotHasKey('error', $decoded);
    $this->assertArrayNotHasKey('error_type', $decoded);
  }

  /**
   * Provides tools whose execution ends in a failure result.
   */
  public static function failedResultProvider(): array {
    return [
      'failure result' => ['failing_tool'],
      'exception in doExecute()' => ['throwing_tool'],
    ];
  }

  /**
   * Tests that a permission-gated tool runs with a permitted --uid.
   */
  public function testRunToolUidWithPermission(): void {
    $this->enableModules(['system']);
    Role::create(['id' => 'tool_runner', 'label' => 'Tool runner'])
      ->grantPermission('administer users')
      ->save();
    $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create([
        'uid' => 3,
        'name' => 'runner',
        'status' => 1,
        'roles' => ['tool_runner'],
      ])
      ->save();

    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('permission_gated_tool', [
      'input' => [],
      'json' => FALSE,
      'uid' => 3,
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringContainsString('Permission granted.', $content);
  }

  /**
   * Tests running a tool as a blocked user.
   */
  public function testRunToolBlockedUid(): void {
    $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create([
        'uid' => 2,
        'name' => 'blocked',
        'status' => 0,
      ])
      ->save();

    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('no_requirements_tool', [
      'input' => [],
      'json' => FALSE,
      'uid' => 2,
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString("User '2' is blocked.", $content);
    $this->assertStringNotContainsString('Running tool:', $content);
  }

  /**
   * Tests running a tool as a nonexistent user.
   */
  public function testRunToolUnknownUid(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('no_requirements_tool', [
      'input' => [],
      'json' => FALSE,
      'uid' => 99,
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString("User '99' not found.", $content);
    $this->assertStringNotContainsString('Running tool:', $content);
  }

  /**
   * Tests that a numeric-prefixed garbage --uid fails instead of becoming 1.
   */
  public function testRunToolInvalidUidNumericPrefix(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('no_requirements_tool', [
      'input' => [],
      'json' => FALSE,
      'uid' => '1abc',
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString("Invalid --uid value '1abc': expected a non-negative integer user ID.", $content);
    $this->assertStringNotContainsString('Running tool:', $content);
    $this->assertStringNotContainsString('Success:', $content);
  }

  /**
   * Tests that a non-numeric --uid fails instead of silently becoming 0.
   */
  public function testRunToolInvalidUidNonNumeric(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('no_requirements_tool', [
      'input' => [],
      'json' => FALSE,
      'uid' => 'admin',
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString("Invalid --uid value 'admin': expected a non-negative integer user ID.", $content);
    $this->assertStringNotContainsString('Running tool:', $content);
    $this->assertStringNotContainsString('Success:', $content);
  }

  /**
   * Tests that a bare --uid flag (boolean TRUE) fails instead of becoming 1.
   *
   * A bare `--uid` with no value reaches the command callback as boolean
   * TRUE from the Drush/Console option layer, which the old `(int)` cast
   * silently mapped to uid 1.
   */
  public function testRunToolInvalidUidBareFlag(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('no_requirements_tool', [
      'input' => [],
      'json' => FALSE,
      'uid' => TRUE,
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString("Invalid --uid value 'true': expected a non-negative integer user ID.", $content);
    $this->assertStringNotContainsString('Running tool:', $content);
    $this->assertStringNotContainsString('Success:', $content);
  }

  /**
   * Tests that a negative --uid fails with the invalid-value message.
   */
  public function testRunToolInvalidUidNegative(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('no_requirements_tool', [
      'input' => [],
      'json' => FALSE,
      'uid' => '-1',
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString("Invalid --uid value '-1': expected a non-negative integer user ID.", $content);
    $this->assertStringNotContainsString('Running tool:', $content);
    $this->assertStringNotContainsString('Success:', $content);
  }

  /**
   * Tests that a user ID larger than PHP can represent fails before casting.
   */
  public function testRunToolInvalidUidOverflow(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);
    $uid = (string) PHP_INT_MAX . '0';

    $result = $command->runTool('no_requirements_tool', [
      'input' => [],
      'json' => FALSE,
      'uid' => $uid,
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString("Invalid --uid value '$uid'", $content);
    $this->assertStringNotContainsString('Running tool:', $content);
  }

  /**
   * Tests valid integer UID representations and the selected account.
   */
  #[DataProvider('validUidProvider')]
  public function testRunToolValidUid(string $uid, int $expectedUid): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('current_user_tool', [
      'input' => [],
      'json' => TRUE,
      'uid' => $uid,
    ]);

    $content = $output->fetch();
    $decoded = json_decode($content, TRUE);
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertNotNull($decoded);
    $this->assertTrue($decoded['success']);
    $this->assertSame($expectedUid, $decoded['outputs']['uid']);
  }

  /**
   * Provides valid non-negative integer UID representations.
   */
  public static function validUidProvider(): array {
    return [
      'decimal string' => ['1', 1],
      'positive sign' => ['+1', 1],
      'surrounding whitespace' => [' 1 ', 1],
      'zero' => ['0', 0],
    ];
  }

  /**
   * Tests that a leading-zero UID fails without executing a tool.
   */
  public function testRunToolLeadingZeroUid(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('current_user_tool', [
      'input' => [],
      'json' => TRUE,
      'uid' => '01',
    ]);

    $decoded = json_decode($output->fetch(), TRUE);
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertFalse($decoded['success']);
    $this->assertStringContainsString('Invalid --uid value', $decoded['error']);
    $this->assertSame('invalid_option', $decoded['error_type']);
    $this->assertArrayNotHasKey('outputs', $decoded);
  }

  /**
   * Tests that a malformed --input token fails loudly.
   */
  public function testRunToolMalformedInputToken(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('greeting_tool', [
      'input' => ['bogus'],
      'json' => FALSE,
      'uid' => NULL,
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString("Invalid input 'bogus'", $content);
    $this->assertStringNotContainsString('Running tool:', $content);
  }

  /**
   * Tests that scalar and null JSON inputs fail with an input-shape error.
   */
  #[DataProvider('scalarJsonInputProvider')]
  public function testRunToolScalarJsonInput(string $value): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('greeting_tool', [
      'input' => [$value],
      'json' => FALSE,
      'uid' => NULL,
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString("Invalid input '$value': JSON input must be an object containing named input values.", $content);
    $this->assertStringNotContainsString('Running tool:', $content);
  }

  /**
   * Provides valid JSON that does not decode to an object.
   */
  public static function scalarJsonInputProvider(): array {
    return [
      'integer' => ['42'],
      'null' => ['null'],
    ];
  }

  /**
   * Tests that positional JSON input fails instead of causing a TypeError.
   */
  public function testRunToolJsonListInput(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('greeting_tool', [
      'input' => ['["World"]'],
      'json' => FALSE,
      'uid' => NULL,
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString('JSON input must be an object containing named input values.', $content);
    $this->assertStringNotContainsString('Running tool:', $content);
  }

  /**
   * Tests that a numeric JSON input name fails without a TypeError.
   */
  public function testRunToolNumericJsonInputName(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('greeting_tool', [
      'input' => ['{"0":"World"}'],
      'json' => TRUE,
      'uid' => NULL,
    ]);

    $content = $output->fetch();
    $decoded = json_decode($content, TRUE);
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertNotNull($decoded);
    $this->assertFalse($decoded['success']);
    $this->assertSame("Invalid input name '0': names must be non-empty and must not be integer keys.", $decoded['error']);
    $this->assertSame('invalid_option', $decoded['error_type']);
  }

  /**
   * Tests that a numeric name=value input fails without a TypeError.
   */
  public function testRunToolNumericKeyValueInputName(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('greeting_tool', [
      'input' => ['0=World'],
      'json' => TRUE,
      'uid' => NULL,
    ]);

    $content = $output->fetch();
    $decoded = json_decode($content, TRUE);
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertNotNull($decoded);
    $this->assertFalse($decoded['success']);
    $this->assertSame("Invalid input name '0': names must be non-empty and must not be integer keys.", $decoded['error']);
    $this->assertSame('invalid_option', $decoded['error_type']);
  }

  /**
   * Tests that a direct scalar-zero input is not treated as no input.
   */
  public function testRunToolDirectScalarZeroInput(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->runTool('greeting_tool', [
      'input' => '0',
      'json' => TRUE,
      'uid' => NULL,
    ]);

    $content = $output->fetch();
    $decoded = json_decode($content, TRUE);
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertNotNull($decoded);
    $this->assertFalse($decoded['success']);
    $this->assertStringContainsString("Invalid input '0'", $decoded['error']);
    $this->assertSame('invalid_option', $decoded['error_type']);
  }

  /**
   * Tests running a tool with JSON output hides the "Running tool" message.
   */
  public function testRunToolJsonOutputHidesRunningMessage(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->runTool('no_requirements_tool', [
      'input' => [],
      'json' => TRUE,
      'uid' => 1,
    ]);

    $content = $output->fetch();
    $this->assertStringNotContainsString('Running tool:', $content);
    $decoded = json_decode($content, TRUE);
    $this->assertNotNull($decoded);
    $this->assertTrue($decoded['success']);
  }

  /**
   * Tests running a tool with an optional map input omitted.
   */
  public function testRunToolWithOptionalMapOmitted(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->runTool('optional_map_tool', [
      'input' => ['{"name":"Alice"}'],
      'json' => TRUE,
      'uid' => 1,
    ]);

    $content = $output->fetch();
    $decoded = json_decode($content, TRUE);
    $this->assertNotNull($decoded);
    $this->assertTrue($decoded['success']);
    $this->assertStringContainsString('Name: Alice', $decoded['outputs']['result']);
  }

}
