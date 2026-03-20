<?php

namespace Drupal\tritonled_configurator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the TritonLED Product Configurator block.
 *
 * Renders only the configurator UI (dropdowns, SKU, submit).
 * The product image is rendered separately by ConfiguratorImageBlock.
 *
 * @Block(
 *   id = "tritonled_configurator_block",
 *   admin_label = @Translation("Produktkonfigurator"),
 *   category = @Translation("TritonLED"),
 * )
 */
class ConfiguratorBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $build = [];

    // The configurator UI div — configurator.js attaches here.
    $build['configurator'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'data-triton-configurator' => TRUE,
        'id' => 'triton-configurator',
      ],
    ];

    return $build;
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
  public function getCacheTags(): array {
    $product = $this->routeMatch->getParameter('commerce_product');
    if ($product) {
      return Cache::mergeTags(parent::getCacheTags(), $product->getCacheTags());
    }
    return parent::getCacheTags();
  }

}
