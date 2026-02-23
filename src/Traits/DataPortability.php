<?php
/**
 * Data Portability Trait
 *
 * Handles export and import of settings as JSON-encodable arrays.
 *
 * @package     ArrayPress\RegisterSettingFields\Traits
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Traits;

/**
 * Trait DataPortability
 */
trait DataPortability {

	/**
	 * Export settings as a JSON-encodable array.
	 *
	 * Returns decrypted values for all non-constant fields. Encrypted fields
	 * are exported in plain text so the JSON can be imported into another
	 * environment (which may have different encryption keys).
	 *
	 * @return array
	 */
	public function export_settings(): array {
		$values   = $this->get_values();
		$exported = [];

		foreach ( $this->fields as $field_key => $field ) {
			// Skip fields that get their value from constants
			if ( $this->is_encrypted_field( $field ) && $this->has_field_constant( $field_key, $field ) ) {
				continue;
			}

			// Skip layout fields that have no stored data
			if ( in_array( $field['type'] ?? '', [
				'message',
				'html',
				'separator',
				'heading',
				'hidden',
				'action_button',
				'clipboard',
			], true ) ) {
				continue;
			}

			$exported[ $field_key ] = $values[ $field_key ] ?? ( $field['default'] ?? '' );
		}

		return [
			'settings_id' => $this->id,
			'version'     => 1,
			'exported_at' => current_time( 'mysql' ),
			'data'        => $exported,
		];
	}

	/**
	 * Import settings from an exported array.
	 *
	 * Each field value is sanitized through the standard field sanitizer
	 * before saving. Encrypted fields are re-encrypted with the current
	 * environment's encryption key.
	 *
	 * @param array $import The import data (must contain 'data' key).
	 *
	 * @return bool|string True on success, error message on failure.
	 */
	public function import_settings( array $import ) {
		if ( empty( $import['data'] ) || ! is_array( $import['data'] ) ) {
			return __( 'Invalid import file: no data found.', 'setting-fields' );
		}

		// Validate settings ID if present
		if ( ! empty( $import['settings_id'] ) && $import['settings_id'] !== $this->id ) {
			return sprintf(
				__( 'Import file is for "%s", not "%s".', 'setting-fields' ),
				$import['settings_id'],
				$this->id
			);
		}

		$sanitized = [];

		foreach ( $this->fields as $field_key => $field ) {
			if ( ! array_key_exists( $field_key, $import['data'] ) ) {
				continue;
			}

			$sanitized[ $field_key ] = $this->sanitize_field_value(
				$field_key,
				$field,
				$import['data'][ $field_key ]
			);
		}

		if ( empty( $sanitized ) ) {
			return __( 'No valid fields found in import data.', 'setting-fields' );
		}

		// Merge with existing values (don't blow away fields not in the export)
		$current = get_option( $this->config['option_name'], [] );

		if ( ! is_array( $current ) ) {
			$current = [];
		}

		$merged = array_merge( $current, $sanitized );

		update_option( $this->config['option_name'], $merged );

		return true;
	}

}