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
use Drupal\tool\TypedData\EntityInputDefinition;
use Drupal\tool\TypedData\EntityOutputDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\user\Entity\Role;

/**
 * A test tool that returns its entity input through entity outputs.
 *
 * The "mode" input picks the result: "passthrough" returns the entity as
 * both the single and the multiple output, "string" puts a string where the
 * entity belongs, "mixed_list" puts a string in the second slot of the
 * multiple output, "null_item" puts NULL in the first slot, and
 * "config_entity" also returns an unsaved user role. Used to verify that
 * output validation checks the shape of an entity output and not the
 * entity's fields.
 */
#[Tool(
  id: 'entity_output_tool',
  label: new TranslatableMarkup('Entity Output Tool'),
  description: new TranslatableMarkup('A test tool with a single and a multiple entity output.'),
  operation: ToolOperation::Read,
  input_definitions: [
    'entity' => new EntityInputDefinition(
      data_type: 'node',
      label: new TranslatableMarkup('Entity'),
      description: new TranslatableMarkup('The node to return.'),
    ),
    'mode' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Mode'),
      description: new TranslatableMarkup('One of passthrough, string, mixed_list, null_item, or config_entity.'),
      constraints: ['Choice' => ['choices' => ['passthrough', 'string', 'mixed_list', 'null_item', 'config_entity']]],
    ),
  ],
  output_definitions: [
    'entity' => new EntityOutputDefinition(
      data_type: 'entity:node',
      label: new TranslatableMarkup('Entity'),
      description: new TranslatableMarkup('The node that was passed in.'),
    ),
    // Declared as the list a multiple entity output converts to (#3583049):
    // the unlabeled item keeps its schema unchanged.
    'entities' => new ListOutputDefinition(
      label: new TranslatableMarkup('Entities'),
      description: new TranslatableMarkup('The node that was passed in, as a list.'),
      required: FALSE,
      item_definition: new EntityOutputDefinition(
        data_type: 'entity:node',
        label: '',
        description: '',
      ),
    ),
    'role' => new EntityOutputDefinition(
      data_type: 'entity:user_role',
      label: new TranslatableMarkup('Role'),
      description: new TranslatableMarkup('A config entity output.'),
      required: FALSE,
    ),
  ],
)]
final class EntityOutputTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $entity = $values['entity'];
    $outputs = match ($values['mode']) {
      'passthrough' => ['entity' => $entity, 'entities' => [$entity]],
      'string' => ['entity' => 'not an entity'],
      'mixed_list' => ['entity' => $entity, 'entities' => [$entity, 'not an entity']],
      'null_item' => ['entity' => $entity, 'entities' => [NULL]],
      'config_entity' => ['entity' => $entity, 'role' => Role::create(['id' => 'reviewer', 'label' => 'Reviewer'])],
    };
    return ExecutableResult::success(
      new TranslatableMarkup('Entity output tool executed.'),
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
