<?php

namespace Drupal\tool\TypedData;

/**
 * Carries the value semantics shared by the definition families.
 *
 * Multiplicity and the default value are part of the input and output
 * definition contract but not of typed data's, so the families store them
 * here. getConstraints() reports only the constraints the tool author
 * declared: the published JSON Schema is built from it and must not
 * advertise the typed-data defaults. The defaults still apply to
 * validation, which runs against the definition EffectiveDataDefinition
 * rebuilds.
 *
 * @see \Drupal\tool\TypedData\EffectiveDataDefinition
 */
trait ToolDataDefinitionTrait {

  /**
   * Whether the definition is multivalue.
   *
   * @var bool
   */
  protected $isMultiple = FALSE;

  /**
   * The default value.
   *
   * @var mixed
   */
  protected $defaultValue;

  /**
   * Stores the definition-level constructor arguments.
   *
   * Label, description and required go into the typed-data definition
   * array, multiplicity and the default value into their own properties,
   * and constraints are added one at a time.
   *
   * @param string|\Stringable|null $label
   *   The label.
   * @param string|\Stringable|null $description
   *   The description.
   * @param bool $required
   *   Whether a value is required.
   * @param bool $multiple
   *   Whether the definition is multivalue.
   * @param mixed $default_value
   *   The default value.
   * @param array<string, mixed> $constraints
   *   Constraints keyed by constraint name.
   */
  protected function initializeDefinition($label, $description, $required, $multiple, $default_value, array $constraints): void {
    $this->definition['label'] = $label;
    $this->definition['description'] = $description;
    $this->definition['required'] = $required;
    $this->isMultiple = $multiple;
    $this->defaultValue = $default_value;
    foreach ($constraints as $constraint_name => $options) {
      $this->addConstraint($constraint_name, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isMultiple() {
    return $this->isMultiple;
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple($multiple = TRUE) {
    $this->isMultiple = $multiple;
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * DataDefinition::setRequired() has no default for its parameter and the
   * definition family interfaces declare setRequired($required = TRUE), so
   * the trait carries a compatible implementation.
   */
  public function setRequired($required = TRUE) {
    $this->definition['required'] = $required;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return $this->defaultValue;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue($default_value) {
    $this->defaultValue = $default_value;
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * Returns only the constraints the tool author declared.
   * DataDefinition::getConstraints() would merge in the typed-data
   * defaults (PrimitiveType, NotNull on required definitions, data-type
   * constraints, AllowedValues), and the normalizer iterates
   * getConstraints() to build the published schema, so inheriting that
   * merge would change what every tool advertises. The defaults still
   * apply to validation: EffectiveDataDefinition rebuilds a definition
   * that merges them.
   */
  public function getConstraints() {
    return $this->definition['constraints'] ?? [];
  }

}
