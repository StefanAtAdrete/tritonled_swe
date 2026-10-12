<?php

declare(strict_types=1);

namespace Drupal\tool\Handle;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\tool\TypedData\OutputDefinition;
use Drupal\tool\TypedData\OutputDefinitionInterface;

/**
 * Handles entity handle upcasting/downcasting logic.
 *
 * This service provides reusable methods for resolving handles to entities
 * (input) and storing entities as handles (output). Connector modules
 * delegate to this after their own invoker checks.
 */
class EntityHandleTransformer {

  /**
   * Constructs an EntityHandleTransformer.
   *
   * @param \Drupal\tool\Handle\ToolHandleStoreInterface $handleStore
   *   The handle store service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected readonly ToolHandleStoreInterface $handleStore,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Resolves a handle to its stored value.
   *
   * If the value is a handle string, resolves it to a content entity. If not a
   * handle, returns the value as-is.
   *
   * @param mixed $value
   *   The input value (possibly a handle string).
   *
   * @return mixed
   *   The resolved value, or the original if not a handle.
   *
   * @throws \Drupal\tool\Handle\InvalidHandleException
   *   Thrown if the value has the handle prefix but is malformed.
   * @throws \Drupal\tool\Handle\UnexpectedHandleValueException
   *   Thrown if the handle resolves to a non-content-entity value.
   */
  public function resolveInput(mixed $value): mixed {
    if (!$this->handleStore->hasHandlePrefix($value)) {
      return $value;
    }
    if (!$this->handleStore->isHandle($value)) {
      throw new InvalidHandleException(sprintf(
        'The handle "%s" is not a valid handle.',
        $value,
      ));
    }

    $resolved = $this->handleStore->resolve($value);
    if (!$resolved instanceof ContentEntityInterface) {
      throw new UnexpectedHandleValueException(sprintf(
        'The handle "%s" does not reference a content entity.',
        $value,
      ));
    }

    return $resolved;
  }

  /**
   * Transforms an entity output value into a handle.
   *
   * If the value is a ContentEntityInterface, stores it and returns a
   * HandleTransformResult with the handle string and a hint. If not an
   * entity, returns NULL (no transformation needed).
   *
   * @param string $output_name
   *   The output key name.
   * @param mixed $value
   *   The output value.
   * @param bool $with_hint
   *   (optional) Whether to build the identity hint. Callers that aggregate
   *   their own hint (e.g. batch transforms) can pass FALSE to skip the hint
   *   construction and its label access check.
   *
   * @return \Drupal\tool\Handle\HandleTransformResult|null
   *   The transform result, or NULL if the value is not an entity.
   */
  public function transformOutput(string $output_name, mixed $value, bool $with_hint = TRUE): ?HandleTransformResult {
    if (!$value instanceof ContentEntityInterface) {
      return NULL;
    }

    $handle = $this->handleStore->store($value);

    if (!$with_hint) {
      return new HandleTransformResult($handle);
    }

    // Build a markdown-kv identity hint for LLM accuracy.
    $hint = "Output `{$output_name}` entity handle:\n";
    $hint .= $this->buildEntityHintLines($value, $handle);
    $hint .= 'Pass this handle to subsequent tool calls that accept entity inputs.';

    return new HandleTransformResult($handle, $hint);
  }

  /**
   * Builds the markdown-kv identity lines for one entity handle.
   *
   * Shared by the single-entity hint and batch hint builders so the per-entity
   * format cannot drift between them.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $handle
   *   The handle referencing the entity.
   *
   * @return string
   *   The identity lines, each terminated by a newline.
   */
  public function buildEntityHintLines(ContentEntityInterface $entity, string $handle): string {
    $hint = "- handle: `{$handle}`\n";
    $hint .= "  type: {$entity->getEntityTypeId()}\n";

    if ($entity->bundle() !== $entity->getEntityTypeId()) {
      $hint .= "  bundle: {$entity->bundle()}\n";
    }

    if ($entity->isNew()) {
      $hint .= "  status: new, unsaved\n";
    }
    else {
      $hint .= "  id: {$entity->id()}\n";
    }

    if ($entity->language()) {
      $hint .= "  language: {$entity->language()->getId()}\n";
    }

    $label = $this->getAccessibleLabel($entity);
    if ($label !== NULL) {
      $hint .= "  label: {$label}\n";
    }

    return $hint;
  }

