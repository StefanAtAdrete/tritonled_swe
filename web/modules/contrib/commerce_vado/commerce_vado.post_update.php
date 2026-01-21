<?php

/**
 * @file
 * Post update functions for Vado.
 */

/**
 * Create the exclude parent field storage.
 */
function commerce_vado_post_update_1() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->import([
    'field.storage.commerce_product_variation.exclude_parent',
  ]);
  $message = implode('<br>', $result->getFailed());

  return $message;
}
