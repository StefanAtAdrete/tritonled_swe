<?php

namespace Drupal\commerce_vado\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the variation add-on group item entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_vado_group_item",
 *   label = @Translation("Variation add-on group item", context = "Commerce"),
 *   label_collection = @Translation("Variation add-on group items", context = "Commerce"),
 *   label_singular = @Translation("variation add-on group item", context = "Commerce"),
 *   label_plural = @Translation("variation add-on group items", context = "Commerce"),
 *   label_count = @PluralTranslation(
 *     singular = "@count variation add-on group item",
 *     plural = "@count variation add-on group items",
 *     context = "Commerce",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\commerce\CommerceContentEntityStorage",
 *     "views_data" = "Drupal\commerce\CommerceEntityViewsData",
 *     "inline_form" = "Drupal\commerce_vado\Form\VadoGroupItemInlineForm",
 *   },
 *   base_table = "commerce_vado_group_item",
 *   admin_permission = "administer commerce_vado_group",
 *   entity_keys = {
 *     "id" = "group_item_id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class VadoGroupItem extends ContentEntityBase implements VadoGroupItemInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->get('group_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupId() {
    return $this->get('group_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function hasVariation() {
    return !$this->get('variation')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function getVariation() {
    return $this->get('variation')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariationId() {
    return $this->get('variation')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefault() {
    return (bool) $this->get('default')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefault($default = TRUE) {
    $this->set('default', $default);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupItemDiscount() {
    return $this->get('group_item_discount')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupItemDiscountMultiplier() {
    return (100 - $this->getGroupItemDiscount()) / 100;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupItemDiscountAmountMultiplier() {
    return $this->getGroupItemDiscount() / 100;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroupItemDiscount($value) {
    $this->set('group_item_discount', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasGroupItemDiscount() {
    return !$this->get('group_item_discount')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    $tags = parent::getCacheTagsToInvalidate();
    // Invalidate the group caches.
    if ($group = $this->getGroup()) {
      $tags = Cache::mergeTags($tags, $group->getCacheTags());
    }
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // The group backreference, populated by VadoGroup::postSave().
    $fields['group_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Group'))
      ->setDescription(t('The parent group.'))
      ->setSetting('display_description', TRUE)
      ->setSetting('target_type', 'commerce_vado_group')
      ->setReadOnly(TRUE);

    $fields['default'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Default selection'))
      ->setDescription(t('Controls whether this group item is the default selection for the group.'))
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 99,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('Override the variation title. (Optional)'))
      ->setSetting('display_description', TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['variation'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Variation'))
      ->setDescription(t('The product variation reference.'))
      ->setSetting('display_description', TRUE)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_product_variation')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_autocomplete_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['group_item_discount'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Group item discount'))
      ->setDescription(t('Enter a percentage to discount the price of this group item for this group. Use 0 to exclude this group item from the group and bundle discounts.'))
      ->setSetting('display_description', TRUE)
      ->setSetting('max', 100)
      ->setSetting('suffix', '%')
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the group item was created.'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the group item was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

}
