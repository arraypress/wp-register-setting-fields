<?php
/**
 * Settings Actions Trait
 *
 * Handles field callback execution and persistence for reset,
 * action buttons, and license fields.
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
 * Trait SettingsActions
 */
trait SettingsActions {

	/**
	 * Reset settings to their default values.
	 *
	 * Resets fields for the specified tab (or all fields) back to their
	 * configured defaults. Fields not in the reset scope are preserved.
	 * Layout-only fields that store no data are skipped.
	 *
	 * @param string $tab Optional. Tab key to limit reset scope. Empty resets all.
	 *
	 * @return int Number of fields reset.
	 */
	public function reset_settings( string $tab = '' ): int {
		$current = get_option( $this->config['option_name'], [] );

		if ( ! is_array( $current ) ) {
			$current = [];
		}

		$reset_count = 0;
		$skip_types  = [ 'message', 'html', 'separator', 'heading', 'hidden', 'action_button', 'clipboard' ];

		foreach ( $this->fields as $field_key => $field ) {
			if ( ! empty( $tab ) && ( $field['tab'] ?? '' ) !== $tab ) {
				continue;
			}

			if ( in_array( $field['type'] ?? '', $skip_types, true ) ) {
				continue;
			}

			$current[ $field_key ] = $field['default'] ?? '';
			$reset_count++;
		}

		update_option( $this->config['option_name'], $current );

		return $reset_count;
	}

	/**
	 * Process an action button callback.
	 *
	 * Validates the field type, executes the action_callback, and
	 * normalizes the result into a consistent array.
	 *
	 * @param string $field_key   The action button field key.
	 * @param string $input_value Optional input value from the field.
	 *
	 * @return array|WP_Error Normalized result array or WP_Error.
	 */
	public function process_action( string $field_key, string $input_value = '' ) {
		$field = $this->fields[ $field_key ] ?? null;

		if ( ! $field || ( $field['type'] ?? '' ) !== 'action_button' ) {
			return new WP_Error(
				'invalid_field',
				__( 'Invalid action button field.', 'setting-fields' ),
				[ 'status' => 400 ]
			);
		}

		$callback = $field['action_callback'] ?? null;

		if ( ! is_callable( $callback ) ) {
			return new WP_Error(
				'invalid_callback',
				__( 'No action callback defined for this field.', 'setting-fields' ),
				[ 'status' => 500 ]
			);
		}

		$result = call_user_func( $callback, [
			'settings_id' => $this->id,
			'field_key'   => $field_key,
			'input_value' => $input_value,
		] );

		return $this->normalize_callback_result( $result );
	}

	/**
	 * Process a license action (activate/deactivate).
	 *
	 * Executes the field's callback, normalizes the result, and
	 * persists status/expiry to the stored option value.
	 *
	 * @param string $field_key The license field key.
	 * @param string $key       The license key.
	 * @param string $action    Either 'activate' or 'deactivate'.
	 *
	 * @return array|WP_Error Normalized result array or WP_Error.
	 */
	public function process_license( string $field_key, string $key, string $action ) {
		$field = $this->fields[ $field_key ] ?? null;

		if ( ! $field || ( $field['type'] ?? '' ) !== 'license' ) {
			return new WP_Error(
				'invalid_field',
				__( 'Invalid license field.', 'setting-fields' ),
				[ 'status' => 400 ]
			);
		}

		$callback = $field['callback'] ?? null;

		if ( ! is_callable( $callback ) ) {
			return new WP_Error(
				'invalid_callback',
				__( 'No callback defined for this license field.', 'setting-fields' ),
				[ 'status' => 500 ]
			);
		}

		$result = call_user_func( $callback, [
			'settings_id' => $this->id,
			'field_key'   => $field_key,
			'key'         => $key,
			'action'      => $action,
		] );

		// Normalize result
		if ( is_bool( $result ) ) {
			$result = [
				'success' => $result,
				'message' => $result
					? __( 'License action completed successfully.', 'setting-fields' )
					: __( 'License action failed.', 'setting-fields' ),
			];
		}

		if ( is_string( $result ) ) {
			$result = [
				'success' => true,
				'message' => $result,
			];
		}

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		$result = wp_parse_args( (array) $result, [
			'success'   => true,
			'message'   => __( 'License action completed.', 'setting-fields' ),
			'status'    => null,
			'expiry'    => null,
			'url'       => null,
			'url_label' => null,
		] );

		// Persist status and expiry
		if ( $result['status'] ) {
			$options = get_option( $this->config['option_name'], [] );
			$current = wp_parse_args( (array) ( $options[ $field_key ] ?? [] ), [
				'key'    => '',
				'status' => 'inactive',
				'expiry' => '',
			] );

			$current['key']    = $key;
			$current['status'] = sanitize_key( $result['status'] );

			if ( $result['expiry'] !== null ) {
				$current['expiry'] = sanitize_text_field( $result['expiry'] );
			}

			$options[ $field_key ] = $current;
			update_option( $this->config['option_name'], $options );
		}

		return $result;
	}

	/**
	 * Normalize a callback result into a consistent array.
	 *
	 * Accepts bool, string, array, or WP_Error and returns a
	 * normalized array with 'success' and 'message' keys.
	 *
	 * @param mixed $result The raw callback result.
	 *
	 * @return array|WP_Error Normalized result array or WP_Error.
	 */
	protected function normalize_callback_result( $result ) {
		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( is_bool( $result ) ) {
			return [
				'success' => $result,
				'message' => $result
					? __( 'Action completed successfully.', 'setting-fields' )
					: __( 'Action failed.', 'setting-fields' ),
			];
		}

		if ( is_string( $result ) ) {
			return [
				'success' => true,
				'message' => $result,
			];
		}

		if ( is_array( $result ) ) {
			return wp_parse_args( $result, [
				'success' => true,
				'message' => __( 'Action completed.', 'setting-fields' ),
			] );
		}

		return [
			'success' => true,
			'message' => __( 'Action completed.', 'setting-fields' ),
		];
	}

}