<?php

namespace Drupal\tool;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\Core\TypedData\Validation\ContextualValidatorInterface;
use Drupal\Core\TypedData\Validation\RecursiveValidator;
use Drupal\tool\Exception\InputException;
use Drupal\tool\Tool\InvokerCapability;
use Drupal\tool\TypedData\EffectiveDataDefinition;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\tool\TypedData\InputDefinitionRefinerInterface;
use Drupal\tool\TypedData\ListDefinitionBase;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Provides typed inputs functionality for plugins.
 */
trait TypedInputsTrait {

  use DependencySerializationTrait;
  use TypedDataTrait;
  use ValueViolationsTrait;

  const STORAGE_CONFIGURATION = 'config';
  const STORAGE_INPUT = 'input';
  const INPUT_SOURCE_INPUT = 'input';
  const INPUT_SOURCE_CONFIG = 'config';
  const INPUT_SOURCE_LOCKED = 'locked';
  const INPUT_SOURCE_DEFAULT = 'default';

  /**
   * The input definitions for this plugin.
   *
   * @var \Drupal\tool\TypedData\InputDefinitionInterface[]
   */
  protected ?array $inputDefinitions = NULL;

  /**
   * The storage for input and configuration values.
   *
   * Values are held as typed data; only explicitly set names have an
   * entry. A NULL entry marks a name explicitly set to NULL: it has no
   * typed data - map and list typed data would read a stored NULL back as
   * an empty array - and reads resolve through the definition default,
   * the way a value-less core Context resolved before #3583018.
   *
   * @var array<string, array<string, \Drupal\Core\TypedData\TypedDataInterface|null>>
   */
  protected array $storage = [];

