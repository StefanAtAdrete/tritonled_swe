<?php

declare(strict_types=1);

namespace Drupal\tool\Drush\Commands;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Unicode;
use Drupal\tool\Drush\DrushInvoker;
use Drupal\tool\Drush\RequirementStatus;
use Drupal\tool\Normalizer\ToolDefinitionSerializer;
use Drupal\tool\Tool\ToolDefinition;
use Drupal\tool\Tool\ToolManager;
use Drupal\user\PermissionHandlerInterface;
use Drush\Attributes\Argument;
use Drush\Attributes\Command;
use Drush\Attributes\Option;
use Drush\Attributes\Usage;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Get detailed information about a specific tool.
 */
final class ToolInfoCommand extends DrushCommands {

  use AutowireTrait;
  use ToolOutputTrait;

  /**
   * Constructs a ToolInfoCommand object.
   *
   * @param \Drupal\tool\Tool\ToolManager $toolManager
   *   The tool plugin manager.
   * @param \Drupal\tool\Normalizer\ToolDefinitionSerializer $definitionSerializer
   *   The tool definition serializer.
   * @param \Drupal\user\PermissionHandlerInterface $permissionHandler
   *   The permission handler.
   */
  public function __construct(
    #[Autowire(service: 'plugin.manager.tool')]
    private readonly ToolManager $toolManager,
    #[Autowire(service: 'tool.definition_serializer')]
    private readonly ToolDefinitionSerializer $definitionSerializer,
    #[Autowire(service: 'user.permissions')]
    private readonly PermissionHandlerInterface $permissionHandler,
  ) {
    parent::__construct();
  }

  /**
   * Get detailed information about a specific tool.
   *
   * @param string $tool_id
   *   The tool plugin ID.
   * @param array $options
   *   Command options.
   */
  #[Command(name: 'tool:info', aliases: ['tinfo'])]
  #[Argument(name: 'tool_id', description: 'The tool plugin ID.')]
  #[Option(name: 'format', description: 'Output format: table (default), markdown, or json.')]
  #[Usage(name: 'drush tool:info send_email', description: 'Get information about the send_email tool.')]
  #[Usage(name: 'drush tool:info send_email --format=markdown', description: 'Get information in markdown format.')]
  #[Usage(name: 'drush tool:info send_email --format=json', description: 'Get information as JSON.')]
  public function toolInfo(
    string $tool_id,
    array $options = [
      'format' => 'table',
    ],
  ): int {
    if (!$this->toolManager->hasDefinition($tool_id)) {
      $this->io()->writeln("<error>Tool '$tool_id' not found.</error>");
      return self::EXIT_FAILURE;
    }

    /** @var \Drupal\tool\Tool\ToolDefinition $definition */
    $definition = $this->toolManager->getDefinition($tool_id);
    $requirements = RequirementStatus::forTool($this->toolManager, $tool_id);
    $schemas = $this->buildSchemas($tool_id);

    if ($options['format'] === 'json') {
      $this->outputInfoAsJson($tool_id, $definition, $requirements, $schemas);
      return self::EXIT_SUCCESS;
    }

    if ($options['format'] === 'markdown') {
      $this->outputInfoAsMarkdown($tool_id, $definition, $requirements, $schemas['input'] ?? []);
      return self::EXIT_SUCCESS;
    }

    $this->io()->writeln('');
    $this->io()->writeln('<info>Tool: ' . $tool_id . '</info>');
    $this->io()->writeln('<info>' . str_repeat('=', strlen('Tool: ' . $tool_id)) . '</info>');
    $this->io()->writeln('');

    $this->io()->writeln('<comment>Label:</comment> ' . (string) $definition->getLabel());
    $this->io()->writeln('<comment>Description:</comment> ' . (string) $definition->getDescription());
    $this->io()->writeln('<comment>Operation:</comment> ' . (string) $definition->getOperation()->getLabel() . ' (' . (string) $definition->getOperation()->value . ')');
    $this->io()->writeln('<comment>Destructive:</comment> ' . ($definition->isDestructive() ? 'Yes' : 'No'));
    $this->io()->writeln('<comment>Permission:</comment> ' . ($definition->getPermission() ?? 'None'));
    foreach ($this->unknownPermissions($definition) as $name) {
      $this->io()->writeln("<error>Warning:</error> '$name' matches no defined permission.");
    }
    $this->io()->writeln('<comment>Provider:</comment> ' . ($definition->getProvider() ?? 'Unknown'));
    $this->io()->writeln('<comment>Requirements:</comment> ' . $requirements->label());
    $this->io()->writeln('');

    $this->outputInputDefinitions($definition, $schemas['input'] ?? []);
    $this->outputOutputDefinitions($definition);

    $this->io()->writeln('<info>Usage Example:</info>');
    $this->printUsageExample($tool_id, $definition);
    return self::EXIT_SUCCESS;
  }

