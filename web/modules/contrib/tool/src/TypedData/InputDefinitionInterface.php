<?php

namespace Drupal\tool\TypedData;

use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Defines an interface for input definitions.
 */
interface InputDefinitionInterface extends DataDefinitionInterface {

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
   * Returns if the input is locked.
   *
   * @return bool
   *   Returns TRUE if the input is locked, FALSE otherwise.
   */
  public function isLocked(): bool;

  /**
   * Sets whether the value is locked.
   *
   * @param bool $locked
   *   Whether a data value is locked.
   *
   * @return $this
   */
  public function setLocked($locked = TRUE): static;

  /**
   * Gets the example values for the input.
   *
   * Examples are illustrative values emitted as the JSON Schema examples
   * keyword; they are annotations, not validation constraints. Examples are
   * authored against the definition as declared: a subscriber that re-types
   * an input in place is responsible for updating or clearing them, the same
   * contract that applies to the default value.
   *
   * @return array
   *   The example values, or an empty array when none are set.
   */
  public function getExamples(): array;

  /**
   * Sets the example values for the input.
   *
   * @param array $examples
   *   The example values.
   *
   * @return $this
   */
  public function setExamples(array $examples): static;

}
