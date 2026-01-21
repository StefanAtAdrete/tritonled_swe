<?php

namespace Drupal\commerce_vado\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Defines the interface for vado groups.
 */
interface VadoGroupInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the group widget plugin.
   *
   * @return \Drupal\commerce_vado\Plugin\Commerce\VadoGroupWidget\VadoGroupWidgetInterface
   *   The group widget plugin.
   */
  public function getGroupWidget();

  /**
   * Gets the group items.
   *
   * @return \Drupal\commerce_vado\Entity\VadoGroupItemInterface[]
   *   The group items.
   */
  public function getItems();

  /**
   * Sets the group items.
   *
   * @param \Drupal\commerce_vado\Entity\VadoGroupItemInterface[] $group_items
   *   The group items.
   *
   * @return $this
   */
  public function setItems(array $group_items);

  /**
   * Gets whether the group has group items.
   *
   * @return bool
   *   TRUE if the group has group items, FALSE otherwise.
   */
  public function hasItems();

  /**
   * Adds a group item.
   *
   * @param \Drupal\commerce_vado\Entity\VadoGroupItemInterface $group_item
   *   The group item.
   *
   * @return $this
   */
  public function addItem(VadoGroupItemInterface $group_item);

  /**
   * Removes a group item.
   *
   * @param \Drupal\commerce_vado\Entity\VadoGroupItemInterface $group_item
   *   The group item.
   *
   * @return $this
   */
  public function removeItem(VadoGroupItemInterface $group_item);

  /**
   * Checks whether the group has a given group item.
   *
   * @param \Drupal\commerce_vado\Entity\VadoGroupItemInterface $group_item
   *   The group item.
   *
   * @return bool
   *   TRUE if the group item was found, FALSE otherwise.
   */
  public function hasItem(VadoGroupItemInterface $group_item);

  /**
   * Gets the default group items.
   *
   * @return \Drupal\commerce_vado\Entity\VadoGroupItemInterface[]
   *   The default group items.
   */
  public function getDefaultItems();

  /**
   * Gets whether the group has default group items.
   *
   * @return bool
   *   TRUE if the group has default group items, FALSE otherwise.
   */
  public function hasDefaultItems();

  /**
   * Gets whether the group is required.
   *
   * @return bool
   *   TRUE if the group is required, FALSE otherwise.
   */
  public function isRequired();

  /**
   * Sets whether the group is required.
   *
   * @return $this
   */
  public function setRequired($required = TRUE);

  /**
   * Gets the group discount percentage.
   *
   * @return int
   *   The group discount percentage.
   */
  public function getGroupDiscount();

  /**
   * Gets the group discount multiplier.
   *
   * The multiplier is the value the price will be multiplied by
   * to apply the discount.
   *
   * @return float
   *   The group discount multiplier.
   */
  public function getGroupDiscountMultiplier();

  /**
   * Gets the group discount amount as a float.
   *
   * @return float
   *   The group discount amount expressed as a float.
   */
  public function getGroupDiscountAmountMultiplier();

  /**
   * Sets the group discount percentage.
   *
   * @param int $value
   *   The value.
   *
   * @return $this
   */
  public function setGroupDiscount($value);

  /**
   * Gets whether the group has a discount percentage.
   *
   * @return bool
   *   TRUE if the group has a discount percentage, FALSE otherwise.
   */
  public function hasGroupDiscount();

  /**
   * Gets the variation add-on group creation timestamp.
   *
   * @return int
   *   Creation timestamp of the variation add-on group.
   */
  public function getCreatedTime();

  /**
   * Sets the variation add-on group creation timestamp.
   *
   * @param int $timestamp
   *   The variation add-on group creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

}
