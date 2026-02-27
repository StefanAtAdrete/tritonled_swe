<?php

namespace Drupal\tritonled_compat\EventSubscriber;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ImportFinishedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Clears feeds_item field after import to prevent AJAX 500 errors.
 *
 * Feeds module bug: feeds_item field on entities causes 500 errors in
 * Media Library AJAX and "modified by another user" on product edit forms.
 *
 * This subscriber automatically clears feeds_item on all commerce_product
 * and commerce_product_variation entities after every Feeds import.
 *
 * See: /docs/03-solutions/feeds-item-ajax-bug.md
 */
class FeedsImportSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      FeedsEvents::IMPORT_FINISHED => 'onImportFinished',
    ];
  }

  /**
   * Clears feeds_item on products and variations after import.
   */
  public function onImportFinished(ImportFinishedEvent $event): void {
    $entity_type_manager = \Drupal::entityTypeManager();

    // Clear on product variations.
    $variations = $entity_type_manager->getStorage('commerce_product_variation')->loadMultiple();
    foreach ($variations as $variation) {
      $variation->set('feeds_item', []);
      $variation->save();
    }

    // Clear on products.
    $products = $entity_type_manager->getStorage('commerce_product')->loadMultiple();
    foreach ($products as $product) {
      $product->set('feeds_item', []);
      $product->save();
    }

    \Drupal::logger('tritonled_compat')->info('Cleared feeds_item on all products and variations after Feeds import.');
  }

}
