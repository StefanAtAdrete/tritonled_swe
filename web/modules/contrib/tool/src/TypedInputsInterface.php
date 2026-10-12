<?php

namespace Drupal\tool;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Defines an interface for plugins with typed inputs.
 */
interface TypedInputsInterface extends ConfigurableInterface {

  /**
   * Sets the input definitions.
   *
   * @param \Drupal\tool\TypedData\InputDefinitionInterface[] $input_definitions
   *   An array of input definitions keyed by their names.
   *
   * @return $this
   *   The current instance for method chaining.
   */
  public function setInputDefinitions(array $input_definitions): static;

  /**
   * Checks if a specific input definition exists by name.
   *
   * @param string $name
   *   The name of the input definition.
   *
   * @return bool
   *   TRUE if the input definition exists, FALSE otherwise.
   */
  public function hasInputDefinition(string $name): bool;

  /**
   * Gets the input definitions.
   *
   * @return \Drupal\tool\TypedData\InputDefinitionInterface[]
   *   An array of input definitions keyed by their names.
   */
  public function getInputDefinitions(bool $include_locked = FALSE): array;

  /**
   * Gets a specific input definition by name.
   *
   * @param string $name
   *   The name of the input definition.
   *
   * @return \Drupal\tool\TypedData\InputDefinitionInterface
   *   The input definition object.
   *
   * @throws \Drupal\tool\Exception\InputException
   *   When no input definition with the given name exists.
   */
  public function getInputDefinition(string $name): InputDefinitionInterface;

  /**
   * Gets the executable values for the inputs.
   *
   * @param bool $skip_validation
   *   Whether to skip validation of the values.
   *
   * @return array<string, mixed>
   *   An array of executable values.
   */
  public function getExecutableValues(bool $skip_validation = FALSE): array;

  /**
   * Gets the executable value for a specific input by name.
   *
   * @param string $name
   *   The name of the input.
   * @param bool $skip_validation
   *   Whether to skip validation of the value.
   *
   * @return mixed
   *   The executable value.
   */
  public function getExecutableValue(string $name, bool $skip_validation = FALSE): mixed;

  /**
   * Gets the configuration for the typed inputs.
   *
   * @return array<string, mixed>
   *   An array of configuration settings.
   */
  public function getConfiguration(): array;

  /**
   * Sets the configuration for the typed inputs.
   *
   * @param array<string, mixed> $configuration
   *   An array of configuration settings.
   *
   * @return $this
   *   The current instance for method chaining.
   */
  public function setConfiguration(array $configuration): static;

  /**
   * Provides the default configuration for the typed inputs.
   *
   * @return array<string, mixed>
   *   An array of default configuration settings.
   */
  public function defaultConfiguration(): array;

  /**
   * Gets an explicitly configured value.
   *
   * Raw accessor: reports only explicitly configured values. Unset keys
   * return NULL — use getConfiguration() for the complete snapshot with
   * definition defaults applied, or getExecutableValue() for the full
   * input > configuration > default resolution chain.
   *
   * @param string $name
   *   The name of the configuration value.
   * @param bool $return_typed_data
   *   TRUE to return the stored typed data, FALSE (default) to return the
   *   plain value.
   *
   * @return mixed
   *   The configuration value, or NULL if not set.
   */
  public function getConfigurationValue(string $name, bool $return_typed_data = FALSE): mixed;

  /**
   * Sets a configuration value.
   *
   * @param string $name
   *   The name of the configuration value.
   * @param mixed $value
   *   The value to set for the configuration.
   * @param bool $skip_validation
   *   Whether to skip validation for this value.
   *
   * @return $this
   *   The current instance for method chaining.
   *
   * @throws \Drupal\tool\Exception\InputException
   *   If validation fails for the provided value.
   */
  public function setConfigurationValue(string $name, mixed $value, bool $skip_validation = FALSE): static;

  /**
   * Checks if a configuration value has been explicitly set.
   *
   * @param string $name
   *   The name of the configuration value to check.
   *
   * @return bool
   *   TRUE if the configuration value is explicitly set, FALSE otherwise.
   */
  public function hasConfigurationValue(string $name): bool;

  /**
   * Gets the typed data holding a specific input's value.
   *
   * @param string $name
   *   The name of the input.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   Fresh typed data carrying the input's resolved value: the stored
   *   value, or the definition default when none is stored. A detached
   *   wrapper, not a deep copy: replacing its value does not change the
   *   input - use setInputValue() for that - but an object value (an entity,
   *   for example) is the shared instance, so mutations to it remain
   *   visible through the tool. A resolved NULL reads back as the data
   *   type's empty shape (an empty array for maps and lists); getInputValue()
   *   is the value-level truth. A list-definition value comes back with
   *   untyped items - the effective rebuild keeps core's default item
   *   definition, while a multiple definition's items keep their data
   *   type - and validateInputs() checks each item declared as an
   *   input definition.
   */
  public function getInputData(string $name): TypedDataInterface;

  /**
   * Checks if a specific input has a value set.
   *
   * @param string $name
   *   The name of the input.
   *
   * @return bool
   *   TRUE if the input has a value, FALSE otherwise.
   */
  public function hasInputValue(string $name): bool;

  /**
   * Sets the value for a specific input by name.
   *
   * @param string $name
   *   The name of the context.
   * @param mixed $value
   *   The value to set for the context.
   *
   * @return $this
   *   The current instance for method chaining.
   */
  public function setInputValue(string $name, mixed $value): static;

  /**
   * Gets the value of a specific input by name.
   *
   * @param string $name
   *   The name of the input.
   *
   * @return mixed
   *   The value of the input.
   */
  public function getInputValue(string $name): mixed;

  /**
   * Gets all input values.
   *
   * @return array<string, mixed>
   *   An array of input values keyed by their names.
   */
  public function getInputValues(): array;

  /**
   * Validates resolved executable input values without triggering execution.
   *
   * Call before access() or execute() to obtain detailed violation messages
   * suitable for caller self-correction. Locked inputs use their definition
   * default; other values resolve from explicit input, configuration, then the
   * definition default. An empty required context without a default is returned
   * as a named constraint violation. An empty context with a default resolves
   * to that default. Unexpected resolution exceptions are not suppressed.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   The constraint violations, if any.
   */
  public function validateInputs(): ConstraintViolationListInterface;

  /**
   * Validate the provided input values.
   *
   * @param array<string, mixed> $values
   *   The keyed input values to validate.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   A list of constraint violations, if any.
   */
  public function validateInputValues(array $values): ConstraintViolationListInterface;

  /**
   * Validates a specific input value by name.
   *
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $definition
   *   The input definition.
   * @param mixed $value
   *   The value to validate.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   A list of constraint violations, if any.
   */
  public function validateInputValue(InputDefinitionInterface $definition, mixed $value): ConstraintViolationListInterface;

}