  /**
   * Output tool info as markdown format.
   *
   * @param string $tool_id
   *   The tool plugin ID.
   * @param \Drupal\tool\Tool\ToolDefinition $definition
   *   The tool definition.
   * @param \Drupal\tool\Drush\RequirementStatus $requirements
   *   The tool's requirement status.
   * @param array $inputSchema
   *   The JSON Schema of the tool's inputs, empty when it has none.
   */
  private function outputInfoAsMarkdown(string $tool_id, ToolDefinition $definition, RequirementStatus $requirements, array $inputSchema): void {
    $output = $this->output();

    $output->writeln('# ' . $tool_id);
    $output->writeln('');
    $output->writeln('**Label:** ' . (string) $definition->getLabel());
    $output->writeln('**Description:** ' . (string) $definition->getDescription());
    $output->writeln('**Operation:** ' . (string) $definition->getOperation()->getLabel() . ' (' . $definition->getOperation()->value . ')');
    $output->writeln('**Destructive:** ' . ($definition->isDestructive() ? 'Yes' : 'No'));
    $output->writeln('**Permission:** ' . ($definition->getPermission() ?? 'None'));
    foreach ($this->unknownPermissions($definition) as $name) {
      $output->writeln("**Warning:** '$name' matches no defined permission.");
    }
    $output->writeln('**Provider:** ' . ($definition->getProvider() ?? 'Unknown'));
    $output->writeln('**Requirements:** ' . $requirements->label());
    $output->writeln('');

    // Input definitions.
    $inputRows = $this->buildInputRows($definition, $inputSchema);
    if (!empty($inputRows)) {
      $output->writeln('## Input Definitions');
      $output->writeln('');
      $output->writeln('| Name | Type | Required | Multiple | Locked | Constraints | Description |');
      $output->writeln('|------|------|----------|----------|--------|-------------|-------------|');
      foreach ($inputRows as $row) {
        $output->writeln($this->markdownRow([
          $row['name'],
          $row['type'],
          $row['required'],
          $row['multiple'],
          $row['locked'],
          $row['constraints'],
          $row['description'],
        ]));
      }
      $output->writeln('');
    }
    else {
      $output->writeln('*No input definitions.*');
      $output->writeln('');
    }

    // Output definitions.
    $outputDefinitions = $definition->getOutputDefinitions();
    if (!empty($outputDefinitions)) {
      $output->writeln('## Output Definitions');
      $output->writeln('');
      $output->writeln('| Name | Type | Required | Multiple | Description |');
      $output->writeln('|------|------|----------|----------|-------------|');
      foreach ($outputDefinitions as $name => $outputDef) {
        $output->writeln($this->markdownRow([
          $name,
          $outputDef->getDataType(),
          $outputDef->isRequired() ? 'Yes' : 'No',
          $outputDef->isMultiple() ? 'Yes' : 'No',
          (string) $outputDef->getDescription(),
        ]));
      }
      $output->writeln('');
    }
    else {
      $output->writeln('*No output definitions.*');
      $output->writeln('');
    }

    // Usage example.
    $output->writeln('## Usage Example');
    $output->writeln('');
    $output->writeln('```bash');
    $this->printUsageExample($tool_id, $definition, $output);
    $output->writeln('```');
  }

  /**
   * Formats one markdown table row.
   *
   * A pipe inside a cell would start a new column and a newline a new row,
   * so both are neutralized before the cells are joined.
   *
   * @param list<string> $cells
   *   The cell contents.
   *
   * @return string
   *   The row.
   */
  private function markdownRow(array $cells): string {
    $escaped = array_map(
      static fn(string $cell): string => str_replace(["\r\n", "\r", "\n", '|'], [' ', ' ', ' ', '\|'], $cell),
      $cells,
    );
    return '| ' . implode(' | ', $escaped) . ' |';
  }

  /**
   * Lists the declared permission names that match no defined permission.
   *
   * @param \Drupal\tool\Tool\ToolDefinition $definition
   *   The tool definition.
   *
   * @return list<string>
   *   The unknown names, in declaration order.
   */
  private function unknownPermissions(ToolDefinition $definition): array {
    $permission = $definition->getPermission();
    if ($permission === NULL) {
      return [];
    }
    // Display only; ToolManager::checkPermission() holds the real parse,
    // and discovery rejects a string mixing the two operators.
    $names = str_contains($permission, ',') ? explode(',', $permission) : explode('+', $permission);
    $names = array_map('trim', $names);
    return array_values(array_diff($names, array_keys($this->permissionHandler->getPermissions())));
  }

