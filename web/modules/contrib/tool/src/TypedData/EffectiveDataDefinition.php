<?php

namespace Drupal\tool\TypedData;

use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Builds the data definition a value is stored and validated against.
 *
 * The input and output definition families report only the constraints the
 * tool author declared: the published JSON Schema is built from
 * getConstraints() and must not advertise the typed-data defaults.
 * Validation has the opposite need - typed data validates whatever the
 * definition reports, and dropping the defaults (PrimitiveType, NotNull on
 * required definitions, data-type constraints, the entity classes' dynamic
 * EntityType and Bundle constraints) would silently stop type-checking
 * every value.
 *
 * This builder resolves the tension the way core's ContextDefinition
 * resolved it: it rebuilds a fresh core definition of the declared data
 * type from the type registry - so the definition class always matches
 * what the data type's typed-data class expects - copies the label,
 * description and required flag across, and merges the author constraints
 * into the rebuilt definition's own. A multiple definition rebuilds as a
 * list of its data type. Map property definitions are copied across,
 * rebuilt recursively; list item definitions are not - the rebuilt list
 * keeps its data type's untyped item, matching the previous behavior,
 * with list items validated against the declared item definition by
 * TypedInputsTrait::validateInputValue() instead.
 *
 * A list is the one exception to copying the required flag across. For a
 * list, "required" means present, not non-empty: the published JSON Schema
 * allows [] for a required list, and the advertised minimum comes only from
 * a Count constraint the author declares (#3583040). Typed data disagrees.
 * Each getConstraints() call adds NotNull through
 * TypedDataManager::getDefaultConstraints() whenever isRequired() is TRUE,
 * and core's NotNullConstraintValidator reads an empty list as NULL. So the
 * rebuilt list is not marked required - the only way to keep NotNull off
 * it - and the value walkers reject NULL for a required list themselves.
 *
 * A map is not marked required either, in both directions, for the same
 * reason: required means present and not NULL, and an empty map is still a
 * value (#3583069). NotNull reads an empty map, and a map whose values are
 * all NULL, as NULL. The value walkers reject NULL for a required map
 * themselves, and check the declared properties of a map before typed data
 * sees the value, so a map missing a required property still fails there:
 * core skips the properties of an empty map
 * (https://www.drupal.org/project/drupal/issues/3256536). "Must not be
 * empty" is an explicit NotBlank or Count the author declares.
 *
 * @internal
 *   Value-storage plumbing for TypedInputsTrait, TypedOutputsTrait and
 *   ContextDefinitionNormalizer; not a public API.
 */
final class EffectiveDataDefinition {

  /**
   * Builds the effective definition for value storage and validation.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface|\Drupal\Core\Plugin\Context\ContextDefinitionInterface $definition
   *   The declared definition, usually an input or output definition. A
   *   core context definition - the ai module's override path still
   *   supplies them - answers through its own getDataDefinition() rebuild.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   A fresh core definition carrying the merged constraint set; a list
   *   definition wrapping the data type when the declared definition is
   *   multiple. The declared definition is never modified.
   *
   * @throws \Exception
   *   When the declared data type is not a known typed-data type.
   */
  public static function createFrom(DataDefinitionInterface|ContextDefinitionInterface $definition): DataDefinitionInterface {
    if (!$definition instanceof DataDefinitionInterface) {
      return $definition->getDataDefinition();
    }

    $manager = \Drupal::typedDataManager();
    $is_multiple = ($definition instanceof InputDefinitionInterface || $definition instanceof OutputDefinitionInterface)
      && $definition->isMultiple();
    if ($is_multiple) {
      $effective = $manager->createListDataDefinition($definition->getDataType());
    }
    else {
      $effective = $manager->createDataDefinition($definition->getDataType());
    }
    if (!$effective) {
      throw new \Exception("The data type '{$definition->getDataType()}' is invalid");
    }
    // The manager is typed as returning the interface, which has no
    // setters; every core definition class is a DataDefinition.
    assert($effective instanceof DataDefinition);
    // A required list or map is not marked required here, so typed data
    // adds no NotNull to it; see the class documentation.
    $may_be_empty = $effective instanceof ListDataDefinitionInterface
      || $effective instanceof MapDataDefinition;
    $effective->setLabel($definition->getLabel())
      ->setDescription($definition->getDescription())
      ->setRequired($definition->isRequired() && !$may_be_empty);
    $effective->setConstraints($effective->getConstraints() + $definition->getConstraints());

    if ($is_multiple) {
      assert($effective instanceof ListDataDefinitionInterface);
      $item = $effective->getItemDefinition();
      assert($item instanceof DataDefinition);
      // Scalar input definitions applied their declared constraints to each
      // item of a multiple value; keep that scope unchanged.
      if ($definition instanceof InputDefinition) {
        foreach ($definition->getConstraints() as $constraint_name => $options) {
          $item->addConstraint($constraint_name, $options);
        }
      }
      self::copyPropertyDefinitions($definition, $item);
    }
    else {
      self::copyPropertyDefinitions($definition, $effective);
    }
    return $effective;
  }

  /**
   * Copies a map family definition's properties onto a rebuilt definition.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $declared
   *   The declared definition.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $effective
   *   The freshly rebuilt definition, or the item definition of the list
   *   wrapping a multiple declared definition.
   */
  private static function copyPropertyDefinitions(DataDefinitionInterface $declared, DataDefinitionInterface $effective): void {
    if ($declared instanceof MapDataDefinition && $effective instanceof MapDataDefinition) {
      foreach ($declared->getPropertyDefinitions() as $name => $property) {
        $effective->setPropertyDefinition($name, self::createFrom($property));
      }
    }
  }

}
