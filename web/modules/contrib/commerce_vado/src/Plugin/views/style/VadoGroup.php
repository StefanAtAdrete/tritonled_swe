<?php

namespace Drupal\commerce_vado\Plugin\views\style;

use Drupal\Component\Utility\Xss;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * EntityReference style plugin.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "commerce_vado_group",
 *   title = @Translation("Vado group item title"),
 *   help = @Translation("Returns results as a PHP array of labels and rendered rows."),
 *   theme = "views_view_unformatted",
 *   register_theme = FALSE,
 *   display_types = {"commerce_vado_group"}
 * )
 */
class VadoGroup extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  public function render() {
    if (!empty($this->view->live_preview)) {
      return parent::render();
    }

    // Group the rows according to the grouping field, if specified.
    $sets = $this->renderGrouping($this->view->result, $this->options['grouping']);

    // @todo We don't display grouping info for now. Could be useful for select
    // widget, though.
    $results = [];
    foreach ($sets as $records) {
      foreach ($records as $values) {
        // Grab the alias of the 'id' field added by
        // entity_reference_plugin_display.
        $id_field_alias = $this->view->storage->get('base_field');

        // If there's a group items relationship, use the group item id alias.
        if (isset($values->_relationship_entities["group_items_target_id"])) {
          $id_field_alias = $values->_relationship_entities["group_items_target_id"]->id();
        }
        $results[$id_field_alias] = $this->view->rowPlugin->render($values);
        // Sanitize HTML, remove line breaks and extra whitespace.
        $results[$id_field_alias]['#post_render'][] = function ($html, array $elements) {
          return Xss::filterAdmin(preg_replace('/\s\s+/', ' ', str_replace("\n", '', $html)));
        };
      }
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function evenEmpty() {
    return TRUE;
  }

}
