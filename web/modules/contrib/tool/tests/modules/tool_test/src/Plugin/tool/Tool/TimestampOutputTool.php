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
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;

/**
 * A test tool whose outputs are returned in a PHP shape, not the wire shape.
 *
 * Each output is a data type whose advertised schema differs from the value
 * a tool naturally returns: a Unix integer for a timestamp, a float for a
 * decimal, and the storage format for a datetime. The map repeats the
 * timestamp one level down so nested members are covered too.
 */
#[Tool(
  id: 'timestamp_output_tool',
  label: new TranslatableMarkup('Timestamp Output Tool'),
  description: new TranslatableMarkup('A test tool that reports a moment in time.'),
  operation: ToolOperation::Read,
  output_definitions: [
    'occurred_at' => new OutputDefinition(
      data_type: 'timestamp',
      label: new TranslatableMarkup('Occurred at'),
      description: new TranslatableMarkup('When the thing happened.'),
    ),
    'amount' => new OutputDefinition(
      data_type: 'decimal',
      label: new TranslatableMarkup('Amount'),
      description: new TranslatableMarkup('A decimal amount.'),
    ),
    'scheduled_for' => new OutputDefinition(
      data_type: 'datetime_iso8601',
      label: new TranslatableMarkup('Scheduled for'),
      description: new TranslatableMarkup('When the thing is scheduled.'),
      constraints: ['DateTime' => []],
    ),
    'summary' => new MapOutputDefinition(
      label: new TranslatableMarkup('Summary'),
      description: new TranslatableMarkup('A map with a timestamp member.'),
      property_definitions: [
        'last_seen' => new OutputDefinition(
          data_type: 'timestamp',
          label: new TranslatableMarkup('Last seen'),
          description: new TranslatableMarkup('When the thing was last seen.'),
        ),
      ],
    ),
  ],
)]
final class TimestampOutputTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('Moment reported.'),
      [
        'occurred_at' => 1785606907,
        'amount' => 19.99,
        'scheduled_for' => '2026-08-01T12:35:07',
        'summary' => ['last_seen' => 1785606907],
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
