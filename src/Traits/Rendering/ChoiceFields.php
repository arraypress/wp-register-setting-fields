<?php
/**
 * Choice Fields Rendering Trait
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Traits\Rendering;

/**
 * Trait ChoiceFields
 *
 * Renders selection/choice field types.
 */
trait ChoiceFields {

	/**
	 * Render a select dropdown field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_select( array $field, string $name, string $id, $value ): void {
		$options = $field['options'] ?? [];

		$extra = [
			'class' => 'setting-fields-select ' . ( $field['class'] ?? '' ),
		];

		if ( ! empty( $field['multiple'] ) ) {
			$extra['multiple'] = 'multiple';
			$name              .= '[]';
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		printf( '<select%s>', $attrs );

		// Placeholder option
		if ( ! empty( $field['placeholder'] ) ) {
			printf(
				'<option value="">%s</option>',
				esc_html( $field['placeholder'] )
			);
		}

		$this->render_select_options( $options, $value );

		echo '</select>';
	}

	/**
	 * Render select options, supporting optgroups.
	 *
	 * @param array $options Options array.
	 * @param mixed $value   Current value(s).
	 *
	 * @return void
	 */
	protected function render_select_options( array $options, $value ): void {
		$selected_values = is_array( $value ) ? $value : [ $value ];

		foreach ( $options as $option_value => $option_label ) {
			// Check for optgroup
			if ( is_array( $option_label ) && isset( $option_label['options'] ) ) {
				printf( '<optgroup label="%s">', esc_attr( $option_label['label'] ?? $option_value ) );
				$this->render_select_options( $option_label['options'], $value );
				echo '</optgroup>';
			} elseif ( is_array( $option_label ) ) {
				// Optgroup shorthand: 'Group' => ['val1' => 'Label1', ...]
				printf( '<optgroup label="%s">', esc_attr( $option_value ) );
				$this->render_select_options( $option_label, $value );
				echo '</optgroup>';
			} else {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $option_value ),
					in_array( (string) $option_value, array_map( 'strval', $selected_values ), true ) ? ' selected' : '',
					esc_html( $option_label )
				);
			}
		}
	}

	/**
	 * Render a Select2 enhanced dropdown.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_select2( array $field, string $name, string $id, $value ): void {
		$options  = $field['options'] ?? [];
		$multiple = $field['multiple'] ?? true;

		if ( ! is_array( $value ) ) {
			$value = $multiple && ! empty( $value ) ? [ $value ] : $value;
		}

		$extra = [
			'class'            => 'setting-fields-select2 ' . ( $field['class'] ?? '' ),
			'data-select2'     => 'true',
			'data-allow-clear' => $field['allow_clear'] ?? true ? 'true' : 'false',
			'style'            => 'width: 100%; max-width: 400px;',
		];

		if ( $multiple ) {
			$extra['multiple'] = 'multiple';
			$name              .= '[]';
		}

		if ( ! empty( $field['placeholder'] ) ) {
			$extra['data-placeholder'] = $field['placeholder'];
		}

		if ( isset( $field['max_selections'] ) ) {
			$extra['data-maximum-selection-length'] = $field['max_selections'];
		}

		if ( ! empty( $field['tags'] ) ) {
			$extra['data-tags'] = 'true';
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		printf( '<select%s>', $attrs );

		if ( ! empty( $field['placeholder'] ) && ! $multiple ) {
			echo '<option value=""></option>';
		}

		$this->render_select_options( $options, $value );

		echo '</select>';
	}

	/**
	 * Render a single checkbox field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_checkbox( array $field, string $name, string $id, $value ): void {
		$checked = filter_var( $value, FILTER_VALIDATE_BOOLEAN );

		$extra = [
			'type'  => 'checkbox',
			'value' => '1',
			'class' => 'setting-fields-checkbox ' . ( $field['class'] ?? '' ),
		];

		if ( $checked ) {
			$extra['checked'] = 'checked';
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		echo '<label class="setting-fields-checkbox-label">';
		printf( '<input%s />', $attrs );

		if ( ! empty( $field['checkbox_label'] ) ) {
			echo ' <span>' . esc_html( $field['checkbox_label'] ) . '</span>';
		}
		echo '</label>';
	}

	/**
	 * Render a toggle switch field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_toggle( array $field, string $name, string $id, $value ): void {
		$checked = filter_var( $value, FILTER_VALIDATE_BOOLEAN );

		$extra = [
			'type'  => 'checkbox',
			'value' => '1',
			'class' => 'setting-fields-toggle-input',
		];

		if ( $checked ) {
			$extra['checked'] = 'checked';
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		echo '<label class="setting-fields-toggle">';
		printf( '<input%s />', $attrs );
		echo '<span class="setting-fields-toggle-slider"></span>';
		echo '</label>';

		if ( ! empty( $field['checkbox_label'] ) ) {
			echo ' <span class="setting-fields-toggle-label">' . esc_html( $field['checkbox_label'] ) . '</span>';
		}
	}

	/**
	 * Render a checkbox group field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_checkbox_group( array $field, string $name, string $id, $value ): void {
		$options = $field['options'] ?? [];
		$values  = is_array( $value ) ? $value : [];
		$layout  = $field['layout'] ?? 'vertical';

		$class = 'setting-fields-checkbox-group';
		if ( $layout === 'horizontal' ) {
			$class .= ' setting-fields-checkbox-group--horizontal';
		}

		echo '<fieldset class="' . esc_attr( $class ) . '">';

		foreach ( $options as $option_value => $option_label ) {
			$option_id = $id . '_' . sanitize_key( $option_value );
			$checked   = in_array( (string) $option_value, array_map( 'strval', $values ), true );

			echo '<label class="setting-fields-checkbox-group-item">';
			printf(
				'<input type="checkbox" name="%s[]" id="%s" value="%s"%s />',
				esc_attr( $name ),
				esc_attr( $option_id ),
				esc_attr( $option_value ),
				$checked ? ' checked' : ''
			);
			echo ' <span>' . esc_html( $option_label ) . '</span>';
			echo '</label>';
		}

		echo '</fieldset>';
	}

	/**
	 * Render a radio button group field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_radio( array $field, string $name, string $id, $value ): void {
		$options = $field['options'] ?? [];
		$layout  = $field['layout'] ?? 'vertical';

		$class = 'setting-fields-radio-group';
		if ( $layout === 'horizontal' ) {
			$class .= ' setting-fields-radio-group--horizontal';
		}

		echo '<fieldset class="' . esc_attr( $class ) . '">';

		foreach ( $options as $option_value => $option_label ) {
			$option_id = $id . '_' . sanitize_key( $option_value );
			$checked   = (string) $option_value === (string) $value;

			echo '<label class="setting-fields-radio-item">';
			printf(
				'<input type="radio" name="%s" id="%s" value="%s"%s />',
				esc_attr( $name ),
				esc_attr( $option_id ),
				esc_attr( $option_value ),
				$checked ? ' checked' : ''
			);
			echo ' <span>' . esc_html( $option_label ) . '</span>';
			echo '</label>';
		}

		echo '</fieldset>';
	}

	/**
	 * Render a button group field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_button_group( array $field, string $name, string $id, $value ): void {
		$options = $field['options'] ?? [];

		echo '<div class="setting-fields-button-group">';

		foreach ( $options as $option_value => $option_label ) {
			$option_id = $id . '_' . sanitize_key( $option_value );
			$checked   = (string) $option_value === (string) $value;
			$class     = 'button setting-fields-button-group-item';

			if ( $checked ) {
				$class .= ' button-primary';
			}

			printf(
				'<input type="radio" name="%s" id="%s" value="%s" class="screen-reader-text"%s />',
				esc_attr( $name ),
				esc_attr( $option_id ),
				esc_attr( $option_value ),
				$checked ? ' checked' : ''
			);

			printf(
				'<label for="%s" class="%s">%s</label>',
				esc_attr( $option_id ),
				esc_attr( $class ),
				esc_html( $option_label )
			);
		}

		echo '</div>';
	}

}
