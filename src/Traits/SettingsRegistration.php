<?php
/**
 * Settings Registration Trait
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Traits;

/**
 * Trait SettingsRegistration
 *
 * Handles WordPress Settings API registration.
 */
trait SettingsRegistration {

	/**
	 * Register settings with WordPress Settings API.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			$this->config['option_group'],
			$this->config['option_name'],
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => $this->get_defaults(),
			]
		);
	}

	/**
	 * Sanitize all settings before saving.
	 *
	 * @param array $input The submitted settings.
	 *
	 * @return array
	 */
	public function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			$input = [];
		}

		$sanitized = [];
		$old_value = get_option( $this->config['option_name'], [] );

		foreach ( $this->fields as $key => $field ) {
			$value = $input[ $key ] ?? null;

			// Handle checkboxes that aren't submitted when unchecked
			if ( in_array( $field['type'], [ 'checkbox', 'toggle' ], true ) && $value === null ) {
				$value = false;
			}

			// Sanitize the value
			$sanitized[ $key ] = $this->sanitize_field_value( $key, $field, $value );
		}

		/**
		 * Filter the sanitized settings before saving.
		 *
		 * @param array $sanitized The sanitized settings.
		 * @param array $input     The original input.
		 * @param array $old_value The previous settings value.
		 * @param string $id       The settings ID.
		 */
		return apply_filters( 'setting_fields_sanitize_settings', $sanitized, $input, $old_value, $this->id );
	}

	/**
	 * Get default values for all fields.
	 *
	 * @return array
	 */
	protected function get_defaults(): array {
		$defaults = [];

		foreach ( $this->fields as $key => $field ) {
			$defaults[ $key ] = $field['default'] ?? '';
		}

		return $defaults;
	}

	/**
	 * Reset settings to defaults.
	 *
	 * @param string|null $tab Optional tab to reset. If null, resets all.
	 *
	 * @return bool
	 */
	public function reset_to_defaults( ?string $tab = null ): bool {
		$defaults = $this->get_defaults();
		$current  = get_option( $this->config['option_name'], [] );

		if ( $tab !== null ) {
			// Reset only fields in specified tab
			$tab_fields = $this->get_fields_for_tab( $tab );
			foreach ( $tab_fields as $key => $field ) {
				$current[ $key ] = $defaults[ $key ] ?? '';
			}
			$new_value = $current;
		} else {
			// Reset all
			$new_value = $defaults;
		}

		return update_option( $this->config['option_name'], $new_value );
	}

	/**
	 * Delete all settings.
	 *
	 * @return bool
	 */
	public function delete_settings(): bool {
		return delete_option( $this->config['option_name'] );
	}

}
