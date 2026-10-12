<?php

declare(strict_types=1);

namespace Drupal\tool\Normalizer;

use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\tool\Tool\ToolDefinitionNormalizeDirection;
use Drupal\tool\Tool\ToolInterface;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\tool\TypedData\OutputDefinitionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Serializes tool definitions with invoker-aware context.
 *
 * This service wraps the serializer and builds the correct context bag
 * (invoker, tool_id, direction, definition_name) for each definition,
 * so callers don't need to assemble it manually.
 */
class ToolDefinitionSerializer {

  /**
   * Constructs a ToolDefinitionSerializer.
   *
   * @param \Symfony\Component\Serializer\Normalizer\NormalizerInterface|null $serializer
   *   The serializer service, or NULL if serialization module is not installed.
   */
  public function __construct(
    protected readonly ?NormalizerInterface $serializer,
  ) {
  }

  /**
   * Normalizes all input definitions for a tool.
   *
   * Builds the correct context bag for each input definition, including
   * invoker identity and direction, so that ToolDefinitionNormalizeEvent
   * subscribers can transform definitions during normalization.
   *
   * @param \Drupal\tool\Tool\ToolInterface $tool
   *   The tool instance.
   * @param string $format
   *   The normalization format. Defaults to 'json_schema'.
   * @param bool $include_locked
   *   Whether to include locked input definitions.
   *
   * @return array
   *   An array of normalized input definitions keyed by their names.
   */
  public function normalizeInputDefinitions(ToolInterface $tool, string $format = 'json_schema', bool $include_locked = FALSE): array {
    $this->assertSerializerAvailable();
    $normalized = [];

    foreach ($tool->getInputDefinitions($include_locked) as $name => $definition) {
      $normalized[$name] = $this->normalizeInputDefinition($tool, $name, $definition, $format);
    }

    return $normalized;
  }

  /**
   * Builds a complete JSON Schema object for a tool's inputs.
   *
   * Returns the full schema with 'type', 'properties', and 'required' keys
   * rather than just the properties array.
   *
   * @param \Drupal\tool\Tool\ToolInterface $tool
   *   The tool instance.
   * @param string $format
   *   The normalization format. Defaults to 'json_schema'.
   * @param bool $include_locked
   *   Whether to include locked input definitions.
   *
   * @return array
   *   A JSON Schema object with 'type', 'properties', and optionally
   *   'required'.
   */
  public function normalizeInputSchema(ToolInterface $tool, string $format = 'json_schema', bool $include_locked = FALSE): array {
    // For a tool without inputs, an empty PHP array would encode as []
    // rather than the {} JSON Schema expects for object members.
    // `additionalProperties` is only emitted here at the root: top-level
    // input names are fixed by the plugin definition (refiners and normalize
    // events replace definitions per-name but cannot add names), whereas map
    // definitions may legitimately gain properties through refinement or be
    // free-form, so maps must stay open.
    $schema = [
      'type' => 'object',
      'properties' => $this->normalizeInputDefinitions($tool, $format, $include_locked) ?: new \ArrayObject(),
      'additionalProperties' => FALSE,
    ];

    foreach ($tool->getInputDefinitions($include_locked) as $name => $definition) {
      if ($definition->isRequired()) {
        $schema['required'] ??= [];
        $schema['required'][] = $name;
      }
    }

    return $schema;
  }

  /**
   * Normalizes a single input definition for a tool.
   *
   * @param \Drupal\tool\Tool\ToolInterface $tool
   *   The tool instance.
   * @param string $name
   *   The input definition name.
   * @param \Drupal\tool\TypedData\InputDefinitionInterface|\Drupal\Core\Plugin\Context\ContextDefinitionInterface $definition
   *   The input definition. Core context definitions are accepted for the
   *   ai module's override path, which supplies them (#3583018).
   * @param string $format
   *   The normalization format. Defaults to 'json_schema'.
   *
   * @return array
   *   The normalized input definition.
   */
  public function normalizeInputDefinition(ToolInterface $tool, string $name, InputDefinitionInterface|ContextDefinitionInterface $definition, string $format = 'json_schema'): array {
    return $this->normalizeDefinition($tool, $name, $definition, ToolDefinitionNormalizeDirection::Input, $format);
  }

  /**
   * Normalizes all output definitions for a tool.
   *
   * Builds the correct context bag for each output definition, including
   * invoker identity and direction, so that ToolDefinitionNormalizeEvent
   * subscribers can transform definitions during normalization.
   *
   * @param \Drupal\tool\Tool\ToolInterface $tool
   *   The tool instance.
   * @param string $format
   *   The normalization format. Defaults to 'json_schema'.
   *
   * @return array
   *   An array of normalized output definitions keyed by their names.
   */
  public function normalizeOutputDefinitions(ToolInterface $tool, string $format = 'json_schema'): array {
    $this->assertSerializerAvailable();
    $normalized = [];

    foreach ($tool->getOutputDefinitions() as $name => $definition) {
      $normalized[$name] = $this->normalizeOutputDefinition($tool, $name, $definition, $format);
    }

    return $normalized;
  }

