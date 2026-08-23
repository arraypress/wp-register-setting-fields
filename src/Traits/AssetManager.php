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

use ArrayPress\RegisterSettingFields\Utils\Runtime;

use ArrayPress\RegisterSettingFields\RestApi;

/**
 * Trait AssetManager
 *
 * Handles enqueueing of scripts and styles.
 */
trait AssetManager {

	/**
	 * Handle for the bundled Select2 build.
	 *
	 * Deliberately *not* derived per build. Every registration of it is guarded
	 * by wp_script_is(), so the first plugin to register wins and the rest reuse
	 * it — one copy of a third-party library on the page is the goal, and is the
	 * inverse of the per-build derivation applied to everything this library
	 * owns. The cost is version skew if two builds ship different Select2s.
	 */
	private const SELECT2_HANDLE = 'arraypress-select2';

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
		$this->enqueue_field_specific_assets();
		$this->enqueue_core_assets();
		$this->localize_scripts();
	}

	/**
	 * Enqueue core CSS and JS.
	 *
	 * @return void
	 */
	protected function enqueue_core_assets(): void {
		wp_enqueue_composer_style(
			Runtime::handle(),
			__FILE__,
			'css/setting-fields.css'
		);

		// Build script dependencies
		$script_deps = [ 'jquery' ];

		$types_used = $this->get_field_types_used();

		// Add Select2 dependency if any Select2-based fields exist
		// This includes: select2, select_multiple, post, page, taxonomy, user, ajax
		if ( array_intersect( [
			'select2',
			'select_multiple',
			'post',
			'page',
			'taxonomy',
			'user',
			'ajax',
		], $types_used ) ) {
			$script_deps[] = self::SELECT2_HANDLE;
		}

		// Add color picker dependency
		if ( in_array( 'color', $types_used, true ) ) {
			$script_deps[] = 'wp-color-picker';
		}

		// Add jQuery UI Sortable for gallery, repeater, and sortable fields
		if ( array_intersect( [ 'gallery', 'repeater', 'sortable' ], $types_used ) ) {
			$script_deps[] = 'jquery-ui-sortable';
		}

		wp_enqueue_composer_script(
			Runtime::handle(),
			__FILE__,
			'js/setting-fields.js',
			$script_deps
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

		// Select2 - needed for select2, select_multiple, and all relational fields
		if ( array_intersect( [
			'select2',
			'select_multiple',
			'post',
			'page',
			'taxonomy',
			'user',
			'ajax',
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
	 * Enqueue Select2 library from CDN.
	 *
	 * @return void
	 */
	protected function enqueue_select2(): void {
		if ( ! wp_script_is( self::SELECT2_HANDLE, 'registered' ) ) {
			wp_register_composer_script(
				self::SELECT2_HANDLE,
				__FILE__,
				'js/select2.min.js',
				[ 'jquery' ],
				'4.1.0-rc.0'
			);
		}

		if ( ! wp_style_is( self::SELECT2_HANDLE, 'registered' ) ) {
			wp_register_composer_style(
				self::SELECT2_HANDLE,
				__FILE__,
				'css/select2.min.css',
				[],
				'4.1.0-rc.0'
			);
		}

		wp_enqueue_script( self::SELECT2_HANDLE );
		wp_enqueue_style( self::SELECT2_HANDLE );
	}

	/**
	 * Localize scripts with necessary data.
	 *
	 * @return void
	 */
	protected function localize_scripts(): void {
		$data = [
			'restUrl'    => rest_url( RestApi::rest_namespace() . '/' ),
			'restNonce'  => wp_create_nonce( 'wp_rest' ),
			'settingsId' => $this->id,
			'optionName' => $this->config['option_name'],
			'i18n'       => [
				'selectFile'         => __( 'Select File', 'setting-fields' ),
				'selectImage'        => __( 'Select Image', 'setting-fields' ),
				'selectImages'       => __( 'Select Images', 'setting-fields' ),
				'useFile'            => __( 'Use This File', 'setting-fields' ),
				'useImage'           => __( 'Use This Image', 'setting-fields' ),
				'useImages'          => __( 'Use These Images', 'setting-fields' ),
				'remove'             => __( 'Remove', 'setting-fields' ),
				'addRow'             => __( 'Add Row', 'setting-fields' ),
				'confirmRemove'      => __( 'Are you sure you want to remove this?', 'setting-fields' ),
				'searching'          => __( 'Searching...', 'setting-fields' ),
				'noResults'          => __( 'No results found', 'setting-fields' ),
				'loading'            => __( 'Loading...', 'setting-fields' ),
				'errorLoading'       => __( 'Error loading results', 'setting-fields' ),
				'inputTooShort'      => __( 'Please enter {n} or more characters', 'setting-fields' ),
				'inputTooLong'       => __( 'Please delete {n} characters', 'setting-fields' ),
				'maximumSelected'    => __( 'You can only select {n} items', 'setting-fields' ),
				'licenseKeyRequired' => __( 'Please enter a license key.', 'arraypress' ),
				'licenseInactive'    => __( 'Inactive', 'arraypress' ),
				'licenseActive'      => __( 'Active', 'arraypress' ),
				'licenseExpired'     => __( 'Expired', 'arraypress' ),
				'licenseInvalid'     => __( 'Invalid', 'arraypress' ),
				'licenseExpires'     => __( 'Expires: ', 'arraypress' ),
				'resetting'          => __( 'Resetting…', 'setting-fields' ),
				'resetFailed'        => __( 'Reset failed.', 'setting-fields' ),
				'exportSuccess'      => __( 'Settings exported successfully.', 'setting-fields' ),
				'exportFailed'       => __( 'Export failed.', 'setting-fields' ),
				'importConfirm'      => __( 'Are you sure you want to import settings? This will overwrite current values for any matching fields.', 'setting-fields' ),
				'importInvalidFile'  => __( 'Please select a valid JSON file.', 'setting-fields' ),
				'importInvalidJson'  => __( 'File contains invalid JSON.', 'setting-fields' ),
				'importSuccess'      => __( 'Settings imported. Reloading…', 'setting-fields' ),
				'importFailed'       => __( 'Import failed.', 'setting-fields' ),
			],
		];

		wp_localize_script( Runtime::handle(), Runtime::js_object( 'Data' ), $data );
		// Also published into a registry keyed by script handle. Two
		// Strauss-prefixed copies each enqueue their own script, and a bare
		// global would leave whichever localized last owning it for both; the
		// script resolves its own entry from the id WordPress stamps on its
		// <script> element.
		wp_add_inline_script(
			Runtime::handle(),
			sprintf(
				'window.ArrayPressSettingFields=window.ArrayPressSettingFields||{};window.ArrayPressSettingFields[%s]=%s;',
				wp_json_encode( Runtime::handle() ),
				wp_json_encode( $data )
			),
			'before'
		);

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