  /**
   * Output tool info as JSON format.
   *
   * @param string $tool_id
   *   The tool plugin ID.
   * @param \Drupal\tool\Tool\ToolDefinition $definition
   *   The tool definition.
   * @param \Drupal\tool\Drush\RequirementStatus $requirements
   *   The tool's requirement status.
   * @param array{input: array, output: array}|null $schemas
   *   The input and output JSON Schemas, NULL when the tool could not be
   *   instantiated.
   */
  private function outputInfoAsJson(string $tool_id, ToolDefinition $definition, RequirementStatus $requirements, ?array $schemas): void {
    $data = $this->buildToolSummary($tool_id, $definition, $requirements);
    $data['permission'] = $definition->getPermission();

    $inputDefinitions = $definition->getInputDefinitions(TRUE);
    $inputs = [];
    foreach ($inputDefinitions as $name => $inputDef) {
      $inputs[$name] = [
        'type' => $inputDef->getDataType(),
        'label' => (string) $inputDef->getLabel(),
        'description' => (string) $inputDef->getDescription(),
        'required' => $inputDef->isRequired(),
        'multiple' => $inputDef->isMultiple(),
        'locked' => $inputDef->isLocked(),
      ];
    }

    $outputDefinitions = $definition->getOutputDefinitions();
    $outputs = [];
    foreach ($outputDefinitions as $name => $outputDef) {
      $outputs[$name] = [
        'type' => $outputDef->getDataType(),
        'label' => (string) $outputDef->getLabel(),
        'description' => (string) $outputDef->getDescription(),
        'required' => $outputDef->isRequired(),
        'multiple' => $outputDef->isMultiple(),
      ];
    }

    // Both are maps keyed by name. An empty PHP array would encode as [],
    // while Json::encode() writes an empty \ArrayObject as {}.
    $data['inputs'] = $inputs ?: new \ArrayObject();
    $data['outputs'] = $outputs ?: new \ArrayObject();

    if ($schemas !== NULL) {
      $data['input_schema'] = $schemas['input'];
      // The output schemas are keyed by name too, so they need the same
      // treatment as the outputs map.
      $data['output_schema'] = $schemas['output'] ?: new \ArrayObject();
    }

    $this->output()->writeln(Json::encode($data));
  }

  /**
   * Normalizes the tool's inputs and outputs to JSON Schema.
   *
   * The serializer needs a tool instance rather than the definition because
   * normalization is invoker-aware: ToolDefinitionNormalizeEvent subscribers
   * key on the invoker to reshape definitions at that boundary.
   *
   * @param string $tool_id
   *   The tool plugin ID.
   *
   * @return array{input: array, output: array}|null
   *   The input object schema and the output schemas keyed by name, or NULL
   *   when the tool cannot be instantiated.
   */
  private function buildSchemas(string $tool_id): ?array {
    try {
      $tool = $this->toolManager->createInstance($tool_id, [], DrushInvoker::create());
    }
    catch (\Throwable) {
      // The requirement status already reports why create() failed, and
      // there is no instance to normalize.
      return NULL;
    }
    return [
      'input' => $this->definitionSerializer->normalizeInputSchema($tool, include_locked: TRUE),
      'output' => $this->definitionSerializer->normalizeOutputDefinitions($tool),
    ];
  }

  /**
   * Builds one display row per input, plus a row per property of a map input.
   *
   * @param \Drupal\tool\Tool\ToolDefinition $definition
   *   The tool definition.
   * @param array $inputSchema
   *   The JSON Schema of the tool's inputs.
   *
   * @return list<array{name: string, type: string, required: string, multiple: string, locked: string, label: string, description: string, constraints: string}>
   *   The rows in declaration order.
   */
  private function buildInputRows(ToolDefinition $definition, array $inputSchema): array {
    $properties = $this->schemaProperties($inputSchema);
    $rows = [];
    foreach ($definition->getInputDefinitions(TRUE) as $name => $inputDef) {
      $schema = $properties[$name] ?? [];
      $rows[] = [
        'name' => $name,
        'type' => $inputDef->getDataType(),
        'required' => $inputDef->isRequired() ? 'Yes' : 'No',
        'multiple' => $inputDef->isMultiple() ? 'Yes' : 'No',
        'locked' => $inputDef->isLocked() ? 'Yes' : 'No',
        'label' => (string) $inputDef->getLabel(),
        'description' => (string) $inputDef->getDescription(),
        'constraints' => $this->summarizeConstraints($schema),
      ];
      foreach ($this->buildPropertyRows($schema, 1) as $row) {
        $rows[] = $row;
      }
    }
    return $rows;
  }

