<?php

declare(strict_types=1);

namespace Drupal\tool_ai_connector\Context;

use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Marks a core context definition adapted from a tool definition.
 *
 * The ai module's function-call contract is typed on core context
 * definitions, while the Tool API's input definitions are typed data
 * (#3583016). The adapter classes carry the original tool definition so
 * consumers that need the richer typed-data shape - the override rebuild
 * in ToolPluginBase::applyContextDefinitionOverridesToInputSchema() - can
 * recover it.
 */
interface ToolContextDefinitionInterface {

  /**
   * Gets the tool definition this context definition was adapted from.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   The original tool input definition, unchanged by any mutation made
   *   to the adapter.
   */
  public function getToolDefinition(): DataDefinitionInterface;

  /**
   * Converts the adapter back into a tool definition.
   *
   * The one authoritative interpretation of the adapter: a clone of the
   * wrapped definition's structure carrying the adapter's complete
   * mutable state - label, description, required, multiplicity, default
   * value and constraints (replaced, so removals apply). Runtime data
   * definitions and schema generation both derive from this result.
   *
   * An adapted list, which crosses as core's multiple, maps back the way a
   * multiple definition was read: Count binds the list and every other
   * constraint its item. With the multiple flag cleared, the result is a
   * single value of the item's definition.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   The tool definition the adapter currently describes.
   *
   * @throws \LogicException
   *   When the adapter's data type was changed: the wrapped structure
   *   cannot follow a type change.
   */
  public function toToolDefinition(): DataDefinitionInterface;

}
