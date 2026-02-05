<?php
/**
 * Config Parser Trait
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Traits;

/**
 * Trait ConfigParser
 *
 * Handles parsing and normalizing the configuration array.
 */
trait ConfigParser {

	/**
	 * Parse the configuration array.
	 *
	 * @return void
	 */
	protected function parse_config(): void {
		$this->parse_tabs();
		$this->parse_sections();
		$this->parse_fields();
	}

	/**
	 * Parse tabs configuration.
	 *
	 * @return void
	 */
	protected function parse_tabs(): void {
		if ( empty( $this->config['tabs'] ) ) {
			$this->tabs = [];
			return;
		}

		foreach ( $this->config['tabs'] as $key => $tab ) {
			// Handle simple string format: 'general' => 'General Settings'
			if ( is_string( $tab ) ) {
				$this->tabs[ $key ] = [
					'label' => $tab,
					'icon'  => '',
				];
			} else {
				// Full array format
				$this->tabs[ $key ] = wp_parse_args( $tab, [
					'label' => ucfirst( $key ),
					'icon'  => '',
				] );
			}
		}
	}

	/**
	 * Parse sections configuration.
	 *
	 * @return void
	 */
	protected function parse_sections(): void {
		if ( empty( $this->config['sections'] ) ) {
			$this->sections = [];
			return;
		}

		foreach ( $this->config['sections'] as $key => $section ) {
			$this->sections[ $key ] = wp_parse_args( $section, [
				'title'       => '',
				'description' => '',
				'tab'         => '',
			] );
		}
	}

	/**
	 * Parse fields configuration.
	 *
	 * @return void
	 */
	protected function parse_fields(): void {
		if ( empty( $this->config['fields'] ) ) {
			$this->fields = [];
			return;
		}

		$first_tab = ! empty( $this->tabs ) ? array_key_first( $this->tabs ) : '';

		foreach ( $this->config['fields'] as $key => $field ) {
			$this->fields[ $key ] = $this->normalize_field( $key, $field, $first_tab );
		}
	}

	/**
	 * Normalize a single field configuration.
	 *
	 * @param string $key       Field key.
	 * @param array  $field     Field configuration.
	 * @param string $first_tab First tab key for default.
	 *
	 * @return array
	 */
	protected function normalize_field( string $key, array $field, string $first_tab ): array {
		$defaults = [
			'type'        => 'text',
			'label'       => ucfirst( str_replace( [ '_', '-' ], ' ', $key ) ),
			'description' => '',
			'default'     => '',
			'tab'         => $first_tab,
			'section'     => '',
			'required'    => false,
			'class'       => '',
			'placeholder' => '',
			'show_when'   => [],
		];

		$field = wp_parse_args( $field, $defaults );

		// Type-specific defaults
		$field = $this->apply_type_defaults( $field );

		return $field;
	}

	/**
	 * Apply type-specific default values.
	 *
	 * @param array $field Field configuration.
	 *
	 * @return array
	 */
	protected function apply_type_defaults( array $field ): array {
		switch ( $field['type'] ) {
			case 'checkbox':
			case 'toggle':
				if ( $field['default'] === '' ) {
					$field['default'] = false;
				}
				break;

			case 'checkbox_group':
			case 'gallery':
			case 'repeater':
				if ( $field['default'] === '' ) {
					$field['default'] = [];
				}
				break;

			case 'number':
			case 'range':
				$field = wp_parse_args( $field, [
					'min'  => null,
					'max'  => null,
					'step' => 1,
				] );
				break;

			case 'textarea':
				$field = wp_parse_args( $field, [
					'rows' => 5,
					'cols' => 50,
				] );
				break;

			case 'wysiwyg':
				$field = wp_parse_args( $field, [
					'rows'          => 10,
					'media_buttons' => true,
					'teeny'         => false,
					'quicktags'     => true,
				] );
				break;

			case 'code':
				$field = wp_parse_args( $field, [
					'language' => 'html',
					'rows'     => 10,
				] );
				break;

			case 'select':
			case 'radio':
			case 'button_group':
				// Don't set empty options if optgroups already exists
				if ( empty( $field['options'] ) && empty( $field['optgroups'] ) ) {
					$field['options'] = [];
				}
				break;

			case 'select2':
			case 'select_multiple':
				// Don't set empty options if optgroups already exists
				if ( empty( $field['options'] ) && empty( $field['optgroups'] ) ) {
					$field['options'] = [];
				}
				$field = wp_parse_args( $field, [
					'multiple' => true,
				] );
				if ( $field['default'] === '' ) {
					$field['default'] = [];
				}
				break;

			case 'color':
				$field = wp_parse_args( $field, [
					'alpha' => false,
				] );
				break;

			case 'image':
			case 'file':
				$field = wp_parse_args( $field, [
					'return_format' => 'id',
					'library'       => 'all',
				] );
				break;

			case 'post':
			case 'post_ajax':
				$field = wp_parse_args( $field, [
					'post_type' => 'post',
					'multiple'  => false,
				] );
				break;

			case 'page':
			case 'page_ajax':
				$field = wp_parse_args( $field, [
					'post_type' => 'page',
					'multiple'  => false,
				] );
				break;

			case 'taxonomy':
			case 'taxonomy_ajax':
				$field = wp_parse_args( $field, [
					'taxonomy' => 'category',
					'multiple' => false,
				] );
				break;

			case 'user':
			case 'user_ajax':
				$field = wp_parse_args( $field, [
					'role'     => '',
					'multiple' => false,
				] );
				break;

			case 'dimensions':
				$field = wp_parse_args( $field, [
					'units'        => [ 'px', 'em', 'rem', '%' ],
					'default_unit' => 'px',
				] );
				break;

			case 'link':
				$field = wp_parse_args( $field, [
					'return_format' => 'array',
				] );
				break;
		}

		return $field;
	}

	/**
	 * Get fields for a specific tab.
	 *
	 * @param string $tab Tab key.
	 *
	 * @return array
	 */
	protected function get_fields_for_tab( string $tab ): array {
		if ( empty( $this->tabs ) ) {
			return $this->fields;
		}

		return array_filter( $this->fields, function ( $field ) use ( $tab ) {
			return ( $field['tab'] ?? '' ) === $tab;
		} );
	}

	/**
	 * Get sections for a specific tab.
	 *
	 * @param string $tab Tab key.
	 *
	 * @return array
	 */
	protected function get_sections_for_tab( string $tab ): array {
		return array_filter( $this->sections, function ( $section ) use ( $tab ) {
			return ( $section['tab'] ?? '' ) === $tab;
		} );
	}

}