  /**
   * Builds indented rows for the properties of a map schema.
   *
   * @param array $schema
   *   The schema of a map input, or of a list whose items are maps.
   * @param int $depth
   *   The nesting depth, used to indent the property name.
   *
   * @return list<array{name: string, type: string, required: string, multiple: string, locked: string, label: string, description: string, constraints: string}>
   *   The property rows, empty for a scalar schema.
   */
  private function buildPropertyRows(array $schema, int $depth): array {
    // A multiple map input emits its property schema under 'items'.
    if (isset($schema['items']) && is_array($schema['items'])) {
      $schema = $schema['items'];
    }
    $required = isset($schema['required']) && is_array($schema['required']) ? $schema['required'] : [];
    $rows = [];
    foreach ($this->schemaProperties($schema) as $name => $property) {
      // An optional multiple property is typed ['array', 'null'], so resolve
      // the type rather than comparing the raw keyword. A multiple row shows
      // the item type, as the top-level rows do.
      $multiple = $this->schemaType($property) === 'array';
      $valueSchema = $multiple && isset($property['items']) && is_array($property['items']) ? $property['items'] : $property;
      $rows[] = [
        'name' => str_repeat('  ', $depth) . '└ ' . $name,
        'type' => $this->schemaType($valueSchema),
        'required' => in_array($name, $required, TRUE) ? 'Yes' : 'No',
        'multiple' => $multiple ? 'Yes' : 'No',
        'locked' => '',
        'label' => is_string($property['title'] ?? NULL) ? $property['title'] : '',
        'description' => is_string($property['description'] ?? NULL) ? $property['description'] : '',
        'constraints' => $this->summarizeConstraints($property),
      ];
      foreach ($this->buildPropertyRows($property, $depth + 1) as $row) {
        $rows[] = $row;
      }
    }
    return $rows;
  }

  /**
   * Reads the 'properties' member of an object schema as a plain array.
   *
   * An empty properties member is an \ArrayObject so it encodes as {}.
   *
   * @param array $schema
   *   An object schema.
   *
   * @return array<string, array>
   *   The property schemas keyed by name.
   */
  private function schemaProperties(array $schema): array {
    $properties = $schema['properties'] ?? [];
    if ($properties instanceof \ArrayObject) {
      $properties = $properties->getArrayCopy();
    }
    if (!is_array($properties)) {
      return [];
    }
    return array_filter($properties, 'is_array');
  }

  /**
   * Reads the JSON Schema type of a value, ignoring a null branch.
   *
   * @param array $schema
   *   The value schema.
   *
   * @return string
   *   The type name, or an empty string when the schema declares none.
   */
  private function schemaType(array $schema): string {
    if (isset($schema['type'])) {
      $types = is_array($schema['type']) ? $schema['type'] : [$schema['type']];
      return implode('|', array_diff(array_map('strval', $types), ['null']));
    }
    // Optional scalars on 11.1+ cores are emitted as oneOf [type, null].
    $branches = isset($schema['oneOf']) && is_array($schema['oneOf']) ? $schema['oneOf'] : [];
    foreach ($branches as $branch) {
      if (is_array($branch) && isset($branch['type']) && $branch['type'] !== 'null') {
        return $this->schemaType($branch);
      }
    }
    return '';
  }

  /**
   * Summarizes the schema keywords that narrow a value beyond its type.
   *
   * @param array $schema
   *   The value schema.
   *
   * @return string
   *   A comma-separated summary, or an empty string when nothing narrows it.
   */
  private function summarizeConstraints(array $schema): string {
    $parts = [];
    if (isset($schema['enum']) && is_array($schema['enum'])) {
      $parts[] = implode('|', array_map(
        static fn(mixed $value): string => is_string($value) ? $value : Json::encode($value),
        $schema['enum'],
      ));
    }
    if (array_key_exists('const', $schema)) {
      $parts[] = 'const ' . Json::encode($schema['const']);
    }
    $keywords = [
      'format',
      'pattern',
      'minimum',
      'maximum',
      'minLength',
      'maxLength',
      'minItems',
      'maxItems',
      'minProperties',
      'maxProperties',
    ];
    foreach ($keywords as $keyword) {
      if (isset($schema[$keyword]) && is_scalar($schema[$keyword])) {
        $parts[] = $keyword . ' ' . $schema[$keyword];
      }
    }
    if (isset($schema['items']) && is_array($schema['items'])) {
      $items = $this->summarizeConstraints($schema['items']);
      if ($items !== '') {
        $parts[] = 'items: ' . $items;
      }
    }
    return implode(', ', $parts);
  }

