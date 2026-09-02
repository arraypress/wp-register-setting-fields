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

declare( strict_types=1 );

if ( ! function_exists( 'get_setting_fields_email' ) ) {
	/**
	 * What the site owner typed into an email_editor field, in the shape
	 * wp-register-emails reads.
	 *
	 * The field stores `recipient`, `subject`, `heading` and `body`, and an
	 * `enabled` toggle if the consumer gave it one. `Emails::compose()` reads
	 * `enabled`, `to`, `subject`, `content` and `context`. This is the seam
	 * between the two, and it used to be stale on both sides — it read a
	 * `message` the field never stored and returned a `title` nothing
	 * consumed, so an owner's edited heading and body were quietly thrown
	 * away and the plugin's defaults went out instead.
	 *
	 *     register_email( 'my_plugin', 'purchase_receipt', [
	 *         'settings' => fn() => get_setting_fields_email( 'my_plugin', 'email_purchase_receipt' ),
	 *     ] );
	 *
	 * A part the owner left empty is left out, so the email's registered
	 * default stands in for it.
	 *
	 * @param string $settings_id Settings ID (e.g., 'sugarcart').
	 * @param string $field_key   The email_editor field key (e.g., 'email_purchase_receipt').
	 *
	 * @return array{enabled?: bool, to?: string[], subject?: string, content?: string, context?: array{title: string}}
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

		// Several addresses, separated by commas — which is what the field
		// says beside its recipient box.
		$to = array_filter( array_map( 'sanitize_email', explode( ',', (string) ( $value['recipient'] ?? '' ) ) ) );

		if ( [] !== $to ) {
			$result['to'] = array_values( $to );
		}

		if ( ! empty( $value['subject'] ) ) {
			$result['subject'] = (string) $value['subject'];
		}

		// The editor stores the body without its <p> tags, as wp_editor
		// does; wpautop() puts them back.
		if ( ! empty( $value['body'] ) ) {
			$result['content'] = wpautop( (string) $value['body'] );
		}

		// The heading is the template's title, which sits above the body.
		if ( ! empty( $value['heading'] ) ) {
			$result['context'] = [ 'title' => (string) $value['heading'] ];
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
			return (string) $value['recipient'];
		}

		return (string) get_option( 'admin_email', '' );
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
