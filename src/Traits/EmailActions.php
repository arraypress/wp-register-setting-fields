<?php
/**
 * Email Actions Trait
 *
 * Handles email preview generation and test email sending
 * for email_editor fields.
 *
 * @package     ArrayPress\RegisterSettingFields\Traits
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Traits;

use WP_Error;

/**
 * Trait EmailActions
 */
trait EmailActions {

	/**
	 * Generate an email preview.
	 *
	 * Routes through the registered email system if configured,
	 * falls back to the field's preview_callback, then to a simple
	 * HTML preview.
	 *
	 * @param string $field_key The email editor field key.
	 * @param array  $data      Email data: subject, title, subtitle, message.
	 *
	 * @return array|WP_Error Array with 'html' key or WP_Error.
	 */
	public function preview_email( string $field_key, array $data ) {
		$field = $this->fields[ $field_key ] ?? null;

		if ( ! $field || ( $field['type'] ?? '' ) !== 'email_editor' ) {
			return new WP_Error(
				'invalid_field',
				__( 'Invalid email editor field.', 'setting-fields' ),
				[ 'status' => 400 ]
			);
		}

		$data = wp_parse_args( $data, [
			'subject'  => '',
			'title'    => '',
			'subtitle' => '',
			'message'  => '',
		] );

		// Route through wp-register-emails if configured
		$email_group    = $field['email_group'] ?? '';
		$email_template = $field['email_template'] ?? '';

		if ( $email_group && $email_template && function_exists( 'get_email_preview_html' ) ) {
			$html = get_email_preview_html( $email_group, $email_template, $data );

			if ( $html ) {
				return [ 'html' => $html ];
			}

			return new WP_Error(
				'preview_error',
				__( 'Email template preview returned empty.', 'setting-fields' ),
				[ 'status' => 500 ]
			);
		}

		// Legacy: use preview_callback if provided
		$callback = $field['preview_callback'] ?? null;

		if ( ! is_callable( $callback ) ) {
			return [
				'html' => $this->build_simple_email_html( $data['subject'], $data['message'] ),
			];
		}

		$result = call_user_func( $callback, array_merge( $data, [
			'settings_id' => $this->id,
			'field_key'   => $field_key,
			'field'       => $field,
		] ) );

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( is_string( $result ) ) {
			return [ 'html' => $result ];
		}

		if ( is_array( $result ) ) {
			if ( isset( $result['html'] ) ) {
				return $result;
			}

			if ( isset( $result['subject'], $result['message'] ) ) {
				return [
					'html' => $this->build_simple_email_html( $result['subject'], $result['message'] ),
				];
			}

			return $result;
		}

		return [ 'html' => (string) $result ];
	}

	/**
	 * Send a test email.
	 *
	 * Routes through the registered email system if configured,
	 * falls back to the field's send_callback, then to wp_mail.
	 *
	 * @param string $field_key The email editor field key.
	 * @param string $email     Recipient email address.
	 * @param array  $data      Email data: subject, title, subtitle, message.
	 *
	 * @return array|WP_Error Normalized result array or WP_Error.
	 */
	public function send_test_email( string $field_key, string $email, array $data ) {
		$field = $this->fields[ $field_key ] ?? null;

		if ( ! $field || ( $field['type'] ?? '' ) !== 'email_editor' ) {
			return new WP_Error(
				'invalid_field',
				__( 'Invalid email editor field.', 'setting-fields' ),
				[ 'status' => 400 ]
			);
		}

		if ( ! is_email( $email ) ) {
			return new WP_Error(
				'invalid_email',
				__( 'Invalid email address.', 'setting-fields' ),
				[ 'status' => 400 ]
			);
		}

		$data = wp_parse_args( $data, [
			'subject'  => '',
			'title'    => '',
			'subtitle' => '',
			'message'  => '',
		] );

		// Route through wp-register-emails if configured
		$email_group    = $field['email_group'] ?? '';
		$email_template = $field['email_template'] ?? '';

		if ( $email_group && $email_template && function_exists( 'send_email_template' ) ) {
			$sent = send_email_template( $email_group, $email_template, array_merge( $data, [
				'to'      => $email,
				'context' => 'test',
				'preview' => true,
			] ) );

			if ( $sent ) {
				return [
					'success' => true,
					/* translators: %s: recipient email address */
					'message' => sprintf( __( 'Test email sent to %s', 'setting-fields' ), $email ),
				];
			}

			return new WP_Error(
				'send_failed',
				__( 'Failed to send test email.', 'setting-fields' ),
				[ 'status' => 500 ]
			);
		}

		// Legacy: use send_callback if provided
		$callback = $field['send_callback'] ?? null;

		if ( ! is_callable( $callback ) ) {
			$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
			$sent    = wp_mail( $email, $data['subject'], wpautop( $data['message'] ), $headers );

			if ( $sent ) {
				return [
					'success' => true,
					/* translators: %s: recipient email address */
					'message' => sprintf( __( 'Test email sent to %s', 'setting-fields' ), $email ),
				];
			}

			return new WP_Error(
				'send_failed',
				__( 'Failed to send test email. Check your server mail configuration.', 'setting-fields' ),
				[ 'status' => 500 ]
			);
		}

		$result = call_user_func( $callback, array_merge( $data, [
			'to'          => $email,
			'settings_id' => $this->id,
			'field_key'   => $field_key,
			'field'       => $field,
		] ) );

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( $result === true || ( is_array( $result ) && ! empty( $result['success'] ) ) ) {
			return [
				'success' => true,
				'message' => is_array( $result ) && isset( $result['message'] )
					? $result['message']
					/* translators: %s: recipient email address */
					: sprintf( __( 'Test email sent to %s', 'setting-fields' ), $email ),
			];
		}

		return new WP_Error(
			'send_failed',
			is_array( $result ) && isset( $result['message'] )
				? $result['message']
				: __( 'Failed to send test email.', 'setting-fields' ),
			[ 'status' => 500 ]
		);
	}

	/**
	 * Build a simple HTML email preview.
	 *
	 * @param string $subject The email subject.
	 * @param string $message The email body (raw HTML or plain text).
	 *
	 * @return string Complete HTML document.
	 */
	protected function build_simple_email_html( string $subject, string $message ): string {
		return sprintf(
			'<!DOCTYPE html><html><head><meta charset="utf-8"><title>%s</title></head>'
			. '<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; padding: 40px; max-width: 600px; margin: 0 auto;">'
			. '<h2 style="margin-bottom: 20px;">%s</h2>'
			. '<hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">'
			. '%s</body></html>',
			esc_html( $subject ),
			esc_html( $subject ),
			wpautop( $message )
		);
	}
}
