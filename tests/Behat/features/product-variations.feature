@commerce @smoke
Feature: Commerce Product Variation Switching
  As a customer
  I want to select different product variations
  So that I can see updated price and product details

  Background:
    Given I am an anonymous user

  @javascript
  Scenario: Product page loads with default variation
    Given I am on "/product/1"
    And I wait for AJAX to finish
    Then I should see the button "Add to cart"
    And I should see a ".attribute-widgets" element

  @javascript
  Scenario: Switch product watt variation updates price
    Given I am on "/product/1"
    When I select a different option from the watt dropdown
    And I wait for AJAX to finish
    Then the product variation should have changed
    And I should see a different price

  @javascript
  Scenario: Multiple variation attributes update correctly
    Given I am on "/product/1"
    When I select a different watt option
    And I wait for AJAX to finish
    And I select a different CCT option
    And I wait for AJAX to finish
    Then the product variation should have changed
    And the SKU should be updated

  @smoke
  Scenario: Add to cart button is visible
    Given I am on "/product/1"
    Then I should see the button "Add to cart"
