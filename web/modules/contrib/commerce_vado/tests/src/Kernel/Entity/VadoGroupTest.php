<?php

namespace Drupal\Tests\commerce_vado\Kernel\Entity;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_vado\Entity\VadoGroup;
use Drupal\commerce_vado\Entity\VadoGroupItem;
use Drupal\Tests\commerce_cart\Kernel\CartKernelTestBase;

/**
 * Tests the Group entity.
 *
 * @group commerce_vado
 */
class VadoGroupTest extends CartKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'commerce_vado',
  ];

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation1;

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation2;

  /**
   * The group to test against.
   *
   * @var \Drupal\commerce_vado\Entity\VadoGroup
   */
  protected $group;

  /**
   * The group item to test against.
   *
   * @var \Drupal\commerce_vado\Entity\VadoGroupItem
   */
  protected $groupItem1;

  /**
   * The group item to test against.
   *
   * @var \Drupal\commerce_vado\Entity\VadoGroupItem
   */
  protected $groupItem2;

  /**
   * The group item to test against.
   *
   * @var \Drupal\commerce_vado\Entity\VadoGroupItem[]
   */
  protected $groupItems;

  /**
   * The group to test against.
   *
   * @var \Drupal\commerce_vado\Entity\VadoGroup
   */
  protected $duplicateGroup;

  /**
   * The group item to test against.
   *
   * @var \Drupal\commerce_vado\Entity\VadoGroupItem
   */
  protected $duplicateGroupItem;

  /**
   * The group item to test against.
   *
   * @var \Drupal\commerce_vado\Entity\VadoGroupItem
   */
  protected $duplicateGroupItem1;

  /**
   * The group item to test against.
   *
   * @var \Drupal\commerce_vado\Entity\VadoGroupItem
   */
  protected $duplicateGroupItem2;

  /**
   * The group item to test against.
   *
   * @var \Drupal\commerce_vado\Entity\VadoGroupItem[]
   */
  protected $duplicateGroupItems;

  /**
   * The vado field manager.
   *
   * @var \Drupal\commerce_vado\VadoFieldManagerInterface
   */
  protected $vadoFieldManager;

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('commerce_vado_group_item');
    $this->installEntitySchema('commerce_vado_group');
    $this->installConfig(['commerce_vado']);

    $this->vadoFieldManager = $this->container->get('commerce_vado.field_manager');
    $this->vadoFieldManager->installField('variation_groups', 'default');

    $user = $this->createUser(['administer commerce_vado_group']);
    $this->user = $this->reloadEntity($user);
    $this->container->get('current_user')->setAccount($user);

    $this->variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 0,
    ]);
    $this->variation1->save();

    $this->variation2 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $this->variation2->save();

    $this->groupItem1 = VadoGroupItem::create();
    $this->groupItem1->save();
    $this->groupItem1->set('variation', $this->variation1);
    $this->groupItem1->save();
    // setItems() and addItem() do not seem to set the group_id backreference.
    $this->groupItem1->set('group_id', 1);
    $this->groupItem1->save();

    $this->groupItem2 = VadoGroupItem::create();
    $this->groupItem2->save();
    $this->groupItem2->set('variation', $this->variation2);
    $this->groupItem2->save();
    // setItems() and addItem() do not seem to set the group_id backreference.
    $this->groupItem2->set('group_id', 1);
    $this->groupItem2->save();

    $this->groupItems = [
      $this->groupItem1,
      $this->groupItem2,
    ];

    $this->group = VadoGroup::create();
    $this->group->save();
    $this->group->set('title', 'Group 1');
    $this->group->save();
  }

  /**
   * The vado group test.
   */
  public function testVadoGroup() {

    $this->group->setCreatedTime(635879700);
    $this->assertEquals(635879700, $this->group->getCreatedTime());

    $this->group->setRequired(TRUE);
    $this->group->save();
    $this->assertTrue($this->group->isRequired());
    $this->group->setRequired(FALSE);
    $this->group->save();
    $this->assertFalse($this->group->isRequired());

    $this->group->setGroupDiscount(10);
    $this->group->save();
    $this->assertTrue($this->group->hasGroupDiscount());
    $this->assertEquals(10, $this->group->getGroupDiscount());
    $this->assertEquals(.9, $this->group->getGroupDiscountMultiplier());
    $this->assertEquals(.1, $this->group->getGroupDiscountAmountMultiplier());
    $this->group->setGroupDiscount(NULL);
    $this->group->save();
    $this->assertFalse($this->group->hasGroupDiscount());
  }

  /**
   * The vado group item group test.
   */
  public function testVadoGroupItemGroup() {

    $this->group->addItem($this->groupItem1);
    $this->group->save();
    $this->assertTrue($this->group->hasItems());
    $this->assertTrue($this->group->hasItem($this->groupItem1));
    $this->assertEquals(1, $this->groupItem1->id());
    $this->assertEquals(1, $this->groupItem1->getGroupId());
    $this->assertNotNull($this->groupItem1->getGroup());
    $this->group->RemoveItem($this->groupItem1);
    $this->group->save();
    $this->assertFalse($this->group->hasItems());
    $this->assertFalse($this->group->hasItem($this->groupItem1));

    $this->group->setItems($this->groupItems);
    $this->group->save();
    $this->assertTrue($this->group->hasItems());
    $this->assertTrue($this->group->hasItem($this->groupItem1));
    $this->assertTrue($this->group->hasItem($this->groupItem2));
    $this->assertEquals(1, $this->groupItem1->id());
    $this->assertEquals(2, $this->groupItem2->id());
    $this->assertEquals(1, $this->groupItem1->getGroupId());
    $this->assertEquals(1, $this->groupItem2->getGroupId());
    $this->assertNotNull($this->groupItem1->getGroup());
    $this->assertNotNull($this->groupItem2->getGroup());
  }

  /**
   * The vado group duplicate test.
   */
  public function testVadoGroupDuplicate() {

    // Set the group values to duplicate.
    $this->group->setRequired(TRUE);
    $this->group->save();
    $this->assertTrue($this->group->isRequired());

    $this->group->setGroupDiscount(10);
    $this->group->save();
    $this->assertTrue($this->group->hasGroupDiscount());
    $this->assertEquals(10, $this->group->getGroupDiscount());
    $this->assertEquals(.9, $this->group->getGroupDiscountMultiplier());
    $this->assertEquals(.1, $this->group->getGroupDiscountAmountMultiplier());

    $this->group->setItems($this->groupItems);
    $this->group->save();

    $this->assertTrue($this->group->hasItems());
    $this->assertTrue($this->group->hasItem($this->groupItem1));
    $this->assertTrue($this->group->hasItem($this->groupItem2));
    $this->assertNotNull($this->groupItem1->getGroup());
    $this->assertEquals(1, $this->groupItem1->getGroupId());
    $this->assertNotNull($this->groupItem2->getGroup());
    $this->assertEquals(1, $this->groupItem2->getGroupId());
    $this->assertEquals(1, $this->groupItem1->getVariationId());
    $this->assertEquals(2, $this->groupItem2->getVariationId());

    // Create the duplicate group, and check it's values.
    $this->duplicateGroup = $this->group->createDuplicate();
    $this->duplicateGroup->save();
    $this->assertEquals('Group 1', $this->duplicateGroup->label());
    $this->assertTrue($this->duplicateGroup->isRequired());
    $this->assertEquals(10, $this->duplicateGroup->getGroupDiscount());
    $this->assertTrue($this->duplicateGroup->hasItems());
    $this->assertFalse($this->duplicateGroup->hasItem($this->groupItem1));
    $this->assertFalse($this->duplicateGroup->hasItem($this->groupItem2));

    // @todo Figure out a better way to identify the 2 duplicate group items.
    $this->duplicateGroupItems = $this->duplicateGroup->getItems();
    foreach ($this->duplicateGroupItems as $this->duplicateGroupItem) {
      if ($this->duplicateGroupItem->id() == 3) {
        $this->duplicateGroupItem1 = $this->duplicateGroupItem;
      }
      if ($this->duplicateGroupItem->id() == 4) {
        $this->duplicateGroupItem2 = $this->duplicateGroupItem;
      }
    }

    // Check that the duplicate group item id's are new, and successive.
    $this->assertTrue($this->duplicateGroup->hasItem($this->duplicateGroupItem1));
    $this->assertTrue($this->duplicateGroup->hasItem($this->duplicateGroupItem2));
    $this->assertEquals(2, $this->duplicateGroupItem1->getGroupId());
    $this->assertNotNull($this->duplicateGroupItem1->getGroup());
    $this->assertEquals(2, $this->duplicateGroupItem2->getGroupId());
    $this->assertNotNull($this->duplicateGroupItem2->getGroup());
    $this->assertNotEquals(1, $this->duplicateGroupItem1->id());
    $this->assertNotEquals(2, $this->duplicateGroupItem2->id());
    $this->assertEquals(3, $this->duplicateGroupItem1->id());
    $this->assertEquals(4, $this->duplicateGroupItem2->id());
    $this->assertEquals(1, $this->duplicateGroupItem1->getVariationId());
    $this->assertEquals(2, $this->duplicateGroupItem2->getVariationId());

    // Set the discount on original #1 and make sure duplicate #1 is still NULL.
    $this->groupItem1->set('group_item_discount', 10);
    $this->groupItem1->save();
    $this->assertEquals(10, $this->groupItem1->getGroupItemDiscount());
    $this->assertNull($this->duplicateGroupItem1->getGroupItemDiscount());

    // Remove duplicate #1 and make sure original #1 is still there.
    $this->duplicateGroup->RemoveItem($this->duplicateGroupItem1);
    $this->duplicateGroup->save();
    $this->assertTrue($this->group->hasItem($this->groupItem1));
    $this->assertFalse($this->duplicateGroup->hasItem($this->duplicateGroupItem1));
    $this->assertTrue($this->duplicateGroup->hasItem($this->duplicateGroupItem2));

    // Set the discount on duplicate #2 and make sure original #2 is still NULL.
    $this->duplicateGroupItem2->set('group_item_discount', 10);
    $this->duplicateGroupItem2->save();
    $this->assertEquals(10, $this->duplicateGroupItem2->getGroupItemDiscount());
    $this->assertNull($this->groupItem2->getGroupItemDiscount());

    // Delete the duplicate group and make sure the original group stays.
    $this->duplicateGroup->delete();
    $this->assertTrue($this->group->hasItems());
    $this->assertTrue($this->group->hasItem($this->groupItem1));
    $this->assertTrue($this->group->hasItem($this->groupItem2));
    $this->assertNotNull($this->groupItem1->getGroup());
    $this->assertEquals(1, $this->groupItem1->getGroupId());
    $this->assertNotNull($this->groupItem2->getGroup());
    $this->assertEquals(1, $this->groupItem2->getGroupId());
  }

}
