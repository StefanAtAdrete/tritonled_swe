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
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\tool\TypedData\InputDefinitionRefinerInterface;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;

/**
 * A test tool exercising the refinement narrowing contract.
 *
 * Each refined target demonstrates one branch of the contract: changing the
 * data type or the multiplicity is forbidden, while refining 'any' to a
 * concrete type and narrowing an entity type to a sub-type are permitted.
 * A multiple definition returned for a list converts to a list first.
 */
#[Tool(
  id: 'refiner_contract_tool',
  label: new TranslatableMarkup('Refiner Contract Tool'),
  description: new TranslatableMarkup('Exercises the refinement narrowing contract.'),
  operation: ToolOperation::Transform,
  input_definitions: [
    'type_source' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Type source'),
      description: new TranslatableMarkup('Triggers the type-changing refinement.'),
      required: FALSE,
    ),
    'type_target' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Type target'),
      description: new TranslatableMarkup('Illegally refined to an integer.'),
      required: FALSE,
    ),
    'multiple_source' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Multiple source'),
      description: new TranslatableMarkup('Triggers the multiplicity-changing refinement.'),
      required: FALSE,
    ),
    'multiple_target' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Multiple target'),
      description: new TranslatableMarkup('Illegally refined to a multiple input.'),
      required: FALSE,
    ),
    'any_source' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Any source'),
      description: new TranslatableMarkup('Triggers the any-narrowing refinement.'),
      required: FALSE,
    ),
    'any_target' => new InputDefinition(
      data_type: 'any',
      label: new TranslatableMarkup('Any target'),
      description: new TranslatableMarkup('Legally refined from any to an integer.'),
      required: FALSE,
    ),
    'entity_source' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Entity source'),
      description: new TranslatableMarkup('Triggers the entity-narrowing refinement.'),
      required: FALSE,
    ),
    'entity_target' => new InputDefinition(
      data_type: 'entity',
      label: new TranslatableMarkup('Entity target'),
      description: new TranslatableMarkup('Legally refined from entity to entity:node.'),
      required: FALSE,
    ),
    'multi_to_list_source' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Multi-to-list source'),
      description: new TranslatableMarkup('Triggers the multiple-map-to-list refinement.'),
      required: FALSE,
    ),
    // The list targets are declared as the lists that multiple: TRUE
    // converts to (#3583049), with unlabeled map items.
    'multi_to_list_target' => new ListInputDefinition(
      label: new TranslatableMarkup('Multi-to-list target'),
      description: new TranslatableMarkup('A multiple map legally refined to a list of maps.'),
      required: FALSE,
      item_definition: new MapInputDefinition(
        label: '',
        description: '',
      ),
    ),
    'multi_to_list_bad_source' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Multi-to-list bad source'),
      description: new TranslatableMarkup('Triggers the illegal multiple-map-to-list refinement.'),
      required: FALSE,
    ),
    'multi_to_list_bad_target' => new ListInputDefinition(
      label: new TranslatableMarkup('Multi-to-list bad target'),
      description: new TranslatableMarkup('A multiple map illegally refined to a list of strings.'),
      required: FALSE,
      item_definition: new MapInputDefinition(
        label: '',
        description: '',
      ),
    ),
    'list_to_multiple_source' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('List-to-multiple source'),
      description: new TranslatableMarkup('Triggers the list-to-multiple refinement.'),
      required: FALSE,
    ),
    'list_to_multiple_target' => new ListInputDefinition(
      label: new TranslatableMarkup('List-to-multiple target'),
      description: new TranslatableMarkup('A list of field names refined with a new multiple definition.'),
      required: FALSE,
      item_definition: new InputDefinition(
        data_type: 'string',
        label: '',
        description: '',
      ),
    ),
  ],
  input_definition_refiners: [
    'type_target' => ['type_source'],
    'multiple_target' => ['multiple_source'],
    'any_target' => ['any_source'],
    'entity_target' => ['entity_source'],
    'multi_to_list_target' => ['multi_to_list_source'],
    'multi_to_list_bad_target' => ['multi_to_list_bad_source'],
    'list_to_multiple_target' => ['list_to_multiple_source'],
  ],
)]
final class RefinerContractTool extends ToolBase implements InputDefinitionRefinerInterface {

  /**
   * {@inheritdoc}
   */
  public function refineInputDefinition(string $name, InputDefinitionInterface $definition, array $values): InputDefinitionInterface {
    return match ($name) {
      'type_target' => new InputDefinition(
        data_type: 'integer',
        label: new TranslatableMarkup('Type target'),
        description: new TranslatableMarkup('Illegally refined to an integer.'),
        required: FALSE,
      ),
      'multiple_target' => new InputDefinition(
        data_type: 'string',
        label: new TranslatableMarkup('Multiple target'),
        description: new TranslatableMarkup('Illegally refined to a multiple input.'),
        required: FALSE,
        multiple: TRUE,
      ),
      'any_target' => new InputDefinition(
        data_type: 'integer',
        label: new TranslatableMarkup('Any target'),
        description: new TranslatableMarkup('Legally refined from any to an integer.'),
        required: FALSE,
      ),
      'entity_target' => new EntityInputDefinition(
        data_type: 'node',
        label: new TranslatableMarkup('Entity target'),
        description: new TranslatableMarkup('Legally refined from entity to entity:node.'),
        required: FALSE,
      ),
      // Legal: list of maps → list of maps (same item type).
      'multi_to_list_target' => new ListInputDefinition(
        label: new TranslatableMarkup('Multi-to-list target'),
        description: new TranslatableMarkup('Legally refined from multiple map to list of maps.'),
        required: FALSE,
        item_definition: new MapInputDefinition(
          label: new TranslatableMarkup('List item'),
          description: new TranslatableMarkup('A map item in the list.'),
          required: FALSE,
          property_definitions: [
            'value' => new InputDefinition(
              data_type: 'string',
              label: new TranslatableMarkup('Value'),
              description: new TranslatableMarkup('The value.'),
              required: FALSE,
            ),
            'label' => new InputDefinition(
              data_type: 'string',
              label: new TranslatableMarkup('Label'),
              description: new TranslatableMarkup('The label.'),
              required: FALSE,
            ),
          ],
        ),
      ),
      // Illegal: list of maps → list of strings (item type changes).
      'multi_to_list_bad_target' => new ListInputDefinition(
        label: new TranslatableMarkup('Multi-to-list bad target'),
        description: new TranslatableMarkup('Illegally refined from multiple map to list of strings.'),
        required: FALSE,
        item_definition: new InputDefinition(
          data_type: 'string',
          label: new TranslatableMarkup('String item'),
          description: new TranslatableMarkup('A string item — incompatible with map.'),
          required: FALSE,
        ),
      ),
      // Legal, with a deprecation: a refiner written for multiple: TRUE
      // builds a new definition declaring it. It converts to the list it
      // means (#3583049), which narrows the declared list.
      'list_to_multiple_target' => new InputDefinition(
        data_type: 'string',
        label: $definition->getLabel(),
        description: $definition->getDescription(),
        required: FALSE,
        multiple: TRUE,
        constraints: ['Choice' => ['choices' => ['title', 'body']]],
      ),
      default => $definition,
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('Refiner contract executed.'),
      [],
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
