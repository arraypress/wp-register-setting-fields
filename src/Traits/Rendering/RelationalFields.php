<?php
/**
 * Relational Fields Rendering Trait
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Traits\Rendering;

/**
 * Trait RelationalFields
 *
 * Renders post, taxonomy, and user selection field types.
 * All relational fields use Select2 with AJAX for optimal UX.
 */
trait RelationalFields {

	/**
	 * Render a post select field with Select2 and AJAX.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_post_select( array $field, string $name, string $id, $value ): void {
		$post_type = $field['post_type'] ?? 'post';
		$multiple  = $field['multiple'] ?? false;
		$field_key = $field['_key'] ?? '';

		// Normalize value to array
		if ( ! is_array( $value ) ) {
			$value = $value ? [ $value ] : [];
		}
		$value = array_filter( array_map( 'absint', $value ) );

		// Handle array of post types
		$post_types = is_array( $post_type ) ? $post_type : [ $post_type ];

		// Get selected posts for initial display
		$options = [];
		if ( ! empty( $value ) ) {
			$posts = get_posts( [
				'post_type'      => $post_types,
				'post__in'       => $value,
				'posts_per_page' => - 1,
				'orderby'        => 'post__in',
			] );
			foreach ( $posts as $post ) {
				$options[ $post->ID ] = $post->post_title;
			}
		}

		$extra = [
			'class'            => 'setting-fields-select2 setting-fields-ajax-select ' . ( $field['class'] ?? '' ),
			'data-ajax'        => 'true',
			'data-field-key'   => $field_key,
			'data-field-type'  => 'post',
			'data-post-type'   => is_array( $post_type ) ? implode( ',', $post_type ) : $post_type,
			'data-allow-clear' => 'true',
			'style'            => 'width: 100%; max-width: 400px;',
		];

		if ( $multiple ) {
			$extra['multiple'] = 'multiple';
			$name              .= '[]';
		}

		if ( ! empty( $field['placeholder'] ) ) {
			$extra['data-placeholder'] = $field['placeholder'];
		} else {
			$post_type_obj             = get_post_type_object( $post_types[0] );
			/* translators: %s: singular name of the object type being searched */
			$extra['data-placeholder'] = sprintf( __( 'Search %s...', 'setting-fields' ), $post_type_obj->labels->name ?? $post_types[0] );
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		// Built by build_input_attrs(), which esc_attr()s every attribute name and value before returning.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '<select%s>', $attrs );

		if ( ! $multiple ) {
			echo '<option value=""></option>';
		}

		foreach ( $options as $post_id => $post_title ) {
			printf(
				'<option value="%s" selected>%s</option>',
				esc_attr( $post_id ),
				esc_html( $post_title )
			);
		}

