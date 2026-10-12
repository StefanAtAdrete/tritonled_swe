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
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;

/**
 * A test tool whose map outputs are empty.
 *
 * An empty PHP array encodes as [] where the advertised schema says object.
 * The single map covers the top level; the list covers a map nested in a
 * list, which only the recursive dispatch reaches. The plain map covers a map
 * declared on the plain output class. The required outputs repeat these for
 * a schema without a null branch, and the document covers maps nested in the
 * declared properties of a map.
 */
#[Tool(
  id: 'empty_map_output_tool',
  label: new TranslatableMarkup('Empty Map Output Tool'),
  description: new TranslatableMarkup('A test tool that reports empty maps.'),
  operation: ToolOperation::Read,
  output_definitions: [
    'settings' => new MapOutputDefinition(
      label: new TranslatableMarkup('Settings'),
      description: new TranslatableMarkup('A free-form map with nothing in it.'),
      required: FALSE,
    ),
    'entries' => new ListOutputDefinition(
      label: new TranslatableMarkup('Entries'),
      description: new TranslatableMarkup('A list of free-form maps.'),
      item_definition: new MapOutputDefinition(
        label: new TranslatableMarkup('Entry'),
        description: new TranslatableMarkup('A free-form map.'),
        required: FALSE,
      ),
    ),
    'options' => new OutputDefinition(
      data_type: 'map',
      label: new TranslatableMarkup('Options'),
      description: new TranslatableMarkup('A map declared on the plain output class.'),
      required: FALSE,
    ),
    'required_settings' => new MapOutputDefinition(
      label: new TranslatableMarkup('Required settings'),
      description: new TranslatableMarkup('A required free-form map with nothing in it.'),
      required: TRUE,
    ),
    'required_options' => new OutputDefinition(
      data_type: 'map',
      label: new TranslatableMarkup('Required options'),
      description: new TranslatableMarkup('A required map declared on the plain output class.'),
    ),
    'document' => new MapOutputDefinition(
      label: new TranslatableMarkup('Document'),
      description: new TranslatableMarkup('A map whose declared properties are empty maps.'),
      required: TRUE,
      property_definitions: [
        'settings' => new MapOutputDefinition(
          label: new TranslatableMarkup('Settings'),
          description: new TranslatableMarkup('A required free-form map.'),
          required: TRUE,
        ),
        'options' => new OutputDefinition(
          data_type: 'map',
          label: new TranslatableMarkup('Options'),
          description: new TranslatableMarkup('A required map declared on the plain output class.'),
        ),
        'entries' => new ListOutputDefinition(
          label: new TranslatableMarkup('Entries'),
          description: new TranslatableMarkup('A list of required free-form maps.'),
          required: TRUE,
          item_definition: new MapOutputDefinition(
            label: new TranslatableMarkup('Entry'),
            description: new TranslatableMarkup('A free-form map.'),
            required: TRUE,
          ),
        ),
      ],
    ),
  ],
)]
final class EmptyMapOutputTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('Maps reported.'),
      [
        'settings' => [],
        'entries' => [[]],
        'options' => [],
        'required_settings' => [],
        'required_options' => [],
        'document' => [
          'settings' => [],
          'options' => [],
          'entries' => [[]],
        ],
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
