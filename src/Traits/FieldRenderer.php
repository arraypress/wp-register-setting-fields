<?php
/**
 * Field Renderer Trait
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Traits;

use ArrayPress\RegisterSettingFields\Traits\Rendering\BasicFields;
use ArrayPress\RegisterSettingFields\Traits\Rendering\ChoiceFields;
use ArrayPress\RegisterSettingFields\Traits\Rendering\MediaFields;
use ArrayPress\RegisterSettingFields\Traits\Rendering\RelationalFields;
use ArrayPress\RegisterSettingFields\Traits\Rendering\ComplexFields;
use ArrayPress\RegisterSettingFields\Traits\Rendering\NestedFields;

/**
 * Trait FieldRenderer
 *
 * Main dispatcher for rendering different field types.
 */
trait FieldRenderer {

	use BasicFields;
	use ChoiceFields;
	use MediaFields;
	use RelationalFields;
	use ComplexFields;
	use NestedFields;

	/**
	 * Render a field based on its type.
	 *
	 * @param string $key   Field key.
	 * @param array  $field Field configuration.
	 * @param string $name  Input name attribute.
	 * @param string $id    Input id attribute.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_field( string $key, array $field, string $name, string $id, $value ): void {
		$type = $field['type'] ?? 'text';

		// Store the field key in the field config for renderers that need it
		$field['_key'] = $key;

		/**
		 * Allow custom field rendering.
		 *
		 * @param bool   $rendered Whether the field was rendered.
		 * @param string $key      Field key.
		 * @param array  $field    Field configuration.
		 * @param string $name     Input name attribute.
		 * @param string $id       Input id attribute.
		 * @param mixed  $value    Current value.
		 */
		$rendered = apply_filters( 'setting_fields_render_field', false, $key, $field, $name, $id, $value );

		if ( $rendered ) {
			return;
		}

		// Check for custom render callback first
		if ( ! empty( $field['render_callback'] ) && is_callable( $field['render_callback'] ) ) {
			call_user_func( $field['render_callback'], $field, $name, $id, $value );

			return;
		}

