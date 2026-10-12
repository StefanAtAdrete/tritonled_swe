<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Drush;

use Drupal\Component\Serialization\Json;
use Drupal\tool\Drush\Commands\ToolInfoCommand;
use Drush\Commands\DrushCommands;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Tests the tool:info Drush command.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class ToolInfoCommandTest extends DrushCommandTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'serialization',
    'tool',
    'tool_test',
    'user',
  ];

  /**
   * Creates a configured ToolInfoCommand instance.
   *
   * @param \Symfony\Component\Console\Input\ArrayInput $input
   *   The input.
   * @param \Symfony\Component\Console\Output\BufferedOutput $output
   *   The output.
   *
   * @return \Drupal\tool\Drush\Commands\ToolInfoCommand
   *   The command instance.
   */
  protected function createCommand(ArrayInput $input, BufferedOutput $output): ToolInfoCommand {
    $command = new ToolInfoCommand($this->toolManager, $this->container->get('tool.definition_serializer'), $this->container->get('user.permissions'));
    $this->configureCommand($command, $input, $output);
    return $command;
  }

  /**
   * Tests info for a tool with no inputs or outputs.
   */
  public function testToolInfoSimpleTool(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->toolInfo('no_requirements_tool');

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringContainsString('Tool: no_requirements_tool', $content);
    $this->assertStringContainsString('No Requirements Tool', $content);
    $this->assertStringContainsString('A test tool with no requirements.', $content);
    $this->assertStringContainsString('read', $content);
    $this->assertStringContainsString('No input definitions.', $content);
    $this->assertStringContainsString('No output definitions.', $content);

    // JSON keeps inputs and outputs as maps when there are none.
    $command->toolInfo('no_requirements_tool', ['format' => 'json']);
    $content = $output->fetch();
    $this->assertStringContainsString('"inputs":{}', $content);
    $this->assertStringContainsString('"outputs":{}', $content);
    $this->assertStringContainsString('"output_schema":{}', $content);
  }

  /**
   * Tests info for a tool with input and output definitions.
   */
  public function testToolInfoWithInputsAndOutputs(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->toolInfo('greeting_tool');

    $content = $output->fetch();
    $this->assertStringContainsString('Tool: greeting_tool', $content);
    $this->assertStringContainsString('Greeting Tool', $content);
    $this->assertStringContainsString('A test tool that generates a greeting message.', $content);
    $this->assertStringContainsString('transform', $content);
    $this->assertStringContainsString('Destructive:', $content);
    $this->assertStringContainsString('Yes', $content);

    // Input definitions.
    $this->assertStringContainsString('Input Definitions:', $content);
    $this->assertStringContainsString('name', $content);
    $this->assertStringContainsString('greeting', $content);
    $this->assertStringContainsString('string', $content);

    // Output definitions.
    $this->assertStringContainsString('Output Definitions:', $content);
    $this->assertStringContainsString('message', $content);
  }

  /**
   * Tests that the table shows whether requirements are met.
   */
  public function testToolInfoShowsRequirementStatus(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->toolInfo('no_requirements_tool');
    $this->assertStringContainsString('Requirements: Met', $output->fetch());

    $result = $command->toolInfo('unmet_requirements_tool');
    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringContainsString('Requirements: Test API key is not configured.', $content);

    $command->toolInfo('broken_constructor_tool');
    $this->assertStringContainsString('Requirements: Could not instantiate tool: Service "tool_test.missing" not found.', $output->fetch());
  }

  /**
   * Tests that markdown shows whether requirements are met.
   */
  public function testToolInfoMarkdownShowsRequirementStatus(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->toolInfo('unmet_requirements_tool', ['format' => 'markdown']);

    $this->assertStringContainsString('**Requirements:** Test API key is not configured.', $output->fetch());
  }

  /**
   * Tests that JSON carries the requirement status.
   */
  public function testToolInfoJsonIncludesRequirementStatus(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->toolInfo('no_requirements_tool', ['format' => 'json']);
    $data = Json::decode($output->fetch());
    $this->assertSame(['met' => TRUE, 'message' => NULL], $data['requirements']);

    $result = $command->toolInfo('unmet_requirements_tool', ['format' => 'json']);
    $data = Json::decode($output->fetch());
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertSame(['met' => FALSE, 'message' => 'Test API key is not configured.'], $data['requirements']);
    $this->assertArrayHasKey('input_schema', $data);

    $result = $command->toolInfo('broken_constructor_tool', ['format' => 'json']);
    $data = Json::decode($output->fetch());
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertFalse($data['requirements']['met']);
    $this->assertArrayNotHasKey('input_schema', $data);
    $this->assertArrayNotHasKey('output_schema', $data);
  }

  /**
   * Tests that every format shows the permission a tool declares.
   *
   * A misspelled permission denies every account except user 1 and accounts
   * with an administrator role, which pass every permission check, so it goes
   * unnoticed on a site tested as an administrator. The declaration has to be
   * visible.
   */
  public function testToolInfoShowsDeclaredPermission(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->toolInfo('declared_permission_tool');
    $this->assertStringContainsString('Permission: operate test tools,audit test tools', $output->fetch());

    $command->toolInfo('declared_permission_tool', ['format' => 'markdown']);
    $this->assertStringContainsString('**Permission:** operate test tools,audit test tools', $output->fetch());

    $command->toolInfo('declared_permission_tool', ['format' => 'json']);
    $this->assertSame('operate test tools,audit test tools', Json::decode($output->fetch())['permission']);

    // A tool that declares none says so in every format.
    $command->toolInfo('no_requirements_tool');
    $this->assertStringContainsString('Permission: None', $output->fetch());

    $command->toolInfo('no_requirements_tool', ['format' => 'markdown']);
    $this->assertStringContainsString('**Permission:** None', $output->fetch());

    $command->toolInfo('no_requirements_tool', ['format' => 'json']);
    $data = Json::decode($output->fetch());
    $this->assertArrayHasKey('permission', $data);
    $this->assertNull($data['permission']);
  }

  /**
   * Tests that the table and markdown flag a name no permission matches.
   */
  public function testToolInfoFlagsUnknownPermission(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->toolInfo('derived_permission_tool:misspelled');
    $this->assertStringContainsString("Warning: 'operate test tool' matches no defined permission.", $output->fetch());

    $command->toolInfo('derived_permission_tool:misspelled', ['format' => 'markdown']);
    $this->assertStringContainsString("**Warning:** 'operate test tool' matches no defined permission.", $output->fetch());

    // Names that match defined permissions are not flagged.
    $command->toolInfo('declared_permission_tool');
    $this->assertStringNotContainsString('matches no defined permission', $output->fetch());

    $command->toolInfo('declared_permission_tool', ['format' => 'markdown']);
    $this->assertStringNotContainsString('matches no defined permission', $output->fetch());
  }

  /**
   * Tests info for a nonexistent tool.
   */
  public function testToolInfoNotFound(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->toolInfo('nonexistent_tool');

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString("Tool 'nonexistent_tool' not found.", $content);
  }

  /**
   * Tests that info shows a usage example.
   */
  public function testToolInfoUsageExample(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->toolInfo('greeting_tool');

    $content = $output->fetch();
    $this->assertStringContainsString('Usage Example:', $content);
    $this->assertStringContainsString('drush tool:run greeting_tool', $content);
    $this->assertStringContainsString('--input=', $content);
  }

  /**
   * Tests usage example for a tool with no inputs.
   */
  public function testToolInfoUsageExampleNoInputs(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->toolInfo('no_requirements_tool');

    $content = $output->fetch();
    $this->assertStringContainsString('Usage Example:', $content);
    $this->assertStringContainsString('drush tool:run no_requirements_tool', $content);
    $this->assertStringNotContainsString('--input=', $content);
  }

  /**
   * Tests info in markdown format for a tool with inputs and outputs.
   */
  public function testToolInfoMarkdown(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->toolInfo('greeting_tool', ['format' => 'markdown']);

    $content = $output->fetch();
    // Header and metadata.
    $this->assertStringContainsString('# greeting_tool', $content);
    $this->assertStringContainsString('**Label:** Greeting Tool', $content);
    $this->assertStringContainsString('**Destructive:** Yes', $content);

    // Markdown tables.
    $this->assertStringContainsString('## Input Definitions', $content);
    $this->assertStringContainsString('| name |', $content);
    $this->assertStringContainsString('| greeting |', $content);
    $this->assertStringContainsString('## Output Definitions', $content);
    $this->assertStringContainsString('| message |', $content);

    // Usage example in code block.
    $this->assertStringContainsString('```bash', $content);
    $this->assertStringContainsString('drush tool:run greeting_tool', $content);
    $this->assertStringContainsString('```', $content);
  }

  /**
   * Tests that JSON output carries the input and output JSON Schemas.
   */
  public function testToolInfoJsonIncludesSchemas(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->toolInfo('schema_matrix_tool', ['format' => 'json']);

    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $data = Json::decode($output->fetch());

    // The flat inputs stay as they were: downstream skills read them.
    $this->assertSame('string', $data['inputs']['enum_string']['type']);
    $this->assertFalse($data['inputs']['enum_string']['locked']);
    $this->assertSame('map', $data['inputs']['typed_map']['type']);

    $properties = $data['input_schema']['properties'];
    $this->assertSame('object', $data['input_schema']['type']);
    $this->assertSame(['alpha', 'beta', 'gamma', NULL], $properties['enum_string']['enum']);
    $this->assertSame([1, 2, 3, NULL], $properties['enum_integer']['enum']);
    $this->assertSame(['title', 'limit', 'verbose'], array_keys($properties['typed_map']['properties']));
    $this->assertSame(['title'], $properties['typed_map']['required']);
    $this->assertSame(1, $properties['range_integer']['minimum']);
    $this->assertSame(10, $properties['range_integer']['maximum']);

    $this->assertSame(['received'], array_keys($data['output_schema']));
    $this->assertSame('Received', $data['output_schema']['received']['title']);
  }

  /**
   * Tests that the table shows constraints and map properties.
   */
  public function testToolInfoTableShowsConstraints(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->toolInfo('schema_matrix_tool');

    $content = $output->fetch();
    $this->assertStringContainsString('Constraints', $content);
    $this->assertStringContainsString('alpha|beta|gamma', $content);
    $this->assertStringContainsString('minimum 1, maximum 10', $content);
    $this->assertStringContainsString('└ title', $content);
    $this->assertStringContainsString('└ inner', $content);
    $this->assertStringContainsString('└ count', $content);
    // An optional multiple property is typed ['array', 'null'] in the schema.
    $this->assertMatchesRegularExpression('/└ flags\s+\|\s+boolean\s+\|\s+No\s+\|\s+Yes\s+\|/', $content);
  }

  /**
   * Tests that markdown shows constraints and map properties.
   */
  public function testToolInfoMarkdownShowsConstraints(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->toolInfo('schema_matrix_tool', ['format' => 'markdown']);

    $content = $output->fetch();
    $this->assertStringContainsString('| Name | Type | Required | Multiple | Locked | Constraints | Description |', $content);
    // A raw pipe in a cell would split the enum across columns. The input
    // is optional, so its enum lists null (#3583072).
    $this->assertStringContainsString('| enum_string | string | No | No | No | alpha\|beta\|gamma\|null |', $content);
    $this->assertStringNotContainsString('| alpha|beta|gamma |', $content);
    // Count on a map is advertised as property bounds.
    $this->assertStringContainsString('| bounded_map | map | No | No | No | minProperties 1, maxProperties 2 |', $content);
    $this->assertStringContainsString('|   └ title | string | Yes |', $content);
    $this->assertStringContainsString('|   └ flags | boolean | No | Yes |', $content);
  }

  /**
   * Tests info in markdown format for a tool with no inputs.
   */
  public function testToolInfoMarkdownNoInputs(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->toolInfo('no_requirements_tool', ['format' => 'markdown']);

    $content = $output->fetch();
    $this->assertStringContainsString('# no_requirements_tool', $content);
    $this->assertStringContainsString('*No input definitions.*', $content);
    $this->assertStringContainsString('*No output definitions.*', $content);
  }

}
