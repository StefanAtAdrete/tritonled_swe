<?php

declare(strict_types=1);

namespace Drupal\tool_ai_connector\Context;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;

/**
 * A core context definition adapted from a tool input definition.
 *
 * @see \Drupal\tool_ai_connector\Context\ToolContextDefinitionInterface
 */
final class ToolContextDefinition extends ContextDefinition implements ToolContextDefinitionInterface {

  use ToolContextDefinitionTrait;

  /**
   * Adapts a tool definition into a core context definition.
   *
   * Copies the scalar facts of the definition - data type, label,
   * description, required, multiple, default value and the author-declared
   * constraints. Structure (map properties, list items) does not fit the
   * core context shape; consumers needing it recover the tool definition
   * through getToolDefinition().
   *
   * A list crosses as core's multiple, the one place the flag still exists
   * (#3583049): the adapter takes the data type of the item, multiple TRUE
   * and the constraints a multiple definition held - those of the item and
   * the list's Count - so the ai module reads a list the way it read a
   * multiple definition before multiple inputs became lists.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The tool input definition.
   *
   * @return \Drupal\Core\Plugin\Context\ContextDefinitionInterface&\Drupal\tool_ai_connector\Context\ToolContextDefinitionInterface
   *   The adapted context definition; an entity-aware one for entity data
   *   types, because core's ContextDefinition constructor rejects them.
   */
  public static function fromToolDefinition(DataDefinitionInterface $definition): ContextDefinitionInterface&ToolContextDefinitionInterface {
    $data_type = $definition->getDataType();
    $multiple = method_exists($definition, 'isMultiple') && $definition->isMultiple();
    $constraints = $definition->getConstraints();
    if ($definition instanceof ListDataDefinitionInterface) {
      $data_type = $definition->getItemDefinition()->getDataType();
      $multiple = TRUE;
      $constraints = self::getMultipleConstraints($definition);
    }
    $default_value = method_exists($definition, 'getDefaultValue') ? $definition->getDefaultValue() : NULL;
    // Only a type naming a concrete entity type routes to the entity-aware
    // class: core's EntityContextDefinition prefixes a bare 'entity' into
    // the non-existent 'entity:entity' data type, while its plain
    // ContextDefinition accepts 'entity' as-is.
    $class = str_starts_with($data_type, 'entity:')
      ? ToolEntityContextDefinition::class
      : self::class;
    $adapted = new $class(
      $data_type,
      $definition->getLabel(),
      $definition->isRequired(),
      $multiple,
      $definition->getDescription(),
      $default_value,
      $constraints,
    );
    return $adapted->setToolDefinition($definition);
  }

}
