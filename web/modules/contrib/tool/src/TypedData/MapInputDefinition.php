<?php

namespace Drupal\tool\TypedData;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a map input definition.
 */
class MapInputDefinition extends MapDefinitionBase implements InputDefinitionInterface {
  use InputDefinitionTrait;

  /**
   * Constructs a new map input definition object.
   *
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
   * @param \Drupal\tool\TypedData\InputDefinitionInterface[] $property_definitions
   *   An array of property definitions keyed by property name.
   * @param bool $locked
   *   Whether the input is locked, meaning it cannot be changed by context or
   *   configuration.
   */
  public function __construct(string|TranslatableMarkup $label, string|TranslatableMarkup $description, $required = TRUE, $multiple = FALSE, $default_value = NULL, array $constraints = [], array $property_definitions = [], bool $locked = FALSE) {
    parent::__construct(['type' => 'map']);
    $this->initializeDefinition($label, $description, $required, $multiple, $default_value, $constraints);
    $this->setPropertyDefinitions($property_definitions);
    $this->isLocked = $locked;
  }

  /**
   * {@inheritdoc}
   *
   * Overridden because this class's constructor takes a label first and
   * fixes the data type, so the inherited factory cannot construct it
   * (#3582962). The $data_type parameter is ignored.
   */
  public static function create($data_type = 'map') {
    return new static(
      new TranslatableMarkup('Map input'),
      new TranslatableMarkup('A map input.'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * Enforces the input boundary before the shared checks in the base run.
   */
  public function setPropertyDefinitions(array $property_definitions): static {
    foreach ($property_definitions as $property_definition) {
      if (!$property_definition instanceof InputDefinitionInterface) {
        throw new \InvalidArgumentException('Property definitions must be instances of InputDefinitionInterface.');
      }
    }
    return parent::setPropertyDefinitions($property_definitions);
  }

}
