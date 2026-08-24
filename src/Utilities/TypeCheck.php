<?php
/**
 * Type Check Helper Functions
 *
 * Convenience functions for asking what a stored setting means.
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

use ArrayPress\FieldKit\Value;

/*
 * What a value means is the kit's answer, not this library's: the same
 * question is asked of a term's meta, a user's meta and a post's meta, and
 * four copies of "is this on" would draw the line in four slightly different
 * places. These read the value and hand it over.
 */

/** Toggle & Multi-Select Helpers *********************************************/

if ( ! function_exists( 'is_setting_on' ) ) {
	/**
	 * Check if a toggle/checkbox field is enabled.
	 *
	 * @param string $settings_id Settings ID.
	 * @param string $field_key   Field key.
	 *
	 * @return bool
	 */
	function is_setting_on( string $settings_id, string $field_key ): bool {
		return Value::is_on( get_setting_field_value( $settings_id, $field_key, false ) );
	}
}

if ( ! function_exists( 'is_setting_enabled' ) ) {
	/**
	 * Check if a value exists in a checkbox_group or multi-select field.
	 *
	 * @param string $settings_id Settings ID.
	 * @param string $field_key   Field key.
	 * @param string $option      Option value to check for.
	 *
	 * @return bool
	 */
	function is_setting_enabled( string $settings_id, string $field_key, string $option ): bool {
		return Value::includes( get_setting_field_value( $settings_id, $field_key, [] ), $option );
	}
}

if ( ! function_exists( 'get_setting_field_list' ) ) {
	/**
	 * Get a tags or multi-select field as a list of strings.
	 *
	 * @param string $settings_id Settings ID.
	 * @param string $field_key   Field key.
	 *
	 * @return string[]
	 */
	function get_setting_field_list( string $settings_id, string $field_key ): array {
		return Value::list( get_setting_field_value( $settings_id, $field_key, [] ) );
	}
}

if ( ! function_exists( 'get_setting_field_ids' ) ) {
	/**
	 * Get a relational field as a list of object ids.
	 *
	 * @param string $settings_id Settings ID.
	 * @param string $field_key   Field key.
	 *
	 * @return int[]
	 */
	function get_setting_field_ids( string $settings_id, string $field_key ): array {
		return Value::ids( get_setting_field_value( $settings_id, $field_key, [] ) );
	}
}