  /**
   * Output input definitions as a table.
   *
   * @param \Drupal\tool\Tool\ToolDefinition $definition
   *   The tool definition.
   * @param array $inputSchema
   *   The JSON Schema of the tool's inputs.
   */
  private function outputInputDefinitions(ToolDefinition $definition, array $inputSchema): void {
    $inputRows = $this->buildInputRows($definition, $inputSchema);
    if (!empty($inputRows)) {
      $this->io()->writeln('<info>Input Definitions:</info>');
      $this->io()->writeln('');

      $table = new Table($this->output());
      $table->setHeaders(['Name', 'Type', 'Required', 'Multiple', 'Locked', 'Label', 'Constraints', 'Description']);

      foreach ($inputRows as $row) {
        $table->addRow([
          $row['name'],
          $row['type'],
          $row['required'],
          $row['multiple'],
          $row['locked'],
          $row['label'],
          $row['constraints'],
          Unicode::truncate($row['description'], 40, TRUE, TRUE),
        ]);
      }

      $table->render();
      $this->io()->writeln('');
    }
    else {
      $this->io()->writeln('<comment>No input definitions.</comment>');
      $this->io()->writeln('');
    }
  }

  /**
   * Output output definitions as a table.
   *
   * @param \Drupal\tool\Tool\ToolDefinition $definition
   *   The tool definition.
   */
  private function outputOutputDefinitions(ToolDefinition $definition): void {
    $outputDefinitions = $definition->getOutputDefinitions();
    if (!empty($outputDefinitions)) {
      $this->io()->writeln('<info>Output Definitions:</info>');
      $this->io()->writeln('');

      $table = new Table($this->output());
      $table->setHeaders(['Name', 'Type', 'Required', 'Multiple', 'Label', 'Description']);

      foreach ($outputDefinitions as $name => $outputDef) {
        $table->addRow([
          $name,
          $outputDef->getDataType(),
          $outputDef->isRequired() ? 'Yes' : 'No',
          $outputDef->isMultiple() ? 'Yes' : 'No',
          (string) $outputDef->getLabel(),
          (string) $outputDef->getDescription(),
        ]);
      }

      $table->render();
      $this->io()->writeln('');
    }
    else {
      $this->io()->writeln('<comment>No output definitions.</comment>');
      $this->io()->writeln('');
    }
  }

  /**
   * Print usage example for a tool.
   *
   * @param string $tool_id
   *   The tool ID.
   * @param \Drupal\tool\Tool\ToolDefinition $definition
   *   The tool definition.
   * @param \Symfony\Component\Console\Output\OutputInterface|null $output
   *   Optional output interface to write to. If null, uses $this->io().
   */
  private function printUsageExample(string $tool_id, ToolDefinition $definition, ?OutputInterface $output = NULL): void {
    $out = $output ?? $this->io();
    /** @var \Drupal\tool\TypedData\InputDefinitionInterface[] $inputDefinitions */
    $inputDefinitions = $definition->getInputDefinitions();

    if (empty($inputDefinitions)) {
      $out->writeln("drush tool:run $tool_id");
      return;
    }

    $exampleInputs = [];
    foreach ($inputDefinitions as $name => $inputDef) {
      /** @var string $name */
      $exampleInputs[$name] = $this->getExampleValue($inputDef->getDataType(), $name);
    }

    $jsonInput = Json::encode($exampleInputs);
    $out->writeln("drush tool:run $tool_id --input='$jsonInput'");
  }

  /**
   * Get an example value for a data type.
   *
   * @param string $dataType
   *   The data type.
   * @param string $name
   *   The input name.
   *
   * @return mixed
   *   Example value.
   */
  private function getExampleValue(string $dataType, string $name): mixed {
    return match ($dataType) {
      'email' => 'example@example.com',
      'string', 'text' => "example_$name",
      'integer' => 1,
      'boolean' => TRUE,
      'uri' => 'https://example.com',
      'entity:node' => 1,
      'entity:user' => 1,
      'map' => ['some' => 'object'],
      default => str_starts_with($dataType, 'entity:') ? 1 : "example_$name",
    };
  }

}
