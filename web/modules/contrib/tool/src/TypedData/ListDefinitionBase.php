<?php

namespace Drupal\tool\TypedData;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Base class of the list definition family.
 *
 * Extended by ListInputDefinition and ListOutputDefinition. Carries an
 * item definition that survives cloning; each family falls back to an
 * "any" item of its own kind when none was declared.
 */
abstract class ListDefinitionBase extends ListDataDefinition {
  use ToolDataDefinitionTrait;

  /**
   * Whether a fromMultiple() conversion produced this list.
   *
   * A plain property, so clones keep it.
   *
   * @internal
   *   Removed together with multiple in
   *   https://git.drupalcode.org/project/tool/-/work_items/3583049.
   */
  protected bool $convertedFromMultiple = FALSE;

  /**
   * Ensures the item definition is not shared by cloned definitions.
   *
   * ListDataDefinition::__clone() clones unconditionally and would fail on a
   * list declared without an item definition.
   */
  public function __clone() {
    if (isset($this->itemDefinition)) {
      $this->itemDefinition = clone $this->itemDefinition;
    }
  }

  /**
   * Splits the constraints of a multiple definition between list and item.
   *
   * A multiple definition means a list of its data type. Count describes the
   * list and moves to it; every other constraint stays on the item, and
   * nothing is implied for the list: a multiple's constraints always bound
   * its items, a required list is present and not NULL, and may be empty,
   * and "must not be empty" is a NotBlank or Count the author declares on
   * the list itself (#3583069).
   *
   * @param array<string, mixed> $constraints
   *   The constraints of the multiple definition, keyed by name.
   *
   * @return array{0: array<string, mixed>, 1: array<string, mixed>}
   *   The constraints of the list, then those of its item.
   *
   * @internal
   *   Removed together with multiple in
   *   https://git.drupalcode.org/project/tool/-/work_items/3583049. The ai
   *   module boundary in tool_ai_connector still reads core's multiple
   *   through it, so it moves there rather than going away.
   */
  public static function splitMultipleConstraints(array $constraints): array {
    return [
      array_intersect_key($constraints, ['Count' => TRUE]),
      array_diff_key($constraints, ['Count' => TRUE]),
    ];
  }

  /**
   * Checks whether a fromMultiple() conversion produced this list.
   *
   * Both families convert through fromMultiple(), and so does the output
   * bridge. A refiner written against a multiple input definition adds the
   * constraints of each item to the list it now receives. The refinement
   * path moves them to the item of a converted list, and only of a
   * converted list: a list declared as a list means its own constraints.
   *
   * @return bool
   *   TRUE for a list converted from a multiple definition, or a clone of
   *   one.
   *
   * @see \Drupal\tool\TypedData\ListInputDefinition::fromMultiple()
   * @see \Drupal\tool\TypedData\ListOutputDefinition::fromMultiple()
   * @see \Drupal\tool\TypedInputsTrait::splitRefinedMultipleConstraints()
   *
   * @internal
   *   Removed together with multiple in
   *   https://git.drupalcode.org/project/tool/-/work_items/3583049.
   */
  public function isConvertedFromMultiple(): bool {
    return $this->convertedFromMultiple;
  }

  /**
   * Sets the item definition.
   *
   * The parameter matches the typed-data parent's type, as PHP requires of
   * an override; the family boundary is enforced at runtime instead.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The item definition.
   *
   * @return $this
   */
  public function setItemDefinition(DataDefinitionInterface $definition): static {
    if (!$definition instanceof InputDefinitionInterface && !$definition instanceof OutputDefinitionInterface) {
      throw new \InvalidArgumentException('The item definition must be an input or output definition.');
    }
    $this->itemDefinition = $definition;
    return $this;
  }

}
