<?php

namespace Drupal\tool;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\tool\TypedData\EffectiveDataDefinition;
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinitionInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Provides typed outputs functionality for plugins.
 */
trait TypedOutputsTrait {

  use TypedDataTrait;
  use ValueViolationsTrait;

  /**
   * The typed data objects holding the set output values.
   *
   * Only outputs that were explicitly set have an entry. A NULL entry
   * marks an output explicitly set to NULL: it has no typed data - map and
   * list typed data would read a stored NULL back as an empty array - and
   * reads resolve through the definition default, the way a value-less
   * core Context resolved before #3583018.
   *
   * @var array<string, \Drupal\Core\TypedData\TypedDataInterface|null>
   */
  protected array $outputs = [];

  /**
   * {@inheritdoc}
   */
  public function setOutputValue(string $name, mixed $value): static {
    $definition = $this->getOutputDefinition($name);
    $this->outputs[$name] = $value === NULL
      ? NULL
      : $this->getTypedDataManager()->create(EffectiveDataDefinition::createFrom($definition), $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputData(string $name): TypedDataInterface {
    $definition = $this->getOutputDefinition($name);
    $stored = $this->outputs[$name] ?? NULL;
    $value = $stored instanceof TypedDataInterface
      ? $this->getTypedDataManager()->getCanonicalRepresentation($stored)
      : $definition->getDefaultValue();
    return $this->getTypedDataManager()
      ->create(EffectiveDataDefinition::createFrom($definition), $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputValue(string $name): mixed {
    $stored = $this->outputs[$name] ?? NULL;
    if ($stored instanceof TypedDataInterface) {
      return $this->getTypedDataManager()->getCanonicalRepresentation($stored);
    }
    // An output that was never set, or was set to NULL, reads as its
    // default value, while a required output without one throws - the
    // same resolution core's Context applied before #3583018. An
    // undeclared name throws from the definition lookup.
    $definition = $this->getOutputDefinition($name);
    $default_value = $definition->getDefaultValue();
    if (!isset($default_value) && $definition->isRequired()) {
      throw new ContextException("The '{$definition->getDataType()}' context is required and not present.");
    }
    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputValues(): array {
    $values = [];
    foreach ($this->getOutputDefinitions() as $name => $definition) {
      $values[$name] = $this->getOutputValue($name);
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputDefinition(string $name): OutputDefinitionInterface {
    $definition = $this->getPluginDefinition();
    assert($definition instanceof TypedOutputsDefinitionInterface);
    return $definition->getOutputDefinition($name);
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputDefinitions(): array {
    $definition = $this->getPluginDefinition();
    assert($definition instanceof TypedOutputsDefinitionInterface);
    return $definition->getOutputDefinitions();
  }

  /**
   * {@inheritdoc}
   *
   * Outputs are checked the same way inputs are: by shape, not by content.
   * An entity output has to be an entity of the declared type; its fields
   * are not validated, because that would fail a tool for data it merely
   * loaded. Lists and maps are walked per item and property against the
   * declared definitions.
   */
  public function validateOutputs(): ConstraintViolationList {
    $violations = new ConstraintViolationList();
    foreach ($this->getOutputDefinitions() as $name => $definition) {
      $stored = $this->outputs[$name] ?? NULL;
      if ($stored === NULL) {
        // Never set, or explicitly set to NULL: value-less either way.
        if ($definition->isRequired()) {
          $violations->add(new ConstraintViolation(
            new TranslatableMarkup('The @name output is required but was not provided.', ['@name' => $name]),
            '',
            [],
            NULL,
            '',
            NULL
          ));
        }
        continue;
      }
      $value = $this->getTypedDataManager()->getCanonicalRepresentation($stored);
      foreach ($this->validateOutputValue($definition, $value) as $violation) {
        $violations->add(new ConstraintViolation(
          new TranslatableMarkup('Output @name: @message', ['@name' => $name, '@message' => $violation->getMessage()]),
          $violation->getMessageTemplate(),
          $violation->getParameters(),
          $violation->getRoot(),
          $violation->getPropertyPath(),
          $violation->getInvalidValue(),
        ));
      }
    }
    return $violations;
  }

  /**
   * Validates one output value against its definition by shape.
   *
   * Mirrors TypedInputsTrait::validateInputValue(), except that the author
   * constraints on entities and the declared constraints on lists and maps
   * do not run here: optional NULL values pass, multiple values and list
   * items are checked one by one with their position named, map properties
   * are checked against their own definitions, entity values are checked
   * against the effective definition's EntityType and Bundle constraints
   * only - not their fields, not the entity type's own constraints - and
   * everything else goes through core's typed data validation.
   *
   * @param \Drupal\tool\TypedData\OutputDefinitionInterface $definition
   *   The output definition.
   * @param mixed $value
   *   The output value.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationList
   *   The violations found for this value.
   */
  private function validateOutputValue(OutputDefinitionInterface $definition, mixed $value): ConstraintViolationList {
    $violations = new ConstraintViolationList();
    if (!$definition->isRequired() && $value === NULL) {
      return $violations;
    }
    if ($definition->isMultiple() && is_array($value)) {
      $item_definition = clone $definition;
      $item_definition->setMultiple(FALSE);
      // An item slot cannot be omitted: it either holds a value or should
      // not exist.
      $item_definition->setRequired(TRUE);
      foreach ($value as $delta => $item) {
        $this->addItemViolations($violations, $this->validateOutputValue($item_definition, $item), $delta);
      }
      return $violations;
    }
    if ($definition instanceof ListOutputDefinition && is_array($value)) {
      $item_definition = $this->getItemDefinitionForViolations($definition);
      assert($item_definition instanceof OutputDefinitionInterface);
      foreach ($value as $delta => $item) {
        $this->addItemViolations($violations, $this->validateOutputValue($item_definition, $item), $delta);
      }
      return $violations;
    }
    if ($definition instanceof MapOutputDefinition && is_array($value) && $definition->getPropertyDefinitions()) {
      foreach ($definition->getPropertyDefinitions() as $property_name => $property_definition) {
        if (!array_key_exists($property_name, $value)) {
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
        if ($property_definition instanceof OutputDefinitionInterface) {
          $this->addItemViolations($violations, $this->validateOutputValue($property_definition, $value[$property_name]), $property_name, 'property');
        }
      }
      return $violations;
    }
    try {
      $this->resetTypedDataPrototypes($definition);
      $effective_definition = EffectiveDataDefinition::createFrom($definition);
      $typed_data = $this->getTypedDataManager()->create($effective_definition, $value);
      $data_type = $typed_data->getDataDefinition()->getDataType();
      // Every NULL reaches this point, and only for a required definition:
      // a map property or a list item, since validateOutputs() reports a
      // top-level NULL as not provided. A list or map carries no NotNull, so
      // that an empty one stays valid; reject its NULL here.
      if ($value === NULL && ($effective_definition instanceof ListDataDefinitionInterface || $effective_definition instanceof MapDataDefinition)) {
        $violations->addAll($this->validateListNotNull($typed_data));
      }
      elseif ($data_type === 'entity' || str_starts_with($data_type, 'entity:')) {
        if (!$typed_data->getValue() instanceof EntityInterface) {
          $violations->add(new ConstraintViolation(
            new TranslatableMarkup('The @label output must be an entity.', ['@label' => $definition->getLabel()]),
            '',
            [],
            NULL,
            '',
            NULL
          ));
        }
        else {
          // Only the declared-type checks run: the effective definition's
          // dynamic EntityType and Bundle constraints. The entity type's own
          // constraints (EntityChanged, for example) and field validation
          // stay off.
          $constraint_manager = $this->getTypedDataManager()->getValidationConstraintManager();
          $declared_type_constraints = array_intersect_key(
            $typed_data->getDataDefinition()->getConstraints(),
            array_flip(['EntityType', 'Bundle'])
          );
          $constraints = [];
          foreach ($declared_type_constraints as $constraint_name => $constraint_options) {
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
    catch (\Throwable $e) {
      $violations->add(new ConstraintViolation(
        new TranslatableMarkup('The @label output is invalid: @type.', [
          '@label' => $definition->getLabel(),
          '@type' => get_class($e),
        ]),
        '',
        [],
        NULL,
        '',
        NULL
      ));
    }
    return $violations;
  }

}
