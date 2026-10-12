<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Drush;

use Drupal\Component\Serialization\Json;
use Drupal\tool\Drush\Commands\ToolSearchCommand;
use Drush\Commands\DrushCommands;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Tests the tool:search Drush command.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class ToolSearchCommandTest extends DrushCommandTestBase {

  /**
   * Creates a configured ToolSearchCommand instance.
   *
   * @param \Symfony\Component\Console\Input\ArrayInput $input
   *   The input.
   * @param \Symfony\Component\Console\Output\BufferedOutput $output
   *   The output.
   *
   * @return \Drupal\tool\Drush\Commands\ToolSearchCommand
   *   The command instance.
   */
  protected function createCommand(ArrayInput $input, BufferedOutput $output): ToolSearchCommand {
    $command = new ToolSearchCommand($this->toolManager);
    $this->configureCommand($command, $input, $output);
    return $command;
  }

  /**
   * Tests searching with a keyword that matches tools.
   */
  public function testSearchFindsMatchingTools(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->searchTools('greeting');

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringContainsString('greeting_tool', $content);
    $this->assertStringContainsString('Found 1 tool(s) matching: greeting', $content);
  }

  /**
   * Tests that tool:search rejects an invalid operation filter.
   */
  public function testSearchInvalidOperation(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->searchTools('greeting', ['match' => 'or', 'format' => 'table', 'operation' => 'bogus']);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString('Invalid operation: bogus', $content);
  }

  /**
   * Tests that search results show each tool's requirement status.
   */
  public function testSearchShowsRequirementStatus(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->searchTools('requirements');

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringContainsString('Requirements', $content);
    $this->assertMatchesRegularExpression('/no_requirements_tool .*\| Met /', $content);
    $this->assertMatchesRegularExpression('/unmet_requirements_tool .*\| Test API key is not configured\. /', $content);
    $this->assertStringContainsString('Found 2 tool(s) matching: requirements', $content);
  }

  /**
   * Tests that --unmet=hide drops matching tools with unmet requirements.
   */
  public function testSearchHideUnmet(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->searchTools('requirements', [
      'match' => 'or',
      'format' => 'table',
      'operation' => NULL,
      'unmet' => 'hide',
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringContainsString('no_requirements_tool', $content);
    $this->assertStringNotContainsString('unmet_requirements_tool', $content);
    $this->assertStringContainsString('Found 1 tool(s) matching: requirements', $content);
  }

  /**
   * Tests that --unmet=only keeps just the matches with unmet requirements.
   */
  public function testSearchOnlyUnmet(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->searchTools('requirements', [
      'match' => 'or',
      'format' => 'markdown',
      'operation' => NULL,
      'unmet' => 'only',
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringNotContainsString('## no_requirements_tool', $content);
    $this->assertStringContainsString('## unmet_requirements_tool', $content);
    $this->assertStringContainsString('**Requirements:** Test API key is not configured.', $content);
    $this->assertStringContainsString('Found 1 tool(s) matching: requirements', $content);
  }

  /**
   * Tests that a filter leaving no matches reports no tools found.
   */
  public function testSearchUnmetFilterLeavesNoMatches(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->searchTools('greeting', [
      'match' => 'or',
      'format' => 'table',
      'operation' => NULL,
      'unmet' => 'only',
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringContainsString('No tools found matching: greeting', $content);
  }

  /**
   * Tests that an operation filter leaving no candidates reports the keywords.
   */
  public function testSearchOperationFilterLeavesNoMatches(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->searchTools('greeting', [
      'match' => 'or',
      'format' => 'table',
      'operation' => 'trigger',
      'unmet' => 'show',
    ]);

    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertSame("No tools found matching: greeting\n", $output->fetch());
  }

  /**
   * Tests that tool:search rejects an invalid --unmet value.
   */
  public function testSearchInvalidUnmet(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->searchTools('greeting', [
      'match' => 'or',
      'format' => 'table',
      'operation' => NULL,
      'unmet' => 'bogus',
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString('Invalid unmet: bogus. Valid values: show, hide, only', $content);
  }

  /**
   * Tests that --format=json prints the matches and no summary line.
   */
  public function testSearchJson(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->searchTools('requirements', [
      'match' => 'or',
      'format' => 'json',
      'operation' => NULL,
      'unmet' => 'show',
    ]);

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertStringNotContainsString('Found', $content);
    $tools = Json::decode($content);
    $this->assertIsList($tools);
    $byId = array_column($tools, NULL, 'id');
    $this->assertEqualsCanonicalizing(['no_requirements_tool', 'unmet_requirements_tool'], array_keys($byId));
    $this->assertSame(['met' => TRUE, 'message' => NULL], $byId['no_requirements_tool']['requirements']);
    $this->assertSame(['met' => FALSE, 'message' => 'Test API key is not configured.'], $byId['unmet_requirements_tool']['requirements']);
  }

  /**
   * Tests that --format=json with --unmet=hide drops the unmet matches.
   */
  public function testSearchJsonHideUnmet(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->searchTools('requirements', [
      'match' => 'or',
      'format' => 'json',
      'operation' => NULL,
      'unmet' => 'hide',
    ]);

    $this->assertSame(['no_requirements_tool'], array_column(Json::decode($output->fetch()), 'id'));
  }

  /**
   * Tests that --format=markdown keeps its shape when nothing matches.
   */
  public function testSearchMarkdownNoMatches(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->searchTools('nothing_matches_this', [
      'match' => 'or',
      'format' => 'markdown',
      'operation' => NULL,
      'unmet' => 'show',
    ]);

    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertSame("# Tools\n\n*Found 0 tool(s) matching: nothing_matches_this*\n", $output->fetch());
  }

  /**
   * Tests that --format=json prints an empty list when nothing matches.
   */
  public function testSearchJsonNoMatches(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->searchTools('nothing_matches_this', [
      'match' => 'or',
      'format' => 'json',
      'operation' => NULL,
      'unmet' => 'show',
    ]);

    $this->assertSame(DrushCommands::EXIT_SUCCESS, $result);
    $this->assertSame("[]\n", $output->fetch());
  }

  /**
   * Tests that tool:search rejects empty keywords.
   */
  public function testSearchEmptyKeywords(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $result = $command->searchTools('   ');

    $content = $output->fetch();
    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString('Please provide at least one search keyword.', $content);
  }

  /**
   * Tests OR search with multiple keywords.
   */
  public function testSearchOrFilter(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->searchTools('greeting requirements');

    $content = $output->fetch();
    $this->assertStringContainsString('greeting_tool', $content);
    $this->assertStringContainsString('no_requirements_tool', $content);
    $this->assertStringContainsString(' OR ', $content);
  }

  /**
   * Tests AND search with multiple keywords.
   */
  public function testSearchAndFilter(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->searchTools('test tool', ['match' => 'and', 'format' => 'table']);

    $content = $output->fetch();
    // All test tools have "test" and "tool" in their text.
    $this->assertStringContainsString(' AND ', $content);
  }

  /**
   * Tests AND search that is too restrictive matches nothing.
   */
  public function testSearchAndFilterNoResults(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->searchTools('greeting requirements', ['match' => 'AND', 'format' => 'table']);

    $content = $output->fetch();
    $this->assertStringContainsString('No tools found matching:', $content);
  }

  /**
   * Tests search with no matching keyword.
   */
  public function testSearchNoResults(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->searchTools('nonexistentkeyword');

    $content = $output->fetch();
    $this->assertStringContainsString('No tools found matching:', $content);
  }

  /**
   * Tests search with markdown output format.
   */
  public function testSearchMarkdownFormat(): void {
    $input = new ArrayInput([]);
    $output = new BufferedOutput();
    $command = $this->createCommand($input, $output);

    $command->searchTools('greeting', ['match' => 'or', 'format' => 'markdown']);

    $content = $output->fetch();
    $this->assertStringContainsString('# Tools', $content);
    $this->assertStringContainsString('## greeting_tool', $content);
  }

}
