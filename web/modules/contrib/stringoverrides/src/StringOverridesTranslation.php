<?php

namespace Drupal\stringoverrides;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\Translator\StaticTranslation;

/**
 * Provides string overrides.
 */
class StringOverridesTranslation extends StaticTranslation {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a StringOverridesTranslation object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(CacheBackendInterface $cache_backend, ConfigFactoryInterface $config_factory) {
    parent::__construct();
    $this->cacheBackend = $cache_backend;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguage($langcode) {
    $cid = 'stringoverrides:translation_for_' . $langcode;

    $cache_backend = $this->cacheBackend;
    if ($cache = $cache_backend->get($cid)) {
      return $cache->data;
    }
    else {
      $translations = [];
      // Drupal configuration array structure is different from translations
      // array structure, lets transform configuration array.
      $config = $this->configFactory->get('stringoverrides.string_override.' . $langcode);
      $contexts = $config->get('contexts');
      if (!empty($contexts)) {
        foreach ($contexts as $context) {
          foreach ($context['translations'] as $word) {
            $translations[$context['context']][$word['source']] = $word['translation'];
          }
        }
      }
      $cache_backend->set($cid, $translations);
      return $translations;
    }
  }

}
