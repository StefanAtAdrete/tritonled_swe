<?php

namespace Drupal\tool\TypedData;

use Drupal\Core\Config\Schema\SequenceDataDefinition;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Defines an input definition.
 */
class InputDefinition extends DataDefinition implements InputDefinitionInterface {
  use ToolDataDefinitionTrait;
  use InputDefinitionTrait;

  /**
   * Constructs a new input definition object.
   *
   * @param string $data_type
   *   The required data type.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The label of this input definition for the UI.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The description of this input definition for the UI.
   * @param bool $required
   *   Whether the input definition is required.
   * @param bool $multiple
   *   Whether the input definition is multivalue.
   * @param mixed $default_value
   *   The default value of this definition.
   * @param array<string, mixed> $constraints
   *   An array of constraints keyed by the constraint name and a value of an
   *   array constraint options or a NULL.
   * @param bool $locked
   *   Whether the input is locked, meaning it cannot be changed by context or
   *   configuration.
   * @param array $examples
   *   Illustrative example values emitted as the JSON Schema examples
   *   keyword.
   */
  public function __construct($data_type, string|TranslatableMarkup $label, string|TranslatableMarkup $description, $required = TRUE, $multiple = FALSE, $default_value = NULL, ?array $constraints = [], bool $locked = FALSE, array $examples = []) {
    parent::__construct(['type' => $data_type]);
    $this->initializeDefinition($label, $description, $required, $multiple, $default_value, $constraints ?? []);
    $this->isLocked = $locked;
    $this->examples = $examples;
  }

  /**
   * {@inheritdoc}
   *
   * Overridden because the inherited factory neither satisfies this class's
   * required label and description nor returns an InputDefinitionInterface
   * for entity data types (#3582962).
   */
  public static function create($data_type = 'any') {
    if (str_starts_with($data_type, 'entity:')) {
      // The entity branch cannot return static: EntityInputDefinition
      // extends core's EntityDataDefinition, not this class - the same
      // shape as core's ContextDefinition::create().
      // @phpstan-ignore return.type
      return EntityInputDefinition::create($data_type);
    }
    return new static(
      $data_type,
      new TranslatableMarkup('Input'),
      new TranslatableMarkup('A @type input.', ['@type' => $data_type]),
    );
  }

  /**
   * Creates an InputDefinition from a config schema array.
   *
   * @param array $config_schema
   *   The config schema array.
   */
  public static function fromConfigSchema(array $config_schema): InputDefinitionInterface {
    $typed_config_manager = \Drupal::service('config.typed');
    $data_definition = $typed_config_manager->buildDataDefinition($config_schema, NULL);
    return self::fromDataDefinition($data_definition);
  }

  /**
   * Creates an InputDefinition from a DataDefinition.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition.
   */
  public static function fromDataDefinition(DataDefinitionInterface $definition): InputDefinitionInterface {
    // @phpstan-ignore-next-line
    $definition_array = $definition->toArray();
    // @phpstan-ignore-next-line
    $is_config_based = $definition->getTypedDataManager() instanceof TypedConfigManagerInterface;
    // Plain typed-data definitions (notably list item definitions) may omit
    // the 'type' key from toArray(); the definition object itself always
    // knows its type.
    $data_type = $definition_array['type'] ?? $definition->getDataType();
    // Typed-data labels are optional, but input definitions require one.
    $label = $definition->getLabel() ?? new TranslatableMarkup('@type input', ['@type' => $data_type]);
    $description = $definition->getDescription() ?? new TranslatableMarkup('The input definition for @label', ['@label' => $label]);

    // @todo Allow hook_alter here to modify the created InputDefinition.
    if ($definition instanceof MapDataDefinition) {
      $map_definition = new MapInputDefinition(
        label: $label,
        description: $description,
        required: $definition->isRequired(),
      );
      if ($is_config_based) {
        if (!empty($definition_array['mapping'])) {
          $property_definitions = [];
          foreach ($definition_array['mapping'] as $name => $property) {
            $property_definitions[$name] = self::fromConfigSchema($property);
          }
          $map_definition->setPropertyDefinitions($property_definitions);
        }
      }
      else {
        $property_definitions = [];
        foreach ($definition->getPropertyDefinitions() as $name => $property_definition) {
          $property_definitions[$name] = self::fromDataDefinition($property_definition);
        }
        $map_definition->setPropertyDefinitions($property_definitions);
      }

      return $map_definition;
    }
    elseif ($definition instanceof SequenceDataDefinition) {
      // @todo It appears that sequence data is improperly handled upstream
      // for setting item definitions from config schema, as the item definition
      // is mapping to 'any' instead of the actual type.
      // @todo A sequence is more like a map with dynamic properties, but we
      // we don't have a DynamicMapInputDefinition option.
      $list_definition = new ListInputDefinition(
        label: $label,
        description: $description,
        required: $definition->isRequired(),
        default_value: $definition_array['default_value'] ?? NULL,
      );

      if ($is_config_based && isset($definition_array['sequence']['type'])) {
        $list_definition->setItemDefinition(self::fromConfigSchema($definition_array['sequence']));
      }
      else {
        $list_definition->setItemDefinition(self::fromDataDefinition($definition->getItemDefinition()));
      }
      return $list_definition;
    }
    elseif ($definition instanceof ListDataDefinitionInterface) {
      $list_definition = new ListInputDefinition(
        label: $label,
        description: $description,
        required: $definition->isRequired(),
        default_value: $definition_array['default_value'] ?? NULL,
      );
      if (!$is_config_based) {
        $list_definition->setItemDefinition(self::fromDataDefinition($definition->getItemDefinition()));
      }
      return $list_definition;
    }
    elseif ($is_config_based && $definition_array['unwrap_for_canonical_representation']) {
      // If the schema is wrapped, unwrap it to get the actual data type.
      try {
        $reflection = new \ReflectionClass($definition_array['class']);
        $plugin_attribute = $reflection->getAttributes(DataType::class)[0] ?? NULL;
        if (!$plugin_attribute) {
          throw new \RuntimeException("Data type class {$definition_array['class']} is missing the DataType attribute.");
        }
        $data_type = $plugin_attribute->newInstance()->getId();
      }
      catch (\ReflectionException $e) {
        throw new \RuntimeException("Error reflecting data type class {$definition_array['class']}: " . $e->getMessage());
      }
    }
    $default_value = $is_config_based && isset($definition_array['default_value']) ? $definition_array['default_value'] : NULL;
    return new InputDefinition(
      data_type: $data_type,
      label: $label,
      description: $description,
      required: $definition->isRequired(),
      default_value: $default_value,
    );
  }

}