  /**
   * The form adapter instances for the input definitions.
   *
   * @var \Drupal\tool\TypedData\Adapter\TypedDataAdapterInterface[]|null
   */
  protected ?array $formAdapterInstances = NULL;

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    $configuration = [];
    foreach ($this->getInputDefinitions() as $name => $definition) {
      $configuration[$name] = $this->hasConfigurationValue($name)
        ? $this->getConfigurationValue($name)
        : $definition->getDefaultValue();
    }
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): static {
    // Storage holds only explicitly configured values: seeding definition
    // defaults here would make hasConfigurationValue() conflate "configured"
    // with "defaulted", mislabel the executable value source, and leave the
    // default branch of the resolution chain unreachable. Defaults are
    // applied by getConfiguration() and getExecutableValue() instead.
    $this->storage[self::STORAGE_CONFIGURATION] = [];
    foreach (array_keys($this->getInputDefinitions()) as $name) {
      if (array_key_exists($name, $configuration)) {
        $this->setConfigurationValue($name, $configuration[$name], TRUE);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return array_map(function ($definition) {
      return $definition->getDefaultValue();
    }, $this->getInputDefinitions());
  }

  /**
   * {@inheritdoc}
   */
  public function getExecutableValues(bool $skip_validation = FALSE): array {
    $values = [];
    foreach ($this->getInputDefinitions(TRUE) as $name => $definition) {
      $values[$name] = $this->getExecutableValue($name, TRUE);

    }
    if (!$skip_validation) {
      $violations = $this->validateInputValues($values);
      if ($violations->count() > 0) {
        throw new \InvalidArgumentException((string) $violations);
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getExecutableValue(string $name, bool $skip_validation = FALSE): mixed {
    $definition = $this->getInputDefinition($name);
    if ($definition->isLocked()) {
      $value = $this->prepareExecutableValue($name, $definition, $definition->getDefaultValue(), self::INPUT_SOURCE_LOCKED);
    }
    // Next, check if an input value has been set. An explicit NULL keeps
    // the input as the executable source but resolves through the
    // definition default, as a value-less core Context did.
    elseif (array_key_exists($name, $this->storage[self::STORAGE_INPUT] ?? [])) {
      $stored = $this->storage[self::STORAGE_INPUT][$name];
      $value = $stored instanceof TypedDataInterface
        ? $this->getTypedDataManager()->getCanonicalRepresentation($stored)
        : $this->resolveDefaultValue($definition);
      $value = $this->prepareExecutableValue($name, $definition, $value, self::INPUT_SOURCE_INPUT);
    }
    // Next, check if a configuration value has been set.
    elseif ($this->hasConfigurationValue($name)) {
      $value = $this->prepareExecutableValue($name, $definition, $this->getConfigurationValue($name), self::INPUT_SOURCE_CONFIG);
    }
    // Finally, use the default value.
    else {
      $value = $this->prepareExecutableValue($name, $definition, $definition->getDefaultValue(), self::INPUT_SOURCE_DEFAULT);
    }
    if (!$skip_validation) {
      $violations = $this->validateInputValue($definition, $value);
      if ($violations->count() > 0) {
        throw new \InvalidArgumentException((string) $violations);
      }
    }
    return $value;
  }

  /**
   * Prepares a value for execution based on its input definition.
   *
   * Template-method hook for the executable value resolution chain; not
   * part of the public API (#3582962).
   *
   * @param string $name
   *   The name of the input.
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $definition
   *   The input definition.
   * @param mixed $value
   *   The value to prepare.
   * @param string $source
   *   The source of the value: one of the INPUT_SOURCE_* constants.
   *
   * @return mixed
   *   The prepared executable value.
   */
  protected function prepareExecutableValue(string $name, InputDefinitionInterface $definition, mixed $value, string $source): mixed {
    // Source should be one of: input, config, locked, default.
    $allowed_sources = [
      self::INPUT_SOURCE_INPUT,
      self::INPUT_SOURCE_CONFIG,
      self::INPUT_SOURCE_LOCKED,
      self::INPUT_SOURCE_DEFAULT,
    ];
    if (!in_array($source, $allowed_sources, TRUE)) {
      throw new \InvalidArgumentException('Source must be one of: input, config, locked, default.');
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setInputDefinitions(array $input_definitions): static {
    $old_input_definitions = $this->inputDefinitions ?? [];
    $this->inputDefinitions = $input_definitions;
    foreach ($input_definitions as $name => $definition) {
      if (isset($old_input_definitions[$name]) && $old_input_definitions[$name] !== $definition && $this->hasInputValue($name)) {
        $value = $this->getInputValue($name);
        $this->storage[self::STORAGE_INPUT][$name] = $value === NULL
          ? NULL
          : $this->getTypedDataManager()->create(EffectiveDataDefinition::createFrom($definition), $value);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasInputDefinition(string $name): bool {
    $inputs = $this->getInputDefinitions(TRUE);
    return isset($inputs[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getInputDefinitions(bool $include_locked = FALSE): array {
    if (!isset($this->inputDefinitions)) {
      $definition = $this->getPluginDefinition();
      assert($definition instanceof TypedInputsDefinitionInterface);
      // Plugin definitions are shared, but input definitions are mutable.
      $this->inputDefinitions = [];
      foreach ($definition->getInputDefinitions(TRUE) as $name => $input_definition) {
        $this->inputDefinitions[$name] = clone $input_definition;
      }
    }
    if (!$include_locked) {
      return array_filter($this->inputDefinitions, function ($definition) {
        return !$definition->isLocked();
      });
    }
    return $this->inputDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getInputDefinition(string $name): InputDefinitionInterface {
    $inputs = $this->getInputDefinitions(TRUE);
    if (isset($inputs[$name])) {
      return $inputs[$name];
    }
    throw new InputException((string) new TranslatableMarkup('The @name context is not a valid context.', ['@name' => $name]));
  }

  /**
   * {@inheritdoc}
   */
  public function getInputData(string $name): TypedDataInterface {
    $definition = $this->getInputDefinition($name);
    $stored = $this->storage[self::STORAGE_INPUT][$name] ?? NULL;
    $value = $stored instanceof TypedDataInterface
      ? $this->getTypedDataManager()->getCanonicalRepresentation($stored)
      : $definition->getDefaultValue();
    return $this->getTypedDataManager()
      ->create(EffectiveDataDefinition::createFrom($definition), $value);
  }

  /**
   * Resolves the value of an input without a stored value.
   *
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $definition
   *   The input definition.
   *
   * @return mixed
   *   The definition's default value.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   *   When the definition is required and has no default value - the same
   *   resolution core's Context applied to a value-less context.
   */
  private function resolveDefaultValue(InputDefinitionInterface $definition): mixed {
    $default_value = $definition->getDefaultValue();
    if (!isset($default_value) && $definition->isRequired()) {
      throw new ContextException("The '{$definition->getDataType()}' context is required and not present.");
    }
    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function hasInputValue(string $name): bool {
    return array_key_exists($name, $this->storage[self::STORAGE_INPUT] ?? []);
  }

  /**
   * {@inheritdoc}
   */
  public function getInputValues(): array {
    $values = [];
    foreach ($this->getInputDefinitions() as $name => $definition) {
      if (!array_key_exists($name, $this->storage[self::STORAGE_INPUT] ?? [])) {
        $values[$name] = NULL;
        continue;
      }
      $stored = $this->storage[self::STORAGE_INPUT][$name];
      $values[$name] = $stored instanceof TypedDataInterface
        ? $this->getTypedDataManager()->getCanonicalRepresentation($stored)
        : $this->resolveDefaultValue($definition);
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getInputValue(string $name): mixed {
    $stored = $this->storage[self::STORAGE_INPUT][$name] ?? NULL;
    if ($stored instanceof TypedDataInterface) {
      return $this->getTypedDataManager()->getCanonicalRepresentation($stored);
    }
    // An input that was never set, or was set to NULL, reads as its default
    // value, while a required input without one throws - the same
    // resolution core's Context applied before #3583018. An undeclared
    // name throws from the definition lookup.
    return $this->resolveDefaultValue($this->getInputDefinition($name));
  }

  /**
   * {@inheritdoc}
   */
  public function setInputValue(string $name, $value): static {
    if ($this->getInputDefinition($name)->isLocked()) {
      throw new InputException((string) new TranslatableMarkup('The @name input is locked and cannot be changed.', ['@name' => $name]));
    }
    // Reject NULL items in list/multiple inputs before typed-data storage
    // can silently coerce them (e.g. NULL map items become empty arrays).
    // An item slot in a list cannot be "omitted" — it either holds a value
    // or should not exist.
    $definition = $this->getInputDefinition($name);
    if (is_array($value) && ($definition->isMultiple() || $definition->getDataType() === 'list')
      && in_array(NULL, $value, TRUE)) {
      throw new InputException((string) new TranslatableMarkup('The @name input must not contain NULL items.', ['@name' => $name]));
    }
    try {
      // A NULL value stores a marker rather than typed data holding NULL:
      // core Context never stored a NULL either (it produced a value-less
      // context), and map or list typed data would read a stored NULL back
      // as an empty array. Reads then resolve through the definition
      // default, exactly as they did for a value-less context.
      $this->storage[self::STORAGE_INPUT][$name] = $value === NULL
        ? NULL
        : $this->getTypedDataManager()->create(EffectiveDataDefinition::createFrom($definition), $value);
    }
    catch (\InvalidArgumentException $e) {
      // Typed data rejects structurally un-storable values (e.g. a string
      // for a map input) at storage time, before validation can run and
      // name the input. Rethrow with the input name so invokers can echo
      // which input failed alongside the input schema.
      throw new InputException((string) new TranslatableMarkup('The @name input value is invalid: @message', [
        '@name' => $name,
        '@message' => $e->getMessage(),
      ]), 0, $e);
    }
    // If this plugin can refine input definitions, check if this input is a
    // dependency for any refiners and apply them if so.
    if ($this instanceof InputDefinitionRefinerInterface) {
      $input_definitions = $this->getInputDefinitions(TRUE);
      $plugin_definition = $this->getPluginDefinition();
      assert($plugin_definition instanceof TypedInputsDefinitionInterface);
      $refiners = $plugin_definition->getInputDefinitionRefiners();
      foreach ($refiners as $target => $dependencies) {
        if (in_array($name, $dependencies, TRUE)) {
          // Prepare the values for the dependencies and validate.
          $simulated_values = array_intersect_key($this->getExecutableValues(TRUE), array_flip($dependencies));
          $violations = $this->validateInputValues($simulated_values);
          $original_definition = clone $plugin_definition->getInputDefinition($target);
          // A refiner narrows one input using the values of the others, so it
          // can only run once every dependency it declares has a value. Setting
          // one dependency would otherwise invoke the refiner while the rest
          // are still NULL, and an optional dependency being NULL is
          // indistinguishable from a deliberate value, so no violation is
          // raised and the refiner is left to dereference NULL.
          $dependencies_resolved = $this->refinerDependenciesResolved($dependencies, $simulated_values);
          // If no violations, refine the target input definition.
          if ($dependencies_resolved && $violations->count() === 0) {
            $refined_definition = $this->refineInputDefinition($target, $original_definition, $simulated_values);
            $this->splitRefinedMultipleConstraints($plugin_definition->getInputDefinition($target), $refined_definition);
            // A refiner written against a multiple input may build a new
            // definition declaring multiple: TRUE. Before the narrowing check
            // compares it to the declared list, convert it the way
            // addInputDefinition() converts a declaration. This runs after the
            // split above, which is meant for a list the refiner changed in
            // place and would move the constraints of this list a second time.
            if ($refined_definition->isMultiple() && !$refined_definition instanceof ListDataDefinition) {
              @trigger_error(sprintf("Refining the '%s' input to a definition with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Return a ListInputDefinition instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049", $target), E_USER_DEPRECATED);
              $refined_definition = ListInputDefinition::fromMultiple($refined_definition);
            }
            $this->assertRefinementNarrows($target, $original_definition, $refined_definition);
            $input_definitions[$target] = $refined_definition;
          }
          // If violations exist, or a dependency has no value yet, revert to
          // the original definition.
          else {
            $input_definitions[$target] = $original_definition;
          }
        }
      }
      $this->setInputDefinitions($input_definitions);
    }
    return $this;
  }

  /**
   * Determines whether every dependency a refiner declares has a value.
   *
   * Refiners are dispatched from ::setInputValue() for each of their
   * dependencies in turn, so a refiner declaring more than one fires as soon
   * as the first arrives. A refiner may reasonably assume the dependencies it
   * declared are present, so defer it until they all are.
   *
   * @param string[] $dependencies
   *   The input names the refiner declares as its dependencies.
   * @param array<string, mixed> $values
   *   The resolved values of those inputs, keyed by input name.
   *
   * @return bool
   *   TRUE when every dependency resolved to a non-NULL value.
   */
  protected function refinerDependenciesResolved(array $dependencies, array $values): bool {
    foreach ($dependencies as $dependency) {
      if (($values[$dependency] ?? NULL) === NULL) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Moves the item constraints a refiner put on a converted list to the item.
   *
   * A multiple input is converted to a list when it is declared (#3583049),
   * so a refiner written against the multiple definition, one adding a
   * Choice constraint for example, now adds it to the list, where it would
   * bind the whole value instead of each item. The constraints the refiner
   * added are split the way ListInputDefinition::fromMultiple() splits those
   * of a multiple definition: Count stays on the list and every other
   * constraint moves to the item. A list declared as a list is left alone.
   * Each refinement starts from a copy of the declared definition, which
   * never holds a refined constraint, so a constraint is moved once.
   *
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $declared
   *   The definition as the plugin declares it.
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $refined
   *   The definition the refiner returned, changed in place.
   *
   * @internal
   *   Removed together with multiple in
   *   https://git.drupalcode.org/project/tool/-/work_items/3583049.
   */
  protected function splitRefinedMultipleConstraints(InputDefinitionInterface $declared, InputDefinitionInterface $refined): void {
    if (!$refined instanceof ListInputDefinition || !$refined->isConvertedFromMultiple()) {
      return;
    }
    $declared_constraints = $declared instanceof ListDataDefinition ? $declared->getConstraints() : [];
    // Compared by options too, strictly: a refiner may change the options
    // of a constraint the declared definition already carries, such as a
    // Count with new bounds, and a key-only comparison would drop the
    // change.
    $added = array_filter(
      $refined->getConstraints(),
      static fn ($options, string $name): bool => !array_key_exists($name, $declared_constraints) || $declared_constraints[$name] !== $options,
      ARRAY_FILTER_USE_BOTH,
    );
    if ($added === []) {
      return;
    }
    [$list_constraints, $item_constraints] = ListDefinitionBase::splitMultipleConstraints($added);
    $refined->setConstraints(array_diff_key($refined->getConstraints(), $added) + $list_constraints);
    $item = $refined->getItemDefinition();
    foreach ($item_constraints as $constraint_name => $options) {
      $item->addConstraint($constraint_name, $options);
    }
  }

  /**
   * Asserts that a refined definition only narrows the original.
   *
   * The unrefined definition produces the schema invokers advertise, which
   * is static; a refinement changing the data type or multiplicity makes
   * the advertised and accepted shapes disagree, so values passing the
   * invoker's schema validation would fail here and vice versa. Refining
   * 'any' is always narrowing (an untyped schema accepts every value), as
   * is refining an entity type to one of its sub-types.
   *
   * @param string $name
   *   The refined input name.
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $original
   *   The definition as declared by the plugin.
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $refined
   *   The definition the refiner returned.
   *
   * @throws \LogicException
   *   When the refinement changes the data type, the item data type of a
   *   list or the multiplicity.
   */
  protected function assertRefinementNarrows(string $name, InputDefinitionInterface $original, InputDefinitionInterface $refined): void {
    $original_type = $original->getDataType();
    if ($original_type === 'any') {
      return;
    }
    $refined_type = $refined->getDataType();

    // A multiple input (e.g., map+multiple:TRUE) advertises the same schema
    // shape as an explicit list. A refiner may replace it with a
    // ListInputDefinition whose item type narrows the original's declared
    // type. This is valid narrowing: the outer shape stays "array of items"
    // and the item contract only gets stricter.
    if ($original->isMultiple() && $refined_type === 'list' && $refined instanceof ListDataDefinition) {
      $item_type = $refined->getItemDefinition()->getDataType();
      if ($item_type !== 'any' && $item_type !== $original_type && !str_starts_with($item_type, $original_type . ':')) {
        throw new \LogicException(sprintf(
          'Refining the %s input must not change its item data type (%s to %s): refinement may only narrow what the advertised schema accepts.',
          $name,
          $original_type,
          $item_type,
        ));
      }
      return;
    }

    if ($refined_type !== $original_type && !str_starts_with($refined_type, $original_type . ':')) {
      throw new \LogicException(sprintf(
        'Refining the %s input must not change its data type (%s to %s): refinement may only narrow what the advertised schema accepts.',
        $name,
        $original_type,
        $refined_type,
      ));
    }
    if ($refined->isMultiple() !== $original->isMultiple()) {
      throw new \LogicException(sprintf(
        'Refining the %s input must not change its multiplicity: refinement may only narrow what the advertised schema accepts.',
        $name,
      ));
    }

    // A list narrows item by item: its item type follows the rule above for
    // a data type. A multiple input becomes a list when it is declared
    // (#3583049), so this keeps the item check the multiple carve-out gives.
    if ($original instanceof ListDataDefinition && $refined instanceof ListDataDefinition) {
      $original_item_type = $original->getItemDefinition()->getDataType();
      $item_type = $refined->getItemDefinition()->getDataType();
      if ($original_item_type !== 'any' && $item_type !== 'any' && $item_type !== $original_item_type && !str_starts_with($item_type, $original_item_type . ':')) {
        throw new \LogicException(sprintf(
          'Refining the %s input must not change its item data type (%s to %s): refinement may only narrow what the advertised schema accepts.',
          $name,
          $original_item_type,
          $item_type,
        ));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateInputs(): ConstraintViolationListInterface {
    $values = [];
    foreach ($this->getInputDefinitions(TRUE) as $name => $definition) {
      // An input or configuration value explicitly set to NULL resolves
      // through the definition default, which throws for a required
      // definition without one. Report that state as a violation instead,
      // the way the value-less core Context guard did before #3583018.
      $explicit_null = !$definition->isLocked()
        && (array_key_exists($name, $this->storage[self::STORAGE_INPUT] ?? [])
          ? $this->storage[self::STORAGE_INPUT][$name] === NULL
          : ($this->hasConfigurationValue($name) && $this->storage[self::STORAGE_CONFIGURATION][$name] === NULL));
      if ($explicit_null && $definition->isRequired() && $definition->getDefaultValue() === NULL) {
        $values[$name] = NULL;
      }
      else {
        $values[$name] = $this->getExecutableValue($name, TRUE);
      }
    }
    return $this->validateInputValues($values);
  }

  /**
   * {@inheritdoc}
   */
  public function validateInputValues($values): ConstraintViolationListInterface {
    // @todo test is giving property name on mapped values.
    $violations = new ConstraintViolationList();
    foreach ($values as $name => $value) {
      $definition = $this->getInputDefinition($name);
      $input_violations = $this->validateInputValue($definition, $value);
      if ($input_violations->count() > 0) {
        foreach ($input_violations as $key => $violation) {
          $input_violations[$key] = new ConstraintViolation(
            new TranslatableMarkup('Input @name: @message', ['@name' => $name, '@message' => $violation->getMessage()]),
            $violation->getMessageTemplate(),
            $violation->getParameters(),
            $violation->getRoot(),
            $violation->getPropertyPath(),
            $violation->getInvalidValue(),
            $violation->getPlural(),
            $violation->getCode(),
            $violation->getCause()
          );
        }
        $violations->addAll($input_violations);
      }
    }
    return $violations;
  }

  /**
   * {@inheritdoc}
   */
  public function validateInputValue(InputDefinitionInterface $definition, $value): ConstraintViolationListInterface {
    $violations = new ConstraintViolationList();
    // Skip validations for non-required NULL values.
    if (!$definition->isRequired() && $value === NULL) {
      return $violations;
    }
    if ($definition->isMultiple() && is_array($value)) {
      $item_definition = clone $definition;
      $item_definition->setMultiple(FALSE);
      // Items in a list are always required: an item slot cannot be "omitted"
      // — it either holds a value or should not exist. Without this, the
      // NULL+optional early-return above would skip NULL items silently.
      $item_definition->setRequired(TRUE);
      // Count binds the list, never an item (#3583049).
      assert($item_definition instanceof DataDefinition);
      $item_definition->setConstraints(array_diff_key($item_definition->getConstraints(), ['Count' => TRUE]));
      foreach ($value as $delta => $item) {
        $this->addItemViolations($violations, $this->validateInputValue($item_definition, $item), $delta);
      }
    }
    else {
      // Typed-data validation recursively covers map properties, but not the
      // items of an ItemList, so list items must be validated here against
      // the list's item definition (#3582982).
      if ($definition instanceof ListDataDefinition && is_array($value)
        && ($list_item_definition = $this->getItemDefinitionForViolations($definition)) instanceof InputDefinitionInterface) {
        foreach ($value as $delta => $item) {
          $this->addItemViolations($violations, $this->validateInputValue($list_item_definition, $item), $delta);
        }
        $violations->addAll($this->validateDeclaredConstraints($definition, $value));
      }
      // Maps with declared properties are validated property-by-property
      // rather than delegating to core's $context->validate(). This avoids
      // core's recursive typed-data traversal, which validates entity
      // completeness when we only need type-checks. (The declared-constraints
      // run below may instantiate absent properties a constraint names; each
      // constraint gets fresh data so nothing leaks between them.)
      // Each property recurses through validateInputValue() which already
      // handles NULL+optional, entity type-checks, lists, and nested maps.
      // (#3583004)
      elseif ($definition instanceof MapDataDefinition && is_array($value) && $definition->getPropertyDefinitions()) {
        foreach ($definition->getPropertyDefinitions() as $property_name => $property_definition) {
          if (!array_key_exists($property_name, $value)) {
            // Not provided — PATCH semantics: absence means "not touched".
            // Required-but-absent properties produce a violation.
            if ($property_definition->isRequired()) {
              $violations->add(new ConstraintViolation(
                new TranslatableMarkup('The @label property is required.', ['@label' => $property_definition->getLabel()]),
                '',
                [],
                NULL,
                $property_name,
                NULL
              ));
            }
            continue;
          }
          if ($property_definition instanceof InputDefinitionInterface) {
            $this->addItemViolations($violations, $this->validateInputValue($property_definition, $value[$property_name]), $property_name, 'property');
          }
        }
        $members_rejected = $violations->count() > 0;
        $violations->addAll($this->validateDeclaredConstraints($definition, $value, $members_rejected));
      }
      else {
        try {
          $this->resetTypedDataPrototypes($definition);
          $effective_definition = EffectiveDataDefinition::createFrom($definition);
          $typed_data = $this->getTypedDataManager()->create($effective_definition, $value);
          // The effective definition wraps a multiple definition in a list,
          // so a multivalue entity input reads as 'list' here and takes the
          // typed-data branch, as it did when core Context held the value.
          $data_type = $typed_data->getDataDefinition()->getDataType();
          // Every NULL reaches this branch, and only for a required
          // definition. A list or map carries no NotNull, so that an empty
          // one stays valid; reject its NULL here.
          if ($value === NULL && ($effective_definition instanceof ListDataDefinitionInterface || $effective_definition instanceof MapDataDefinition)) {
            $violations->addAll($this->validateListNotNull($typed_data));
          }
          elseif ($data_type === 'entity' || str_starts_with($data_type, 'entity:')) {
            // Content and config entities both satisfy an entity input: core
            // derives "entity:TYPE" for every entity type.
            if (!$typed_data->getValue() instanceof EntityInterface) {
              // An invoker without the handle capability has no way to
              // supply an entity, so the violation says what to do, not
              // only what is wrong (#3583046). Tool does not know which
              // sibling tool fits, so the hint stays generic.
              $invoker = $this->invoker;
              $message = $invoker !== NULL && !$invoker->has(InvokerCapability::EntitiesAsHandles)
                ? new TranslatableMarkup('The @label input must be an entity. The @invoker invoker cannot pass entities; use a tool that takes an entity type and ID instead.', [
                  '@label' => $definition->getLabel(),
                  '@invoker' => $invoker->id,
                ])
                : new TranslatableMarkup('The @label input must be an entity.', ['@label' => $definition->getLabel()]);
              $violations->add(new ConstraintViolation(
                $message,
                '',
                [],
                NULL,
                '',
                NULL
              ));
            }
            else {
              // The declared-type checks - the effective definition's
              // dynamic EntityType and Bundle constraints - run together
              // with the author's constraints, read from the declared
              // definition, which reports them without the typed-data
              // defaults. The entity type's own constraints (EntityChanged,
              // for example) and field validation stay off. On a name clash
              // the type check wins, as it does in the effective
              // definition's own merge.
              $constraint_manager = $this->getTypedDataManager()->getValidationConstraintManager();
              $entity_constraints = array_intersect_key(
                $typed_data->getDataDefinition()->getConstraints(),
                array_flip(['EntityType', 'Bundle'])
              ) + $definition->getConstraints();
              $constraints = [];
              foreach ($entity_constraints as $constraint_name => $constraint_options) {
                $constraints[] = $constraint_manager->create($constraint_name, $constraint_options);
              }
              if ($constraints) {
                $violations->addAll($this->getTypedDataManager()->getValidator()->validate($typed_data, $constraints));
              }
            }
          }
          else {
            $violations->addAll($typed_data->validate());
          }
        }
        catch (\Exception $e) {
          $violations->add(new ConstraintViolation(
            new TranslatableMarkup('The @label input is invalid: @message', [
              '@label' => $definition->getLabel(),
              '@message' => $e->getMessage(),
            ]),
            '',
            [],
            NULL,
            '',
            NULL
          ));
        }
      }
    }
    return $violations;
  }

  /**
   * Validates a list or map input against the constraints its author declared.
   *
   * Only the declared constraints run, not the typed-data defaults of the
   * definition the value is built from - a required list must accept []
   * (#3583040) - and a declared Count is what bounds the value as a whole:
   * on a map it counts the properties.
   *
   * The walks in ::validateInputValue() have already validated each item or
   * property against its own definition, but core's validator, given
   * constraints for the root, still descends into every member and runs
   * the member's own constraints - for a map, a second report of each
   * property fault, and full validation of an entity property's fields.
   * Each constraint is therefore validated as a non-root call, the way
   * core's ComplexData validates the properties it names: only the given
   * constraint runs, and a member is visited only when that constraint
   * reads it.
   *
   * A map is built from its effective definition, so a constraint reading
   * its structure - ComplexData naming a declared property - finds the
   * property with its declared type. A list keeps the untyped items of its
   * bare data type: no list constraint reads the items.
   *
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $definition
   *   The list or map input definition.
   * @param array $value
   *   The list or map value.
   * @param bool $members_rejected
   *   Whether the walk already reported any violation on the map value. A
   *   constraint reading a property value that does not fit the property's
   *   type - a string for a nested map, an array for a string - throws
   *   instead of reporting a violation. While the walk has rejected the
   *   value, every such exception is dropped, related to the rejected
   *   property or not: the walk's violation already rejects the value, and
   *   the remaining constraints still run one by one. Once the walk passes
   *   the value, they propagate, as the fault then lies in the declaration.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   The violations of the constraints declared on the list or map itself;
   *   its items or properties are validated against their own definitions
   *   separately.
   *
   * @throws \Exception
   *   When a constraint declaration is invalid: an unknown name, options the
   *   constraint rejects, or a constraint that cannot apply to what it
   *   reads, such as ComplexData naming a property the map does not
   *   declare. The mistake is the tool author's, so it fails loudly instead
   *   of being reported to a caller that cannot fix it (#3583048).
   */
  protected function validateDeclaredConstraints(InputDefinitionInterface $definition, array $value, bool $members_rejected = FALSE): ConstraintViolationListInterface {
    $violations = new ConstraintViolationList();
    $declared_constraints = $definition->getConstraints();
    if ($declared_constraints === []) {
      return $violations;
    }
    $typed_data_manager = $this->getTypedDataManager();
    $constraint_manager = $typed_data_manager->getValidationConstraintManager();
    $constraints = [];
    foreach ($declared_constraints as $name => $options) {
      $constraints[] = $constraint_manager->create($name, $options);
    }
    $data_definition = $definition instanceof MapDataDefinition
      ? EffectiveDataDefinition::createFrom($definition)
      : $typed_data_manager->createDataDefinition($definition->getDataType());
    // A member a constraint reads is created from the prototype cache, where
    // a same-named member of other data may sit (#3616328).
    $this->resetTypedDataPrototypes($definition);
    $validator = $typed_data_manager->getValidator();
    assert($validator instanceof RecursiveValidator);
    foreach ($constraints as $constraint) {
      // Each constraint gets its own data: reading a property the value
      // lacks adds it to the map's value, which a later Count would count.
      // A non-root call appends the data's name to the violation path and
      // fails on the NULL name of root data, so the root is named with the
      // empty string.
      $data = $typed_data_manager->create($data_definition, $value, '');
      $contextual_validator = $validator->startContext($data);
      assert($contextual_validator instanceof ContextualValidatorInterface);
      try {
        $contextual_validator->validate($data, $constraint, NULL, FALSE);
      }
      catch (\InvalidArgumentException | UnexpectedTypeException $e) {
        if (!$members_rejected) {
          throw $e;
        }
      }
      $violations->addAll($contextual_validator->getViolations());
    }
    return $violations;
  }

  /**
   * Gets a specific configuration value by name.
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
  public function getConfigurationValue(string $name, bool $return_typed_data = FALSE): mixed {
    if (!$this->hasConfigurationValue($name)) {
      return NULL;
    }
    $stored = $this->storage[self::STORAGE_CONFIGURATION][$name];
    if ($return_typed_data) {
      return $stored;
    }
    return $stored instanceof TypedDataInterface
      ? $this->getTypedDataManager()->getCanonicalRepresentation($stored)
      : $this->resolveDefaultValue($this->getInputDefinition($name));
  }

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
  public function setConfigurationValue(string $name, mixed $value, bool $skip_validation = FALSE): static {
    // If isset in getInputDefinitions(), validate constraints.
    if (!$skip_validation) {
      $violations = $this->validateInputValue($this->getInputDefinition($name), $value);
      if ($violations->count() > 0) {
        // If there are violations, throw an exception or handle them as needed.
        foreach ($violations as $violation) {
          throw new InputException((string) $violation->getMessage());
        }
      }
    }
    if ($this->hasInputDefinition($name)) {
      // A NULL value stores the marker, the same resolution
      // setInputValue() applies: reads fall back to the definition default.
      $this->storage[self::STORAGE_CONFIGURATION][$name] = $value === NULL
        ? NULL
        : $this->getTypedDataManager()->create(EffectiveDataDefinition::createFrom($this->getInputDefinition($name)), $value);
    }
    // @todo Check what to do if definition doesn't exist.
    return $this;
  }

  /**
   * Checks if a configuration value exists.
   *
   * @param string $name
   *   The name of the configuration value to check.
   *
   * @return bool
   *   TRUE if the configuration value exists, FALSE otherwise.
   */
  public function hasConfigurationValue(string $name): bool {
    return array_key_exists($name, $this->storage[self::STORAGE_CONFIGURATION] ?? []);
  }

}
