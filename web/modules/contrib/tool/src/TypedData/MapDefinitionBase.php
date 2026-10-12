<?php

namespace Drupal\tool\TypedData;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Base class of the map definition family.
 *
 * Extended by MapInputDefinition and MapOutputDefinition. Carries authored
 * property definitions that survive cloning and serialization, and a
 * default value assembled from the properties.
 */
abstract class MapDefinitionBase extends MapDataDefinition {
  use ToolDataDefinitionTrait;

  /**
   * Ensures property definitions are not shared by cloned definitions.
   */
  public function __clone() {
    foreach ($this->propertyDefinitions ?? [] as $name => $definition) {
      $this->propertyDefinitions[$name] = clone $definition;
    }
  }

  /**
   * Keeps the property definitions when serialized.
   *
   * ComplexDataDefinitionBase::__sleep() drops them because entity data
   * definitions derive theirs lazily. Ours are authored, and plugin
   * definitions travel through the discovery cache, so they must survive.
   */
  public function __sleep(): array {
    $vars = get_object_vars($this);
    unset($vars['typedDataManager']);
    return array_keys($vars);
  }

  /**
   * Sets the property definitions.
   *
   * A property declared with multiple: TRUE is converted to the list it
   * means, with a deprecation (#3583049), through the fromMultiple() of its
   * own family, the way a tool definition converts a multiple input or
   * output. A converted list is not multiple, so setting it again converts
   * nothing, and cloning a map copies its properties without setting them.
   *
   * @param array<string, \Drupal\tool\TypedData\InputDefinitionInterface|\Drupal\tool\TypedData\OutputDefinitionInterface> $property_definitions
   *   The property definitions keyed by property name.
   *
   * @return $this
   */
  public function setPropertyDefinitions(array $property_definitions): static {
    foreach ($property_definitions as $name => $property_definition) {
      // PHP turns a numeric key such as the "403" in system.site's page
      // mapping into an integer, so integer keys are names too.
      if (!is_string($name) && !is_int($name)) {
        throw new \InvalidArgumentException('Value definition names must be strings.');
      }
      if (!$property_definition instanceof InputDefinitionInterface && !$property_definition instanceof OutputDefinitionInterface) {
        throw new \InvalidArgumentException('Value definitions must be input or output definitions.');
      }
      if ($property_definition->isMultiple() && !$property_definition instanceof ListDataDefinition) {
        if ($property_definition instanceof InputDefinitionInterface) {
          @trigger_error(sprintf("Declaring the '%s' property with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Declare a ListInputDefinition instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049", $name), E_USER_DEPRECATED);
          $property_definitions[$name] = ListInputDefinition::fromMultiple($property_definition);
        }
        else {
          @trigger_error(sprintf("Declaring the '%s' property with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Declare a ListOutputDefinition instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049", $name), E_USER_DEPRECATED);
          $property_definitions[$name] = ListOutputDefinition::fromMultiple($property_definition);
        }
      }
    }
    $this->propertyDefinitions = $property_definitions;
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * Routes through setPropertyDefinitions(), so a single property passes the
   * same checks as a full set, including each family's boundary.
   */
  public function setPropertyDefinition($name, ?DataDefinitionInterface $definition = NULL): static {
    $property_definitions = $this->getPropertyDefinitions();
    if (isset($definition)) {
      $property_definitions[$name] = $definition;
    }
    else {
      unset($property_definitions[$name]);
    }
    return $this->setPropertyDefinitions($property_definitions);
  }

  /**
   * Gets the property definitions.
   *
   * @return array<string, \Drupal\tool\TypedData\InputDefinitionInterface|\Drupal\tool\TypedData\OutputDefinitionInterface>
   *   The property definitions keyed by property name.
   */
  public function getPropertyDefinitions(): array {
    // @phpstan-ignore return.type
    return $this->propertyDefinitions ?? [];
  }

  /**
   * Gets a specific property definition by name.
   */
  public function getPropertyDefinition($name): ?DataDefinitionInterface {
    $definition = $this->propertyDefinitions[$name] ?? NULL;
    return $definition instanceof InputDefinitionInterface || $definition instanceof OutputDefinitionInterface ? $definition : NULL;
  }

  /**
   * {@inheritdoc}
   *
   * A map with no explicit default that is not required stays NULL, so an
   * absent optional map is not turned into a half-filled array that fails
   * validation. A required one assembles its default from the properties.
   */
  public function getDefaultValue() {
    $default = $this->defaultValue;
    if ($default === NULL && !$this->isRequired()) {
      return NULL;
    }
    if ($default === NULL) {
      foreach ($this->getPropertyDefinitions() as $name => $property_definition) {
        $default[$name] = $property_definition->getDefaultValue();
      }
    }
    return $default;
  }

}
