<?php

/**
 * @file
 * Post update functions for the Tool module.
 */

/**
 * Installs the serialization module, a newly declared dependency.
 */
function tool_post_update_install_serialization(): void {
  \Drupal::service('module_installer')->install(['serialization']);
}
