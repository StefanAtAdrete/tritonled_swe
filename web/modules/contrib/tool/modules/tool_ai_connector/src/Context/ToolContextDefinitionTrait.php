<?php

declare(strict_types=1);

namespace Drupal\tool_ai_connector\Context;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\tool\TypedData\EffectiveDataDefinition;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\tool\TypedData\ListDefinitionBase;
use Drupal\tool\TypedData\OutputDefinitionInterface;

/**
 * Carries the tool definition a context definition was adapted from.
 *
 * @see \Drupal\tool_ai_connector\Context\ToolContextDefinitionInterface
 */
trait ToolContextDefinitionTrait {

  /**
   * The tool definition this context definition was adapted from.
   */
  protected DataDefinitionInterface $toolDefinition;

  /**
   * {@inheritdoc}
   */
  public function getToolDefinition(): DataDefinitionInterface {
    return $this->toolDefinition;
  }

  /**
   * {@inheritdoc}
   *
   * Derived from toToolDefinition() rather than from this adapter's flat
   * copy, so typed data created from a context minted on this definition
   * keeps map properties and entity typing while reflecting every
   * supported mutation made to the adapter.
   */
  public function getDataDefinition() {
    return EffectiveDataDefinition::createFrom($this->toToolDefinition());
  }

  /**
   * {@inheritdoc}
   */
  public function toToolDefinition(): DataDefinitionInterface {
    $list = $this->toolDefinition instanceof ListDataDefinitionInterface
      ? $this->toolDefinition
      : NULL;
    $wrapped_data_type = $list !== NULL
      ? $list->getItemDefinition()->getDataType()
      : $this->toolDefinition->getDataType();
    if ($this->getDataType() !== $wrapped_data_type) {
      throw new \LogicException(sprintf(
        "The data type of an adapted context definition cannot be changed: the adapter was changed to '%s' but wraps a '%s' definition.",
        $this->getDataType(),
        $wrapped_data_type,
      ));
    }
    if ($list !== NULL) {
      return $this->isMultiple() ? $this->toListDefinition($list) : $this->toItemDefinition($list);
    }
    $definition = clone $this->toolDefinition;
    // Every core definition class is a DataDefinition; the interface has
    // no setters.
    assert($definition instanceof DataDefinition);
    $definition->setLabel($this->getLabel())
      ->setDescription($this->getDescription())
      ->setRequired($this->isRequired());
    // Replace rather than merge, so a constraint removed from the adapter
    // is removed from the result.
    $definition->setConstraints($this->getConstraints());
    if ($definition instanceof InputDefinitionInterface || $definition instanceof OutputDefinitionInterface) {
      $definition->setMultiple($this->isMultiple());
      $definition->setDefaultValue($this->getDefaultValue());
    }
    return $definition;
  }

  /**
   * Sets the tool definition this context definition was adapted from.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The original tool input definition.
   *
   * @return $this
   *   The current instance.
   *
   * @internal
   *   Written by ToolContextDefinition::fromToolDefinition() only.
   */
  public function setToolDefinition(DataDefinitionInterface $definition): static {
    $this->toolDefinition = $definition;
    return $this;
  }

  /**
   * Gets the constraints a list presents as a core multiple definition.
   *
   * A multiple definition held the constraints of each item together with
   * Count, which binds the list; ListDefinitionBase::splitMultipleConstraints()
   * separates them again.
   *
   * @param \Drupal\Core\TypedData\ListDataDefinitionInterface $list
   *   The list definition.
   *
   * @return array<string, mixed>
   *   The constraints, keyed by name.
   */
  protected static function getMultipleConstraints(ListDataDefinitionInterface $list): array {
    $item_constraints = $list->getItemDefinition()->getConstraints();
    return $item_constraints + array_intersect_key($list->getConstraints(), ['Count' => TRUE]);
  }

  /**
   * Converts a multiple adapter of a list back into the list.
   *
   * Count goes to the list and every other constraint of the adapter to the
   * item, replaced so that removals apply. Only a constraint the adapter
   * added or changed is placed the way a multiple definition placed it.
   * Nothing but Count is ever implied for the list: a multiple's
   * constraints bound its items, so a list-level NotBlank is only ever the
   * author's, and never the adapter's to remove (#3583069). What the
   * presented constraints implied for the list - its Count - leaves it
   * once the adapter removed or changed them. The other constraints of the
   * list, and everything else of the item, are kept.
   *
   * @param \Drupal\Core\TypedData\ListDataDefinitionInterface $list
   *   The wrapped list definition.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   A copy of the list carrying the adapter's state.
   */
  protected function toListDefinition(ListDataDefinitionInterface $list): DataDefinitionInterface {
    $definition = clone $list;
    // Every list definition class is a ListDataDefinition; the interface
    // has no setters.
    assert($definition instanceof ListDataDefinition);
    $definition->setLabel($this->getLabel())
      ->setDescription($this->getDescription())
      ->setRequired($this->isRequired());
    if ($definition instanceof InputDefinitionInterface || $definition instanceof OutputDefinitionInterface) {
      $definition->setDefaultValue($this->getDefaultValue());
    }
    $presented = static::getMultipleConstraints($list);
    $constraints = $this->getConstraints();
    // Compared strictly: options may hold objects, which a loose comparison
    // would walk into.
    $changed = array_filter(
      $constraints,
      static fn ($options, $name): bool => !array_key_exists($name, $presented) || $presented[$name] !== $options,
      ARRAY_FILTER_USE_BOTH,
    );
    [$changed_list_constraints] = ListDefinitionBase::splitMultipleConstraints($changed);
    [$presented_list_constraints] = ListDefinitionBase::splitMultipleConstraints($presented);
    [$kept_list_constraints] = ListDefinitionBase::splitMultipleConstraints(array_diff_key($constraints, $changed));
    $withdrawn = array_diff_key($presented_list_constraints, $kept_list_constraints);
    $definition->setConstraints(array_diff_key($list->getConstraints(), $withdrawn) + $changed_list_constraints);
    // Cloning a list clones its item, so the wrapped item stays untouched.
    $item_definition = $definition->getItemDefinition();
    assert($item_definition instanceof DataDefinition);
    $item_definition->setConstraints(array_diff_key($constraints, ['Count' => TRUE]));
    return $definition;
  }

  /**
   * Converts an adapter of a list whose multiple flag was cleared.
   *
   * A multiple definition whose flag was cleared described one value, so
   * the result is a copy of the item carrying the adapter's state.
   *
   * @param \Drupal\Core\TypedData\ListDataDefinitionInterface $list
   *   The wrapped list definition.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   A copy of the item carrying the adapter's state.
   */
  protected function toItemDefinition(ListDataDefinitionInterface $list): DataDefinitionInterface {
    $definition = clone $list->getItemDefinition();
    // Every core definition class is a DataDefinition; the interface has
    // no setters.
    assert($definition instanceof DataDefinition);
    $definition->setLabel($this->getLabel())
      ->setDescription($this->getDescription())
      ->setRequired($this->isRequired());
    $definition->setConstraints($this->getConstraints());
    if ($definition instanceof InputDefinitionInterface || $definition instanceof OutputDefinitionInterface) {
      $definition->setMultiple(FALSE);
      $definition->setDefaultValue($this->getDefaultValue());
    }
    if ($definition instanceof InputDefinitionInterface && $list instanceof InputDefinitionInterface) {
      $definition->setLocked($list->isLocked());
    }
    return $definition;
  }

}
