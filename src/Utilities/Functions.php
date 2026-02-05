<?php
/**
 * Helper Functions
 *
 * Global helper functions for accessing setting field values.
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

/** Value Access - Replacements for get_option/update_option ******************/

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

/** Type Helpers **************************************************************/

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
		return filter_var(
			get_setting_field_value( $settings_id, $field_key, false ),
			FILTER_VALIDATE_BOOLEAN
		);
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
		$value = get_setting_field_value( $settings_id, $field_key, [] );

		return is_array( $value ) && in_array( $option, $value, true );
	}
}

/** Page Field Helpers ********************************************************/

if ( ! function_exists( 'get_setting_field_page_id' ) ) {
	/**
	 * Get a page/post ID from a setting field.
	 *
	 * @param string $settings_id Settings ID.
	 * @param string $field_key   Field key.
	 *
	 * @return int Page/post ID or 0 if not set.
	 */
	function get_setting_field_page_id( string $settings_id, string $field_key ): int {
		return absint( get_setting_field_value( $settings_id, $field_key, 0 ) );
	}
}

if ( ! function_exists( 'get_setting_field_page_url' ) ) {
	/**
	 * Get the URL for a page/post stored in a setting field.
	 *
	 * @param string $settings_id Settings ID.
	 * @param string $field_key   Field key.
	 * @param string $fallback    Fallback URL if page not set.
	 *
	 * @return string Page URL or fallback.
	 */
	function get_setting_field_page_url( string $settings_id, string $field_key, string $fallback = '' ): string {
		$page_id = get_setting_field_page_id( $settings_id, $field_key );

		if ( $page_id > 0 ) {
			$url = get_permalink( $page_id );

			if ( $url ) {
				return $url;
			}
		}

		return $fallback ?: home_url( '/' );
	}
}

if ( ! function_exists( 'is_setting_field_page' ) ) {
	/**
	 * Check if currently viewing the page stored in a setting field.
	 *
	 * @param string $settings_id Settings ID.
	 * @param string $field_key   Field key.
	 *
	 * @return bool
	 */
	function is_setting_field_page( string $settings_id, string $field_key ): bool {
		if ( ! is_singular() ) {
			return false;
		}

		$page_id = get_setting_field_page_id( $settings_id, $field_key );

		return $page_id > 0 && is_page( $page_id );
	}
}