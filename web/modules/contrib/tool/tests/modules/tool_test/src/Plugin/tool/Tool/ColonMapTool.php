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
 * A test tool with a colon-containing nested property name.
 *
 * Used to verify that reserved characters in property names survive the
 * escape/unescape round-trip through connectors whose providers restrict
 * property names (e.g. the AI module's alphanumeric requirement).
 */
#[Tool(
  id: 'colon_map_tool',
  label: new TranslatableMarkup('Colon Map Tool'),
  description: new TranslatableMarkup('A test tool with a colon in a nested property name.'),
  operation: ToolOperation::Transform,
  input_definitions: [
    'payload' => new MapInputDefinition(
      label: new TranslatableMarkup('Payload'),
      description: new TranslatableMarkup('A map with a colon-containing property name.'),
      property_definitions: [
        'field:tags' => new InputDefinition(
          data_type: 'string',
          label: new TranslatableMarkup('Field tags'),
          description: new TranslatableMarkup('A colon-containing property.'),
        ),
      ],
    ),
  ],
  output_definitions: [
    'payload' => new OutputDefinition(
      data_type: 'any',
      label: new TranslatableMarkup('Payload'),
      description: new TranslatableMarkup('The payload that was received.'),
    ),
  ],
)]
final class ColonMapTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('Colon map test executed.'),
      ['payload' => $values['payload']],
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
