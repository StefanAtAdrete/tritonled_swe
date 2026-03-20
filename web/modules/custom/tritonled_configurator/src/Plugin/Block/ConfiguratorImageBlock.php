<?php

namespace Drupal\tritonled_configurator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the TritonLED Configurator Image block.
 *
 * Renders the default product image from field_configurator_media using
 * view mode configurator_image. Wrapped in .triton-configurator-image so
 * configurator.js can find and swap the img src/srcset on selection change.
 *
 * Place this block separately in Layout Builder — independent of the
 * Produktkonfigurator block so each can be positioned freely on the page.
 *
 * @Block(
 *   id = "tritonled_configurator_image_block",
 *   admin_label = @Translation("Konfigurator-bild"),
 *   category = @Translation("TritonLED"),
 * )
 */
class ConfiguratorImageBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $product = $this->routeMatch->getParameter('commerce_product');

    if (!$product || !$product->hasField('field_configurator_media') || $product->get('field_configurator_media')->isEmpty()) {
      return [];
    }

    $media = $product->get('field_configurator_media')->first()->entity;
    if (!$media) {
      return [];
    }

    $view_builder = $this->entityTypeManager->getViewBuilder('media');

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['triton-configurator-image']],
      'media' => $view_builder->view($media, 'configurator_image'),
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
