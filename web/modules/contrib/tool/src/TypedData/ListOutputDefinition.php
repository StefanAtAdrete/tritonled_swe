<?php

namespace Drupal\tool\TypedData;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Defines a list output definition.
 */
class ListOutputDefinition extends ListDefinitionBase implements OutputDefinitionInterface {
  use ExamplesTrait;

  /**
   * Constructs a new output definition object.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The label of this output definition for the UI.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The description of this output definition for the UI.
   * @param bool $required
   *   Whether the tool must set this output.
   * @param mixed $default_value
   *   The default value of this definition.
   * @param array<string, mixed> $constraints
   *   An array of constraints keyed by the constraint name and a value of an
   *   array constraint options or a NULL.
   * @param \Drupal\tool\TypedData\OutputDefinitionInterface|null $item_definition
   *   The definition for list items.
   */
  public function __construct(string|TranslatableMarkup $label, string|TranslatableMarkup $description, $required = TRUE, $default_value = NULL, array $constraints = [], ?OutputDefinitionInterface $item_definition = NULL) {
    parent::__construct(['type' => 'list']);
    // @phpstan-ignore assign.propertyType
    $this->itemDefinition = $item_definition;
    $this->initializeDefinition($label, $description, $required, FALSE, $default_value, $constraints);
  }

  /**
   * {@inheritdoc}
   *
   * Overridden because this class's constructor takes a label first and
   * fixes the data type, so the inherited factory cannot construct it. The
   * $data_type parameter is ignored.
   */
  public static function create($data_type = 'list') {
    return new static(
      new TranslatableMarkup('List output'),
      new TranslatableMarkup('A list output.'),
    );
  }

  /**
   * Converts a multiple output definition into a list output definition.
   *
   * The output twin of ListInputDefinition::fromMultiple(). The list takes
   * the label, description, required flag and default value of the multiple
   * definition, and the constraints that describe the list, such as Count
   * (see ListDefinitionBase::splitMultipleConstraints()). The item is a copy
   * of the multiple definition that keeps its data type, structure,
   * examples and every other constraint the author declared. It is required
   * and carries no label, description or default value of its own, the way
   * the normalizer describes the item of a multiple definition, so the
   * advertised JSON Schema does not change. An output has no lock to carry
   * over. The list is marked as converted, see isConvertedFromMultiple().
   *
   * @param \Drupal\tool\TypedData\OutputDefinitionInterface $definition
   *   The multiple output definition. It is not modified.
   *
   * @return static
   *   The list output definition.
   *
   * @internal
   *   Removed together with multiple in
   *   https://git.drupalcode.org/project/tool/-/work_items/3583049.
   */
  public static function fromMultiple(OutputDefinitionInterface $definition): static {
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
      ->setDefaultValue(NULL);

    $label = $definition->getLabel();
    $description = $definition->getDescription();
    $list = new static(
      label: $label instanceof TranslatableMarkup ? $label : (string) $label,
      description: $description instanceof TranslatableMarkup ? $description : (string) $description,
      required: $definition->isRequired(),
      default_value: $definition->getDefaultValue(),
      constraints: $list_constraints,
      item_definition: $item,
    );
    $list->convertedFromMultiple = TRUE;
    return $list;
  }

  /**
   * {@inheritdoc}
   *
   * Enforces the output boundary before the shared check in the base runs.
   */
  public function setItemDefinition(DataDefinitionInterface $item_definition): static {
    if (!$item_definition instanceof OutputDefinitionInterface) {
      throw new \InvalidArgumentException('The item definition must be an instance of OutputDefinitionInterface.');
    }
    return parent::setItemDefinition($item_definition);
  }

  /**
   * {@inheritdoc}
   *
   * The fallback item is an output definition, not the base's core one.
   */
  public function getItemDefinition(): OutputDefinitionInterface {
    if (!$this->itemDefinition instanceof OutputDefinitionInterface) {
      return OutputDefinition::create('any');
    }
    return $this->itemDefinition;
  }

}
