<?php

namespace Drupal\tool\TypedData;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an entity input definition.
 */
class EntityInputDefinition extends EntityDefinitionBase implements InputDefinitionInterface {
  use InputDefinitionTrait;

  /**
   * Constructs a new entity input definition object.
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
  public function __construct($data_type, string|TranslatableMarkup $label, string|TranslatableMarkup $description, $required = TRUE, $multiple = FALSE, $default_value = NULL, array $constraints = [], bool $locked = FALSE, array $examples = []) {
    parent::__construct(['type' => static::normalizeEntityDataType($data_type)]);
    $this->initializeDefinition($label, $description, $required, $multiple, $default_value, $constraints);
    $this->isLocked = $locked;
    $this->examples = $examples;
  }

  /**
   * {@inheritdoc}
   *
   * Overridden because the inherited factory takes an entity type ID and a
   * bundle and does not satisfy this class's required label and description
   * (#3582962). Accepts both 'entity:node' and bare 'node' forms; the
   * constructor normalizes the prefix. $bundle exists only because PHP
   * requires this override to accept everything
   * EntityDataDefinition::create() accepts; it is ignored.
   */
  public static function create($data_type = 'any', $bundle = NULL) {
    return new static(
      $data_type,
      new TranslatableMarkup('Entity input'),
      new TranslatableMarkup('A @type entity input.', ['@type' => $data_type]),
    );
  }

}
