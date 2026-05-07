<?php

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\taxonomy\Entity\Term;


/**
 * Implements hook_token_info().
 */
function taxonomy_menu_ui_token_info() {
  $info['tokens']['term']['menu-link'] = [
    'name' => t('Menu link'),
    'description' => t("The menu link for this term."),
    'type' => 'menu-link',
  ];
  return $info;
}

/**
 * Implements hook_tokens().
 */
function taxonomy_menu_ui_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
  $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }

  // Commerce product tokens.
  if ($type == 'entity' && $data["entity_type"] === 'taxonomy_term' && !empty($data['entity'])) {
    /* @var Term */
    $term = $data['entity'];

    if (isset($term->menu) && $term->menu['enabled']) {
      $menu_link_id = $term->menu['entity_id'] ? $term->menu['entity_id'] : taxonomy_menu_ui_get_menu_link_defaults($term)['entity_id'];
      if ($menu_link_id) {
        foreach ($tokens as $name => $original) {
          // Chained token relationships.
          if ($menu_tokens = \Drupal::token()->findWithPrefix($tokens, 'menu-link')) {
            if ($menu_link = MenuLinkContent::load($menu_link_id)) {
              /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link */
              $replacements += \Drupal::token()->generate('menu-link', $menu_tokens, ['menu-link' => $menu_link], $options, $bubbleable_metadata);
            }
          }
        }
      }
    }
  }

  return $replacements;
}
