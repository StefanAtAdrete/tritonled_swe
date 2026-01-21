<?php

namespace Drupal\commerce_vado\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Defines the interface for vado group items.
 */
interface VadoGroupItemInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the parent group.
   *
   * @return \Drupal\commerce_vado\Entity\VadoGroupInterface|null
   *   The group, or NULL.
   */
  public function getGroup();

  /**
   * Gets the parent group ID.
   *
   * @return int|null
   *   The group ID, or NULL.
   */
  public function getGroupId();

  /**
   * Gets whether the group item has a variation.
   *
   * @return bool
   *   TRUE if the group item has a variation, FALSE otherwise.
   */
  public function hasVariation();

  /**
   * Gets the variation.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface|null
   *   The variation, or NULL.
   */
  public function getVariation();

  /**
   * Gets the variation ID.
   *
   * @return int
   *   The variation ID.
   */
  public function getVariationId();

  /**
   * Gets the group item title.
   *
   * @return string
   *   The group item title
   */
  public function getTitle();

  /**
   * Sets the group item title.
   *
   * @param string $title
   *   The group item title.
   *
   * @return $this
   */
  public function setTitle($title);

  /**
   * Gets whether the group item is default.
   *
   * @return bool
   *   TRUE if the group item is default, FALSE otherwise.
   */
  public function isDefault();

  /**
   * Sets whether the group item is default.
   *
   * @return $this
   */
  public function setDefault($default = TRUE);

  /**
   * Gets the group item discount percentage.
   *
   * @return int
   *   The group item discount percentage.
   */
  public function getGroupItemDiscount();

  /**
   * Gets the group item discount multiplier.
   *
   * The multiplier is the value the price will be multiplied by
   * to apply the discount.
   *
   * @return float
   *   The group item discount multiplier.
   */
  public function getGroupItemDiscountMultiplier();

  /**
   * Gets the group item discount amount as a float.
   *
   * @return float
   *   The group item discount amount expressed as a float.
   */
  public function getGroupItemDiscountAmountMultiplier();

  /**
   * Sets the group item discount percentage.
   *
   * @param int $value
   *   The value.
   *
   * @return $this
   */
  public function setGroupItemDiscount($value);

  /**
   * Gets whether the group item has a discount percentage.
   *
   * @return bool
   *   TRUE if the group has a discount percentage, FALSE otherwise.
   */
  public function hasGroupItemDiscount();

  /**
   * Gets the group item creation timestamp.
   *
   * @return int
   *   The group item creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the group item creation timestamp.
   *
   * @param int $timestamp
   *   The group item creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

}
