<?php

namespace Drupal\fences_presets\Form;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the fences presets pattern duplicate form.
 */
class FencesPresetDuplicateForm extends FencesPresetForm {

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity->createDuplicate();
    return $this;
  }

}
