<?php

namespace Drupal\commerce_vado;

/**
 * The vado field manager interface.
 */
interface VadoFieldManagerInterface {

  /**
   * Gets the vado config settings.
   *
   * @return \Drupal\Core\Config\Config
   *   The vado config settings.
   */
  public function getConfig();

  /**
   * Gets the vado fields.
   *
   * @return array[]
   *   The vado fields.
   */
  public function getVadoFields();

  /**
   * Gets the primary fields.
   *
   * Primary fields are the fields used to determine if
   * vado is enabled for a given product variation type.
   *
   * @return array
   *   The primary fields.
   */
  public function getPrimaryFields();

  /**
   * Determines if vado is enabled for the given variation type.
   *
   * Vado is enabled for a variation type if one of the primary
   * vado fields is enabled.
   *
   * @param string $variation_type_id
   *   The variation type id.
   *
   * @return bool
   *   TRUE if vado is enabled for the variation type, FALSE otherwise.
   */
  public function isVadoEnabled(string $variation_type_id);

  /**
   * Checks whether a variation type has a field.
   *
   * @param string $field_name
   *   The field name.
   * @param string $variation_type_id
   *   The variation type id.
   *
   * @return bool
   *   TRUE if the variation type has the provided field, FALSE otherwise.
   */
  public function hasField(string $field_name, string $variation_type_id);

  /**
   * Checks whether a variation type has data for a field.
   *
   * @param string $field_name
   *   The field name.
   * @param string $variation_type_id
   *   The variation type id.
   *
   * @return bool
   *   TRUE if the variation type has data for the field, FALSE otherwise.
   */
  public function hasFieldData(string $field_name, string $variation_type_id);

  /**
   * Installs a vado field on a variation type.
   *
   * @param string $field_name
   *   The field name.
   * @param string $variation_type_id
   *   The variation type id.
   */
  public function installField(string $field_name, string $variation_type_id);

  /**
   * Installs the default form display on a variation type.
   *
   * @param string $field_name
   *   The field name.
   * @param string $variation_type_id
   *   The variation type id.
   */
  public function installDefaultFormDisplay(string $field_name, string $variation_type_id);

  /**
   * Updates the fields on the variation type.
   *
   * $fields = [
   *  'vado_field_name' => boolean
   * ]
   *
   * @param array $fields
   *   An array of vado fields to update.
   *   The key is the field name, the value is a boolean.
   * @param string $variation_type_id
   *   The variation type id.
   */
  public function updateFields(array $fields, string $variation_type_id);

}
