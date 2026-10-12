<?php

declare(strict_types=1);

namespace Drupal\tool_test\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\OutputDefinition;

/**
 * A test tool whose result honors or breaks its required output on demand.
 *
 * The "mode" input picks the result: "complete" sets the required output,
 * "omit" leaves it out, "null" sets it to NULL, "invalid" sets a value of
 * the wrong type, and "url_object" puts a Url object in the uri output,
 * which makes core's validation throw a TypeError. Used
 * to verify that execute() turns a success with broken outputs into a
 * failure.
 */
#[Tool(
  id: 'required_output_tool',
  label: new TranslatableMarkup('Required Output Tool'),
  description: new TranslatableMarkup('A test tool that can omit or break its required output.'),
  operation: ToolOperation::Read,
  input_definitions: [
    'mode' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Mode'),
      description: new TranslatableMarkup('One of complete, omit, null, invalid, or url_object.'),
      constraints: ['Choice' => ['choices' => ['complete', 'omit', 'null', 'invalid', 'url_object']]],
    ),
  ],
  output_definitions: [
    'answer' => new OutputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Answer'),
      description: new TranslatableMarkup('A required string output.'),
    ),
    'link' => new OutputDefinition(
      data_type: 'uri',
      label: new TranslatableMarkup('Link'),
      description: new TranslatableMarkup('An optional uri output.'),
      required: FALSE,
    ),
  ],
)]
final class RequiredOutputTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $outputs = match ($values['mode']) {
      'complete' => ['answer' => 'forty-two'],
      'omit' => [],
      'null' => ['answer' => NULL],
      'invalid' => ['answer' => ['not' => 'a string']],
      'url_object' => ['answer' => 'forty-two', 'link' => Url::fromRoute('<front>')],
    };
    return ExecutableResult::success(
      new TranslatableMarkup('Required output tool executed.'),
      $outputs,
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
