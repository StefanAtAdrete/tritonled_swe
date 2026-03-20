<?php

namespace Drupal\tritonled_configurator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the TritonLED Product Configurator block.
 *
 * Renders the configurator UI + the default product image (from
 * field_configurator_media, view mode configurator_image). JS handles
 * image switching by replacing src/srcset on the rendered img element.
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [];

    // Render default image from field_configurator_media if available.
    $product = $this->routeMatch->getParameter('commerce_product');
    if ($product && $product->hasField('field_configurator_media') && !$product->get('field_configurator_media')->isEmpty()) {
      // Render only the first (default) media entity.
      $media = $product->get('field_configurator_media')->first()->entity;
      if ($media) {
        $view_builder = $this->entityTypeManager->getViewBuilder('media');
        $build['image'] = $view_builder->view($media, 'configurator_image');
        $build['image']['#prefix'] = '<div class="triton-configurator-image">';
        $build['image']['#suffix'] = '</div>';
      }
    }

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
