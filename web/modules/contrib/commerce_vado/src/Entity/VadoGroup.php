<?php

namespace Drupal\commerce_vado\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the variation add-on group entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_vado_group",
 *   label = @Translation("Variation add-on group", context = "Commerce"),
 *   label_collection = @Translation("Variation add-on groups", context = "Commerce"),
 *   label_singular = @Translation("variation add-on group", context = "Commerce"),
 *   label_plural = @Translation("variation add-on groups", context = "Commerce"),
 *   label_count = @PluralTranslation(
 *     singular = "@count variation add-on group",
 *     plural = "@count variation add-on groups",
 *     context = "Commerce",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\commerce\CommerceContentEntityStorage",
 *     "list_builder" = "Drupal\commerce_vado\VadoGroupListBuilder",
 *     "views_data" = "Drupal\commerce\CommerceEntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\commerce_vado\Form\VadoGroupForm",
 *       "add" = "Drupal\commerce_vado\Form\VadoGroupForm",
 *       "edit" = "Drupal\commerce_vado\Form\VadoGroupForm",
 *       "duplicate" = "Drupal\commerce_vado\Form\VadoGroupForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\entity\Routing\AdminHtmlRouteProvider",
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_vado_group",
 *   admin_permission = "administer commerce_vado_group",
 *   entity_keys = {
 *     "id" = "group_id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/vado-groups/{commerce_vado_group}",
 *     "add-form" = "/admin/commerce/vado-groups/add",
 *     "edit-form" = "/admin/commerce/vado-groups/{commerce_vado_group}/edit",
 *     "duplicate-form" = "/admin/commerce/vado-groups/{commerce_vado_group}/duplicate",
 *     "delete-form" = "/admin/commerce/vado-groups/{commerce_vado_group}/delete",
 *     "delete-multiple-form" = "/admin/commerce/vado-groups/delete",
 *     "collection" = "/admin/commerce/vado-groups",
 *   },
 *   field_ui_base_route = "entity.commerce_vado_group.collection",
 * )
 */
class VadoGroup extends ContentEntityBase implements VadoGroupInterface {

  use EntityChangedTrait;

