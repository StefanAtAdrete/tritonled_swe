<?php

namespace Drupal\tool;

use Drupal\tool\TypedData\OutputDefinitionInterface;

/**
 * Defines an interface for plugins with typed output definitions.
 */
interface TypedOutputsDefinitionInterface {

  /**
   * Gets the definitions of all output contexts.
   *
   * @return \Drupal\tool\TypedData\OutputDefinitionInterface[]
   *   An array of output definitions keyed by their names.
   */
  public function getOutputDefinitions(): array;

  /**
   * Gets the definition of a specific output context by name.
   *
   * @param string $name
   *   The name of the output context.
   *
   * @return \Drupal\tool\TypedData\OutputDefinitionInterface
   *   The output definition object.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   *   If the output definition does not exist.
   */
  public function getOutputDefinition(string $name): OutputDefinitionInterface;

  /**
   * Checks if a specific output context definition exists by name.
   *
   * @param string $name
   *   The name of the output context.
   *
   * @return bool
   *   TRUE if the context definition exists, FALSE otherwise.
   */
  public function hasOutputDefinition(string $name): bool;

  /**
   * Adds a new output context definition.
   *
   * @param string $name
   *   The name of the output context.
   * @param \Drupal\tool\TypedData\OutputDefinitionInterface $definition
   *   The output definition object.
   *
   * @return $this
   *   The current instance for chaining.
   */
  public function addOutputDefinition(string $name, OutputDefinitionInterface $definition): static;

  /**
   * Removes a specific output context definition by name.
   *
   * @param string $name
   *   The name of the output context.
   *
   * @return $this
   *   The current instance for chaining.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   *   If the context definition does not exist.
   */
  public function removeOutputDefinition(string $name): static;

}