  /**
   * Transforms an entity input definition to a string for schema.
   *
   * If the definition is a content entity type, returns a string
   * InputDefinition describing it as a handle. Otherwise, returns NULL. The
   * transform is local to the definition: the normalizer dispatches the item
   * of a list and each property of a map on their own, so an entity becomes
   * a handle wherever it sits, and a definition without a label keeps none.
   *
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $definition
   *   The input definition.
   *
   * @return \Drupal\tool\TypedData\InputDefinitionInterface|null
   *   The transformed definition, or NULL if not a content entity type.
   */
  public function transformInputDefinition(InputDefinitionInterface $definition): ?InputDefinitionInterface {
    if (!$this->isContentEntityDefinition($definition)) {
      return NULL;
    }

    $description = $this->appendHandleNote($definition, 'pass a handle from a previous tool call');

    return new InputDefinition(
      'string',
      $definition->getLabel() ?? '',
      // Passed as a plain string: wrapping in a TranslatableMarkup '@'
      // placeholder would HTML-escape it (apostrophes become &#039; in
      // LLM-facing schemas), and the fragments are already translated.
      $description,
      $definition->isRequired(),
      $definition->isMultiple(),
    );
  }

  /**
   * Transforms an entity output definition to a string for schema.
   *
   * If the definition is a content entity type, returns a string output
   * definition describing it as a handle. Otherwise, returns NULL. Like
   * transformInputDefinition(), the transform is local to the definition.
   *
   * @param \Drupal\tool\TypedData\OutputDefinitionInterface $definition
   *   The output definition.
   *
   * @return \Drupal\tool\TypedData\OutputDefinitionInterface|null
   *   The transformed definition, or NULL if not a content entity type.
   */
  public function transformOutputDefinition(OutputDefinitionInterface $definition): ?OutputDefinitionInterface {
    if (!$this->isContentEntityDefinition($definition)) {
      return NULL;
    }

    $description = $this->appendHandleNote($definition, 'returns a handle for use in subsequent tool calls');

    return new OutputDefinition(
      'string',
      $definition->getLabel() ?? '',
      // Passed as a plain string: wrapping in a TranslatableMarkup '@'
      // placeholder would HTML-escape it (apostrophes become &#039; in
      // LLM-facing schemas), and the fragments are already translated.
      $description,
      $definition->isRequired(),
      $definition->isMultiple(),
    );
  }

  /**
   * Appends the handle note to a definition's description.
   *
   * The label is left out on purpose: ContextDefinitionNormalizer prefixes
   * it when building the schema description, so including it here doubles it.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface|\Drupal\Core\Plugin\Context\ContextDefinitionInterface $definition
   *   The original definition.
   * @param string $note
   *   The handle note, without parentheses.
   *
   * @return string
   *   The original description followed by the parenthesized note.
   */
  private function appendHandleNote(DataDefinitionInterface|ContextDefinitionInterface $definition, string $note): string {
    $description = (string) $definition->getDescription();
    return ($description === '' ? '' : $description . ' ') . '(' . $note . ')';
  }

  /**
   * Determines whether a definition represents a content entity.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface|\Drupal\Core\Plugin\Context\ContextDefinitionInterface $definition
   *   The definition. The definition families satisfy the typed-data side
   *   (#3583017); core context definitions stay accepted for external
   *   callers.
   *
   * @return bool
   *   TRUE if the definition represents a content entity.
   */
  public function isContentEntityDefinition(DataDefinitionInterface|ContextDefinitionInterface $definition): bool {
    $data_type = $definition->getDataType();
    if ($data_type === 'entity') {
      return TRUE;
    }
    if (!str_starts_with($data_type, 'entity:')) {
      return FALSE;
    }

    $entity_type_id = substr($data_type, strlen('entity:'));
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id, FALSE);
    return $entity_type !== NULL
      && is_subclass_of($entity_type->getClass(), ContentEntityInterface::class);
  }

  /**
   * Gets the entity label if the user has view access to the label field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to get the label from.
   *
   * @return string|null
   *   The entity label, or NULL if no label key exists or access is denied.
   */
  public function getAccessibleLabel(ContentEntityInterface $entity): ?string {
    $label_key = $entity->getEntityType()->getKey('label');
    if (!$label_key || !$entity->hasField($label_key)) {
      return NULL;
    }

    if (!$entity->get($label_key)->access('view')) {
      return NULL;
    }

    $label = $entity->label();
    return $label !== NULL && $label !== '' ? (string) $label : NULL;
  }

}
