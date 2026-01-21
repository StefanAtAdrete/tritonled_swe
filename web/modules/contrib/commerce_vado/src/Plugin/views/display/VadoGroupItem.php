<?php

namespace Drupal\commerce_vado\Plugin\views\display;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The plugin that handles a VadoGroupItem display.
 *
 * "commerce_vado_group_item_display" is a custom property.
 *
 * @todo to retrieve all views with a 'Vado Group Item' display.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "commerce_vado_group_item",
 *   title = @Translation("Vado group item (Title)"),
 *   admin = @Translation("Vado group item source"),
 *   help = @Translation("Builds the title to display for commerce vado group items."),
 *   theme = "views_view",
 *   register_theme = FALSE,
 *   uses_menu_links = FALSE,
 *   base = {"commerce_vado_group_item"},
 *   commerce_vado_group_item_display = TRUE
 * )
 */
class VadoGroupItem extends DisplayPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesAJAX = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesPager = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesAttachments = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesMore = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesAreas = FALSE;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new EntityReference object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Force the style plugin to 'entity_reference_style' and the row plugin to
    // 'fields'.
    $options['style']['contains']['type'] = ['default' => 'commerce_vado_group_item'];
    $options['defaults']['default']['style'] = FALSE;
    $options['row']['contains']['type'] = ['default' => 'commerce_vado_group_item'];
    $options['defaults']['default']['row'] = FALSE;

    // Set the display title to an empty string (not used in this display type).
    $options['title']['default'] = '';
    $options['defaults']['default']['title'] = FALSE;

    // Always renders all rows.
    $options['pager']['contains']['type']['default'] = 'none';

    // @todo vado price discounts can depend on what parent variation and group
    // they are being rendered from.  Need to create a custom cache context
    // to be able to properly cache any rendered discount pricing.
    $options['cache']['contains']['type']['default'] = 'none';
    $options['defaults']['default']['cache'] = FALSE;

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);
    unset($options['title']);
    unset($options['pager']);
    unset($options['exposed_form']);
    unset($options['cache']);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'commerce_vado_group_item';
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return $this->view->render($this->display['id']);
  }

  /**
   * Builds the view result as a renderable array.
   *
   * @return array
   *   Renderable array or empty array.
   */
  public function render() {
    if (!empty($this->view->result) && $this->view->style_plugin->evenEmpty()) {
      return $this->view->style_plugin->render($this->view->result);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function usesExposed() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function displaysExposed() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function usesLinkDisplay() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Disable the preview because our custom fields depend on contextual data.
    $this->view->live_preview = FALSE;

    // Limit the query based on the commerce_vado_group being rendered.
    $group_id = $this->getOption('commerce_vado_group');
    $conditions = new Condition('AND');
    $table = $this->view->storage->get('base_table');
    $conditions->condition($table . '.group_id', $group_id);
    $this->view->query->addWhere(0, $conditions);
  }

}
