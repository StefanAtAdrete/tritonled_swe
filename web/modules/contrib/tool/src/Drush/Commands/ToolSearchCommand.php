<?php

declare(strict_types=1);

namespace Drupal\tool\Drush\Commands;

use Drupal\tool\Drush\UnmetFilter;
use Drupal\tool\Tool\ToolManager;
use Drupal\tool\Tool\ToolOperation;
use Drush\Attributes\Argument;
use Drush\Attributes\Command;
use Drush\Attributes\Option;
use Drush\Attributes\Usage;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Search for tools by keywords.
 */
final class ToolSearchCommand extends DrushCommands {

  use AutowireTrait;
  use ToolOutputTrait;

  /**
   * Constructs a ToolSearchCommand object.
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
   * Search for tools by keywords.
   *
   * @param string $keywords
   *   Space-separated keywords to search for.
   * @param array $options
   *   Command options.
   */
  #[Command(name: 'tool:search', aliases: ['tsearch'])]
  #[Argument(name: 'keywords', description: 'Space-separated keywords to search for.')]
  #[Option(name: 'match', description: 'Match mode: or (default) or and.')]
  #[Option(name: 'format', description: 'Output format: table (default), markdown, or json.')]
  #[Option(name: 'operation', description: 'Filter by operation: explain, read, transform, trigger, write.')]
  #[Option(name: 'unmet', description: 'Tools with unmet requirements: show (default), hide, or only.')]
  #[Usage(name: 'drush tool:search "email send"', description: 'Search for tools matching "email" OR "send".')]
  #[Usage(name: 'drush tool:search "email send" --match=and', description: 'Search for tools matching "email" AND "send".')]
  #[Usage(name: 'drush tool:search "email send" --format=markdown', description: 'Search with full descriptions in markdown.')]
  #[Usage(name: 'drush tool:search "email" --format=json', description: 'Search and print the matches as a JSON list of summaries.')]
  #[Usage(name: 'drush tool:search "email" --operation=read', description: 'Search for read-only tools matching "email".')]
  #[Usage(name: 'drush tool:search "email" --unmet=hide', description: 'Search only tools whose requirements are met.')]
  public function searchTools(
    string $keywords,
    array $options = [
      'match' => 'or',
      'format' => 'table',
      'operation' => NULL,
      'unmet' => 'show',
    ],
  ): int {
    $searchTerms = array_filter(array_map('trim', explode(' ', strtolower($keywords))));

    if (empty($searchTerms)) {
      $this->io()->writeln('<error>Please provide at least one search keyword.</error>');
      return self::EXIT_FAILURE;
    }

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

    $useAndFilter = strtolower($options['match']) === 'and';
    $operator = $useAndFilter ? 'AND' : 'OR';

    $matchingTools = [];
    foreach ($definitions as $plugin_id => $definition) {
      /** @var \Drupal\tool\Tool\ToolDefinition $definition */
      $searchableText = strtolower(
        $plugin_id . ' ' .
        (string) $definition->getLabel() . ' ' .
        (string) $definition->getDescription()
      );

      if ($useAndFilter) {
        // AND filter: all keywords must match.
        $allMatch = TRUE;
        foreach ($searchTerms as $term) {
          if (!str_contains($searchableText, $term)) {
            $allMatch = FALSE;
            break;
          }
        }
        if ($allMatch) {
          $matchingTools[$plugin_id] = $definition;
        }
      }
      else {
        // OR filter: any keyword must match.
        foreach ($searchTerms as $term) {
          if (str_contains($searchableText, $term)) {
            $matchingTools[$plugin_id] = $definition;
            break;
          }
        }
      }
    }

    $statuses = $this->requirementStatuses($this->toolManager, $matchingTools);
    $matchingTools = $this->filterByRequirementStatus($matchingTools, $statuses, $unmetFilter);

    if ($options['format'] === 'json') {
      $this->outputToolsAsJson($matchingTools, $statuses, $this->output());
      return self::EXIT_SUCCESS;
    }

    $summary = 'Found ' . count($matchingTools) . ' tool(s) matching: ' . implode(" $operator ", $searchTerms);

    if ($options['format'] === 'markdown') {
      $this->outputToolsAsMarkdown($matchingTools, $statuses, $this->output(), $summary);
      return self::EXIT_SUCCESS;
    }

    if (empty($matchingTools)) {
      $this->io()->writeln('<comment>No tools found matching: ' . implode(" $operator ", $searchTerms) . '</comment>');
      return self::EXIT_SUCCESS;
    }

    $this->outputToolsAsTable($matchingTools, $statuses, $this->output());
    $this->io()->writeln('');
    $this->io()->writeln('<info>' . $summary . '</info>');
    return self::EXIT_SUCCESS;
  }

}
