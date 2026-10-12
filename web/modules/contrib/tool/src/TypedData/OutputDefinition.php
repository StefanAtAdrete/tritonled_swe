<?php

namespace Drupal\tool\TypedData;

use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Defines an output definition.
 */
class OutputDefinition extends DataDefinition implements OutputDefinitionInterface {
  use ToolDataDefinitionTrait;
  use ExamplesTrait;

  /**
   * Constructs a new output definition object.
   *
   * @param string $data_type
   *   The required data type.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The label of this output definition for the UI.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The description of this output definition for the UI.
   * @param bool $required
   *   Whether the tool must set this output.
   * @param bool $multiple
   *   Whether the output is multivalue.
   * @param mixed $default_value
   *   The default value of this definition.
   * @param array<string, mixed> $constraints
   *   An array of constraints keyed by the constraint name and a value of an
   *   array constraint options or a NULL.
   * @param array $examples
   *   Illustrative example values emitted as the JSON Schema examples
   *   keyword.
   */
  public function __construct($data_type, string|TranslatableMarkup $label, string|TranslatableMarkup $description, $required = TRUE, $multiple = FALSE, $default_value = NULL, ?array $constraints = [], array $examples = []) {
    parent::__construct(['type' => $data_type]);
    $this->initializeDefinition($label, $description, $required, $multiple, $default_value, $constraints ?? []);
    $this->examples = $examples;
  }

  /**
   * {@inheritdoc}
   *
   * Overridden because the inherited factory neither satisfies this class's
   * required label and description nor returns an OutputDefinitionInterface
   * for entity data types.
   */
  public static function create($data_type = 'any') {
    if (str_starts_with($data_type, 'entity:')) {
      // The entity branch cannot return static: EntityOutputDefinition
      // extends core's EntityDataDefinition, not this class - the same
      // shape as core's ContextDefinition::create().
      // @phpstan-ignore return.type
      return EntityOutputDefinition::create($data_type);
    }
    return new static(
      $data_type,
      new TranslatableMarkup('Output'),
      new TranslatableMarkup('A @type output.', ['@type' => $data_type]),
    );
  }

  /**
   * Converts a core context definition into the matching output class.
   *
   * Temporary acceptance bridge so tools that still declare outputs with core
   * context classes keep loading while they convert. Map and list children
   * are converted recursively, and a multiple definition becomes a list
   * output of its data type through ListOutputDefinition::fromMultiple(). A
   * missing description is synthesized from the label so the LLM-facing
   * schema always carries one.
   *
   * @param \Drupal\Core\Plugin\Context\ContextDefinitionInterface|\Drupal\tool\TypedData\InputDefinitionInterface|\Drupal\tool\TypedData\OutputDefinitionInterface $definition
   *   The definition to convert - a core context definition, or an input
   *   definition declared where an output belongs. Returned unchanged when
   *   it already is an output definition.
   * @param string $name
   *   The output name, used as the fallback label.
   *
   * @return \Drupal\tool\TypedData\OutputDefinitionInterface
   *   The output definition.
   *
   * @internal
   *   Removed together with the #3583015 acceptance shim in
   *   https://git.drupalcode.org/project/tool/-/work_items/3583050.
   */
  public static function fromContextDefinition(ContextDefinitionInterface|InputDefinitionInterface|OutputDefinitionInterface $definition, string $name): OutputDefinitionInterface {
    if ($definition instanceof OutputDefinitionInterface) {
      return $definition;
    }

    $label = $definition->getLabel();
    $label = $label instanceof TranslatableMarkup ? $label : (string) $label;
    if ($label === '') {
      $label = $name;
    }
    $description = $definition->getDescription();
    $description = $description instanceof TranslatableMarkup ? $description : (string) $description;
    if ($description === '') {
      // A plain-string label is concatenated rather than passed through a
      // TranslatableMarkup '@' placeholder, which would HTML-escape it
      // (apostrophes become &#039; in LLM-facing schemas).
      $description = $label instanceof TranslatableMarkup
        ? new TranslatableMarkup('The @label output.', ['@label' => $label])
        : sprintf('The %s output.', $label);
    }

    // A multiple definition is a list of its data type (#3583049): it
    // converts as a single value first, which resolves the label and
    // description as above, then to a list the way a multiple output
    // declared on a tool converts.
    if ($definition->isMultiple()) {
      $single = clone $definition;
      $single->setMultiple(FALSE);
      return ListOutputDefinition::fromMultiple(static::fromContextDefinition($single, $name)->setMultiple());
    }

    // Match by typed-data parent, not by class, so structured input
    // definitions convert alongside plain core ones. Core's
    // EntityContextDefinition is not an EntityDataDefinition, so the entity
    // branch checks both.
    if ($definition instanceof MapDataDefinition) {
      $properties = [];
      foreach ($definition->getPropertyDefinitions() as $property_name => $property_definition) {
        $properties[$property_name] = static::fromContextDefinition($property_definition, $property_name);
      }
      return new MapOutputDefinition($label, $description, $definition->isRequired(), FALSE, $definition->getDefaultValue(), $definition->getConstraints(), $properties);
    }
    if ($definition instanceof ListDataDefinition) {
      $item = static::fromContextDefinition($definition->getItemDefinition(), $name);
      return new ListOutputDefinition($label, $description, $definition->isRequired(), $definition->getDefaultValue(), $definition->getConstraints(), $item);
    }
    if ($definition instanceof EntityContextDefinition || $definition instanceof EntityDataDefinitionInterface) {
      return new EntityOutputDefinition($definition->getDataType(), $label, $description, $definition->isRequired(), FALSE, $definition->getDefaultValue(), $definition->getConstraints());
    }
    return new static($definition->getDataType(), $label, $description, $definition->isRequired(), FALSE, $definition->getDefaultValue(), $definition->getConstraints());
  }

}
