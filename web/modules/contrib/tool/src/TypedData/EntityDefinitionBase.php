<?php

namespace Drupal\tool\TypedData;

use Drupal\Core\Entity\TypedData\EntityDataDefinition;

/**
 * Base class of the entity definition family.
 *
 * Extended by EntityInputDefinition and EntityOutputDefinition.
 * EntityDataDefinition derives its data type from an EntityType constraint;
 * ours is declared, as it was on EntityContextDefinition, and never carries
 * that constraint because getConstraints() feeds the published schema.
 */
abstract class EntityDefinitionBase extends EntityDataDefinition {
  use ToolDataDefinitionTrait;

  /**
   * Prefixes a bare entity type ID with 'entity:'.
   *
   * EntityContextDefinition::__construct() did this so the class could be
   * constructed as new EntityInputDefinition('node').
   *
   * @param string $data_type
   *   An entity data type, with or without the 'entity:' prefix.
   *
   * @return string
   *   The prefixed data type.
   */
  protected static function normalizeEntityDataType($data_type): string {
    if (!str_starts_with($data_type, 'entity:')) {
      $data_type = "entity:$data_type";
    }
    return $data_type;
  }

  /**
   * {@inheritdoc}
   *
   * DataDefinition::getDataType(): the declared type, not one derived from
   * constraints.
   */
  public function getDataType() {
    return !empty($this->definition['type']) ? $this->definition['type'] : 'any';
  }

  /**
   * {@inheritdoc}
   *
   * EntityContextDefinition::getEntityTypeId(): the data type is the entity
   * type ID prefixed by 'entity:' (7 characters). A bundle-qualified type such
   * as 'entity:node:article' therefore returns 'node:article', as it did
   * there, where nothing read it.
   */
  public function getEntityTypeId() {
    return substr($this->getDataType(), 7);
  }

  /**
   * {@inheritdoc}
   *
   * An entity definition has no authored properties. The inherited version
   * derives field definitions from getEntityTypeId(), which is not an entity
   * type ID for a bundle-qualified type (see above), so the entity type
   * manager throws. Nothing in the tool stack walks the properties of an
   * entity definition, so this returns none rather than leaving that path
   * open.
   */
  public function getPropertyDefinitions(): array {
    return [];
  }

}
