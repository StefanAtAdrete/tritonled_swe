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
 * A tool declaring structured data types on the plain definition classes.
 *
 * The 'map' and 'list' data types are commonly declared on the scalar
 * InputDefinition and OutputDefinition classes rather than the map and
 * list families; value storage and validation must build typed data whose
 * definition class matches the data type regardless (#3583018). The
 * profile and stats maps also carry a list property, declared as the list
 * a multiple property converts to (#3583049).
 */
#[Tool(
  id: 'plain_structure_tool',
  label: new TranslatableMarkup('Plain Structure Tool'),
  description: new TranslatableMarkup('A test tool declaring structured types on plain definition classes.'),
  operation: ToolOperation::Read,
  input_definitions: [
    'options' => new InputDefinition(
      data_type: 'map',
      label: new TranslatableMarkup('Options'),
      description: new TranslatableMarkup('A map declared on the plain input class.'),
      required: FALSE,
    ),
    'rows' => new InputDefinition(
      data_type: 'list',
      label: new TranslatableMarkup('Rows'),
      description: new TranslatableMarkup('A list declared on the plain input class.'),
      required: FALSE,
    ),
    'profile' => new MapInputDefinition(
      label: new TranslatableMarkup('Profile'),
      description: new TranslatableMarkup('A map with a multiple property.'),
      required: FALSE,
      property_definitions: [
        'tags' => new ListInputDefinition(
          label: new TranslatableMarkup('Tags'),
          description: new TranslatableMarkup('A multiple string property.'),
          required: FALSE,
          item_definition: new InputDefinition(
            data_type: 'string',
            label: '',
            description: '',
          ),
        ),
      ],
    ),
  ],
  output_definitions: [
    'summary' => new OutputDefinition(
      data_type: 'map',
      label: new TranslatableMarkup('Summary'),
      description: new TranslatableMarkup('A map declared on the plain output class.'),
      required: FALSE,
    ),
    'items' => new OutputDefinition(
      data_type: 'list',
      label: new TranslatableMarkup('Items'),
      description: new TranslatableMarkup('A list declared on the plain output class.'),
      required: FALSE,
    ),
    'stats' => new MapOutputDefinition(
      label: new TranslatableMarkup('Stats'),
      description: new TranslatableMarkup('A map with a multiple property.'),
      required: FALSE,
      property_definitions: [
        'tags' => new ListOutputDefinition(
          label: new TranslatableMarkup('Tags'),
          description: new TranslatableMarkup('A multiple string property.'),
          required: FALSE,
          item_definition: new OutputDefinition(
            data_type: 'string',
            label: '',
            description: '',
          ),
        ),
      ],
    ),
  ],
)]
final class PlainStructureTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('Executed.'),
      [
        'summary' => $values['options'],
        'items' => $values['rows'],
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
