<?php

namespace Drupal\tritonled_configurator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the TritonLED Static Product Specifications block.
 *
 * Renders static product specs (lifetime, material, IK, certifications,
 * temperature) from the staticSpecs key in field_configurator_schema.
 * These values are fixed per product model and do not change with
 * configurator selections — no JS dependency.
 *
 * @Block(
 *   id = "tritonled_static_specs_block",
 *   admin_label = @Translation("Produktspecifikationer (statiska)"),
 *   category = @Translation("TritonLED"),
 * )
 */
class StaticSpecsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RouteMatchInterface $route_match,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // Get the product from the current route.
    $product = $this->routeMatch->getParameter('commerce_product');
    if (!$product) {
      return [];
    }

    // Load staticSpecs from the configurator schema field.
    $schemaField = $product->get('field_configurator_schema');
    if ($schemaField->isEmpty()) {
      return [];
    }

    $schema = json_decode($schemaField->value, TRUE);
    if (empty($schema['staticSpecs'])) {
      return [];
    }

    $staticSpecs = $schema['staticSpecs'];

    // Build rows: spec_key => translated label.
    $rows = $this->specRows();
    $tableRows = '';
    foreach ($rows as $key => $label) {
      if (empty($staticSpecs[$key])) {
        continue;
      }
      $value = htmlspecialchars($staticSpecs[$key], ENT_QUOTES, 'UTF-8');
      $tableRows .= '<tr>';
      $tableRows .= '<th class="specs-label-col">' . $label . '</th>';
      $tableRows .= '<td>' . $value . '</td>';
      $tableRows .= '</tr>' . "\n";
    }

    if (!$tableRows) {
      return [];
    }

    $heading = $this->t('Product specifications');

    $markup = '<div class="static-specs-wrapper" id="static-specs">'
      . '<h3 class="h5 mb-3 d-print-none">' . $heading . '</h3>'
      . '<table class="table table-sm table-bordered static-specs-table mb-3">'
      . '<tbody>'
      . $tableRows
      . '</tbody>'
      . '</table>'
      . '</div>';

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['static-specs-block-wrapper'],
      ],
      'content' => [
        '#markup' => \Drupal\Core\Render\Markup::create($markup),
      ],
    ];
  }

  /**
   * Returns spec row definitions: key => translated label.
   */
  protected function specRows(): array {
    return [
      'lifetime'       => $this->t('Average Lifetime'),
      'temperature'    => $this->t('Operating Temperature'),
      'material'       => $this->t('Material'),
      'ik'             => $this->t('IK Grade'),
      'certifications' => $this->t('Certifications'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.path']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return Cache::PERMANENT;
  }

}
