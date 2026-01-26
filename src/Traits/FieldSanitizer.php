<?php
/**
 * Field Sanitizer Trait
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Traits;

/**
 * Trait FieldSanitizer
 *
 * Handles sanitization of field values.
 */
trait FieldSanitizer {

	/**
	 * Sanitize a field value based on its type.
	 *
	 * @param string $key   Field key.
	 * @param array  $field Field configuration.
	 * @param mixed  $value The value to sanitize.
	 *
	 * @return mixed
	 */
	protected function sanitize_field_value( string $key, array $field, $value ) {
		$type = $field['type'] ?? 'text';

		/**
		 * Filter the value before sanitization.
		 *
		 * @param mixed  $value The value to sanitize.
		 * @param array  $field The field configuration.
		 * @param string $key   The field key.
		 */
		$value = apply_filters( 'setting_fields_pre_sanitize_value', $value, $field, $key );

		// Check for custom sanitize callback first
		if ( ! empty( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
			$sanitized = call_user_func( $field['sanitize_callback'], $value, $field, $key );
		} else {
			$sanitized = match ( $type ) {
				'text', 'password' => sanitize_text_field( (string) $value ),
				'textarea' => sanitize_textarea_field( (string) $value ),
				'email' => sanitize_email( (string) $value ),
				'url' => esc_url_raw( (string) $value ),
				'tel' => $this->sanitize_tel( $value ),
				'number', 'range' => $this->sanitize_number( $value, $field ),
				'checkbox', 'toggle' => $this->sanitize_boolean( $value ),
				'select', 'radio', 'button_group' => $this->sanitize_choice( $value, $field ),
				'select2', 'select_multiple', 'checkbox_group' => $this->sanitize_multiple_choice( $value, $field ),
				'wysiwyg' => wp_kses_post( (string) $value ),
				'code' => $value, // Allow raw code
				'color' => sanitize_hex_color( (string) $value ) ?: '',
				'date' => $this->sanitize_date( $value ),
				'time' => $this->sanitize_time( $value ),
				'datetime' => $this->sanitize_datetime( $value ),
				'image', 'file' => $this->sanitize_attachment( $value ),
				'gallery' => $this->sanitize_gallery( $value ),
				'link' => $this->sanitize_link( $value ),
				'post', 'post_ajax' => $this->sanitize_post_id( $value, $field ),
				'taxonomy', 'taxonomy_ajax' => $this->sanitize_term_id( $value, $field ),
				'user', 'user_ajax' => $this->sanitize_user_id( $value, $field ),
				'dimensions' => $this->sanitize_dimensions( $value ),
				'repeater' => $this->sanitize_repeater( $value, $field ),
				'group' => $this->sanitize_group( $value, $field ),
				'sortable' => $this->sanitize_sortable( $value ),
				'email_editor' => $this->sanitize_email_editor( $value ),
				default => sanitize_text_field( (string) $value ),
			};
		}

		/**
		 * Filter the sanitized value.
		 *
		 * @param mixed  $sanitized The sanitized value.
		 * @param mixed  $value     The original value.
		 * @param array  $field     The field configuration.
		 * @param string $key       The field key.
		 */
		return apply_filters( 'setting_fields_sanitize_value', $sanitized, $value, $field, $key );
	}

	/**
	 * Sanitize telephone number.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string
	 */
	protected function sanitize_tel( $value ): string {
		return preg_replace( '/[^\d+\-\(\)\s]/', '', (string) $value );
	}

	/**
	 * Sanitize number value.
	 *
	 * @param mixed $value The value.
	 * @param array $field Field configuration.
	 *
	 * @return float|int
	 */
	protected function sanitize_number( $value, array $field ) {
		$value = is_numeric( $value ) ? $value : 0;
		$step  = $field['step'] ?? 1;

		// Determine if we should return int or float
		if ( is_float( $step ) || strpos( (string) $step, '.' ) !== false ) {
			$value = (float) $value;
		} else {
			$value = (int) $value;
		}

		// Apply min/max constraints
		if ( isset( $field['min'] ) && $value < $field['min'] ) {
			$value = $field['min'];
		}
		if ( isset( $field['max'] ) && $value > $field['max'] ) {
			$value = $field['max'];
		}

		return $value;
	}

	/**
	 * Sanitize boolean value.
	 *
	 * @param mixed $value The value.
	 *
	 * @return bool
	 */
	protected function sanitize_boolean( $value ): bool {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Sanitize single choice value.
	 *
	 * @param mixed $value The value.
	 * @param array $field Field configuration.
	 *
	 * @return string
	 */
	protected function sanitize_choice( $value, array $field ): string {
		$value   = sanitize_text_field( (string) $value );
		$options = $field['options'] ?? [];

		// Validate against available options
		if ( ! empty( $options ) && ! array_key_exists( $value, $options ) ) {
			return $field['default'] ?? '';
		}

		return $value;
	}

	/**
	 * Sanitize multiple choice value.
	 *
	 * @param mixed $value The value.
	 * @param array $field Field configuration.
	 *
	 * @return array
	 */
	protected function sanitize_multiple_choice( $value, array $field ): array {
		if ( ! is_array( $value ) ) {
			$value = [];
		}

		$options   = $field['options'] ?? [];
		$sanitized = [];

		foreach ( $value as $v ) {
			$v = sanitize_text_field( (string) $v );
			// Validate against options if they exist
			if ( empty( $options ) || array_key_exists( $v, $options ) ) {
				$sanitized[] = $v;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize date value.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string
	 */
	protected function sanitize_date( $value ): string {
		$value = sanitize_text_field( (string) $value );

		// Validate date format
		$date = \DateTime::createFromFormat( 'Y-m-d', $value );
		if ( $date && $date->format( 'Y-m-d' ) === $value ) {
			return $value;
		}

		return '';
	}

	/**
	 * Sanitize time value.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string
	 */
	protected function sanitize_time( $value ): string {
		$value = sanitize_text_field( (string) $value );

		// Validate time format (H:i or H:i:s)
		if ( preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value ) ) {
			return $value;
		}

		return '';
	}

	/**
	 * Sanitize datetime value.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string
	 */
	protected function sanitize_datetime( $value ): string {
		$value = sanitize_text_field( (string) $value );

		// Validate datetime format
		$date = \DateTime::createFromFormat( 'Y-m-d\TH:i', $value );
		if ( $date ) {
			return $value;
		}

		return '';
	}

	/**
	 * Sanitize attachment ID.
	 *
	 * @param mixed $value The value.
	 *
	 * @return int
	 */
	protected function sanitize_attachment( $value ): int {
		$id = absint( $value );

		if ( $id > 0 && get_post_type( $id ) === 'attachment' ) {
			return $id;
		}

		return 0;
	}

	/**
	 * Sanitize gallery (array of attachment IDs).
	 *
	 * @param mixed $value The value.
	 *
	 * @return array
	 */
	protected function sanitize_gallery( $value ): array {
		if ( ! is_array( $value ) ) {
			$value = [];
		}

		return array_filter( array_map( [ $this, 'sanitize_attachment' ], $value ) );
	}

	/**
	 * Sanitize link field.
	 *
	 * @param mixed $value The value.
	 *
	 * @return array
	 */
	protected function sanitize_link( $value ): array {
		if ( ! is_array( $value ) ) {
			return [
				'url'    => '',
				'text'   => '',
				'target' => '',
			];
		}

		return [
			'url'    => esc_url_raw( $value['url'] ?? '' ),
			'text'   => sanitize_text_field( $value['text'] ?? '' ),
			'target' => in_array( $value['target'] ?? '', [ '_blank', '_self' ], true ) ? $value['target'] : '_self',
		];
	}

	/**
	 * Sanitize post ID.
	 *
	 * @param mixed $value The value.
	 * @param array $field Field configuration.
	 *
	 * @return int|array
	 */
	protected function sanitize_post_id( $value, array $field ) {
		$post_type = $field['post_type'] ?? 'post';
		$multiple  = $field['multiple'] ?? false;

		if ( $multiple ) {
			if ( ! is_array( $value ) ) {
				return [];
			}

			return array_filter( array_map( function ( $id ) use ( $post_type ) {
				$id = absint( $id );
				if ( $id > 0 && get_post_type( $id ) === $post_type ) {
					return $id;
				}
				return 0;
			}, $value ) );
		}

		$id = absint( $value );
		if ( $id > 0 && get_post_type( $id ) === $post_type ) {
			return $id;
		}

		return 0;
	}

	/**
	 * Sanitize term ID.
	 *
	 * @param mixed $value The value.
	 * @param array $field Field configuration.
	 *
	 * @return int|array
	 */
	protected function sanitize_term_id( $value, array $field ) {
		$taxonomy = $field['taxonomy'] ?? 'category';
		$multiple = $field['multiple'] ?? false;

		if ( $multiple ) {
			if ( ! is_array( $value ) ) {
				return [];
			}

			return array_filter( array_map( function ( $id ) use ( $taxonomy ) {
				$id = absint( $id );
				if ( $id > 0 && term_exists( $id, $taxonomy ) ) {
					return $id;
				}
				return 0;
			}, $value ) );
		}

		$id = absint( $value );
		if ( $id > 0 && term_exists( $id, $taxonomy ) ) {
			return $id;
		}

		return 0;
	}

	/**
	 * Sanitize user ID.
	 *
	 * @param mixed $value The value.
	 * @param array $field Field configuration.
	 *
	 * @return int|array
	 */
	protected function sanitize_user_id( $value, array $field ) {
		$multiple = $field['multiple'] ?? false;

		if ( $multiple ) {
			if ( ! is_array( $value ) ) {
				return [];
			}

			return array_filter( array_map( function ( $id ) {
				$id = absint( $id );
				if ( $id > 0 && get_user_by( 'id', $id ) ) {
					return $id;
				}
				return 0;
			}, $value ) );
		}

		$id = absint( $value );
		if ( $id > 0 && get_user_by( 'id', $id ) ) {
			return $id;
		}

		return 0;
	}

	/**
	 * Sanitize dimensions field.
	 *
	 * @param mixed $value The value.
	 *
	 * @return array
	 */
	protected function sanitize_dimensions( $value ): array {
		if ( ! is_array( $value ) ) {
			return [
				'top'    => '',
				'right'  => '',
				'bottom' => '',
				'left'   => '',
				'unit'   => 'px',
			];
		}

		return [
			'top'    => is_numeric( $value['top'] ?? '' ) ? (float) $value['top'] : '',
			'right'  => is_numeric( $value['right'] ?? '' ) ? (float) $value['right'] : '',
			'bottom' => is_numeric( $value['bottom'] ?? '' ) ? (float) $value['bottom'] : '',
			'left'   => is_numeric( $value['left'] ?? '' ) ? (float) $value['left'] : '',
			'unit'   => sanitize_text_field( $value['unit'] ?? 'px' ),
		];
	}

	/**
	 * Sanitize repeater field.
	 *
	 * @param mixed $value The value.
	 * @param array $field Field configuration.
	 *
	 * @return array
	 */
	protected function sanitize_repeater( $value, array $field ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$sub_fields = $field['sub_fields'] ?? [];
		$sanitized  = [];

		foreach ( $value as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$sanitized_row = [];
			foreach ( $sub_fields as $sub_key => $sub_field ) {
				$sub_value                 = $row[ $sub_key ] ?? null;
				$sanitized_row[ $sub_key ] = $this->sanitize_field_value( $sub_key, $sub_field, $sub_value );
			}
			$sanitized[] = $sanitized_row;
		}

		return $sanitized;
	}

	/**
	 * Sanitize group field.
	 *
	 * @param mixed $value The value.
	 * @param array $field Field configuration.
	 *
	 * @return array
	 */
	protected function sanitize_group( $value, array $field ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$sub_fields = $field['sub_fields'] ?? [];
		$sanitized  = [];

		foreach ( $sub_fields as $sub_key => $sub_field ) {
			$sub_value               = $value[ $sub_key ] ?? null;
			$sanitized[ $sub_key ] = $this->sanitize_field_value( $sub_key, $sub_field, $sub_value );
		}

		return $sanitized;
	}

	/**
	 * Sanitize sortable field.
	 *
	 * @param mixed $value The value.
	 *
	 * @return array
	 */
	protected function sanitize_sortable( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values( array_map( 'sanitize_text_field', $value ) );
	}

	/**
	 * Sanitize email editor field.
	 *
	 * @param mixed $value The value.
	 *
	 * @return array
	 */
	protected function sanitize_email_editor( $value ): array {
		if ( ! is_array( $value ) ) {
			return [
				'enabled' => true,
				'subject' => '',
				'body'    => '',
			];
		}

		return [
			'enabled' => ! empty( $value['enabled'] ),
			'subject' => sanitize_text_field( $value['subject'] ?? '' ),
			'body'    => wp_kses_post( $value['body'] ?? '' ),
		];
	}

}
