<?php
/**
 * License Field Helper Functions
 *
 * Utility functions for reading and updating license field data
 * stored by the setting fields library.
 *
 * File: src/Helpers/License.php
 *
 * @package ArrayPress\WP\Register\SettingFields
 */

declare( strict_types=1 );

use ArrayPress\RegisterSettingFields\Registry;

if ( ! function_exists( 'get_setting_field_license' ) ) {
	/**
	 * Get license field data.
	 *
	 * Returns the full license array (key, status, expiry) for a
	 * given settings instance and field key.
	 *
	 * @param string $settings_id The settings instance ID.
	 * @param string $field_key   The license field key.
	 *
	 * @return array{key: string, status: string, expiry: string} License data.
	 */
	function get_setting_field_license( string $settings_id, string $field_key ): array {
		$value = get_setting_field_value( $settings_id, $field_key, [] );

		return wp_parse_args( (array) $value, [
			'key'    => '',
			'status' => 'inactive',
			'expiry' => '',
		] );
	}
}

if ( ! function_exists( 'get_setting_field_license_key' ) ) {
	/**
	 * Get just the license key string.
	 *
	 * @param string $settings_id The settings instance ID.
	 * @param string $field_key   The license field key.
	 *
	 * @return string The license key.
	 */
	function get_setting_field_license_key( string $settings_id, string $field_key ): string {
		$license = get_setting_field_license( $settings_id, $field_key );

		return $license['key'];
	}
}

if ( ! function_exists( 'get_setting_field_license_status' ) ) {
	/**
	 * Get the license status.
	 *
	 * @param string $settings_id The settings instance ID.
	 * @param string $field_key   The license field key.
	 *
	 * @return string The status: inactive, active, expired, or invalid.
	 */
	function get_setting_field_license_status( string $settings_id, string $field_key ): string {
		$license = get_setting_field_license( $settings_id, $field_key );

		return $license['status'];
	}
}

if ( ! function_exists( 'is_setting_field_license_active' ) ) {
	/**
	 * Check if a license is currently active.
	 *
	 * @param string $settings_id The settings instance ID.
	 * @param string $field_key   The license field key.
	 *
	 * @return bool True if the license status is 'active'.
	 */
	function is_setting_field_license_active( string $settings_id, string $field_key ): bool {
		return get_setting_field_license_status( $settings_id, $field_key ) === 'active';
	}
}

if ( ! function_exists( 'update_setting_field_license_status' ) ) {
	/**
	 * Update the license status and optionally the expiry.
	 *
	 * Use this from cron jobs, webhooks, or remote license checks
	 * to update the stored license state without going through the
	 * settings page.
	 *
	 * @param string $settings_id The settings instance ID.
	 * @param string $field_key   The license field key.
	 * @param string $status      New status: inactive, active, expired, or invalid.
	 * @param string $expiry      Optional new expiry date string.
	 *
	 * @return bool True if the update succeeded.
	 */
	function update_setting_field_license_status( string $settings_id, string $field_key, string $status, string $expiry = '' ): bool {
		$allowed_statuses = [ 'inactive', 'active', 'expired', 'invalid' ];

		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			return false;
		}

		$instance = Registry::instance()->get( $settings_id );

		if ( ! $instance ) {
			return false;
		}

		$option_name = $instance->get_option_name();
		$options     = get_option( $option_name, [] );
		$current     = wp_parse_args( (array) ( $options[ $field_key ] ?? [] ), [
			'key'    => '',
			'status' => 'inactive',
			'expiry' => '',
		] );

		$current['status'] = $status;

		if ( $expiry !== '' ) {
			$current['expiry'] = sanitize_text_field( $expiry );
		}

		$options[ $field_key ] = $current;

		return update_option( $option_name, $options );
	}
}