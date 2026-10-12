<?php

namespace Drupal\tool\TypedData;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Defines a list input definition.
 */
class ListInputDefinition extends ListDefinitionBase implements InputDefinitionInterface {
  use InputDefinitionTrait;

  /**
   * Constructs a new list input definition object.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The label of this input definition for the UI.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The description of this input definition for the UI.
   * @param bool $required
   *   Whether the input definition is required.
   * @param mixed $default_value
   *   The default value of this definition.
   * @param array<string, mixed> $constraints
   *   An array of constraints keyed by the constraint name and a value of an
   *   array constraint options or a NULL.
   * @param \Drupal\tool\TypedData\InputDefinitionInterface|null $item_definition
   *   The definition for list items.
   * @param bool $locked
   *   Whether the input is locked, meaning it cannot be changed by context or
   *   configuration.
   */
  public function __construct(string|TranslatableMarkup $label, string|TranslatableMarkup $description, $required = TRUE, $default_value = NULL, array $constraints = [], ?InputDefinitionInterface $item_definition = NULL, bool $locked = FALSE) {
    parent::__construct(['type' => 'list']);
    // @phpstan-ignore assign.propertyType
    $this->itemDefinition = $item_definition;
    $this->initializeDefinition($label, $description, $required, FALSE, $default_value, $constraints);
    $this->isLocked = $locked;
  }

  /**
   * {@inheritdoc}
   *
   * Overridden because this class's constructor takes a label first and
   * fixes the data type, so the inherited factory cannot construct it
   * (#3582962). The $data_type parameter is ignored.
   */
  public static function create($data_type = 'list') {
    return new static(
      new TranslatableMarkup('List input'),
      new TranslatableMarkup('A list input.'),
    );
  }

  /**
   * Converts a multiple input definition into a list input definition.
   *
   * A multiple definition means a list of its data type. The list takes the
   * label, description, required flag, default value and lock of the
   * multiple definition, and the constraints that describe the list, such
   * as Count (see ListDefinitionBase::splitMultipleConstraints()). The item
   * is a copy of the multiple definition that keeps its data type,
   * structure, examples and every other constraint the author declared. It
   * is required and carries no label or description of its own, the way
   * the normalizer describes the item of a multiple definition, so the
   * advertised JSON Schema does not change. The one exception is a default
   * value: the normalizer advertised it on each item, where a whole list
   * does not fit, while a list definition does not advertise its default.
   * The list is marked as converted, see isConvertedFromMultiple().
   *
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $definition
   *   The multiple input definition. It is not modified.
   *
   * @return static
   *   The list input definition.
   *
   * @internal
   *   Removed together with multiple in
   *   https://git.drupalcode.org/project/tool/-/work_items/3583049.
   */
  public static function fromMultiple(InputDefinitionInterface $definition): static {
    [$list_constraints, $item_constraints] = static::splitMultipleConstraints($definition->getConstraints());

    $item = clone $definition;
    // Every definition class is a DataDefinition; the interface has no
    // label, description or constraint setters.
    assert($item instanceof DataDefinition);
    $item->setLabel('')
      ->setDescription('')
      ->setConstraints($item_constraints);
    $item->setMultiple(FALSE)
      ->setRequired(TRUE)
      ->setDefaultValue(NULL)
      ->setLocked(FALSE);

    $label = $definition->getLabel();
    $description = $definition->getDescription();
    $list = new static(
      label: $label instanceof TranslatableMarkup ? $label : (string) $label,
      description: $description instanceof TranslatableMarkup ? $description : (string) $description,
      required: $definition->isRequired(),
      default_value: $definition->getDefaultValue(),
      constraints: $list_constraints,
      item_definition: $item,
      locked: $definition->isLocked(),
    );
    $list->convertedFromMultiple = TRUE;
    return $list;
  }

  /**
   * {@inheritdoc}
   *
   * Enforces the input boundary before the shared check in the base runs.
   */
  public function setItemDefinition(DataDefinitionInterface $item_definition): static {
    if (!$item_definition instanceof InputDefinitionInterface) {
      throw new \InvalidArgumentException('The item definition must be an instance of InputDefinitionInterface.');
    }
    return parent::setItemDefinition($item_definition);
  }

  /**
   * {@inheritdoc}
   *
   * A list declared without an item definition yields an "any" input item.
   */
  public function getItemDefinition(): InputDefinitionInterface {
    if (!$this->itemDefinition instanceof InputDefinitionInterface) {
      return InputDefinition::create('any');
    }
    return $this->itemDefinition;
  }

}