		// Dispatch to appropriate renderer
		match ( $type ) {
			// Basic text inputs
			'text', 'url', 'email', 'tel', 'password' => $this->render_text_input( $field, $name, $id, $value, $type ),
			'textarea' => $this->render_textarea( $field, $name, $id, $value ),
			'wysiwyg' => $this->render_wysiwyg( $field, $name, $id, $value ),
			'code' => $this->render_code_editor( $field, $name, $id, $value ),

			// Number inputs
			'number' => $this->render_number( $field, $name, $id, $value ),
			'range' => $this->render_range( $field, $name, $id, $value ),

			// Choice fields
			'select' => $this->render_select( $field, $name, $id, $value ),
			'select2', 'select_multiple' => $this->render_select2( $field, $name, $id, $value ),
			'checkbox' => $this->render_checkbox( $field, $name, $id, $value ),
			'toggle' => $this->render_toggle( $field, $name, $id, $value ),
			'checkbox_group' => $this->render_checkbox_group( $field, $name, $id, $value ),
			'radio' => $this->render_radio( $field, $name, $id, $value ),
			'button_group' => $this->render_button_group( $field, $name, $id, $value ),

			// Date/Time
			'color' => $this->render_color( $field, $name, $id, $value ),
			'date' => $this->render_date( $field, $name, $id, $value ),
			'time' => $this->render_time( $field, $name, $id, $value ),
			'datetime' => $this->render_datetime( $field, $name, $id, $value ),

			// Media
			'image' => $this->render_image( $field, $name, $id, $value ),
			'file' => $this->render_file( $field, $name, $id, $value ),
			'gallery' => $this->render_gallery( $field, $name, $id, $value ),

			// Complex
			'clipboard' => $this->render_clipboard( $field, $name, $id, $value ),
			'action_button' => $this->render_action_button( $field, $name, $id, $value ),
			'link' => $this->render_link( $field, $name, $id, $value ),
			'dimensions' => $this->render_dimensions( $field, $name, $id, $value ),
			'oembed' => $this->render_oembed( $field, $name, $id, $value ),
			'email_editor' => $this->render_email_editor( $field, $name, $id, $value ),
			'sortable' => $this->render_sortable( $field, $name, $id, $value ),

			// Relational (all use Select2 with AJAX)
			'post' => $this->render_post_select( $field, $name, $id, $value ),
			'page' => $this->render_page_select( $field, $name, $id, $value ),
			'taxonomy' => $this->render_taxonomy_select( $field, $name, $id, $value ),
			'user' => $this->render_user_select( $field, $name, $id, $value ),

			// Custom AJAX callback
			'ajax' => $this->render_ajax_select( $field, $name, $id, $value ),

			// Nested
			'group' => $this->render_group( $field, $name, $id, $value ),
			'repeater' => $this->render_repeater( $field, $name, $id, $value ),

			// Content/Layout
			'html' => $this->render_html( $field ),
			'message' => $this->render_message( $field ),
			'separator' => $this->render_separator( $field, $name, $id, $value ),
			'heading' => $this->render_heading( $field, $name, $id, $value ),

			// Custom type with callback
			'custom' => $this->render_custom( $field, $name, $id, $value ),

			// Default fallback
			default => $this->render_text_input( $field, $name, $id, $value ),
		};
	}

	/**
	 * Build common input attributes.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param array  $extra Extra attributes.
	 *
	 * @return string
	 */
	protected function build_input_attrs( array $field, string $name, string $id, array $extra = [] ): string {
		$attrs = [
			'name' => $name,
			'id'   => $id,
		];

		// Add common attributes
		if ( ! empty( $field['class'] ) ) {
			$attrs['class'] = $field['class'];
		}
		if ( ! empty( $field['placeholder'] ) ) {
			$attrs['placeholder'] = $field['placeholder'];
		}
		if ( ! empty( $field['required'] ) ) {
			$attrs['required'] = 'required';
		}
		if ( ! empty( $field['readonly'] ) ) {
			$attrs['readonly'] = 'readonly';
		}
		if ( ! empty( $field['disabled'] ) ) {
			$attrs['disabled'] = 'disabled';
		}

		// Add data attributes
		if ( ! empty( $field['data'] ) && is_array( $field['data'] ) ) {
			foreach ( $field['data'] as $data_key => $data_value ) {
				$attrs[ 'data-' . $data_key ] = $data_value;
			}
		}

		// Merge extra attributes
		$attrs = array_merge( $attrs, $extra );

		// Build attribute string
		$attr_string = '';
		foreach ( $attrs as $attr_name => $attr_value ) {
			if ( $attr_value === true ) {
				$attr_string .= ' ' . esc_attr( $attr_name );
			} elseif ( $attr_value !== false && $attr_value !== null ) {
				$attr_string .= sprintf( ' %s="%s"', esc_attr( $attr_name ), esc_attr( $attr_value ) );
			}
		}

		return $attr_string;
	}

	/**
	 * Render HTML content field.
	 *
	 * @param array $field Field configuration.
	 *
	 * @return void
	 */
	protected function render_html( array $field ): void {
		if ( ! empty( $field['content'] ) ) {
			echo wp_kses_post( $field['content'] );
		}
	}

	/**
	 * Render a message/notice field.
	 *
	 * @param array $field Field configuration.
	 *
	 * @return void
	 */
	protected function render_message( array $field ): void {
		$type    = $field['message_type'] ?? 'info';
		$message = $field['content'] ?? $field['message'] ?? '';

		if ( empty( $message ) ) {
			return;
		}

		$class = 'notice notice-' . sanitize_html_class( $type );
		if ( ! empty( $field['inline'] ) ) {
			$class .= ' inline';
		}

		printf(
			'<div class="%s"><p>%s</p></div>',
			esc_attr( $class ),
			wp_kses_post( $message )
		);
	}

}