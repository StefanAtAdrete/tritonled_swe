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
 * Test tool whose 'field' input refines from two dependencies at once.
 *
 * The refiner passes both dependency values on to a helper declaring
 * non-nullable parameters, without checking either first. That is what a
 * refiner is entitled to do with the dependencies it declared, so invoking it
 * before they all have values raises a \TypeError rather than a violation.
 */
#[Tool(
  id: 'multi_dependency_refining_tool',
  label: new TranslatableMarkup('Multi-dependency refining Tool'),
  description: new TranslatableMarkup('A test tool whose refiner declares two dependencies.'),
  operation: ToolOperation::Transform,
  input_definitions: [
    'entity_type' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Entity type'),
      description: new TranslatableMarkup('The first dependency driving refinement.'),
      required: FALSE,
      constraints: ['Choice' => ['choices' => ['node', 'taxonomy_term']]],
    ),
    'bundle' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Bundle'),
      description: new TranslatableMarkup('The second dependency driving refinement.'),
      required: FALSE,
    ),
    'field' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Field'),
      description: new TranslatableMarkup('The field, refined per entity type and bundle.'),
      required: FALSE,
    ),
  ],
  input_definition_refiners: [
    'field' => ['entity_type', 'bundle'],
  ],
)]
final class MultiDependencyRefiningTool extends ToolBase implements InputDefinitionRefinerInterface {

  /**
   * Field names per "entity type:bundle" pair used by the refiner.
   */
  public const FIELDS = [
    'node:article' => ['title', 'body'],
    'node:page' => ['title'],
    'taxonomy_term:tags' => ['name'],
  ];

  /**
   * {@inheritdoc}
   */
  public function refineInputDefinition(string $name, InputDefinitionInterface $definition, array $values): InputDefinitionInterface {
    if ($name === 'field') {
      $definition->addConstraint('Choice', [
        'choices' => self::fieldsFor($values['entity_type'], $values['bundle']),
      ]);
      // A refiner written against the multiple era also declared NotBlank;
      // on a converted list it binds each item, and nothing is implied for
      // the list (#3583069).
      $definition->addConstraint('NotBlank', []);
    }
    return $definition;
  }

  /**
   * Lists the field names available for an entity type and bundle pair.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $bundle
   *   The bundle name.
   *
   * @return string[]
   *   The field names for that pair, or an empty list when it is unknown.
   */
  private static function fieldsFor(string $entity_type, string $bundle): array {
    return self::FIELDS[$entity_type . ':' . $bundle] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('Multi-dependency refining tool executed.'),
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
