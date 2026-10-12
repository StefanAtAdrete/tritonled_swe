<?php

declare(strict_types=1);

namespace Drupal\tool\Drush\Commands;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\tool\Drush\RequirementStatus;
use Drupal\tool\Drush\UnmetFilter;
use Drupal\tool\Tool\ToolDefinition;
use Drupal\tool\Tool\ToolManager;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Shared helper methods for Tool Drush commands.
 */
trait ToolOutputTrait {

  /**
   * Probes the requirements of every listed tool.
   *
   * @param \Drupal\tool\Tool\ToolManager $manager
   *   The tool plugin manager.
   * @param \Drupal\tool\Tool\ToolDefinition[] $definitions
   *   The tool definitions keyed by plugin ID.
   *
   * @return \Drupal\tool\Drush\RequirementStatus[]
   *   The statuses keyed by plugin ID.
   */
  private function requirementStatuses(ToolManager $manager, array $definitions): array {
    $statuses = [];
    foreach (array_keys($definitions) as $plugin_id) {
      $statuses[$plugin_id] = RequirementStatus::forTool($manager, (string) $plugin_id);
    }
    return $statuses;
  }

  /**
   * Drops the tools the --unmet option excludes.
   *
   * @param \Drupal\tool\Tool\ToolDefinition[] $definitions
   *   The tool definitions keyed by plugin ID.
   * @param \Drupal\tool\Drush\RequirementStatus[] $statuses
   *   The statuses keyed by plugin ID.
   * @param \Drupal\tool\Drush\UnmetFilter $filter
   *   The parsed --unmet option.
   *
   * @return \Drupal\tool\Tool\ToolDefinition[]
   *   The definitions that stay in the listing.
   */
  private function filterByRequirementStatus(array $definitions, array $statuses, UnmetFilter $filter): array {
    return array_filter(
      $definitions,
      fn(string|int $plugin_id) => $filter->keeps($statuses[$plugin_id]),
      ARRAY_FILTER_USE_KEY,
    );
  }

  /**
   * Builds the per-tool fields shared by the JSON output of every command.
   *
   * @param string $plugin_id
   *   The tool plugin ID.
   * @param \Drupal\tool\Tool\ToolDefinition $definition
   *   The tool definition.
   * @param \Drupal\tool\Drush\RequirementStatus $requirements
   *   The tool's requirement status.
   *
   * @return array{id: string, label: string, description: string, operation: string, destructive: bool, provider: string, requirements: array{met: bool, message: string|null}}
   *   The summary fields.
   */
  private function buildToolSummary(string $plugin_id, ToolDefinition $definition, RequirementStatus $requirements): array {
    return [
      'id' => $plugin_id,
      'label' => (string) $definition->getLabel(),
      'description' => (string) $definition->getDescription(),
      'operation' => $definition->getOperation()->value,
      'destructive' => $definition->isDestructive(),
      'provider' => $definition->getProvider() ?? 'unknown',
      'requirements' => $requirements->toArray(),
    ];
  }

  /**
   * Output tools as a JSON list of summaries.
   *
   * An empty listing is `[]`, so a consumer always gets one document.
   *
   * @param \Drupal\tool\Tool\ToolDefinition[] $definitions
   *   The tool definitions keyed by plugin ID.
   * @param \Drupal\tool\Drush\RequirementStatus[] $statuses
   *   The statuses keyed by plugin ID.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output interface.
   */
  private function outputToolsAsJson(array $definitions, array $statuses, OutputInterface $output): void {
    $tools = [];
    foreach ($definitions as $plugin_id => $definition) {
      $tools[] = $this->buildToolSummary((string) $plugin_id, $definition, $statuses[$plugin_id]);
    }
    $output->writeln(Json::encode($tools));
  }

