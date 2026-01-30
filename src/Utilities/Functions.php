<?php
/**
 * Helper Functions
 *
 * Global helper functions for accessing setting field values.
 * These are intentionally NOT namespaced for ease of use throughout any codebase.
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

use ArrayPress\RegisterSettingFields\Registry;
use ArrayPress\RegisterSettingFields\SettingFields;

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

if ( ! function_exists( 'get_setting_field_value' ) ) {
	/**
	 * Get a setting value (with automatic decryption for encrypted fields).
	 *
	 * This is the primary helper function for retrieving field values.
	 * It automatically handles decryption and constant fallback.
	 *
	 * @param string $settings_id Settings ID (the ID used when registering).
	 * @param string $field_key   Field key.
	 * @param mixed  $default     Default value.
	 *
	 * @return mixed The decrypted/resolved value.
	 */
	function get_setting_field_value( string $settings_id, string $field_key, $default = null ) {
		$settings = Registry::instance()->get( $settings_id );

		if ( $settings ) {
			return $settings->get_value( $field_key, $default );
		}

		// Fallback to raw option if settings not registered yet
		$options = get_option( $settings_id, [] );

		if ( ! is_array( $options ) ) {
			return $default;
		}

		return $options[ $field_key ] ?? $default;
	}
}

if ( ! function_exists( 'get_setting_field_value_info' ) ) {
	/**
	 * Get detailed information about a setting value.
	 *
	 * Returns an array with:
	 * - value: The actual value (decrypted if applicable)
	 * - source: 'constant', 'database', or 'default'
	 * - is_encrypted: Whether the value is stored encrypted
	 * - constant_name: The constant name (if applicable)
	 *
	 * @param string $settings_id Settings ID.
	 * @param string $field_key   Field key.
	 * @param mixed  $default     Default value.
	 *
	 * @return array{value: mixed, source: string, is_encrypted: bool, constant_name: string|null}
	 */
	function get_setting_field_value_info( string $settings_id, string $field_key, $default = null ): array {
		$settings = Registry::instance()->get( $settings_id );

		if ( $settings && method_exists( $settings, 'get_value_info' ) ) {
			return $settings->get_value_info( $field_key, $default );
		}

		return [
			'value'         => $default,
			'source'        => 'default',
			'is_encrypted'  => false,
			'constant_name' => null,
		];
	}
}

if ( ! function_exists( 'is_setting_from_constant' ) ) {
	/**
	 * Check if a setting value comes from a constant.
	 *
	 * @param string $settings_id Settings ID.
	 * @param string $field_key   Field key.
	 *
	 * @return bool
	 */
	function is_setting_from_constant( string $settings_id, string $field_key ): bool {
		$settings = Registry::instance()->get( $settings_id );

		if ( $settings && method_exists( $settings, 'is_from_constant' ) ) {
			return $settings->is_from_constant( $field_key );
		}

		return false;
	}
}

if ( ! function_exists( 'update_setting_field_value' ) ) {
	/**
	 * Update a single setting value.
	 *
	 * Note: This bypasses encryption. For encrypted fields, use the settings
	 * form or call the settings instance directly.
	 *
	 * @param string $settings_id Settings ID.
	 * @param string $field_key   Field key.
	 * @param mixed  $value       Value to set.
	 *
	 * @return bool
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

if ( ! function_exists( 'get_all_setting_values' ) ) {
	/**
	 * Get all values for a settings page (with decryption applied).
	 *
	 * @param string $settings_id Settings ID.
	 *
	 * @return array
	 */
	function get_all_setting_values( string $settings_id ): array {
		$settings = Registry::instance()->get( $settings_id );

		if ( $settings ) {
			return $settings->get_values();
		}

		return get_option( $settings_id, [] );
	}
}

if ( ! function_exists( 'rotate_setting_encryption_key' ) ) {
	/**
	 * Rotate the encryption key for a settings page.
	 *
	 * Re-encrypts all encrypted field values with a new key.
	 *
	 * @param string $settings_id Settings ID.
	 * @param string $new_key     New encryption key.
	 *
	 * @return bool Whether rotation was successful.
	 */
	function rotate_setting_encryption_key( string $settings_id, string $new_key ): bool {
		$settings = Registry::instance()->get( $settings_id );

		if ( $settings && method_exists( $settings, 'rotate_encryption_key' ) ) {
			return $settings->rotate_encryption_key( $new_key );
		}

		return false;
	}
}