<?php

namespace Drupal\commerce_vado;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The vado group list builder.
 */
class VadoGroupListBuilder extends EntityListBuilder {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new VadoGroupListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));

    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'title' => [
        'data' => $this->t('Title'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'group_widget' => [
        'data' => $this->t('Display Format'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'required' => [
        'data' => $this->t('Required'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'group_discount' => [
        'data' => $this->t('Group Discount'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'created' => [
        'data' => $this->t('Created'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_vado\Entity\VadoGroupInterface $group */
    $group = $entity;
    $row['title']['data'] = Link::fromTextAndUrl($group->label(), $group->toUrl('edit-form'));
    $row['group_widget'] = $group->getGroupWidget()->getLabel();
    $row['required'] = $group->isRequired() ? $this->t('Yes') : $this->t('No');
    $group_discount_display = NULL;
    if ($group->hasGroupDiscount()) {
      $discount = $group->getGroupDiscount();
      $suffix = $discount == 0 ? 'Excluded' : NULL;
      if (!$suffix) {
        $suffix = $discount > 0 ? 'Discount' : 'Markup';
      }
      $group_discount_display = t('@discount% (@suffix)', ['@discount' => (string) abs($discount), '@suffix' => $suffix]);
    }
    $row['group_discount'] = $group_discount_display;
    $row['created'] = $this->dateFormatter->format($entity->getCreatedTime(), 'short');

    return $row + parent::buildRow($entity);
  }

}
