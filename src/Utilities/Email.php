<?php
/**
 * Email Editor Helper Functions
 *
 * Bridge functions for integrating email_editor fields with
 * the wp-register-emails library.
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

if ( ! function_exists( 'get_setting_fields_email' ) ) {
	/**
	 * Get email editor settings for use by wp-register-emails settings_callback.
	 *
	 * Reads the stored email_editor field value (enabled, subject, body, recipient)
	 * and returns it in the format expected by register_email_template's
	 * settings_callback: ['enabled', 'subject', 'message'].
	 *
	 * Usage in register_email_template:
	 *   'settings_callback' => fn() => get_setting_fields_email( 'my_plugin', 'email_purchase_receipt' ),
	 *
	 * @param string $settings_id Settings ID (e.g., 'sugarcart').
	 * @param string $field_key   The email_editor field key (e.g., 'email_purchase_receipt').
	 *
	 * @return array Settings array with 'enabled', 'subject', 'message' keys.
	 *               Returns empty array if settings not found.
	 */
	function get_setting_fields_email( string $settings_id, string $field_key ): array {
		$value = get_setting_field_value( $settings_id, $field_key );

		if ( empty( $value ) || ! is_array( $value ) ) {
			return [];
		}

		$result = [];

		if ( isset( $value['enabled'] ) ) {
			$result['enabled'] = (bool) $value['enabled'];
		}

		if ( ! empty( $value['subject'] ) ) {
			$result['subject'] = $value['subject'];
		}

		// wp_editor stores content without <p> tags; wpautop restores them.
		if ( ! empty( $value['message'] ) ) {
			$result['message'] = wpautop( $value['message'] );
		}

		return $result;
	}
}

if ( ! function_exists( 'get_setting_fields_email_recipient' ) ) {
	/**
	 * Get the recipient email address from an email_editor field.
	 *
	 * Reads the 'recipient' value stored by the show_recipient option
	 * on email_editor fields. Falls back to the site admin email.
	 *
	 * @param string $settings_id Settings ID (e.g., 'sugarcart').
	 * @param string $field_key   The email_editor field key (e.g., 'email_sale_notification').
	 *
	 * @return string Recipient email address.
	 */
	function get_setting_fields_email_recipient( string $settings_id, string $field_key ): string {
		$value = get_setting_field_value( $settings_id, $field_key );

		if ( is_array( $value ) && ! empty( $value['recipient'] ) && is_email( $value['recipient'] ) ) {
			return $value['recipient'];
		}

		return get_option( 'admin_email' );
	}
}

if ( ! function_exists( 'is_setting_fields_email_enabled' ) ) {
	/**
	 * Check if an email_editor field is enabled.
	 *
	 * @param string $settings_id Settings ID.
	 * @param string $field_key   The email_editor field key.
	 *
	 * @return bool True if enabled (or if no stored value and default is enabled).
	 */
	function is_setting_fields_email_enabled( string $settings_id, string $field_key ): bool {
		$value = get_setting_field_value( $settings_id, $field_key );

		if ( is_array( $value ) && isset( $value['enabled'] ) ) {
			return (bool) $value['enabled'];
		}

		// No stored value — assume enabled
		return true;
	}
}