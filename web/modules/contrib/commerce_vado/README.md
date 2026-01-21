Commerce Variation Add-on
-------------------------

CONTENTS OF THIS FILE
---------------------

-   Introduction
-   Requirements
-   Installation
-   Configuration
-   Troubleshooting
-   Maintainers

INTRODUCTION
------------

Commerce Variation Add-On allows normal Drupal Commerce Product Variations
to reference other Drupal Commerce Product Variations, and automatically
add them to the cart.

Some other key features are:
-   The ability nest multiple dynamic groups of variations on a custom
    add to cart form.
-   The ability to lock the quantity of the child variation
    to the quantity of the parent variation.
-   A flexible framework to apply discounts.

So if you would like a flexible solution to add multiple products
to the cart at the same time, this is the module for you.

REQUIREMENTS
------------

This module relies on Drupal Commerce to function.

-   https://drupal.org/project/commerce

INSTALLATION
------------

Install this as you would any other Drupal.org module - Composer is
recommended:

    composer require drupal/commerce_vado

BASIS CONFIGURATION
-------------

-   Primary configuration can be found in the Commerce menu under
    Configuration >> Products >> Variation add-on,
    or @ /admin/commerce/config/vado. Use this configuration page
    to add the required fields to your product variation types.
    The Child Variations field and the Variation Groups field
    are the 2 primary variation reference fields.

-   You can create variation groups in the Commerce menu under Variation Groups,
    or @ /admin/commerce/vado-groups.

-   You can add child variations and/or variation groups by editing your
    parent product variation after enabling the fields.

-   See the documentation for detailed instructions:
    https://www.drupal.org/docs/contributed-modules/commerce-variation-add-on

TROUBLESHOOTING
---------------

When posting issues to the issue queue, please ensure you've provided clear
steps to reproduce the problem you are having, as well as details about
your configuration and how you are trying to implement the module.

MAINTAINERS
-----------

Current maintainers:

-   Tony Ferguson (tonytheferg) - https://www.drupal.org/u/tonytheferg
    -   Primary maintainer, project lead
-   Eric Chew (ericchew) - https://www.drupal.org/u/ericchew
    -   Primary developer, code review

Development for the 2.0.x branch was sponsored by tonytheferg, and ericchew.

ORIGIN
------

The 8.x-1.x branch was originally written by:
-   Gabriel Simmer (gmem) - https://drupal.org/u/gmem

The 8.x-1.x branch was sponsored by:
-   Acro Media Inc. - https://www.drupal.org/acro-media-inc
