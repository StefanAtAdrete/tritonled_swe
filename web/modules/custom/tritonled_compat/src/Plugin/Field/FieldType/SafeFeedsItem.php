<?php

namespace Drupal\tritonled_compat\Plugin\Field\FieldType;

use Drupal\feeds\Plugin\Field\FieldType\FeedsItem;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Safe wrapper for FeedsItem that prevents Layout Builder crashes.
 *
 * Overrides generateSampleValue() to return an empty array, preventing
 * the "fid not found" error when Layout Builder generates sample entities.
 */
class SafeFeedsItem extends FeedsItem {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // Return empty to prevent Layout Builder crash.
    // Feeds module bug: feeds_feed entity uses 'fid' but cleanIds() fails.
    return [];
  }

}
