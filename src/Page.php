<?php
/**
 * Page Field Helper Functions
 *
 * Convenience functions for the page/post a setting points at.
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

use ArrayPress\FieldKit\Value;

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
		return Value::id( get_setting_field_value( $settings_id, $field_key, 0 ) );
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
	 * @return string Page URL, the fallback, or the site's home page.
	 */
	function get_setting_field_page_url( string $settings_id, string $field_key, string $fallback = '' ): string {
		return Value::url( get_setting_field_value( $settings_id, $field_key, 0 ), $fallback );
	}
}

if ( ! function_exists( 'is_setting_field_page' ) ) {
	/**
	 * Check if currently viewing the page stored in a setting field.
	 *
	 * Compares the queried object rather than calling is_page(): a setting
	 * can point at any post type — the kit's `post`, `page` and `ajax` types
	 * all store an id the same way — and is_page() answers false for every
	 * one of them that is not a page, which this used to get wrong.
	 *
	 * @param string $settings_id Settings ID.
	 * @param string $field_key   Field key.
	 *
	 * @return bool
	 */
	function is_setting_field_page( string $settings_id, string $field_key ): bool {
		return Value::is_viewing( get_setting_field_value( $settings_id, $field_key, 0 ) );
	}
}