		echo '</select>';
	}

	/**
	 * Render a page select field with Select2 and AJAX.
	 *
	 * Convenience wrapper for post_select with post_type defaulted to 'page'.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_page_select( array $field, string $name, string $id, $value ): void {
		// Ensure post_type is 'page' (can be overridden if needed)
		$field['post_type'] = $field['post_type'] ?? 'page';

		// Set a sensible default placeholder
		if ( empty( $field['placeholder'] ) ) {
			$field['placeholder'] = __( 'Search pages...', 'setting-fields' );
		}

		$this->render_post_select( $field, $name, $id, $value );
	}

	/**
	 * Render a taxonomy select field with Select2 and AJAX.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_taxonomy_select( array $field, string $name, string $id, $value ): void {
		$taxonomy  = $field['taxonomy'] ?? 'category';
		$multiple  = $field['multiple'] ?? false;
		$field_key = $field['_key'] ?? '';

		// Normalize value to array
		if ( ! is_array( $value ) ) {
			$value = $value ? [ $value ] : [];
		}
		$value = array_filter( array_map( 'absint', $value ) );

		// Get selected terms for initial display
		$options = [];
		if ( ! empty( $value ) ) {
			$terms = get_terms( [
				'taxonomy'   => $taxonomy,
				'include'    => $value,
				'hide_empty' => false,
			] );
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$options[ $term->term_id ] = $term->name;
				}
			}
		}

		$extra = [
			'class'            => 'setting-fields-select2 setting-fields-ajax-select ' . ( $field['class'] ?? '' ),
			'data-ajax'        => 'true',
			'data-field-key'   => $field_key,
			'data-field-type'  => 'taxonomy',
			'data-taxonomy'    => $taxonomy,
			'data-allow-clear' => 'true',
			'style'            => 'width: 100%; max-width: 400px;',
		];

		if ( $multiple ) {
			$extra['multiple'] = 'multiple';
			$name              .= '[]';
		}

		if ( ! empty( $field['placeholder'] ) ) {
			$extra['data-placeholder'] = $field['placeholder'];
		} else {
			$taxonomy_obj              = get_taxonomy( $taxonomy );
			/* translators: %s: singular name of the object type being searched */
			$extra['data-placeholder'] = sprintf( __( 'Search %s...', 'setting-fields' ), $taxonomy_obj->labels->name ?? $taxonomy );
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		// Built by build_input_attrs(), which esc_attr()s every attribute name and value before returning.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '<select%s>', $attrs );

		if ( ! $multiple ) {
			echo '<option value=""></option>';
		}

		foreach ( $options as $term_id => $term_name ) {
			printf(
				'<option value="%s" selected>%s</option>',
				esc_attr( $term_id ),
				esc_html( $term_name )
			);
		}

		echo '</select>';
	}

	/**
	 * Render a user select field with Select2 and AJAX.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_user_select( array $field, string $name, string $id, $value ): void {
		$multiple  = $field['multiple'] ?? false;
		$role      = $field['role'] ?? '';
		$field_key = $field['_key'] ?? '';

		// Normalize value to array
		if ( ! is_array( $value ) ) {
			$value = $value ? [ $value ] : [];
		}
		$value = array_filter( array_map( 'absint', $value ) );

		// Get selected users for initial display
		$options = [];
		if ( ! empty( $value ) ) {
			$users = get_users( [
				'include' => $value,
			] );
			foreach ( $users as $user ) {
				$label = $user->display_name;
				if ( $field['show_email'] ?? false ) {
					$label .= ' (' . $user->user_email . ')';
				}
				$options[ $user->ID ] = $label;
			}
		}

		$extra = [
			'class'            => 'setting-fields-select2 setting-fields-ajax-select ' . ( $field['class'] ?? '' ),
			'data-ajax'        => 'true',
			'data-field-key'   => $field_key,
			'data-field-type'  => 'user',
			'data-role'        => is_array( $role ) ? implode( ',', $role ) : $role,
			'data-allow-clear' => 'true',
			'style'            => 'width: 100%; max-width: 400px;',
		];

		if ( $multiple ) {
			$extra['multiple'] = 'multiple';
			$name              .= '[]';
		}

		if ( ! empty( $field['placeholder'] ) ) {
			$extra['data-placeholder'] = $field['placeholder'];
		} else {
			$extra['data-placeholder'] = __( 'Search users...', 'setting-fields' );
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		// Built by build_input_attrs(), which esc_attr()s every attribute name and value before returning.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '<select%s>', $attrs );

		if ( ! $multiple ) {
			echo '<option value=""></option>';
		}

		foreach ( $options as $user_id => $user_label ) {
			printf(
				'<option value="%s" selected>%s</option>',
				esc_attr( $user_id ),
				esc_html( $user_label )
			);
		}

		echo '</select>';
	}

	/**
	 * Render a custom AJAX select field.
	 *
	 * Used for custom data sources via ajax_callback.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_ajax_select( array $field, string $name, string $id, $value ): void {
		$multiple  = $field['multiple'] ?? false;
		$field_key = $field['_key'] ?? '';

		// Normalize value to array for processing
		$values = $value;
		if ( ! is_array( $values ) ) {
			$values = $values ? [ $values ] : [];
		}

		// Get initial options via callback if we have values
		$options = [];
		if ( ! empty( $values ) && ! empty( $field['ajax_callback'] ) && is_callable( $field['ajax_callback'] ) ) {
			// Call the callback with null search and the IDs to hydrate
			$results = call_user_func( $field['ajax_callback'], '', $values );
			if ( is_array( $results ) ) {
				foreach ( $results as $item ) {
					if ( isset( $item['value'] ) ) {
						$options[ $item['value'] ] = $item['label'] ?? $item['value'];
					}
				}
			}
		}

		$extra = [
			'class'            => 'setting-fields-select2 setting-fields-ajax-select ' . ( $field['class'] ?? '' ),
			'data-ajax'        => 'true',
			'data-field-key'   => $field_key,
			'data-field-type'  => 'ajax',
			'data-allow-clear' => $field['allow_clear'] ?? true ? 'true' : 'false',
			'style'            => 'width: 100%; max-width: 400px;',
		];

		if ( $multiple ) {
			$extra['multiple'] = 'multiple';
			$name              .= '[]';
		}

		if ( ! empty( $field['placeholder'] ) ) {
			$extra['data-placeholder'] = $field['placeholder'];
		} else {
			$extra['data-placeholder'] = __( 'Search...', 'setting-fields' );
		}

		if ( isset( $field['minimum_input_length'] ) ) {
			$extra['data-minimum-input-length'] = $field['minimum_input_length'];
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

		// Built by build_input_attrs(), which esc_attr()s every attribute name and value before returning.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '<select%s>', $attrs );

		if ( ! $multiple ) {
			echo '<option value=""></option>';
		}

		foreach ( $options as $option_value => $option_label ) {
			printf(
				'<option value="%s" selected>%s</option>',
				esc_attr( $option_value ),
				esc_html( $option_label )
			);
		}

		echo '</select>';
	}
}
