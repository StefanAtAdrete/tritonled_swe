<?php

namespace Drupal\tritonled_configurator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a standalone Print / Save as PDF button block.
 *
 * Can be placed anywhere in Layout Builder. The button triggers the same
 * print flow as the one in ConfiguratorSpecsBlock — clones static specs
 * into the configurator-specs container, sets document.title to the SKU,
 * calls window.print(), then restores everything via afterprint event.
 *
 * Works independently of ConfiguratorSpecsBlock placement.
 *
 * @Block(
 *   id = "tritonled_print_button_block",
 *   admin_label = @Translation("Skriv ut / Spara som PDF (knapp)"),
 *   category = @Translation("TritonLED"),
 * )
 */
class PrintButtonBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $label = $this->t('Print / Save as PDF');

    $markup = '<div class="tritonled-print-button-wrapper d-print-none">'
      . '<button type="button" class="btn btn-outline-secondary btn-sm" id="configurator-print-btn">'
      . '<i class="bi bi-printer me-1"></i> ' . $label
      . '</button>'
      . '</div>';

    return [
      '#markup' => \Drupal\Core\Render\Markup::create($markup),
      '#attached' => [
        'library' => [
          'tritonled_configurator/configurator_print',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return Cache::PERMANENT;
  }

}
