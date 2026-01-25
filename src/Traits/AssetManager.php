<?php
/**
 * Asset Manager Trait
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Traits;

/**
 * Trait AssetManager
 *
 * Handles enqueueing of scripts and styles.
 */
trait AssetManager {

	/**
	 * Maybe enqueue assets on the settings page.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 *
	 * @return void
	 */
	public function maybe_enqueue_assets( string $hook_suffix ): void {
		if ( $hook_suffix !== $this->hook_suffix ) {
			return;
		}

		$this->enqueue_assets();
	}

	/**
	 * Enqueue all required assets.
	 *
	 * @return void
	 */
	protected function enqueue_assets(): void {
		$this->enqueue_core_assets();
		$this->enqueue_field_specific_assets();
		$this->localize_scripts();
	}

	/**
	 * Enqueue core CSS and JS.
	 *
	 * @return void
	 */
	protected function enqueue_core_assets(): void {
		wp_enqueue_composer_style(
			'arraypress-setting-fields',
			__FILE__,
			'css/setting-fields.css'
		);

		wp_enqueue_composer_script(
			'arraypress-setting-fields',
			__FILE__,
			'js/setting-fields.js',
			[ 'jquery' ]
		);
	}

	/**
	 * Enqueue assets based on field types used.
	 *
	 * @return void
	 */
	protected function enqueue_field_specific_assets(): void {
		$types_used = $this->get_field_types_used();

		// WordPress media library
		if ( array_intersect( [ 'image', 'file', 'gallery' ], $types_used ) ) {
			wp_enqueue_media();
		}

		// Color picker
		if ( in_array( 'color', $types_used, true ) ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
		}

		// Code editor
		if ( in_array( 'code', $types_used, true ) ) {
			$settings = wp_enqueue_code_editor( [ 'type' => 'text/html' ] );
			if ( $settings !== false ) {
				wp_add_inline_script(
					'code-editor',
					sprintf( 'jQuery.extend( wp.codeEditor.defaultSettings, %s );', wp_json_encode( $settings ) )
				);
			}
		}

		// Select2
		if ( array_intersect( [
			'select2',
			'select_multiple',
			'post_ajax',
			'taxonomy_ajax',
			'user_ajax',
			'ajax'
		], $types_used ) ) {
			$this->enqueue_select2();
		}

		// Date/time pickers
		if ( array_intersect( [ 'date', 'time', 'datetime', 'date_range', 'time_range' ], $types_used ) ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'jquery-ui-datepicker' );
		}
	}

	/**
	 * Enqueue Select2 library from composer assets.
	 * Only loads if Select2 is not already registered by another plugin.
	 *
	 * @return void
	 */
	protected function enqueue_select2(): void {
		// Check if select2 is already registered (by EDD, WooCommerce, etc.)
		if ( ! wp_script_is( 'select2', 'registered' ) ) {
			wp_enqueue_composer_style(
				'select2',
				__FILE__,
				'css/select2.min.css'
			);

			wp_enqueue_composer_script(
				'select2',
				__FILE__,
				'js/select2.min.js',
				[ 'jquery' ]
			);
		} else {
			// Use the already registered version
			wp_enqueue_style( 'select2' );
			wp_enqueue_script( 'select2' );
		}
	}

	/**
	 * Localize scripts with necessary data.
	 *
	 * @return void
	 */
	protected function localize_scripts(): void {
		wp_localize_script( 'arraypress-setting-fields', 'settingFieldsData', [
			'restUrl'    => rest_url( 'setting-fields/v1/' ),
			'restNonce'  => wp_create_nonce( 'wp_rest' ),
			'settingsId' => $this->id,
			'optionName' => $this->config['option_name'],
			'i18n'       => [
				'selectFile'      => __( 'Select File', 'setting-fields' ),
				'selectImage'     => __( 'Select Image', 'setting-fields' ),
				'selectImages'    => __( 'Select Images', 'setting-fields' ),
				'useFile'         => __( 'Use This File', 'setting-fields' ),
				'useImage'        => __( 'Use This Image', 'setting-fields' ),
				'useImages'       => __( 'Use These Images', 'setting-fields' ),
				'remove'          => __( 'Remove', 'setting-fields' ),
				'addRow'          => __( 'Add Row', 'setting-fields' ),
				'confirmRemove'   => __( 'Are you sure you want to remove this?', 'setting-fields' ),
				'searching'       => __( 'Searching...', 'setting-fields' ),
				'noResults'       => __( 'No results found', 'setting-fields' ),
				'loading'         => __( 'Loading...', 'setting-fields' ),
				'errorLoading'    => __( 'Error loading results', 'setting-fields' ),
				'inputTooShort'   => __( 'Please enter {n} or more characters', 'setting-fields' ),
				'inputTooLong'    => __( 'Please delete {n} characters', 'setting-fields' ),
				'maximumSelected' => __( 'You can only select {n} items', 'setting-fields' ),
			],
		] );
	}

	/**
	 * Get all field types currently used.
	 *
	 * @return array
	 */
	protected function get_field_types_used(): array {
		$types = [];

		foreach ( $this->fields as $field ) {
			$types[] = $field['type'];

			// Check sub-fields in repeaters and groups
			if ( isset( $field['sub_fields'] ) ) {
				foreach ( $field['sub_fields'] as $sub_field ) {
					$types[] = $sub_field['type'] ?? 'text';
				}
			}
		}

		return array_unique( $types );
	}

}