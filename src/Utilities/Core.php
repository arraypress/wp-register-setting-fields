<?php
/**
 * Core Helper Functions
 *
 * Registration, value access, and CRUD operations for setting fields.
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

use ArrayPress\RegisterSettingFields\Registry;
use ArrayPress\RegisterSettingFields\SettingFields;

/** Registration **************************************************************/

if ( ! function_exists( 'register_setting_fields' ) ) {
	/**
	 * Register a new settings page.
	 *
	 * @param string $id     Unique identifier.
	 * @param array  $config Configuration array.
	 *
	 * @return SettingFields
	 */
	function register_setting_fields( string $id, array $config ): SettingFields {
		return new SettingFields( $id, $config );
	}
}

if ( ! function_exists( 'get_setting_fields' ) ) {
	/**
	 * Get a registered settings page instance.
	 *
	 * @param string $id Settings ID.
	 *
	 * @return SettingFields|null
	 */
	function get_setting_fields( string $id ): ?SettingFields {
		return Registry::instance()->get( $id );
	}
}

/** Value Access **************************************************************/

if ( ! function_exists( 'get_setting_field_value' ) ) {
	/**
	 * Get a setting value with automatic decryption and constant fallback.
	 *
	 * Use this instead of get_option() for registered settings.
	 *
	 * @param string $settings_id Settings ID.
	 * @param string $field_key   Field key.
	 * @param mixed  $default     Default value if not set.
	 *
	 * @return mixed The resolved value.
	 */
	function get_setting_field_value( string $settings_id, string $field_key, $default = null ) {
		$settings = Registry::instance()->get( $settings_id );

		if ( $settings ) {
			return $settings->get_value( $field_key, $default );
		}

		$options = get_option( $settings_id, [] );

		return is_array( $options ) && isset( $options[ $field_key ] )
			? $options[ $field_key ]
			: $default;
	}
}

if ( ! function_exists( 'update_setting_field_value' ) ) {
	/**
	 * Update a single setting value.
	 *
	 * Use this instead of update_option() for registered settings.
	 * Note: Bypasses encryption - use settings form for encrypted fields.
	 *
	 * @param string $settings_id Settings ID.
	 * @param string $field_key   Field key.
	 * @param mixed  $value       Value to set.
	 *
	 * @return bool True on success, false on failure.
	 */
	function update_setting_field_value( string $settings_id, string $field_key, $value ): bool {
		$options = get_option( $settings_id, [] );

		if ( ! is_array( $options ) ) {
			$options = [];
		}

		$options[ $field_key ] = $value;

		return update_option( $settings_id, $options );
	}
}

if ( ! function_exists( 'delete_setting_field_value' ) ) {
	/**
	 * Delete a single setting value (resets to default on next get).
	 *
	 * @param string $settings_id Settings ID.
	 * @param string $field_key   Field key.
	 *
	 * @return bool True on success, false on failure.
	 */
	function delete_setting_field_value( string $settings_id, string $field_key ): bool {
		$options = get_option( $settings_id, [] );

		if ( ! is_array( $options ) || ! isset( $options[ $field_key ] ) ) {
			return false;
		}

		unset( $options[ $field_key ] );

		return update_option( $settings_id, $options );
	}
}

if ( ! function_exists( 'get_all_setting_values' ) ) {
	/**
	 * Get all values for a settings page with decryption applied.
	 *
	 * @param string $settings_id Settings ID.
	 *
	 * @return array All settings as key-value pairs.
	 */
	function get_all_setting_values( string $settings_id ): array {
		$settings = Registry::instance()->get( $settings_id );

		if ( $settings ) {
			return $settings->get_values();
		}

		$options = get_option( $settings_id, [] );

		return is_array( $options ) ? $options : [];
	}
}