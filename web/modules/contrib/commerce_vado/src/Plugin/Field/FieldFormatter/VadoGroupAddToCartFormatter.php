<?php

namespace Drupal\commerce_vado\Plugin\Field\FieldFormatter;

use Drupal\commerce_product\Plugin\Field\FieldFormatter\AddToCartFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'commerce_vado_group_add_to_cart' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_vado_group_add_to_cart",
 *   label = @Translation("Group add to cart form"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class VadoGroupAddToCartFormatter extends AddToCartFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $product = $items->getEntity();
    if (!empty($product->in_preview)) {
      $elements[0]['add_to_cart_form'] = [
        '#type' => 'actions',
        ['#type' => 'button', '#value' => $this->t('Add to cart')],
      ];
      return $elements;
    }
    if ($product->isNew()) {
      return [];
    }

    $view_mode = $this->viewMode;
    // If the field formatter is rendered in Layout Builder, the `viewMode`
    // property will be `_custom` and the original view mode is stored in the
    // third party settings.
    // @see \Drupal\layout_builder\Plugin\Block\FieldBlock::build
    if (isset($this->thirdPartySettings['layout_builder'])) {
      $view_mode = $this->thirdPartySettings['layout_builder']['view_mode'];
    }

    $elements[0]['add_to_cart_form'] = [
      '#lazy_builder' => [
        'commerce_vado.lazy_builders:addToCartWithAddOnsForm', [
          $product->id(),
          $view_mode,
          $this->getSetting('combine'),
        ],
      ],
      '#create_placeholder' => TRUE,
    ];
    return $elements;
  }

}
