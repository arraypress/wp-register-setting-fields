<?php
/**
 * Type Check Helper Functions
 *
 * Convenience functions for checking toggle, multi-select, and page field values.
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
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
