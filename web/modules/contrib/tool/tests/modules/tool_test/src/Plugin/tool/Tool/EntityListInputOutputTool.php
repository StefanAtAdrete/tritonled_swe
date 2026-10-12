<?php

declare(strict_types=1);

namespace Drupal\tool_test\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Drupal\tool\TypedData\EntityInputDefinition;
use Drupal\tool\TypedData\EntityOutputDefinition;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\ListOutputDefinition;

/**
 * A test tool that accepts and returns a list of entities.
 *
 * Uses ListInputDefinition with entity item definitions on the input side
 * and ListOutputDefinition with entity item definitions on the output side.
 * This exercises the list definition path in isEntityListDefinition()
 * and the batch input resolve path in the subscriber.
 */
#[Tool(
  id: 'entity_list_input_output_tool',
  label: new TranslatableMarkup('Entity List Input/Output Tool'),
  description: new TranslatableMarkup('Accepts and returns a list of entities.'),
  operation: ToolOperation::Transform,
  input_definitions: [
    'entities' => new ListInputDefinition(
      label: new TranslatableMarkup('Entities'),
      description: new TranslatableMarkup('A list of entity handles to process.'),
      item_definition: new EntityInputDefinition(
        data_type: 'node',
        label: new TranslatableMarkup('Entity'),
        description: new TranslatableMarkup('A node entity.'),
      ),
    ),
  ],
  output_definitions: [
    'processed' => new ListOutputDefinition(
      label: new TranslatableMarkup('Processed'),
      description: new TranslatableMarkup('The processed entities.'),
      item_definition: new EntityOutputDefinition(
        data_type: 'entity:node',
        label: new TranslatableMarkup('Entity'),
        description: new TranslatableMarkup('A processed node entity.'),
      ),
    ),
  ],
)]
final class EntityListInputOutputTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $entities = $values['entities'];
    $processed = [];

    foreach ($entities as $entity) {
      if (!$entity instanceof ContentEntityInterface) {
        continue;
      }
      $entity->set('title', 'Processed: ' . $entity->label());
      $processed[] = $entity;
    }

    return ExecutableResult::success(
      new TranslatableMarkup('Processed @count entities.', [
        '@count' => count($processed),
      ]),
      ['processed' => $processed],
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(
    array $values,
    AccountInterface $account,
    bool $return_as_object = FALSE,
  ): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
