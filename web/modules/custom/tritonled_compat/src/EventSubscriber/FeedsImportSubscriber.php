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
 * Only clears entities imported by the current feed (via feeds_item reference),
 * to avoid loading all entities in memory when catalog is large (15 000+).
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
   * Clears feeds_item on products and variations imported by this feed.
   */
  public function onImportFinished(ImportFinishedEvent $event): void {
    $feed = $event->getFeed();
    $feed_id = $feed->id();
    $entity_type_manager = \Drupal::entityTypeManager();
    $count = 0;

    // Entity types that may have feeds_item field.
    $entity_types = [
      'commerce_product_variation',
      'commerce_product',
    ];

    foreach ($entity_types as $entity_type) {
      $storage = $entity_type_manager->getStorage($entity_type);

      // Query only entities imported by this specific feed.
      $ids = $storage->getQuery()
        ->condition('feeds_item.target_id', $feed_id)
        ->accessCheck(FALSE)
        ->execute();

      if (empty($ids)) {
        continue;
      }

      // Process in chunks to avoid memory issues with large catalogs.
      foreach (array_chunk($ids, 50) as $chunk) {
        $entities = $storage->loadMultiple($chunk);
        foreach ($entities as $entity) {
          $entity->set('feeds_item', []);
          $entity->save();
          $count++;
        }
      }
    }

    \Drupal::logger('tritonled_compat')->info(
      'Cleared feeds_item on @count entities after Feeds import (feed ID: @feed_id).',
      ['@count' => $count, '@feed_id' => $feed_id]
    );
  }

}
