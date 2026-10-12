<?php

namespace Drupal\tool\TypedData;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an entity output definition.
 */
class EntityOutputDefinition extends EntityDefinitionBase implements OutputDefinitionInterface {
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
  public function __construct($data_type, string|TranslatableMarkup $label, string|TranslatableMarkup $description, $required = TRUE, $multiple = FALSE, $default_value = NULL, array $constraints = [], array $examples = []) {
    parent::__construct(['type' => static::normalizeEntityDataType($data_type)]);
    $this->initializeDefinition($label, $description, $required, $multiple, $default_value, $constraints);
    $this->examples = $examples;
  }

  /**
   * {@inheritdoc}
   *
   * Overridden because the inherited factory takes an entity type ID and a
   * bundle and does not satisfy this class's required label and description.
   * Accepts both 'entity:node' and bare 'node' forms; the constructor
   * normalizes the prefix. $bundle exists only because PHP requires this
   * override to accept everything EntityDataDefinition::create() accepts; it
   * is ignored.
   */
  public static function create($data_type = 'any', $bundle = NULL) {
    return new static(
      $data_type,
      new TranslatableMarkup('Entity output'),
      new TranslatableMarkup('A @type entity output.', ['@type' => $data_type]),
    );
  }

}
