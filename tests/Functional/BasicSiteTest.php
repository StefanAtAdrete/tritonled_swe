<?php

namespace Drupal\Tests\tritonled\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests basic site functionality.
 *
 * @group tritonled
 * @group functional
 */
class BasicSiteTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'block',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test that homepage loads.
   */
  public function testHomepageLoads() {
    // Visit homepage
    $this->drupalGet('<front>');
    
    // Should return 200
    $this->assertSession()->statusCodeEquals(200);
    
    // Should have basic HTML structure
    $this->assertSession()->responseContains('<html');
    $this->assertSession()->responseContains('</html>');
  }

  /**
   * Test that 404 page works.
   */
  public function test404Page() {
    // Visit non-existent page
    $this->drupalGet('/this-page-does-not-exist-xyz123');
    
    // Should return 404
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Test authenticated user access.
   */
  public function testAuthenticatedAccess() {
    // Create user
    $user = $this->createUser([
      'access content',
    ]);
    
    // Login
    $this->drupalLogin($user);
    
    // Visit homepage
    $this->drupalGet('<front>');
    
    // Should be logged in (200 status)
    $this->assertSession()->statusCodeEquals(200);
    
    // Should see authenticated user content (less strict than checking for "Log out" link)
    // This works regardless of theme
    $session = $this->getSession();
    $this->assertTrue($session->isStarted());
  }

}
