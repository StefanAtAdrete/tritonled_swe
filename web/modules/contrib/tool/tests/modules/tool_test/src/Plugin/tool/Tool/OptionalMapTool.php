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
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool\TypedData\OutputDefinition;

/**
 * A test tool with an optional map input containing property definitions.
 */
#[Tool(
  id: 'optional_map_tool',
  label: new TranslatableMarkup('Optional Map Tool'),
  description: new TranslatableMarkup('A test tool with an optional map input.'),
  operation: ToolOperation::Write,
  input_definitions: [
    'name' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Name'),
      description: new TranslatableMarkup('A required name input.'),
    ),
    'settings' => new MapInputDefinition(
      label: new TranslatableMarkup('Settings'),
      description: new TranslatableMarkup('Optional settings map.'),
      required: FALSE,
      property_definitions: [
        'color' => new InputDefinition(
          data_type: 'string',
          label: new TranslatableMarkup('Color'),
          description: new TranslatableMarkup('A color setting.'),
          required: FALSE,
        ),
        'enabled' => new InputDefinition(
          data_type: 'boolean',
          label: new TranslatableMarkup('Enabled'),
          description: new TranslatableMarkup('Whether enabled.'),
          required: FALSE,
        ),
      ],
    ),
  ],
  output_definitions: [
    'result' => new OutputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Result'),
      description: new TranslatableMarkup('The result message.'),
    ),
  ],
)]
final class OptionalMapTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $name = $values['name'];
    $settings = $values['settings'];
    $message = "Name: $name";
    if (!empty($settings)) {
      $message .= ', Settings: ' . implode(', ', array_filter(array_map('strval', $settings)));
    }

    return ExecutableResult::success(
      new TranslatableMarkup('Done.'),
      ['result' => $message],
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
