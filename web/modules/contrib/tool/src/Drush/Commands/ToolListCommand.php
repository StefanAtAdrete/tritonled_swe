<?php

declare(strict_types=1);

namespace Drupal\tool\Drush\Commands;

use Drupal\tool\Drush\UnmetFilter;
use Drupal\tool\Tool\ToolManager;
use Drupal\tool\Tool\ToolOperation;
use Drush\Attributes\Command;
use Drush\Attributes\Option;
use Drush\Attributes\Usage;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * List all available tools with their descriptions.
 */
final class ToolListCommand extends DrushCommands {

  use AutowireTrait;
  use ToolOutputTrait;

  /**
   * Constructs a ToolListCommand object.
   *
   * @param \Drupal\tool\Tool\ToolManager $toolManager
   *   The tool plugin manager.
   */
  public function __construct(
    #[Autowire(service: 'plugin.manager.tool')]
    private readonly ToolManager $toolManager,
  ) {
    parent::__construct();
  }

  /**
   * List all available tools with their descriptions.
   *
   * @param array $options
   *   Command options.
   */
  #[Command(name: 'tool:list', aliases: ['tools', 'tlist'])]
  #[Option(name: 'format', description: 'Output format: table (default), markdown, or json.')]
  #[Option(name: 'operation', description: 'Filter by operation: explain, read, transform, trigger, write.')]
  #[Option(name: 'unmet', description: 'Tools with unmet requirements: show (default), hide, or only.')]
  #[Usage(name: 'drush tool:list', description: 'List all available tools.')]
  #[Usage(name: 'drush tool:list --format=markdown', description: 'List all tools as markdown with full descriptions.')]
  #[Usage(name: 'drush tool:list --format=json', description: 'List all tools as a JSON list of summaries.')]
  #[Usage(name: 'drush tool:list --operation=read', description: 'List only read-only tools.')]
  #[Usage(name: 'drush tool:list --unmet=only', description: 'List only tools that still need configuration.')]
  public function listTools(
    array $options = [
      'format' => 'table',
      'operation' => NULL,
      'unmet' => 'show',
    ],
  ): int {
    $unmetOption = (string) ($options['unmet'] ?? UnmetFilter::Show->value);
    $unmetFilter = UnmetFilter::tryFrom($unmetOption);
    if (!$unmetFilter) {
      $this->io()->writeln('<error>Invalid unmet: ' . $unmetOption . '. Valid values: ' . UnmetFilter::describeValues() . '</error>');
      return self::EXIT_FAILURE;
    }

    $definitions = $this->toolManager->getDefinitions();

    // Filter by operation if specified.
    if (!empty($options['operation'])) {
      $operation = ToolOperation::tryFrom($options['operation']);
      if (!$operation) {
        $this->io()->writeln('<error>Invalid operation: ' . $options['operation'] . '. Valid values: ' . implode(', ', array_column(ToolOperation::cases(), 'value')) . '</error>');
        return self::EXIT_FAILURE;
      }
      $definitions = array_filter($definitions, fn($def) => $def->getOperation() === $operation);
    }

    $statuses = $this->requirementStatuses($this->toolManager, $definitions);
    $definitions = $this->filterByRequirementStatus($definitions, $statuses, $unmetFilter);

    if ($options['format'] === 'json') {
      $this->outputToolsAsJson($definitions, $statuses, $this->output());
      return self::EXIT_SUCCESS;
    }

    if ($options['format'] === 'markdown') {
      $this->outputToolsAsMarkdown($definitions, $statuses, $this->output());
      return self::EXIT_SUCCESS;
    }

    if (empty($definitions)) {
      $this->io()->writeln('<comment>No tools found.</comment>');
      return self::EXIT_SUCCESS;
    }

    $this->outputToolsAsTable($definitions, $statuses, $this->output());
    $this->io()->writeln('');
    $this->io()->writeln('<info>Total tools: ' . count($definitions) . '</info>');
    return self::EXIT_SUCCESS;
  }

}
