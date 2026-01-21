<?php

namespace Drupal\commerce_vado\Form;

use Drupal\commerce_vado\VadoGroupWidgetManager;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\entity\Form\EntityDuplicateFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the group form.
 */
class VadoGroupForm extends ContentEntityForm {

  use EntityDuplicateFormTrait;

  /**
   * The vado group widget plugin manager.
   *
   * @var \Drupal\commerce_vado\VadoGroupWidgetManager
   */
  protected $groupWidgetManager;

  /**
   * Constructs a new VadoGroupForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\commerce_vado\VadoGroupWidgetManager $group_widget_manager
   *   The vado group widget plugin manager.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, VadoGroupWidgetManager $group_widget_manager) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->groupWidgetManager = $group_widget_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('plugin.manager.commerce_vado_group_widget')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Only run when the form is being submitted.
    if ($form_state->isSubmitted()) {
      $values = $form_state->getValues();
      $selected_widget = $values['group_widget'][0]['target_plugin_id'];

      /** @var \Drupal\commerce_vado\Plugin\Commerce\VadoGroupWidget\VadoGroupWidgetInterface $group_widget_plugin */
      $group_widget_plugin = $this->groupWidgetManager->createInstance($selected_widget);
      $allows_multiple = $group_widget_plugin->allowsMultiple();

      $group_item_keys = Element::children($form['group_items']['widget']['entities']);
      $default_items_count = 0;
      foreach ($group_item_keys as $key) {
        /** @var \Drupal\commerce_vado\Entity\VadoGroupItemInterface $group_item */
        $group_item = $form['group_items']['widget']['entities'][$key]['#entity'];
        if ($group_item->isDefault()) {
          $default_items_count++;
        }
      }

      if (!$allows_multiple && $default_items_count > 1) {
        $form_state->setErrorByName('group_widget', $this->t('The @widget widget does not allow multiple default group items. Please ensure you only have one group item set as default or choose a different widget.', ['@widget' => $group_widget_plugin->getLabel()]));
      }
    }

    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $group = $this->getEntity();
    $save_return = $group->save();
    $this->postSave($group, $this->operation);
    $this->messenger()->addMessage($this->t('The variation add-on group %label has been successfully saved.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_vado_group.collection');
    return $save_return;
  }

}
