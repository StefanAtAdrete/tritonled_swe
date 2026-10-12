<?php

namespace Drupal\Tests\fences\Kernel;

use Drupal\Core\Extension\Theme;
use Drupal\KernelTests\KernelTestBase;

/**
 * Quick tests for fences_requirements() in fences.install.
 *
 * @group fences
 */
class FencesRequirementsInstallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'fences',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['fences']);
    \Drupal::service('module_handler')->loadInclude('fences', 'install');
  }

  /**
   * Tests runtime requirements skip core themes with field.html.twig.
   */
  public function testRuntimeRequirementsSkipsCoreThemes(): void {
    $themes = \Drupal::service('extension.list.theme')->getList();
    $this->assertContainsOnlyInstancesOf(Theme::class, $themes);

    \Drupal::service('theme_installer')->install(['olivero']);
    $requirements = fences_requirements('runtime')['fences'];

    $this->assertSame(REQUIREMENT_OK, $requirements['severity']);
    $this->assertSame(
      'No contrib themes provide a <em>field.html.twig</em> template.',
      $requirements['value']->render()
    );
  }

  /**
   * Tests runtime requirements warn for contrib themes with field.html.twig.
   */
  public function testRuntimeRequirementsWarnsForContribFieldTemplate(): void {
    \Drupal::service('theme_installer')->install(['fences_test_theme_b']);
    $requirements = fences_requirements('runtime')['fences'];

    $this->assertSame(REQUIREMENT_WARNING, $requirements['severity']);
    $this->assertSame(
      'The following contrib themes provide a <em>field.html.twig</em> template: fences_test_theme_b.',
      $requirements['value']->render()
    );
  }

}
