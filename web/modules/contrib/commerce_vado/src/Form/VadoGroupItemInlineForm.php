<?php

namespace Drupal\commerce_vado\Form;

use Drupal\commerce_vado\Entity\VadoGroupItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\inline_entity_form\Form\EntityInlineForm;

/**
 * Defines the inline form for vado group items.
 */
class VadoGroupItemInlineForm extends EntityInlineForm {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels() {
    $labels = [
      'singular' => $this->t('vado group item'),
      'plural' => $this->t('vado group items'),
    ];
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableFields($bundles) {
    $fields = parent::getTableFields($bundles);
    $fields['unit_price'] = [
      'type' => 'callback',
      'label' => $this->t('Price'),
      'weight' => 2,
      'callback' => [get_called_class(), 'getPrice'],
    ];
    $fields['group_item_discount'] = [
      'type' => 'callback',
      'label' => $this->t('Item Discount'),
      'weight' => 3,
      'callback' => [get_called_class(), 'getDiscount'],
    ];
    $fields['sku'] = [
      'type' => 'callback',
      'label' => $this->t('Sku'),
      'weight' => 4,
      'callback' => [get_called_class(), 'getSku'],
    ];
    $fields['default'] = [
      'type' => 'field',
      'label' => $this->t('Default'),
      'weight' => 5,
      'display_options' => [
        'type' => 'boolean',
        'settings' => ['format' => 'yes-no'],
      ],
    ];

    return $fields;
  }

  /**
   * Gets the price to display for the vado group item.
   *
   * @param \Drupal\commerce_vado\Entity\VadoGroupItemInterface $group_item
   *   The vado group item.
   *
   * @return string
   *   The formatted price.
   */
  public static function getPrice(VadoGroupItemInterface $group_item) {
    /** @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter */
    $currency_formatter = \Drupal::service('commerce_price.currency_formatter');
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $group_item->getVariation();
    // If the variation doesn't exist, return empty array.
    if (!$group_item->getVariation()) {
      return [];
    }
    $price = $variation->getPrice();
    return $currency_formatter->format($price->getNumber(), $price->getCurrencyCode());
  }

  /**
   * Gets the sku to display for the vado group item.
   *
   * @param \Drupal\commerce_vado\Entity\VadoGroupItemInterface $group_item
   *   The vado group item.
   *
   * @return string
   *   The vado group item sku.
   */
  public static function getSku(VadoGroupItemInterface $group_item) {
    // If the variation doesn't exist, return empty array.
    if (!$group_item->getVariation()) {
      return [];
    }
    return $group_item->getVariation()->getSku();
  }

  /**
   * Gets the discount to display for the vado group item.
   *
   * @param \Drupal\commerce_vado\Entity\VadoGroupItemInterface $group_item
   *   The vado group item.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The vado group item discount, or an empty string if not set.
   */
  public static function getDiscount(VadoGroupItemInterface $group_item) {
    if (!$group_item->hasGroupItemDiscount()) {
      return '';
    }
    $discount = $group_item->getGroupItemDiscount();
    $suffix = $discount == 0 ? 'Excluded' : NULL;
    if (!$suffix) {
      $suffix = $discount > 0 ? 'Discount' : 'Markup';
    }
    return t('@discount% (@suffix)', [
      '@discount' => (string) abs($discount),
      '@suffix' => $suffix,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    $entity_form = parent::entityForm($entity_form, $form_state);
    $entity_form['#entity_builders'][] = [get_class($this), 'populateTitle'];

    return $entity_form;
  }

  /**
   * Entity builder: populates the group item title from the purchased entity.
   *
   * @param string $entity_type
   *   The entity type identifier.
   * @param \Drupal\commerce_vado\Entity\VadoGroupItemInterface $group_item
   *   The vado group item.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function populateTitle($entity_type, VadoGroupItemInterface $group_item, array $form, FormStateInterface $form_state) {
    $parents = array_slice($form['title']['#parents'], 0, -1);
    $title = $form_state->getValue(array_merge($parents, ['title', 0, 'value']));
    if (empty($title)) {
      $variation = $group_item->getVariation();
      $group_item->setTitle($variation->getTitle());
    }
  }

}
