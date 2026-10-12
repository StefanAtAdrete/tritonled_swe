<?php

declare(strict_types=1);

namespace Drupal\tool_test\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\Entity\Node;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Drupal\tool\TypedData\EntityOutputDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\ListOutputDefinition;

/**
 * A test tool that outputs a list of entities (multiple).
 *
 * Used to test the batch hint behavior when a tool returns multiple entities
 * in a single output — the subscriber should produce one summarized hint
 * rather than per-entity hints.
 */
#[Tool(
  id: 'entity_list_output_tool',
  label: new TranslatableMarkup('Entity List Output Tool'),
  description: new TranslatableMarkup('Returns a list of node entities.'),
  operation: ToolOperation::Read,
  input_definitions: [
    'count' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Count'),
      description: new TranslatableMarkup('Number of entities to return.'),
    ),
  ],
  output_definitions: [
    // Declared as the list a multiple entity output converts to (#3583049):
    // the unlabeled item keeps its schema unchanged.
    'entities' => new ListOutputDefinition(
      label: new TranslatableMarkup('Entities'),
      description: new TranslatableMarkup('The loaded node entities.'),
      required: FALSE,
      item_definition: new EntityOutputDefinition(
        data_type: 'entity:node',
        label: '',
        description: '',
      ),
    ),
  ],
)]
final class EntityListOutputTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $count = (int) $values['count'];
    $nodes = Node::loadMultiple();
    $nodes = array_slice(array_values($nodes), 0, $count);

    return ExecutableResult::success(
      new TranslatableMarkup('Loaded @count entities.', [
        '@count' => count($nodes),
      ]),
      ['entities' => $nodes],
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
