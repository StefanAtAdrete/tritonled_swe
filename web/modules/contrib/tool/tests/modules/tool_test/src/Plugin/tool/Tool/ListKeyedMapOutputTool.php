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
 * A test tool whose maps have the keys 0, 1, 2 in order.
 *
 * A PHP array keyed that way encodes as a JSON array where the advertised
 * schema says object. The outputs cover a map at the top level, declared on
 * the plain output class, as a declared property of a map and as a list
 * item. The list of strings is the control: a list keeps its array.
 */
#[Tool(
  id: 'list_keyed_map_output_tool',
  label: new TranslatableMarkup('List Keyed Map Output Tool'),
  description: new TranslatableMarkup('A test tool that reports maps keyed from zero.'),
  operation: ToolOperation::Read,
  output_definitions: [
    'settings' => new MapOutputDefinition(
      label: new TranslatableMarkup('Settings'),
      description: new TranslatableMarkup('A free-form map keyed from zero.'),
    ),
    'options' => new OutputDefinition(
      data_type: 'map',
      label: new TranslatableMarkup('Options'),
      description: new TranslatableMarkup('A map declared on the plain output class.'),
    ),
    'document' => new MapOutputDefinition(
      label: new TranslatableMarkup('Document'),
      description: new TranslatableMarkup('A map whose declared property is keyed from zero.'),
      property_definitions: [
        'settings' => new MapOutputDefinition(
          label: new TranslatableMarkup('Settings'),
          description: new TranslatableMarkup('A free-form map.'),
        ),
      ],
    ),
    'entries' => new ListOutputDefinition(
      label: new TranslatableMarkup('Entries'),
      description: new TranslatableMarkup('A list of free-form maps.'),
      item_definition: new MapOutputDefinition(
        label: new TranslatableMarkup('Entry'),
        description: new TranslatableMarkup('A free-form map.'),
      ),
    ),
    'tags' => new ListOutputDefinition(
      label: new TranslatableMarkup('Tags'),
      description: new TranslatableMarkup('A list of strings.'),
      item_definition: new OutputDefinition(
        data_type: 'string',
        label: new TranslatableMarkup('Tag'),
        description: new TranslatableMarkup('A tag.'),
      ),
    ),
  ],
)]
final class ListKeyedMapOutputTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('Maps reported.'),
      [
        'settings' => ['a', 'b'],
        'options' => [0 => 'x'],
        'document' => ['settings' => ['a', 'b']],
        'entries' => [['a', 'b']],
        'tags' => ['a', 'b'],
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
