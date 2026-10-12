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
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\tool\TypedData\InputDefinitionRefinerInterface;

/**
 * Test tool whose 'bundle' input refines from the 'entity_type' input.
 *
 * Once a valid entity_type value arrives, the bundle input gains a Choice
 * constraint listing that type's bundles; an invalid dependency value
 * reverts the bundle input to its original, unconstrained definition.
 */
#[Tool(
  id: 'refining_tool',
  label: new TranslatableMarkup('Refining Tool'),
  description: new TranslatableMarkup('A test tool with a refined input definition.'),
  operation: ToolOperation::Transform,
  input_definitions: [
    'entity_type' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Entity type'),
      description: new TranslatableMarkup('The entity type driving refinement.'),
      required: FALSE,
      constraints: ['Choice' => ['choices' => ['node', 'taxonomy_term', 'user']]],
    ),
    'bundle' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Bundle'),
      description: new TranslatableMarkup('The bundle, refined per entity type.'),
      required: FALSE,
    ),
  ],
  input_definition_refiners: [
    'bundle' => ['entity_type'],
  ],
)]
final class RefiningTool extends ToolBase implements InputDefinitionRefinerInterface {

  /**
   * Bundles per entity type used by the refiner.
   */
  public const BUNDLES = [
    'node' => ['article', 'page'],
    'taxonomy_term' => ['tags'],
    'user' => ['user'],
  ];

  /**
   * {@inheritdoc}
   */
  public function refineInputDefinition(string $name, InputDefinitionInterface $definition, array $values): InputDefinitionInterface {
    if ($name === 'bundle' && isset($values['entity_type'])) {
      if ($values['entity_type'] !== 'taxonomy_term') {
        // Exercise the supported in-place refinement style.
        $definition->addConstraint('Choice', [
          'choices' => self::BUNDLES[$values['entity_type']],
        ]);
        if ($values['entity_type'] === 'node') {
          $definition->addConstraint('NotBlank', []);
        }
      }
      else {
        // Exercise the supported replacement-definition refinement style.
        $definition = new InputDefinition(
          data_type: 'string',
          label: new TranslatableMarkup('Bundle'),
          description: new TranslatableMarkup('A @type bundle.', ['@type' => $values['entity_type']]),
          required: FALSE,
          constraints: ['Choice' => ['choices' => self::BUNDLES[$values['entity_type']] ?? []]],
        );
      }
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('Refining tool executed.'),
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
