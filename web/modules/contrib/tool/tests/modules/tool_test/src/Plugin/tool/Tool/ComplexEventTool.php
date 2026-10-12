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
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;

/**
 * A test tool with complex nested inputs and outputs.
 */
#[Tool(
  id: 'complex_event_tool',
  label: new TranslatableMarkup('Complex Event Tool'),
  description: new TranslatableMarkup('A test tool with complex nested inputs and outputs.'),
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
            'preferences' => new MapInputDefinition(
              label: new TranslatableMarkup('Preferences'),
              description: new TranslatableMarkup('Deep profile preferences.'),
              property_definitions: [
                'channel' => new InputDefinition(
                  data_type: 'string',
                  label: new TranslatableMarkup('Channel'),
                  description: new TranslatableMarkup('The preferred channel.'),
                ),
                'tags' => new ListInputDefinition(
                  label: new TranslatableMarkup('Tags'),
                  description: new TranslatableMarkup('Preference tags.'),
                  required: FALSE,
                  item_definition: new InputDefinition(
                    data_type: 'string',
                    label: new TranslatableMarkup('Tag'),
                    description: new TranslatableMarkup('A preference tag.'),
                    required: FALSE,
                  ),
                ),
              ],
            ),
          ],
        ),
        'items' => new ListInputDefinition(
          label: new TranslatableMarkup('Items'),
          description: new TranslatableMarkup('Nested payload items.'),
          required: FALSE,
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
                required: FALSE,
                property_definitions: [
                  'note' => new InputDefinition(
                    data_type: 'string',
                    label: new TranslatableMarkup('Note'),
                    description: new TranslatableMarkup('The item note.'),
                    required: FALSE,
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
            'preferences' => new MapOutputDefinition(
              label: new TranslatableMarkup('Preferences'),
              description: new TranslatableMarkup('Deep output preferences.'),
              property_definitions: [
                'channel' => new OutputDefinition(
                  data_type: 'string',
                  label: new TranslatableMarkup('Channel'),
                  description: new TranslatableMarkup('The output channel.'),
                ),
                'tags' => new ListOutputDefinition(
                  label: new TranslatableMarkup('Tags'),
                  description: new TranslatableMarkup('Output tags.'),
                  required: FALSE,
                  item_definition: new OutputDefinition(
                    data_type: 'string',
                    label: new TranslatableMarkup('Tag'),
                    description: new TranslatableMarkup('An output tag.'),
                    required: FALSE,
                  ),
                ),
              ],
            ),
          ],
        ),
        'items' => new ListOutputDefinition(
          label: new TranslatableMarkup('Items'),
          description: new TranslatableMarkup('Nested output items.'),
          required: FALSE,
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
                required: FALSE,
                property_definitions: [
                  'note' => new OutputDefinition(
                    data_type: 'string',
                    label: new TranslatableMarkup('Note'),
                    description: new TranslatableMarkup('The output item note.'),
                    required: FALSE,
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
final class ComplexEventTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $payload = $values['payload'];
    $profile = $payload['profile'];

    return ExecutableResult::success(
      new TranslatableMarkup('Complex report generated.'),
      [
        'report' => [
          'summary' => $payload['title'] . ' / ' . $profile['alias'],
          'profile' => $profile,
          'items' => $payload['items'] ?? [],
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