  /**
   * Output tools as a table.
   *
   * @param \Drupal\tool\Tool\ToolDefinition[] $definitions
   *   The tool definitions keyed by plugin ID.
   * @param \Drupal\tool\Drush\RequirementStatus[] $statuses
   *   The statuses keyed by plugin ID.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output interface.
   */
  private function outputToolsAsTable(array $definitions, array $statuses, OutputInterface $output): void {
    $table = new Table($output);
    $table->setHeaders(['ID', 'Label', 'Operation', 'Requirements', 'Description']);

    foreach ($definitions as $plugin_id => $definition) {
      $description = (string) $definition->getDescription();
      $description = Unicode::truncate($description, 60, TRUE, TRUE);
      $table->addRow([
        $plugin_id,
        (string) $definition->getLabel(),
        $definition->getOperation()->value,
        $statuses[$plugin_id]->label(),
        $description,
      ]);
    }

    $table->render();
  }

  /**
   * Output tools as markdown format.
   *
   * @param \Drupal\tool\Tool\ToolDefinition[] $definitions
   *   The tool definitions keyed by plugin ID.
   * @param \Drupal\tool\Drush\RequirementStatus[] $statuses
   *   The statuses keyed by plugin ID.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output interface.
   * @param string|null $summary
   *   Optional summary line to display at the end.
   */
  private function outputToolsAsMarkdown(array $definitions, array $statuses, OutputInterface $output, ?string $summary = NULL): void {
    $output->writeln('# Tools');
    $output->writeln('');

    // Go through each definition and output it in markdown format.
    foreach ($definitions as $plugin_id => $definition) {
      $output->writeln('## ' . $plugin_id);
      $output->writeln('');
      $output->writeln('**Label:** ' . (string) $definition->getLabel());
      $output->writeln('**Operation:** ' . $definition->getOperation()->value);
      $output->writeln('**Requirements:** ' . $statuses[$plugin_id]->label());
      $output->writeln('');
      $output->writeln('**Description:**');
      $output->writeln((string) $definition->getDescription());
      $output->writeln('');
      $output->writeln('---');
      $output->writeln('');
    }

    if ($summary) {
      $output->writeln('*' . $summary . '*');
    }
    else {
      $output->writeln('*Total tools: ' . count($definitions) . '*');
    }
  }

  /**
   * Format an output value for display.
   *
   * @param mixed $value
   *   The value to format.
   *
   * @return string
   *   Formatted string.
   */
  private function formatOutputValue(mixed $value): string {
    $value = $this->referenceEntities($value);
    if (is_null($value)) {
      return 'null';
    }
    if (is_bool($value)) {
      return $value ? 'true' : 'false';
    }
    // An \ArrayObject is a map, which Json::encode() writes as an object.
    if (is_array($value) || $value instanceof \ArrayObject) {
      return Json::encode($value);
    }
    if (is_object($value)) {
      if (method_exists($value, '__toString')) {
        return (string) $value;
      }
      return get_class($value);
    }
    return (string) $value;
  }

  /**
   * Replaces each entity in an output value with a reference to it.
   *
   * An entity's field values can hold secrets, such as a user's password
   * hash, so an entity leaves the command as its type, ID, UUID and label
   * only. Entities inside a list or map are replaced too.
   *
   * @param mixed $value
   *   The output value.
   *
   * @return mixed
   *   The value, with every entity replaced by its reference.
   */
  private function referenceEntities(mixed $value): mixed {
    if ($value instanceof EntityInterface) {
      $label = $value->label();
      return [
        'entity_type' => $value->getEntityTypeId(),
        'id' => $value->id(),
        'uuid' => $value->uuid(),
        'label' => $label === NULL ? NULL : (string) $label,
      ];
    }
    if (is_array($value)) {
      return array_map($this->referenceEntities(...), $value);
    }
    // A map keyed from zero arrives wrapped, so it serializes as an object
    // (#3583073); its members still need the entity replacement.
    if ($value instanceof \ArrayObject) {
      return new \ArrayObject(array_map($this->referenceEntities(...), $value->getArrayCopy()));
    }
    return $value;
  }

}
