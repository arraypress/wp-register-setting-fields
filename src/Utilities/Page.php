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