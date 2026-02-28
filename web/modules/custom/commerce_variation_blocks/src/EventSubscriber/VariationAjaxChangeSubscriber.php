<?php

namespace Drupal\commerce_variation_blocks\EventSubscriber;

use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\commerce_product\Event\ProductVariationAjaxChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sends AJAX ReplaceCommands for pseudo-field containers on variation change.
 */
class VariationAjaxChangeSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ProductEvents::PRODUCT_VARIATION_AJAX_CHANGE => 'onVariationAjaxChange',
    ];
  }

  /**
   * Replaces pseudo-field containers when the variant changes.
   *
   * @param \Drupal\commerce_product\Event\ProductVariationAjaxChangeEvent $event
   *   The event.
   */
  public function onVariationAjaxChange(ProductVariationAjaxChangeEvent $event) {
    $variation = $event->getProductVariation();
    $response = $event->getResponse();
    $product_id = $variation->getProductId();

    $view_modes = \Drupal::service('entity_display.repository')
      ->getViewModes('commerce_product_variation');

    $skip = ['default', 'cart', 'card', 'summary'];

    $view_builder = \Drupal::entityTypeManager()
      ->getViewBuilder('commerce_product_variation');

    foreach ($view_modes as $view_mode_id => $view_mode_info) {
      if (in_array($view_mode_id, $skip)) {
        continue;
      }

      $selector = '.commerce-variation-block--' . $view_mode_id . '--' . $product_id;

      $rendered = $view_builder->view($variation, $view_mode_id);

      $replacement = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['commerce-variation-block--' . $view_mode_id . '--' . $product_id],
        ],
        'content' => $rendered,
      ];

      $response->addCommand(new ReplaceCommand($selector, $replacement));
    }
  }

}
