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
use Drupal\tool\TypedData\EntityOutputDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\user\Entity\User;

/**
 * A test tool that returns a user account as a single and a multiple output.
 *
 * The account is loaded by ID, so a caller that cannot pass entities, such
 * as tool:run, can still get one back. Used to verify that an entity output
 * never exposes its field values, such as the password hash.
 */
#[Tool(
  id: 'account_output_tool',
  label: new TranslatableMarkup('Account Output Tool'),
  description: new TranslatableMarkup('Returns the user account with the given ID.'),
  operation: ToolOperation::Read,
  input_definitions: [
    'account_id' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Account ID'),
      description: new TranslatableMarkup('The ID of the user account to return.'),
    ),
  ],
  output_definitions: [
    'account' => new EntityOutputDefinition(
      data_type: 'entity:user',
      label: new TranslatableMarkup('Account'),
      description: new TranslatableMarkup('The user account.'),
    ),
    'accounts' => new EntityOutputDefinition(
      data_type: 'entity:user',
      label: new TranslatableMarkup('Accounts'),
      description: new TranslatableMarkup('The user account, as a list.'),
      multiple: TRUE,
    ),
    'record' => new MapOutputDefinition(
      label: new TranslatableMarkup('Record'),
      description: new TranslatableMarkup('The user account, inside a free-form map keyed from zero.'),
    ),
  ],
)]
final class AccountOutputTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $account = User::load($values['account_id']);
    if ($account === NULL) {
      return ExecutableResult::failure(new TranslatableMarkup('No account with ID @id.', ['@id' => $values['account_id']]));
    }
    return ExecutableResult::success(
      new TranslatableMarkup('Account loaded.'),
      [
        'account' => $account,
        'accounts' => [$account],
        // Keyed from zero, so the formatted map is wrapped as an object and
        // the entity inside it still needs the reference replacement.
        'record' => [$account],
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
