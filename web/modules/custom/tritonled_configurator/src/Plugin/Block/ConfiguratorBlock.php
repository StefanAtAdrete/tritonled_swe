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
 * Renders a container div that the configurator.js behavior attaches to.
 * The JS reads its schema from drupalSettings.tritonConfigurator (set by
 * tritonled_configurator_preprocess_commerce_product) and builds the UI.
 *
 * Place this block via Layout Builder on product pages for:
 * - led_luminaire_max_opti
 * - led_luminaire_srow
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
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
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // The div that configurator.js attaches to.
    // drupalSettings.tritonConfigurator is set by the preprocess hook
    // only when the product has field_configurator_schema filled in.
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'data-triton-configurator' => TRUE,
        'id' => 'triton-configurator',
      ],
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
  public function getCacheTags(): array {
    $product = $this->routeMatch->getParameter('commerce_product');
    if ($product) {
      return Cache::mergeTags(parent::getCacheTags(), $product->getCacheTags());
    }
    return parent::getCacheTags();
  }

}
