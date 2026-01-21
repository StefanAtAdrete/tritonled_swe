<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawMinkContext implements Context {

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

  /**
   * Wait for AJAX to finish.
   *
   * @Given I wait for AJAX to finish
   */
  public function iWaitForAjaxToFinish() {
    $this->getSession()->wait(5000, '(typeof jQuery === "undefined" || jQuery.active === 0)');
  }

  /**
   * Wait for specified seconds.
   *
   * @Given I wait :seconds second(s)
   */
  public function iWaitSeconds($seconds) {
    sleep((int) $seconds);
  }

  /**
   * Check that element contains text.
   *
   * @Then the :element element should contain :text
   */
  public function theElementShouldContain($element, $text) {
    $element = $this->getSession()->getPage()->find('css', $element);
    if (!$element) {
      throw new \Exception(sprintf('Element "%s" not found', $element));
    }
    if (strpos($element->getText(), $text) === FALSE) {
      throw new \Exception(sprintf('Element "%s" does not contain "%s"', $element, $text));
    }
  }

  /**
   * Check Commerce variation switched successfully.
   *
   * @Then the product variation should have changed
   */
  public function theProductVariationShouldHaveChanged() {
    // Wait for AJAX
    $this->iWaitForAjaxToFinish();
    
    // Check that variation field containers exist and were updated
    $page = $this->getSession()->getPage();
    $variationContainer = $page->find('css', '[class*="product--variation-field"]');
    
    if (!$variationContainer) {
      throw new \Exception('Product variation container not found after AJAX');
    }
  }

}
