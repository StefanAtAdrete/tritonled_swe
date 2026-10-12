<?php

declare(strict_types=1);

namespace Drupal\tool_test\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;

/**
 * A test tool with scalar integer values nested in several structure types.
 */
#[Tool(
  id: 'recursive_scalar_transform_tool',
  label: new TranslatableMarkup('Recursive Scalar Transform Tool'),
  description: new TranslatableMarkup('A test tool with scalar values in nested structures.'),
  operation: ToolOperation::Transform,
  input_definitions: [
    // Declared as the list a multiple input converts to (#3583049), like the
    // multiple_numbers output below.
    'multiple_numbers' => new ListInputDefinition(
      label: new TranslatableMarkup('Multiple numbers'),
      description: new TranslatableMarkup('Integer values using a multiple definition.'),
      item_definition: new InputDefinition(
        data_type: 'integer',
        label: '',
        description: '',
      ),
    ),
    'number_list' => new ListInputDefinition(
      label: new TranslatableMarkup('Number list'),
      description: new TranslatableMarkup('Integer values using a list definition.'),
      item_definition: new InputDefinition(
        data_type: 'integer',
        label: new TranslatableMarkup('List number'),
        description: new TranslatableMarkup('An integer list item.'),
      ),
    ),
    'number_map' => new MapInputDefinition(
      label: new TranslatableMarkup('Number map'),
      description: new TranslatableMarkup('Integer values using a map definition.'),
      property_definitions: [
        'map_number' => new InputDefinition(
          data_type: 'integer',
          label: new TranslatableMarkup('Map number'),
          description: new TranslatableMarkup('An integer map property.'),
        ),
      ],
    ),
    // A list of lists is declared level by level: a list definition takes
    // no multiple flag (#3583049). The inner list carries no label or
    // description, as the item of a multiple list did.
    'multiple_number_lists' => new ListInputDefinition(
      label: new TranslatableMarkup('Multiple number lists'),
      description: new TranslatableMarkup('Integer values using a multiple list definition.'),
      item_definition: new ListInputDefinition(
        label: '',
        description: '',
        item_definition: new InputDefinition(
          data_type: 'integer',
          label: new TranslatableMarkup('Multiple list number'),
          description: new TranslatableMarkup('An integer multiple list item.'),
        ),
      ),
    ),
  ],
  output_definitions: [
    // Declared as the list a multiple output converts to (#3583049).
    'multiple_numbers' => new ListOutputDefinition(
      label: new TranslatableMarkup('Multiple numbers'),
      description: new TranslatableMarkup('Integer values using a multiple definition.'),
      required: TRUE,
      item_definition: new OutputDefinition(
        data_type: 'integer',
        label: '',
        description: '',
      ),
    ),
    'number_list' => new ListOutputDefinition(
      label: new TranslatableMarkup('Number list'),
      description: new TranslatableMarkup('Integer values using a list definition.'),
      item_definition: new OutputDefinition(
        data_type: 'integer',
        label: new TranslatableMarkup('List number'),
        description: new TranslatableMarkup('An integer list item.'),
      ),
    ),
    'number_map' => new MapOutputDefinition(
      label: new TranslatableMarkup('Number map'),
      description: new TranslatableMarkup('Integer values using a map definition.'),
      property_definitions: [
        'map_number' => new OutputDefinition(
          data_type: 'integer',
          label: new TranslatableMarkup('Map number'),
          description: new TranslatableMarkup('An integer map property.'),
        ),
      ],
    ),
    'multiple_number_lists' => new ListOutputDefinition(
      label: new TranslatableMarkup('Multiple number lists'),
      description: new TranslatableMarkup('Integer values using a multiple list definition.'),
      item_definition: new ListOutputDefinition(
        label: '',
        description: '',
        item_definition: new OutputDefinition(
          data_type: 'integer',
          label: new TranslatableMarkup('Multiple list number'),
          description: new TranslatableMarkup('An integer multiple list item.'),
        ),
      ),
    ),
  ],
)]
final class RecursiveScalarTransformTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('Scalar structures transformed.'),
      [
        'multiple_numbers' => $values['multiple_numbers'],
        'number_list' => $values['number_list'],
        'number_map' => $values['number_map'],
        'multiple_number_lists' => $values['multiple_number_lists'],
      ],
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
