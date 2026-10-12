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
 * A test tool exercising every input/output type combination.
 *
 * Used for comprehensive testing of definition normalization, value
 * transforms (including entity handle transforms), and nested structures.
 */
#[Tool(
  id: 'comprehensive_types_tool',
  label: new TranslatableMarkup('Comprehensive Types Tool'),
  description: new TranslatableMarkup('Tests all input/output type combinations.'),
  operation: ToolOperation::Transform,
  input_definitions: [
    // Scalar types.
    'name' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Name'),
      description: new TranslatableMarkup('A string input.'),
    ),
    'count' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Count'),
      description: new TranslatableMarkup('An integer input.'),
    ),
    'enabled' => new InputDefinition(
      data_type: 'boolean',
      label: new TranslatableMarkup('Enabled'),
      description: new TranslatableMarkup('A boolean input.'),
      required: FALSE,
    ),
    // Constraint and type-format coverage. All optional so execution tests
    // can keep invoking the tool without providing them.
    'code' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Code'),
      description: new TranslatableMarkup('A pattern-constrained string input.'),
      required: FALSE,
      constraints: ['Regex' => ['pattern' => '/^[a-z]{3}-\d{2}$/']],
    ),
    'price' => new InputDefinition(
      data_type: 'decimal',
      label: new TranslatableMarkup('Price'),
      description: new TranslatableMarkup('A decimal input.'),
      required: FALSE,
    ),
    'published_at' => new InputDefinition(
      data_type: 'datetime_iso8601',
      label: new TranslatableMarkup('Published at'),
      description: new TranslatableMarkup('A datetime input.'),
      required: FALSE,
      constraints: ['DateTime' => []],
    ),
    'changed' => new InputDefinition(
      data_type: 'timestamp',
      label: new TranslatableMarkup('Changed'),
      description: new TranslatableMarkup('A timestamp input.'),
      required: FALSE,
    ),
    'score' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Score'),
      description: new TranslatableMarkup('A comparison-constrained integer input.'),
      required: FALSE,
      constraints: [
        'GreaterThan' => ['value' => 0],
        'LessThanOrEqual' => ['value' => 100],
      ],
    ),
    // Count describes the list, so the list is declared explicitly via
    // ListInputDefinition (#3583049). The item carries no label or
    // description, as the item of the former multiple input did, so the
    // advertised schema is unchanged.
    'labels' => new ListInputDefinition(
      label: new TranslatableMarkup('Labels'),
      description: new TranslatableMarkup('A count-constrained multiple string input.'),
      required: FALSE,
      constraints: ['Count' => ['min' => 1, 'max' => 5]],
      item_definition: new InputDefinition(
        data_type: 'string',
        label: '',
        description: '',
      ),
    ),
    // NotBlank on the list itself: [] is rejected with a published
    // minItems: 1, while required alone would accept it (#3583069).
    'keywords' => new ListInputDefinition(
      label: new TranslatableMarkup('Keywords'),
      description: new TranslatableMarkup('A not-blank list of strings.'),
      required: FALSE,
      constraints: ['NotBlank' => []],
      item_definition: new InputDefinition(
        data_type: 'string',
        label: '',
        description: '',
      ),
    ),
    'attributes' => new MapInputDefinition(
      label: new TranslatableMarkup('Attributes'),
      description: new TranslatableMarkup('A map without declared properties.'),
      required: FALSE,
    ),
    'contact_email' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Contact email'),
      description: new TranslatableMarkup('An email-constrained string input.'),
      required: FALSE,
      constraints: ['Email' => []],
    ),
    'external_id' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('External ID'),
      description: new TranslatableMarkup('A UUID-constrained string input.'),
      required: FALSE,
      constraints: ['Uuid' => []],
    ),
    'nickname' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Nickname'),
      description: new TranslatableMarkup('A not-blank string input.'),
      required: FALSE,
      constraints: ['NotBlank' => []],
    ),
    'rating' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Rating'),
      description: new TranslatableMarkup('A positive-or-zero integer input.'),
      required: FALSE,
      constraints: ['PositiveOrZero' => []],
    ),
    'mode' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Mode'),
      description: new TranslatableMarkup('An identical-to string input.'),
      required: FALSE,
      constraints: ['IdenticalTo' => ['value' => 'live']],
    ),
    'bundle' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Bundle'),
      description: new TranslatableMarkup('A bundle-constrained string input.'),
      required: FALSE,
      constraints: ['EntityBundleExists' => ['entityTypeId' => 'node']],
    ),
    'country' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Country'),
      description: new TranslatableMarkup('A country-code string input.'),
      required: FALSE,
      constraints: ['CountryCode' => []],
    ),
    'threshold' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Threshold'),
      description: new TranslatableMarkup('An optional but not nullable integer input.'),
      required: FALSE,
      constraints: ['NotNull' => []],
    ),
    // Single entity.
    'entity' => new EntityInputDefinition(
      data_type: 'node',
      label: new TranslatableMarkup('Entity'),
      description: new TranslatableMarkup('A single entity input.'),
    ),
    // Multiple entities, declared as the list a multiple entity input
    // converts to (#3583049): the unlabeled item keeps its schema unchanged.
    'multiple_entities' => new ListInputDefinition(
      label: new TranslatableMarkup('Multiple entities'),
      description: new TranslatableMarkup('Multiple entity inputs.'),
      required: FALSE,
      item_definition: new EntityInputDefinition(
        data_type: 'node',
        label: '',
        description: '',
      ),
    ),
    // List of entities (ListInputDefinition with entity items).
    'entity_list' => new ListInputDefinition(
      label: new TranslatableMarkup('Entity list'),
      description: new TranslatableMarkup('A list of entity inputs.'),
      item_definition: new EntityInputDefinition(
        data_type: 'node',
        label: new TranslatableMarkup('Entity item'),
        description: new TranslatableMarkup('An entity list item.'),
      ),
      required: FALSE,
    ),
    // Map with mixed properties including nested entity.
    'metadata' => new MapInputDefinition(
      label: new TranslatableMarkup('Metadata'),
      description: new TranslatableMarkup('A map with mixed types.'),
      required: FALSE,
      property_definitions: [
        'label' => new InputDefinition(
          data_type: 'string',
          label: new TranslatableMarkup('Label'),
          description: new TranslatableMarkup('A string in a map.'),
        ),
        'related_entity' => new EntityInputDefinition(
          data_type: 'node',
          label: new TranslatableMarkup('Related entity'),
          description: new TranslatableMarkup('An entity nested in a map.'),
          required: FALSE,
        ),
        'tags' => new ListInputDefinition(
          label: new TranslatableMarkup('Tags'),
          description: new TranslatableMarkup('A list of strings in a map.'),
          item_definition: new InputDefinition(
            data_type: 'string',
            label: new TranslatableMarkup('Tag'),
            description: new TranslatableMarkup('A tag.'),
          ),
          required: FALSE,
        ),
      ],
    ),
  ],
  output_definitions: [
    // Scalar output.
    'summary' => new OutputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Summary'),
      description: new TranslatableMarkup('A string output.'),
    ),
    // Single entity output.
    'result_entity' => new EntityOutputDefinition(
      data_type: 'entity:node',
      label: new TranslatableMarkup('Result entity'),
      description: new TranslatableMarkup('A single entity output.'),
      required: FALSE,
    ),
    // Multiple entities, declared as the list a multiple entity output
    // converts to (#3583049): the unlabeled item keeps its schema unchanged.
    'result_multiple' => new ListOutputDefinition(
      label: new TranslatableMarkup('Result multiple'),
      description: new TranslatableMarkup('Multiple entity outputs.'),
      required: FALSE,
      item_definition: new EntityOutputDefinition(
        data_type: 'entity:node',
        label: '',
        description: '',
      ),
    ),
    // List of entities output (ListOutputDefinition).
    'result_list' => new ListOutputDefinition(
      label: new TranslatableMarkup('Result list'),
      description: new TranslatableMarkup('A list of entity outputs.'),
      item_definition: new EntityOutputDefinition(
        data_type: 'entity:node',
        label: new TranslatableMarkup('Entity'),
        description: new TranslatableMarkup('An entity in the list.'),
      ),
      required: FALSE,
    ),
    // Map with entity nested inside.
    'result_map' => new MapOutputDefinition(
      label: new TranslatableMarkup('Result map'),
      description: new TranslatableMarkup('A map output with nested entity.'),
      property_definitions: [
        'note' => new OutputDefinition(
          data_type: 'string',
          label: new TranslatableMarkup('Note'),
          description: new TranslatableMarkup('A string in the map.'),
        ),
        'nested_entity' => new EntityOutputDefinition(
          data_type: 'entity:node',
          label: new TranslatableMarkup('Nested entity'),
          description: new TranslatableMarkup('An entity nested in the output map.'),
        ),
      ],
      required: FALSE,
    ),
  ],
)]
final class ComprehensiveTypesTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $name = $values['name'] ?? '';
    $count = $values['count'] ?? 0;
    $entity = $values['entity'] ?? NULL;
    $multiple_entities = $values['multiple_entities'] ?? [];
    $entity_list = $values['entity_list'] ?? [];
    $metadata = $values['metadata'] ?? [];

    // Build outputs that mirror back the inputs in various structures.
    $outputs = [
      'summary' => "Processed: {$name}, count={$count}",
    ];

    // Single entity output — return the first entity with modified title.
    if ($entity instanceof ContentEntityInterface) {
      $entity->set('title', 'Output: ' . $entity->label());
      $outputs['result_entity'] = $entity;
    }

    // Multiple entity output — return the multiple_entities input.
    if (!empty($multiple_entities)) {
      $outputs['result_multiple'] = $multiple_entities;
    }

    // List output — return the entity_list input.
    if (!empty($entity_list)) {
      $outputs['result_list'] = $entity_list;
    }

    // Map output with nested entity.
    if (!empty($metadata['related_entity']) && $metadata['related_entity'] instanceof ContentEntityInterface) {
      $outputs['result_map'] = [
        'note' => $metadata['label'] ?? 'no label',
        'nested_entity' => $metadata['related_entity'],
      ];
    }

    return ExecutableResult::success(
      new TranslatableMarkup('Comprehensive tool executed.'),
      $outputs,
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
