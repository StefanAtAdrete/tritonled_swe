<?php

namespace Drupal\tool;

use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\tool\TypedData\OutputDefinitionInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Defines an interface for plugins with typed outputs.
 */
interface TypedOutputsInterface {

  /**
   * Sets the value for a specific output context.
   *
   * @param string $name
   *   The name of the output context.
   * @param mixed $value
   *   The value to set for the output context.
   *
   * @return $this
   *   The current instance for method chaining.
   */
  public function setOutputValue(string $name, mixed $value): static;

  /**
   * Gets the typed data holding a specific output's value.
   *
   * @param string $name
   *   The name of the output.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   Fresh typed data carrying the output's resolved value: the stored
   *   value, or the definition default when none is stored. A detached
   *   wrapper, not a deep copy: replacing its value does not change the
   *   output - use setOutputValue() for that - but an object value (an entity,
   *   for example) is the shared instance, so mutations to it remain
   *   visible through the tool. A resolved NULL reads back as the data
   *   type's empty shape (an empty array for maps and lists); getOutputValue()
   *   is the value-level truth. A list-definition value comes back with
   *   untyped items - the effective rebuild keeps core's default item
   *   definition, while a multiple definition's items keep their data
   *   type - and validateOutputs() checks each item declared as an
   *   output definition.
   */
  public function getOutputData(string $name): TypedDataInterface;

  /**
   * Gets the value of a specific output context.
   *
   * This is the PHP value the tool set, for the tool itself and its tests.
   * Anything that leaves the process, such as an MCP response or Drush
   * output, reads the formatted result instead: its transform events swap
   * entities for handles and spell timestamps, decimals, dates, and maps
   * keyed from zero (including empty ones) the way the advertised schema
   * describes them.
   *
   * @param string $name
   *   The name of the output context.
   *
   * @return mixed
   *   The value of the output context. An optional output the tool did not
   *   set reads as its default value (usually NULL).
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   *   If the output is not declared by the tool, or is required but was
   *   never set.
   *
   * @see \Drupal\tool\Tool\ToolInterface::getFormattedResult()
   */
  public function getOutputValue(string $name): mixed;

  /**
   * Gets all output values.
   *
   * These are the PHP values the tool set. Invokers send the formatted
   * result, not this.
   *
   * @return array<string, mixed>
   *   An array of output values keyed by their names. Optional outputs the
   *   tool did not set read as their default values (usually NULL).
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   *   If a required output was never set.
   *
   * @see \Drupal\tool\Tool\ToolInterface::getFormattedResult()
   */
  public function getOutputValues(): array;

  /**
   * Gets the definition of a specific output context.
   *
   * @param string $name
   *   The name of the output context.
   *
   * @return \Drupal\tool\TypedData\OutputDefinitionInterface
   *   The output definition object.
   */
  public function getOutputDefinition(string $name): OutputDefinitionInterface;

  /**
   * Gets all output definitions.
   *
   * @return \Drupal\tool\TypedData\OutputDefinitionInterface[]
   *   An array of output definitions keyed by their names.
   */
  public function getOutputDefinitions(): array;

  /**
   * Validates the output values against their definitions.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationList
   *   A list of constraint violations, if any.
   */
  public function validateOutputs(): ConstraintViolationList;

}
