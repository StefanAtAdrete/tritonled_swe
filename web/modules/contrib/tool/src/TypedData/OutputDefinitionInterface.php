<?php

namespace Drupal\tool\TypedData;

use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Defines an interface for output definitions.
 *
 * Outputs must implement this interface rather than passing a core context
 * definition directly. The interface owns the type boundary so output-only
 * metadata can be added later without narrowing a frozen parameter type.
 */
interface OutputDefinitionInterface extends DataDefinitionInterface {

  /**
   * Determines whether the data is multi-valued.
   *
   * Multiplicity is part of this definition family's own contract, not
   * typed data's.
   *
   * @return bool
   *   Whether the definition represents multiple values.
   */
  public function isMultiple();

  /**
   * Sets whether the data is multi-valued.
   *
   * @param bool $multiple
   *   Whether the definition represents multiple values.
   *
   * @return $this
   *   The current instance.
   */
  public function setMultiple($multiple = TRUE);

  /**
   * Sets whether a value is required.
   *
   * @param bool $required
   *   Whether a value is required.
   *
   * @return $this
   *   The current instance.
   */
  public function setRequired($required = TRUE);

  /**
   * Gets the default value.
   *
   * @return mixed
   *   The default value, or NULL when none is set.
   */
  public function getDefaultValue();

  /**
   * Sets the default value.
   *
   * @param mixed $default_value
   *   The default value.
   *
   * @return $this
   *   The current instance.
   */
  public function setDefaultValue($default_value);

  /**
   * Gets the example values for the output.
   *
   * Examples are illustrative values emitted as the JSON Schema examples
   * keyword; they are annotations, not validation constraints. An example
   * shows the value as published, after output transforms, so a client
   * reading the schema sees what it will receive. A subscriber that re-types
   * an output in place is responsible for updating or clearing them.
   *
   * @return array
   *   The example values, or an empty array when none are set.
   */
  public function getExamples(): array;

  /**
   * Sets the example values for the output.
   *
   * @param array $examples
   *   The example values.
   *
   * @return $this
   */
  public function setExamples(array $examples): static;

}
