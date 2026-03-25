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

    $markup = '<div class="configurator-specs" id="configurator-specs" aria-live="polite">'
      . "\n"

      // Print header (only visible when printing).
      . '<div class="specs-print-header d-none d-print-flex justify-content-between align-items-start mb-3">'
      . '<div class="specs-print-logo"><strong class="fs-4">TritonLED</strong></div>'
      . '<div class="specs-print-contact text-end small">'
      . '<div>TritonLED Sverige AB</div>'
      . '<div>info@tritonled.se</div>'
      . '<div>tritonled.se</div>'
      . '</div>'
      . '</div>'
      . '<hr class="d-none d-print-block mt-0 mb-3">'
      . "\n"

      // Screen heading (hidden when printing).
      . '<h3 class="specs-screen-heading h5 mb-3 d-print-none">Tekniska specifikationer</h3>'
      . "\n"

      // Print image — hidden on screen, filled by JS, shown in print.
      . '<div class="specs-print-image">'
      . '<img id="specs-print-img" src="" alt="" />'
      . '</div>'
      . "\n"

      // Product name + SKU.
      . '<div class="specs-product-info mb-3">'
      . '<div class="fw-bold fs-5" data-spec="product-name">—</div>'
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

      // Print footer (only visible when printing).
      . '<div class="specs-print-footer d-none d-print-block mt-4 pt-3 border-top small text-muted">'
      . '<div class="d-flex justify-content-between">'
      . '<span data-spec="print-date"></span>'
      . '<span>tritonled.se</span>'
      . '</div>'
      . '</div>'
      . "\n"

      // Print button (hidden when printing).
      // onclick added via JS — Drupal XSS strips inline event handlers.
      . '<div class="d-print-none mt-3">'
      . '<button type="button" class="btn btn-outline-secondary btn-sm" id="configurator-print-btn">'
      . '<i class="bi bi-printer me-1"></i> Skriv ut / Spara som PDF'
      . '</button>'
      . '</div>'
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
   * Returns spec row definitions: spec_id => label.
   */
  protected function specRows(): array {
    return [
      'length'   => 'Längd',
      'driver'   => 'Driver',
      'endcap'   => 'Anslutning',
      'cri'      => 'CRI',
      'sensor'   => 'Sensor / Batteri',
      'kelvin'   => 'Färgtemperatur',
      'watt'     => 'Effekt (W)',
      'lumen'    => 'Ljusflöde (lm)',
      'efficacy' => 'Ljuseffektivitet (lm/W)',
      'optic'    => 'Optik',
      'color'    => 'Färg',
      'chips'    => 'Chips',
      'ip_class' => 'IP-klass',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return Cache::PERMANENT;
  }

}