  /**
   * Builds a complete JSON Schema object for a tool's outputs.
   *
   * The twin of normalizeInputSchema(): the schema a consumer publishes for
   * the values a successful execution returns, keyed by output name. Every
   * consumer that wrapped normalizeOutputDefinitions() itself had to repeat
   * the same three decisions, so they live here once: the object wrapper,
   * "required" from each definition, and an empty "properties" that encodes
   * as {} rather than [].
   *
   * Unlike the input schema this one leaves additionalProperties open.
   * ToolBase::execute() only copies declared outputs into the outputs bag,
   * but getFormattedResult() deliberately passes undeclared result values
   * through, so a serialized result may carry members beyond the declared
   * properties and the schema must not close them out.
   *
   * @param \Drupal\tool\Tool\ToolInterface $tool
   *   The tool instance.
   * @param string $format
   *   The normalization format. Defaults to 'json_schema'.
   *
   * @return array
   *   A JSON Schema object with 'type', 'properties', and optionally
   *   'required'.
   */
  public function normalizeOutputSchema(ToolInterface $tool, string $format = 'json_schema'): array {
    $schema = [
      'type' => 'object',
      'properties' => $this->normalizeOutputDefinitions($tool, $format) ?: new \ArrayObject(),
    ];

    foreach ($tool->getOutputDefinitions() as $name => $definition) {
      if ($definition->isRequired()) {
        $schema['required'] ??= [];
        $schema['required'][] = $name;
      }
    }

    return $schema;
  }

  /**
   * Normalizes a single output definition for a tool.
   *
   * @param \Drupal\tool\Tool\ToolInterface $tool
   *   The tool instance.
   * @param string $name
   *   The output definition name.
   * @param \Drupal\tool\TypedData\OutputDefinitionInterface $definition
   *   The output definition.
   * @param string $format
   *   The normalization format. Defaults to 'json_schema'.
   *
   * @return array
   *   The normalized output definition.
   */
  public function normalizeOutputDefinition(ToolInterface $tool, string $name, OutputDefinitionInterface $definition, string $format = 'json_schema'): array {
    return $this->normalizeDefinition($tool, $name, $definition, ToolDefinitionNormalizeDirection::Output, $format);
  }

  /**
   * Builds the base normalizer context from a tool instance.
   *
   * @param \Drupal\tool\Tool\ToolInterface $tool
   *   The tool instance.
   *
   * @return array
   *   The base context array with tool_id and invoker.
   */
  protected function buildBaseContext(ToolInterface $tool): array {
    return [
      'tool_id' => $tool->getPluginId(),
      'tool_invoker' => $tool->getInvoker(),
    ];
  }

  /**
   * Normalizes a definition with invoker-aware context.
   *
   * @param \Drupal\tool\Tool\ToolInterface $tool
   *   The tool instance.
   * @param string $name
   *   The definition name.
   * @param \Drupal\tool\TypedData\InputDefinitionInterface|\Drupal\tool\TypedData\OutputDefinitionInterface|\Drupal\Core\Plugin\Context\ContextDefinitionInterface $definition
   *   The definition. Core context definitions are accepted for the ai
   *   module's override path, which supplies them (#3583018).
   * @param \Drupal\tool\Tool\ToolDefinitionNormalizeDirection $direction
   *   Whether the definition is an input or output definition.
   * @param string $format
   *   The normalization format.
   *
   * @return array
   *   The normalized definition.
   */
  protected function normalizeDefinition(ToolInterface $tool, string $name, InputDefinitionInterface|OutputDefinitionInterface|ContextDefinitionInterface $definition, ToolDefinitionNormalizeDirection $direction, string $format): array {
    $this->assertSerializerAvailable();

    $context = $this->buildBaseContext($tool) + [
      'tool_definition_name' => $name,
      'tool_direction' => $direction,
    ];

    $normalized = $this->serializer->normalize($definition, $format, $context);
    return is_array($normalized) ? $normalized : [];
  }

  /**
   * Asserts that the serializer is available.
   *
   * @throws \LogicException
   *   If the serialization module is not installed.
   */
  protected function assertSerializerAvailable(): void {
    if ($this->serializer === NULL) {
      throw new \LogicException('The serialization module must be installed to use ToolDefinitionSerializer.');
    }
  }

}
