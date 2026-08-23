<?php
/**
 * Basic Fields Rendering Trait
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Traits\Rendering;

/**
 * Trait BasicFields
 *
 * Renders basic text-based field types.
 */
trait BasicFields {

	/**
	 * Render a text-type input field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 * @param string $type  Input type (text, url, email, tel, password).
	 *
	 * @return void
	 */
	protected function render_text_input( array $field, string $name, string $id, $value, string $type = 'text' ): void {
		$extra = [
			'type'  => $type,
			'value' => $value,
			'class' => 'regular-text ' . ( $field['class'] ?? '' ),
		];

		// Size variants
		if ( isset( $field['size'] ) ) {
			$extra['class'] = match ( $field['size'] ) {
				'small' => 'small-text ' . ( $field['class'] ?? '' ),
				'large' => 'large-text ' . ( $field['class'] ?? '' ),
				default => 'regular-text ' . ( $field['class'] ?? '' ),
			};
		}

		// Maxlength
		if ( isset( $field['maxlength'] ) ) {
			$extra['maxlength'] = (int) $field['maxlength'];
		}

		// Minlength
		if ( isset( $field['minlength'] ) ) {
			$extra['minlength'] = (int) $field['minlength'];
		}

		// Pattern
		if ( ! empty( $field['pattern'] ) ) {
			$extra['pattern'] = $field['pattern'];
		}

		// Autocomplete
		if ( isset( $field['autocomplete'] ) ) {
			$extra['autocomplete'] = $field['autocomplete'];
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		// Built by build_input_attrs(), which esc_attr()s every attribute name and value before returning.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '<input%s />', $attrs );
	}

	/**
	 * Render a hidden input field.
	 *
	 * Outputs only a hidden <input> element with no label, description, or wrapper.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_hidden_input( array $field, string $name, string $id, $value ): void {
		printf(
			'<input type="hidden" name="%s" id="%s" value="%s" />',
			esc_attr( $name ),
			esc_attr( $id ),
			esc_attr( $value )
		);
	}

	/**
	 * Render a textarea field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_textarea( array $field, string $name, string $id, $value ): void {
		$rows = $field['rows'] ?? 5;
		$cols = $field['cols'] ?? 50;

		$extra = [
			'rows'  => $rows,
			'cols'  => $cols,
			'class' => 'large-text ' . ( $field['class'] ?? '' ),
		];

		// Maxlength
		if ( isset( $field['maxlength'] ) ) {
			$extra['maxlength'] = (int) $field['maxlength'];
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		printf(
			'<textarea%s>%s</textarea>',
			// Built by build_input_attrs(), which esc_attr()s every attribute name and value before returning.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$attrs,
			esc_textarea( $value )
		);
	}

	/**
	 * Render a WYSIWYG editor field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_wysiwyg( array $field, string $name, string $id, $value ): void {
		$settings = [
			'textarea_name'    => $name,
			'textarea_rows'    => $field['rows'] ?? 10,
			'media_buttons'    => $field['media_buttons'] ?? true,
			'teeny'            => $field['teeny'] ?? false,
			'quicktags'        => $field['quicktags'] ?? true,
			'wpautop'          => $field['wpautop'] ?? true,
			'default_editor'   => $field['default_editor'] ?? '',
			'drag_drop_upload' => $field['drag_drop_upload'] ?? false,
		];

		// Custom TinyMCE settings
		if ( isset( $field['tinymce'] ) ) {
			$settings['tinymce'] = $field['tinymce'];
		}

		echo '<div class="setting-fields-wysiwyg">';
		wp_editor( $value, $id, $settings );
		echo '</div>';
	}

	/**
	 * Render a code editor field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_code_editor( array $field, string $name, string $id, $value ): void {
		$language = $field['language'] ?? 'html';
		$rows     = $field['rows'] ?? 10;

		// Map language to MIME type
		$mime_types = [
			'html'       => 'text/html',
			'css'        => 'text/css',
			'javascript' => 'text/javascript',
			'js'         => 'text/javascript',
			'json'       => 'application/json',
			'php'        => 'application/x-httpd-php',
			'xml'        => 'application/xml',
			'sql'        => 'text/x-sql',
			'markdown'   => 'text/x-markdown',
			'md'         => 'text/x-markdown',
		];

		$mime_type = $mime_types[ $language ] ?? 'text/plain';

		$extra = [
			'rows'          => $rows,
			'class'         => 'large-text code setting-fields-code-editor',
			'data-language' => $language,
			'data-mime'     => $mime_type,
		];

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		printf(
			'<textarea%s>%s</textarea>',
			// Built by build_input_attrs(), which esc_attr()s every attribute name and value before returning.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$attrs,
			esc_textarea( $value )
		);
	}

	/**
	 * Render a number input field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_number( array $field, string $name, string $id, $value ): void {
		$extra = [
			'type'  => 'number',
			'value' => $value,
			'class' => 'small-text ' . ( $field['class'] ?? '' ),
		];

		if ( isset( $field['min'] ) ) {
			$extra['min'] = $field['min'];
		}
		if ( isset( $field['max'] ) ) {
			$extra['max'] = $field['max'];
		}
		if ( isset( $field['step'] ) ) {
			$extra['step'] = $field['step'];
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		// Built by build_input_attrs(), which esc_attr()s every attribute name and value before returning.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '<input%s />', $attrs );

		// Suffix
		if ( ! empty( $field['suffix'] ) ) {
			echo ' <span class="setting-fields-suffix">' . esc_html( $field['suffix'] ) . '</span>';
		}
	}

	/**
	 * Render a range slider field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_range( array $field, string $name, string $id, $value ): void {
		$min  = $field['min'] ?? 0;
		$max  = $field['max'] ?? 100;
		$step = $field['step'] ?? 1;

		$extra = [
			'type'  => 'range',
			'value' => $value,
			'min'   => $min,
			'max'   => $max,
			'step'  => $step,
			'class' => 'setting-fields-range ' . ( $field['class'] ?? '' ),
		];

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		echo '<div class="setting-fields-range-wrapper">';
		// Built by build_input_attrs(), which esc_attr()s every attribute name and value before returning.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '<input%s />', $attrs );

		// Value display
		if ( $field['show_value'] ?? true ) {
			printf(
				'<span class="setting-fields-range-value" data-target="%s">%s</span>',
				esc_attr( $id ),
				esc_html( $value )
			);
		}

		// Suffix
		if ( ! empty( $field['suffix'] ) ) {
			echo ' <span class="setting-fields-suffix">' . esc_html( $field['suffix'] ) . '</span>';
		}

		echo '</div>';
	}

	/**
	 * Render a color picker field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_color( array $field, string $name, string $id, $value ): void {
		$extra = [
			'type'  => 'text',
			'value' => $value,
			'class' => 'setting-fields-color-picker ' . ( $field['class'] ?? '' ),
		];

		// Alpha support
		if ( ! empty( $field['alpha'] ) ) {
			$extra['data-alpha-enabled']        = 'true';
			$extra['data-default-color'] = $field['default'] ?? '';
		}

		// Palette
		if ( ! empty( $field['palettes'] ) && is_array( $field['palettes'] ) ) {
			$extra['data-palettes'] = wp_json_encode( $field['palettes'] );
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		// Built by build_input_attrs(), which esc_attr()s every attribute name and value before returning.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '<input%s />', $attrs );
	}

	/**
	 * Render a date input field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_date( array $field, string $name, string $id, $value ): void {
		$extra = [
			'type'  => 'date',
			'value' => $value,
			'class' => 'setting-fields-date ' . ( $field['class'] ?? '' ),
		];

		if ( isset( $field['min'] ) ) {
			$extra['min'] = $field['min'];
		}
		if ( isset( $field['max'] ) ) {
			$extra['max'] = $field['max'];
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		// Built by build_input_attrs(), which esc_attr()s every attribute name and value before returning.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '<input%s />', $attrs );
	}

	/**
	 * Render a time input field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_time( array $field, string $name, string $id, $value ): void {
		$extra = [
			'type'  => 'time',
			'value' => $value,
			'class' => 'setting-fields-time ' . ( $field['class'] ?? '' ),
		];

		if ( isset( $field['min'] ) ) {
			$extra['min'] = $field['min'];
		}
		if ( isset( $field['max'] ) ) {
			$extra['max'] = $field['max'];
		}
		if ( isset( $field['step'] ) ) {
			$extra['step'] = $field['step'];
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		// Built by build_input_attrs(), which esc_attr()s every attribute name and value before returning.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '<input%s />', $attrs );
	}

	/**
	 * Render a datetime input field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_datetime( array $field, string $name, string $id, $value ): void {
		$extra = [
			'type'  => 'datetime-local',
			'value' => $value,
			'class' => 'setting-fields-datetime ' . ( $field['class'] ?? '' ),
		];

		if ( isset( $field['min'] ) ) {
			$extra['min'] = $field['min'];
		}
		if ( isset( $field['max'] ) ) {
			$extra['max'] = $field['max'];
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		// Built by build_input_attrs(), which esc_attr()s every attribute name and value before returning.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '<input%s />', $attrs );
	}
}