  /**
   * Duplicates commerce vado groups and their group items.
   */
  public function createDuplicate() {
    $duplicate_group = parent::createDuplicate();
    /** @var \Drupal\commerce_vado\Entity\VadoGroupInterface $duplicate_group */
    // Set the timestamp on the duplicate_group.
    $timestamp = \Drupal::time()->getRequestTime();
    $duplicate_group->setCreatedTime($timestamp);
    $duplicate_group_items = [];
    foreach ($duplicate_group->getItems() as $group_item) {
      // Create a duplicate for each group_item so it has a new id.
      $duplicate_group_item = $group_item->createDuplicate();
      // Set the timestamp on the duplicate_group_item as well.
      $timestamp = \Drupal::time()->getRequestTime();
      $duplicate_group_item->setCreatedTime($timestamp);
      // Set the group_id for the duplicate_group_item.
      $duplicate_group_item->set('group_id', $duplicate_group->id());
      // Add the duplicate_group_item to the duplicate_group_items array.
      $duplicate_group_items[] = $duplicate_group_item;
    }
    // Set the duplicate_group_items to the duplicate_group.
    $duplicate_group->setItems($duplicate_group_items);

    return $duplicate_group;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupWidget() {
    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItemInterface $field_item */
    $field_item = $this->get('group_widget')->first();
    /** @var \Drupal\commerce_vado\Plugin\Commerce\VadoGroupWidget\VadoGroupWidgetInterface $plugin */
    $plugin = $field_item->getTargetInstance();
    $plugin->setParentEntity($this);

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    return $this->get('group_items')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function setItems(array $group_items) {
    $this->set('group_items', $group_items);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasItems() {
    return !$this->get('group_items')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function addItem(VadoGroupItemInterface $group_item) {
    if (!$this->hasItem($group_item)) {
      $this->get('group_items')->appendItem($group_item);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem(VadoGroupItemInterface $group_item) {
    $index = $this->getItemIndex($group_item);
    if ($index !== FALSE) {
      $this->get('group_items')->offsetUnset($index);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasItem(VadoGroupItemInterface $group_item) {
    return $this->getItemIndex($group_item) !== FALSE;
  }

  /**
   * Gets the index of the given group item.
   *
   * @param \Drupal\commerce_vado\Entity\VadoGroupItemInterface $group_item
   *   The group item.
   *
   * @return int|bool
   *   The index of the given group item, or FALSE if not found.
   */
  protected function getItemIndex(VadoGroupItemInterface $group_item) {
    $values = $this->get('group_items')->getValue();
    $group_item_ids = array_map(function ($value) {
      return $value['target_id'];
    }, $values);

    return array_search($group_item->id(), $group_item_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultItems() {
    // The group ID must be known to load default items.
    if (!$this->isNew()) {
      $group_item_storage = $this->entityTypeManager()->getStorage('commerce_vado_group_item');
      return $group_item_storage->loadByProperties([
        'group_id' => $this->id(),
        'default' => TRUE,
      ]);
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function hasDefaultItems() {
    // The group ID must be known to load default items.
    if (!$this->isNew()) {
      $group_item_storage = $this->entityTypeManager()->getStorage('commerce_vado_group_item');
      $count = $group_item_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('group_id', $this->id())
        ->condition('default', TRUE)
        ->count()->execute();

      return $count > 0;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired() {
    return (bool) $this->get('required')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequired($required = TRUE) {
    $this->set('required', $required);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupDiscount() {
    return $this->get('group_discount')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupDiscountMultiplier() {
    return (100 - $this->getGroupDiscount()) / 100;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupDiscountAmountMultiplier() {
    return $this->getGroupDiscount() / 100;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroupDiscount($value) {
    $this->set('group_discount', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasGroupDiscount() {
    return !$this->get('group_discount')->isEmpty();
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
    // Get the setting for how to handle unpublished variations.
    $allow_unpublished = \Drupal::config('commerce_vado.settings')->get('allow_unpublished_variations');
    $tags = parent::getCacheTagsToInvalidate();
    // Invalidate the variation caches.
    foreach ($this->getItems() as $group_item) {
      // If the group item doesn't have a variation entity,
      // or if the settings don't allow unpublished variations, continue.
      if (!$group_item->getVariation() || !$allow_unpublished && !$group_item->getVariation()->isPublished()) {
        continue;
      }
      $tags = Cache::mergeTags($tags, $group_item->getVariation()->getCacheTags());
    }
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Ensure there's a back-reference on each group item.
    foreach ($this->getItems() as $group_item) {
      if ($group_item->group_id->isEmpty()) {
        $group_item->group_id = $this->id();
        $group_item->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Delete the group items of a deleted group.
    $group_items = [];
    /** @var \Drupal\commerce_vado\Entity\VadoGroupInterface $entity */
    foreach ($entities as $entity) {
      foreach ($entity->getItems() as $group_item) {
        $group_items[$group_item->id()] = $group_item;
      }
    }
    $group_item_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_vado_group_item');
    $group_item_storage->delete($group_items);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The variation add-on group title.'))
      ->setSetting('display_description', TRUE)
      ->setRequired(TRUE)
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

    $fields['group_widget'] = BaseFieldDefinition::create('commerce_plugin_item:commerce_vado_group_widget')
      ->setLabel(t('Widget'))
      ->setDescription(t('Controls how the group items are selected.'))
      ->setSetting('display_description', TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_plugin_select',
        'weight' => -2,
      ]);

    $fields['required'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Required'))
      ->setDescription(t('Controls whether the group is displayed as required.'))
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['group_discount'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Group discount'))
      ->setDescription(t('Enter a percentage to discount the price of the group items for this group. Use 0 to exclude this group from the bundle discount on the parent variation.'))
      ->setSetting('display_description', TRUE)
      ->setSetting('max', 100)
      ->setSetting('suffix', '%')
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['group_items'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Group items'))
      ->setDescription(t('The variation add-on group items.'))
      ->setSetting('display_description', TRUE)
      ->setRequired(TRUE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'commerce_vado_group_item')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_complex',
        'weight' => 0,
        'settings' => [
          'override_labels' => TRUE,
          'label_singular' => t('group item'),
          'label_plural' => t('group items'),
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the variation add-on group was created.'))
      ->setSetting('display_description', TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the variation add-on group was was last edited.'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
