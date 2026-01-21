<?php

namespace Drupal\commerce_vado\Form;

use Drupal\commerce_cart\Form\AddToCartForm;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_vado\VadoHelper;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the order item add to cart form.
 */
class VadoGroupAddToCartForm extends AddToCartForm {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Clear the selected groups when a new variation is selected.
    $form['#after_build'][] = [get_class($this), 'clearValues'];

    $form['commerce_vado_group'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#weight' => 50,
    ];

    $wrapper_id = Html::getUniqueId('commerce-product-add-to-cart-form');
    $form += [
      '#wrapper_id' => $wrapper_id,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    $selected_variation = $this->getSelectedVariation($form, $form_state);
    if ($selected_variation && $selected_variation->hasField('variation_groups')) {
      $groups = $selected_variation->get('variation_groups')->referencedEntities();
      /** @var \Drupal\commerce_vado\Entity\VadoGroupInterface $group */
      foreach ($groups as $group) {
        $group_widget = $group->getGroupWidget();
        $group_widget->setSelectedVariation($selected_variation);
        $form['commerce_vado_group']['addon_group_' . $group->id()] = $group_widget->buildElement($form, $form_state);
      }
    }

    return $form;
  }

  /**
   * Should clear values.
   *
   * Determines whether the vado group selection values should
   * be cleared on the form and the request.
   *
   * @return bool
   *   TRUE if values should be cleared, FALSE otherwise.
   */
  protected static function shouldClearValues(array $form, FormStateInterface $form_state) {
    // @todo currently only supports the variation title widget, not attributes.
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element && end($triggering_element['#parents']) == 'variation') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Clears the form and request values for vado group selections.
   *
   * Without this, default values would be ignored and group item selections
   * would persist when a new variation is selected.
   */
  public static function clearValues(array $form, FormStateInterface $form_state) {
    if (static::shouldClearValues($form, $form_state)) {
      // Clear the values in the request for the price resolver.
      $request = \Drupal::request();
      $request->request->set('commerce_vado_group', NULL);

      // Clear the user input so the default group item values are selected.
      $user_input = $form_state->getUserInput();
      if (!empty($user_input['commerce_vado_group'])) {
        unset($user_input['commerce_vado_group']);
      }
      $form_state->setUserInput($user_input);
    }

    return $form;
  }

  /**
   * AJAX #ajax callback: Replaces the rendered fields on variation change.
   *
   * Assumes the existence of a 'selected_variation' in $form_state.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Render\MainContent\MainContentRendererInterface $ajax_renderer */
    $ajax_renderer = \Drupal::service('main_content_renderer.ajax');
    $request = \Drupal::request();
    $route_match = \Drupal::service('current_route_match');
    /** @var \Drupal\Core\Ajax\AjaxResponse $response */
    $response = $ajax_renderer->renderResponse($form, $request, $route_match);

    $variation = ProductVariation::load($form_state->get('selected_variation'));
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $form_state->get('product');
    if ($variation->hasTranslation($product->language()->getId())) {
      $variation = $variation->getTranslation($product->language()->getId());
    }
    /** @var \Drupal\commerce_product\ProductVariationFieldRendererInterface $variation_field_renderer */
    $variation_field_renderer = \Drupal::service('commerce_product.variation_field_renderer');
    $view_mode = $form_state->get('view_mode');
    $variation_field_renderer->replaceRenderedFields($response, $variation, $view_mode);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    $addon_group_item_selections = $form_state->getValue(['commerce_vado_group']);
    if (!empty($addon_group_item_selections)) {
      $addon_group_item_selections = VadoHelper::processGroupSelections($addon_group_item_selections);
    }
    $entity->setData('selected_addon_group_items', $addon_group_item_selections);

    return $entity;
  }

  /**
   * Gets the selected variation.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface|null
   *   The selected variation, or NULL.
   */
  protected function getSelectedVariation(array $form, FormStateInterface $form_state) {
    $variation_storage = $this->entityTypeManager->getStorage('commerce_product_variation');

    $selected_variation_id = $form_state->get('selected_variation');
    if (!$selected_variation_id) {
      $selected_variation_id = $form['purchased_entity']['widget'][0]['variation']['#value'];
    }
    if ($selected_variation_id) {
      $form_state->set('selected_variation', $selected_variation_id);
      return $variation_storage->load($selected_variation_id);
    }

    return NULL;
  }

}
