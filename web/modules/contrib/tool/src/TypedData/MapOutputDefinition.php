<?php

namespace Drupal\tool\TypedData;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a map output definition.
 */
class MapOutputDefinition extends MapDefinitionBase implements OutputDefinitionInterface {
  use ExamplesTrait;

  /**
   * Constructs a new output definition object.
   *
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
   * @param \Drupal\tool\TypedData\OutputDefinitionInterface[] $property_definitions
   *   An array of property definitions keyed by property name.
   */
  public function __construct(string|TranslatableMarkup $label, string|TranslatableMarkup $description, $required = TRUE, $multiple = FALSE, $default_value = NULL, array $constraints = [], array $property_definitions = []) {
    parent::__construct(['type' => 'map']);
    $this->initializeDefinition($label, $description, $required, $multiple, $default_value, $constraints);
    $this->setPropertyDefinitions($property_definitions);
  }

  /**
   * {@inheritdoc}
   *
   * Overridden because this class's constructor takes a label first and
   * fixes the data type, so the inherited factory cannot construct it. The
   * $data_type parameter is ignored.
   */
  public static function create($data_type = 'map') {
    return new static(
      new TranslatableMarkup('Map output'),
      new TranslatableMarkup('A map output.'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * Enforces the output boundary before the shared checks in the base run.
   */
  public function setPropertyDefinitions(array $property_definitions): static {
    foreach ($property_definitions as $property_definition) {
      if (!$property_definition instanceof OutputDefinitionInterface) {
        throw new \InvalidArgumentException('Property definitions must be instances of OutputDefinitionInterface.');
      }
    }
    return parent::setPropertyDefinitions($property_definitions);
  }

}
