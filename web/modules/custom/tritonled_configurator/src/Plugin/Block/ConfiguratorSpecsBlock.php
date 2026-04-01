<?php

namespace Drupal\tritonled_configurator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\Markup;

/**
 * Provides the TritonLED Configurator Specifications block.
 *
 * Renders a static HTML structure with data-spec-* placeholders.
 * JavaScript (configurator.js) fills these live when the user makes
 * selections in the configurator. Includes a print/PDF button.
 *
 * @Block(
 *   id = "tritonled_configurator_specs_block",
 *   admin_label = @Translation("Produktspecifikationer (konfigurator)"),
 *   category = @Translation("TritonLED"),
 * )
 */
class ConfiguratorSpecsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $rows = $this->specRows();
    $table_rows = '';
    foreach ($rows as $spec_id => $label) {
      $table_rows .= '<tr class="specs-row" data-spec-row="' . $spec_id . '">';
      $table_rows .= '<th class="specs-label-col">' . $label . '</th>';
      $table_rows .= '<td data-spec="' . $spec_id . '">—</td>';
      $table_rows .= '</tr>' . "\n";
    }

    $specs_heading = $this->t('Technical specifications');

    $markup = '<div class="configurator-specs" id="configurator-specs" aria-live="polite">'
      . "\n"

      // No print header — logo/contact moved to footer (like SROW datasheet).
      . "\n"

      // Screen heading (hidden when printing).
      . '<h3 class="specs-screen-heading h5 mb-3 d-print-none">' . $specs_heading . '</h3>'
      . "\n"

      // Print image — hidden on screen, filled by JS, shown in print.
      . '<div class="specs-print-image">'
      . '<img id="specs-print-img" src="" alt="" />'
      . '</div>'
      . "\n"

      // Product name + SKU.
      . '<div class="specs-product-info mb-3">'
      . '<h2 class="specs-product-name h5 fw-bold mb-1" data-spec="product-name">—</h2>'
      . '<div class="text-muted small">SKU: <code data-spec="sku">—</code></div>'
      . '</div>'
      . "\n"

      // Specs table.
      . '<table class="table table-sm table-bordered specs-table mb-3">'
      . '<tbody>'
      . $table_rows
      . '</tbody>'
      . '</table>'
      . "\n"

      // Print footer — logo left, contact right, date + generated text bottom.
      . '<div class="specs-print-footer d-none d-print-block mt-4 pt-3 border-top small">'
      . '<div class="d-flex justify-content-between align-items-start mb-2">'
      . '<strong class="fs-5">TritonLED</strong>'
      . '<div class="text-end">'
      . '<div>TritonLED Sverige AB</div>'
      . '<div>info@tritonled.se</div>'
      . '<div>tritonled.se</div>'
      . '</div>'
      . '</div>'
      . '<div class="text-muted" data-spec="print-generated"></div>'
      . '</div>'
      . "\n"

      // Print button is now a separate PrintButtonBlock — placeable anywhere.
      . "\n"

      . '</div>';

    return [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'configurator-specs-wrapper',
        'class' => ['configurator-specs-wrapper'],
      ],
      '#attached' => [
        'library' => [
          'tritonled_configurator/configurator_print',
        ],
      ],
      'content' => [
        '#markup' => Markup::create($markup),
      ],
    ];
  }

  /**
   * Returns spec row definitions: spec_id => translated label.
   */
  protected function specRows(): array {
    return [
      'length'   => $this->t('Length'),
      'driver'   => $this->t('Driver'),
      'endcap'   => $this->t('Connection'),
      'cri'      => $this->t('CRI'),
      'sensor'   => $this->t('Sensor / Battery'),
      'kelvin'   => $this->t('Color temperature'),
      'watt'     => $this->t('Power (W)'),
      'lumen'    => $this->t('Luminous flux (lm)'),
      'efficacy' => $this->t('Efficacy (lm/W)'),
      'optic'    => $this->t('Optics'),
      'color'    => $this->t('Color'),
      'chips'    => $this->t('Chips'),
      'ip_class' => $this->t('IP class'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return Cache::PERMANENT;
  }

}
