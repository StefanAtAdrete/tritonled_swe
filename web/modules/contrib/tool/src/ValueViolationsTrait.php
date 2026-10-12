<?php

namespace Drupal\tool;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\TypedDataInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Helpers shared by input and output value validation.
 */
trait ValueViolationsTrait {

  /**
   * Adds item-level violations with their position named.
   *
   * The position lets a caller correcting a long list know which element
   * failed instead of guessing.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationList $violations
   *   The list to add to.
   * @param \Symfony\Component\Validator\ConstraintViolationListInterface $item_violations
   *   The violations found for one item or property value.
   * @param int|string $position
   *   The zero-based array index or the map property name.
   * @param string $kind
   *   Either 'item' (list index) or 'property' (map key).
   */
  protected function addItemViolations(ConstraintViolationList $violations, ConstraintViolationListInterface $item_violations, int|string $position, string $kind = 'item'): void {
    foreach ($item_violations as $violation) {
      $message = $kind === 'property'
        ? new TranslatableMarkup('(property @position) @message', [
          '@position' => $position,
          '@message' => $violation->getMessage(),
        ])
        : new TranslatableMarkup('(item @position) @message', [
          '@position' => $position,
          '@message' => $violation->getMessage(),
        ]);
      $violations->add(new ConstraintViolation(
        $message,
        $violation->getMessageTemplate(),
        $violation->getParameters(),
        $violation->getRoot(),
        $violation->getPropertyPath(),
        $violation->getInvalidValue(),
        $violation->getPlural(),
        $violation->getCode(),
        $violation->getCause()
      ));
    }
  }

  /**
   * Rejects NULL for a required list, or a required map output.
   *
   * EffectiveDataDefinition does not mark a list or a map output required,
   * so typed data adds no NotNull to it and an empty one stays valid
   * (#3583040, #3583069). Required still rejects NULL: this runs core's
   * NotNull constraint on the value explicitly, so the violation is the one
   * typed data reported before.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $typed_data
   *   The list or map typed data holding the NULL value of a required
   *   definition.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   The NotNull violation.
   *
   * @see \Drupal\tool\TypedData\EffectiveDataDefinition
   */
  protected function validateListNotNull(TypedDataInterface $typed_data): ConstraintViolationListInterface {
    $typed_data_manager = $this->getTypedDataManager();
    $not_null = $typed_data_manager->getValidationConstraintManager()->create('NotNull', []);
    return $typed_data_manager->getValidator()->validate($typed_data, [$not_null]);
  }

  /**
   * Gets the definition the items of a list are validated against.
   *
   * Violation messages name a value by the label of its definition. An item
   * declared without a label, such as the item of a list converted from a
   * multiple definition, would leave a blank there, so a copy of the item
   * carrying the label of the list stands in for it. The copy is only
   * validated against and never advertised, so no schema changes.
   *
   * @param \Drupal\Core\TypedData\ListDataDefinition $definition
   *   The list definition.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface|null
   *   The item definition, or NULL when the list has none.
   */
  protected function getItemDefinitionForViolations(ListDataDefinition $definition): ?DataDefinitionInterface {
    $item_definition = $definition->getItemDefinition();
    if ($item_definition instanceof DataDefinition && (string) $item_definition->getLabel() === '' && (string) $definition->getLabel() !== '') {
      $item_definition = clone $item_definition;
      $item_definition->setLabel($definition->getLabel());
    }
    return $item_definition;
  }

  /**
   * Clears core's typed data prototype cache before validating a map or list.
   *
   * Works around a Drupal core bug where TypedDataManager's prototype cache
   * keys do not include the property's data type or constraints. This causes
   * collisions when multiple MapDataDefinitions (or ListDataDefinitions)
   * define a property or item with the same name but different types or
   * constraints.
   *
   * @see https://www.drupal.org/project/drupal/issues/3616328
   */
  protected function resetTypedDataPrototypes(DataDefinitionInterface $definition): void {
    if (!$definition instanceof MapDataDefinition && !$definition instanceof ListDataDefinition) {
      return;
    }
    $typed_data_manager = \Drupal::service('typed_data_manager');
    $ref = new \ReflectionProperty($typed_data_manager, 'prototypes');
    $ref->setAccessible(TRUE);
    $ref->setValue($typed_data_manager, []);
  }

}
