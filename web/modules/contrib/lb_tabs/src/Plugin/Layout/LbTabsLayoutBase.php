<?php

namespace Drupal\lb_tabs\Plugin\Layout;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Render\Element;

abstract class LbTabsLayoutBase extends LayoutDefault {
  // @fixme Remove links from labels.
  // @fixme Do weight sorting first if needed.

  /**
   * Set our dynamic regions.
   */
  protected function setPluginDefinitionRegions() {
    $regionMap = [];
    // We only need the label region for tabs if settings say so.
    // Accordions use every odd block as label, so no region needed.
    // Note that ::build ensures the template always sees the label region.
    if ($this->needsLabelBlockRegion() && $this->configuration['labels_from_blocks']) {
      $regionMap['label_blocks'] = ['label' => $this->t('Label blocks')];
    }
    $regionMap['content_blocks'] = ['label' => $this->t('Content blocks')];
    $this->pluginDefinition->setRegions($regionMap);
    $this->pluginDefinition->setDefaultRegion('content_blocks');
  }
  abstract protected function needsLabelBlockRegion();

  /**
   * {@inheritDoc}
   */
  public function build(array $regions) {
    $this->setPluginDefinitionRegions();
    $build = parent::build($regions);
    // Element::children() needs this.
    $build += ['label_blocks' => [], 'content_blocks' => []];

    // No label blocks, so set labels from block titles.
    if (!$this->configuration['labels_from_blocks']) {
      foreach (Element::children($build['content_blocks'], TRUE) as $i => $contentKey) {
        $build['label_blocks']["label_$i"] = ['#plain_text' => $build['content_blocks'][$contentKey]['#configuration']['label'] ?? $i + 1];
      }
    }

    // Labels from blocks, but accordion has no label region, so move every odd
    // block to the label region.
    if ($this->configuration['labels_from_blocks'] && !$this->needsLabelBlockRegion()) {
      foreach (Element::children($build['content_blocks'], TRUE) as $i => $contentKey) {
        if ($i % 2 === 0) {
          $build['label_blocks']["label_$i"] = $build['content_blocks'][$contentKey];
          unset($build['content_blocks'][$contentKey]);
        }
      }
    }

    // Fill label and content blocks if needed.
    if (!empty($build['label_blocks'])) {
      // Element::children guarantees to return numeric arrays.
      $labelKeys = Element::children($build['label_blocks'], TRUE);
      $contentKeys = Element::children($build['content_blocks'], TRUE);
      // Sort by weight, as twig does not have such a method.
      $build['label_blocks'] = array_replace(array_flip($labelKeys), $build['label_blocks']) + $build['label_blocks'];
      $build['content_blocks'] = array_replace(array_flip($contentKeys), $build['content_blocks']) + $build['content_blocks'];
      // Fill up both arrays to have same cardinality.
      $contentWithNoLabelKeys = array_diff_key($contentKeys, $labelKeys);
      foreach ($contentWithNoLabelKeys as $i => $contentKey) {
        $indexBase1 = (string)($i + 1);
        $build['label_blocks']["label_$i"] = ['#plain_text' => $indexBase1];
      }
      $labelWithNoContentKeys = array_diff_key($labelKeys, $contentKeys);
      foreach ($labelWithNoContentKeys as $i => $labelKey) {
        $build['content_blocks']["content_$i"] = [];
      }
    }

    // @aee template_preprocess_layout
    $idPrefix = $this->pluginDefinition->id();
    $domId = Html::getUniqueId($idPrefix);
    $build['#settings']['dom_id'] = $domId;
    $build['#attached']['drupalSettings'][$idPrefix][$domId] = [
      'active' => is_numeric($this->configuration['initially_active_item'])
        ? (int) $this->configuration['initially_active_item']
        : FALSE,
      'collapsible' => (bool) $this->configuration['collapsible'],
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
        'initially_active_item' => NULL,
        'collapsible' => FALSE,
        'labels_from_blocks' => FALSE,
      ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['initially_active_item'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#title' => $this->t('Initially active item'),
      '#description' => $this->t('The zero-based index of the item that is active initially. If empty and collapsible is set, none is active initially.'),
      '#default_value' => $this->configuration['initially_active_item'],
    ];
    $form['collapsible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collapsible'),
      '#description' => $this->t('When set to true, the active panel can be closed.'),
      '#default_value' => $this->configuration['collapsible'],
    ];
    $form['labels_from_blocks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use blocks as labels'),
      '#description' => $this->t('Uses blocks for label, giving far more power. All links in label region will be removed. Otherwise uses title of content block for label.'),
      '#default_value' => $this->configuration['labels_from_blocks'],
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['initially_active_item'] = $form_state->getValue('initially_active_item');
    $this->configuration['collapsible'] = $form_state->getValue('collapsible');
    $this->configuration['labels_from_blocks'] = $form_state->getValue('labels_from_blocks');
  }

}
