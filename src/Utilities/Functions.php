<?php
/**
 * Helper Functions
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Utilities;

use ArrayPress\RegisterSettingFields\Registry;
use ArrayPress\RegisterSettingFields\RestApi;
use ArrayPress\RegisterSettingFields\SettingFields;

// Auto-register REST API routes
RestApi::register();

if ( ! function_exists( __NAMESPACE__ . '\\register_setting_fields' ) ) {
	/**
	 * Register a new settings page.
	 *
	 * @param string $id     Unique identifier.
	 * @param array  $config Configuration array.
	 *
	 * @return SettingFields
	 */
	function register_setting_fields( string $id, array $config ): SettingFields {
		return Registry::instance()->register( $id, $config );
	}
}

if ( ! function_exists( __NAMESPACE__ . '\\get_setting_fields' ) ) {
	/**
	 * Get a registered settings page.
	 *
	 * @param string $id Settings ID.
	 *
	 * @return SettingFields|null
	 */
	function get_setting_fields( string $id ): ?SettingFields {
		return Registry::instance()->get( $id );
	}
}

if ( ! function_exists( __NAMESPACE__ . '\\get_setting_field_value' ) ) {
	/**
	 * Get a setting value.
	 *
	 * @param string $option_name Option name.
	 * @param string $field_key   Field key.
	 * @param mixed  $default     Default value.
	 *
	 * @return mixed
	 */
	function get_setting_field_value( string $option_name, string $field_key, $default = null ) {
		$options = get_option( $option_name, [] );

		if ( ! is_array( $options ) ) {
			return $default;
		}

		return $options[ $field_key ] ?? $default;
	}
}

if ( ! function_exists( __NAMESPACE__ . '\\update_setting_field_value' ) ) {
	/**
	 * Update a single setting value.
	 *
	 * @param string $option_name Option name.
	 * @param string $field_key   Field key.
	 * @param mixed  $value       Value to set.
	 *
	 * @return bool
	 */
	function update_setting_field_value( string $option_name, string $field_key, $value ): bool {
		$options = get_option( $option_name, [] );

		if ( ! is_array( $options ) ) {
			$options = [];
		}

		$options[ $field_key ] = $value;

		return update_option( $option_name, $options );
	}
}
