<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Drush;

use Drupal\Component\Serialization\Json;
use Drupal\tool\Drush\Commands\ToolListCommand;
use Drush\Commands\DrushCommands;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Tests the tool:list Drush command.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class ToolListCommandTest extends DrushCommandTestBase {

  /**
   * Creates a configured ToolListCommand instance.
   *
   * @param \Symfony\Component\Console\Input\ArrayInput $input
   *   The input.
   * @param \Symfony\Component\Console\Output\BufferedOutput $output
   *   The output.
   *
   * @return \Drupal\tool\Drush\Commands\ToolListCommand
   *   The command instance.
   */
  protected function createCommand(ArrayInput $input, BufferedOutput $output): ToolListCommand {
    $command = new ToolListCommand($this->toolManager);
    $this->configureCommand($command, $input, $output);
    return $command;
  }

  /**
   * Tests that tool:list outputs all available tools.
   */
  public function testListToolsTable(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->listTools();

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringContainsString('no_requirements_tool', $content);
    $this->assertStringContainsString('greeting_tool', $content);
    $this->assertStringContainsString('Total tools:', $content);
  }

  /**
   * Tests that tool:list rejects an invalid operation filter.
   */
  public function testListToolsInvalidOperation(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->listTools(['format' => 'table', 'operation' => 'bogus']);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString('Invalid operation: bogus', $content);
  }

  /**
   * Tests that tool:list with markdown format outputs markdown.
   */
  public function testListToolsMarkdown(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->listTools(['format' => 'markdown']);

    $content = $output->fetch();
    $this->assertStringContainsString('# Tools', $content);
    $this->assertStringContainsString('## no_requirements_tool', $content);
    $this->assertStringContainsString('## greeting_tool', $content);
    $this->assertStringContainsString('**Label:**', $content);
    $this->assertStringContainsString('**Operation:**', $content);
  }

  /**
   * Tests that the table shows each tool's requirement status.
   */
  public function testListToolsShowsRequirementStatus(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->listTools();

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringContainsString('Requirements', $content);
    $this->assertMatchesRegularExpression('/no_requirements_tool .*\| Met /', $content);
    $this->assertMatchesRegularExpression('/unmet_requirements_tool .*\| Test API key is not configured\. /', $content);
    $this->assertMatchesRegularExpression('/broken_constructor_tool .*\| Could not instantiate tool: Service "tool_test.missing" not found\. /', $content);
  }

  /**
   * Tests that markdown shows each tool's requirement status.
   */
  public function testListToolsMarkdownShowsRequirementStatus(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->listTools(['format' => 'markdown']);

    $content = $output->fetch();
    $this->assertStringContainsString('**Requirements:** Met', $content);
    $this->assertStringContainsString('**Requirements:** Test API key is not configured.', $content);
  }

  /**
   * Tests that --unmet=hide drops tools with unmet requirements.
   */
  public function testListToolsHideUnmet(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->listTools(['format' => 'table', 'unmet' => 'hide']);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringContainsString('no_requirements_tool', $content);
    $this->assertStringNotContainsString('unmet_requirements_tool', $content);
    $this->assertStringNotContainsString('broken_constructor_tool', $content);
    $this->assertStringContainsString('Total tools: ' . (count($this->toolManager->getDefinitions()) - 2), $content);
  }

  /**
   * Tests that --unmet=only lists just the tools with unmet requirements.
   */
  public function testListToolsOnlyUnmet(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->listTools(['format' => 'table', 'unmet' => 'only']);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringNotContainsString('no_requirements_tool', $content);
    $this->assertStringContainsString('unmet_requirements_tool', $content);
    $this->assertStringContainsString('broken_constructor_tool', $content);
    $this->assertStringContainsString('Total tools: 2', $content);
  }

  /**
   * Tests that tool:list rejects an invalid --unmet value.
   */
  public function testListToolsInvalidUnmet(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->listTools(['format' => 'table', 'unmet' => 'bogus']);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString('Invalid unmet: bogus. Valid values: show, hide, only', $content);
  }

  /**
   * Tests that --format=json prints one summary per tool and nothing else.
   */
  public function testListToolsJson(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->listTools(['format' => 'json']);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringNotContainsString('Total tools', $content);
    $tools = Json::decode($content);
    $this->assertIsList($tools);
    $this->assertCount(count($this->toolManager->getDefinitions()), $tools);

    $byId = array_column($tools, NULL, 'id');
    $this->assertSame([
      'id' => 'no_requirements_tool',
      'label' => 'No Requirements Tool',
      'description' => 'A test tool with no requirements.',
      'operation' => 'read',
      'destructive' => FALSE,
      'provider' => 'tool_test',
      'requirements' => ['met' => TRUE, 'message' => NULL],
    ], $byId['no_requirements_tool']);
    $this->assertSame(['met' => FALSE, 'message' => 'Test API key is not configured.'], $byId['unmet_requirements_tool']['requirements']);
    $this->assertArrayNotHasKey('input_schema', $byId['no_requirements_tool']);
    $this->assertArrayNotHasKey('permission', $byId['declared_permission_tool']);
  }

  /**
   * Tests that --format=json honors the operation and unmet filters.
   */
  public function testListToolsJsonAppliesFilters(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->listTools(['format' => 'json', 'operation' => 'read', 'unmet' => 'only']);

    $tools = Json::decode($output->fetch());
    $this->assertEqualsCanonicalizing(['broken_constructor_tool', 'unmet_requirements_tool'], array_column($tools, 'id'));
    $this->assertSame(['read'], array_unique(array_column($tools, 'operation')));
  }

  /**
   * Tests that a filter leaving no tools reports no tools found.
   */
  public function testListToolsFilterLeavesNoTools(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->listTools(['format' => 'table', 'operation' => 'trigger']);

    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertSame("No tools found.\n", $output->fetch());
  }

  /**
   * Tests that --format=markdown keeps its shape when nothing is listed.
   */
  public function testListToolsMarkdownNoTools(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->listTools(['format' => 'markdown', 'operation' => 'trigger']);

    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertSame("# Tools\n\n*Total tools: 0*\n", $output->fetch());
  }

  /**
   * Tests that --format=json prints an empty list when nothing is listed.
   */
  public function testListToolsJsonNoTools(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->listTools(['format' => 'json', 'operation' => 'trigger']);

    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertSame("[]\n", $output->fetch());
  }

  /**
   * Tests that tool:list includes the correct tool count.
   */
  public function testListToolsCount(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $definitions = $this->toolManager->getDefinitions();
    $command->listTools();

    $content = $output->fetch();
    $this->assertStringContainsString('Total tools: ' . count($definitions), $content);
  }

}
