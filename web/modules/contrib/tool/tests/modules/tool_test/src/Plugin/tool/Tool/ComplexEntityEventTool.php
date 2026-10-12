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
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;

/**
 * A test tool with nested entity inputs and outputs.
 */
#[Tool(
  id: 'complex_entity_event_tool',
  label: new TranslatableMarkup('Complex Entity Event Tool'),
  description: new TranslatableMarkup('A test tool with nested entity inputs and outputs.'),
  operation: ToolOperation::Transform,
  input_definitions: [
    'payload' => new MapInputDefinition(
      label: new TranslatableMarkup('Payload'),
      description: new TranslatableMarkup('A complex input payload.'),
      property_definitions: [
        'title' => new InputDefinition(
          data_type: 'string',
          label: new TranslatableMarkup('Title'),
          description: new TranslatableMarkup('The payload title.'),
        ),
        'profile' => new MapInputDefinition(
          label: new TranslatableMarkup('Profile'),
          description: new TranslatableMarkup('Nested profile data.'),
          property_definitions: [
            'alias' => new InputDefinition(
              data_type: 'string',
              label: new TranslatableMarkup('Alias'),
              description: new TranslatableMarkup('The profile alias.'),
            ),
            'featured_entity' => new EntityInputDefinition(
              data_type: 'node',
              label: new TranslatableMarkup('Featured entity'),
              description: new TranslatableMarkup('A featured profile entity.'),
            ),
          ],
        ),
        'items' => new ListInputDefinition(
          label: new TranslatableMarkup('Items'),
          description: new TranslatableMarkup('Nested payload items.'),
          item_definition: new MapInputDefinition(
            label: new TranslatableMarkup('Item'),
            description: new TranslatableMarkup('A nested payload item.'),
            property_definitions: [
              'label' => new InputDefinition(
                data_type: 'string',
                label: new TranslatableMarkup('Label'),
                description: new TranslatableMarkup('The item label.'),
              ),
              'metadata' => new MapInputDefinition(
                label: new TranslatableMarkup('Metadata'),
                description: new TranslatableMarkup('Deep item metadata.'),
                property_definitions: [
                  'related_entity' => new EntityInputDefinition(
                    data_type: 'node',
                    label: new TranslatableMarkup('Related entity'),
                    description: new TranslatableMarkup('A related item entity.'),
                  ),
                ],
              ),
            ],
          ),
        ),
      ],
    ),
  ],
  output_definitions: [
    'report' => new MapOutputDefinition(
      label: new TranslatableMarkup('Report'),
      description: new TranslatableMarkup('A complex output report.'),
      property_definitions: [
        'summary' => new OutputDefinition(
          data_type: 'string',
          label: new TranslatableMarkup('Summary'),
          description: new TranslatableMarkup('The report summary.'),
        ),
        'profile' => new MapOutputDefinition(
          label: new TranslatableMarkup('Profile'),
          description: new TranslatableMarkup('Nested profile output.'),
          property_definitions: [
            'alias' => new OutputDefinition(
              data_type: 'string',
              label: new TranslatableMarkup('Alias'),
              description: new TranslatableMarkup('The output alias.'),
            ),
            'featured_entity' => new EntityOutputDefinition(
              data_type: 'node',
              label: new TranslatableMarkup('Featured entity'),
              description: new TranslatableMarkup('A featured profile entity.'),
            ),
          ],
        ),
        'items' => new ListOutputDefinition(
          label: new TranslatableMarkup('Items'),
          description: new TranslatableMarkup('Nested output items.'),
          item_definition: new MapOutputDefinition(
            label: new TranslatableMarkup('Item'),
            description: new TranslatableMarkup('A nested output item.'),
            property_definitions: [
              'label' => new OutputDefinition(
                data_type: 'string',
                label: new TranslatableMarkup('Label'),
                description: new TranslatableMarkup('The output item label.'),
              ),
              'metadata' => new MapOutputDefinition(
                label: new TranslatableMarkup('Metadata'),
                description: new TranslatableMarkup('Deep output item metadata.'),
                property_definitions: [
                  'related_entity' => new EntityOutputDefinition(
                    data_type: 'node',
                    label: new TranslatableMarkup('Related entity'),
                    description: new TranslatableMarkup('A related item entity.'),
                  ),
                ],
              ),
            ],
          ),
        ),
      ],
    ),
  ],
)]
final class ComplexEntityEventTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $payload = $values['payload'];
    $profile = $payload['profile'];
    $featured_entity = $profile['featured_entity'];
    if (!$featured_entity instanceof ContentEntityInterface) {
      throw new \UnexpectedValueException('The featured entity input must be a content entity.');
    }
    $featured_entity->set('title', 'Featured entity returned');

    $items = $payload['items'];
    foreach ($items as $delta => $item) {
      $related_entity = $item['metadata']['related_entity'];
      if (!$related_entity instanceof ContentEntityInterface) {
        throw new \UnexpectedValueException('The related entity input must be a content entity.');
      }
      $related_entity->set('title', 'Related entity returned ' . ($delta + 1));
      $items[$delta]['metadata']['related_entity'] = $related_entity;
    }

    return ExecutableResult::success(
      new TranslatableMarkup('Complex entity report generated.'),
      [
        'report' => [
          'summary' => $payload['title'] . ' / ' . $profile['alias'],
          'profile' => $profile,
          'items' => $items,
        ],
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
