<?php

declare(strict_types=1);

namespace Drupal\tool_test\Plugin\tool\Tool;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Drupal\tool\TypedData\InputDefinition;

/**
 * A test tool that relies on its declared permission for access.
 */
#[Tool(
  id: 'declared_permission_tool',
  label: new TranslatableMarkup('Declared Permission Tool'),
  description: new TranslatableMarkup('A test tool that declares two permissions, both of which are required.'),
  operation: ToolOperation::Read,
  input_definitions: [
    'subject' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Subject'),
      description: new TranslatableMarkup('The subject to read.'),
    ),
  ],
  permission: 'operate test tools,audit test tools',
)]
final class DeclaredPermissionTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(new TranslatableMarkup('Done.'));
  }

}
