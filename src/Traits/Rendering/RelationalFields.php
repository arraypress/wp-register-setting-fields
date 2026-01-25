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
 */
trait RelationalFields {

	/**
	 * Render a post select field (static options).
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

		// Get posts
		$posts = get_posts( [
			'post_type'      => $post_type,
			'posts_per_page' => $field['limit'] ?? 100,
			'orderby'        => $field['orderby'] ?? 'title',
			'order'          => $field['order'] ?? 'ASC',
			'post_status'    => $field['post_status'] ?? 'publish',
		] );

		// Build options
		$options = [];
		foreach ( $posts as $post ) {
			$options[ $post->ID ] = $post->post_title;
		}

		// Render as select
		$field['options']  = $options;
		$field['multiple'] = $multiple;

		$this->render_select( $field, $name, $id, $value );
	}

	/**
	 * Render a post AJAX select field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_post_ajax( array $field, string $name, string $id, $value ): void {
		$post_type = $field['post_type'] ?? 'post';
		$multiple  = $field['multiple'] ?? false;
		$field_key = $field['_key'] ?? '';

		// Normalize value to array
		if ( ! is_array( $value ) ) {
			$value = $value ? [ $value ] : [];
		}
		$value = array_filter( array_map( 'absint', $value ) );

		// Get selected posts for initial display
		$options = [];
		if ( ! empty( $value ) ) {
			$posts = get_posts( [
				'post_type'      => $post_type,
				'post__in'       => $value,
				'posts_per_page' => - 1,
				'orderby'        => 'post__in',
			] );
			foreach ( $posts as $post ) {
				$options[ $post->ID ] = $post->post_title;
			}
		}

		$extra = [
			'class'              => 'setting-fields-ajax-select ' . ( $field['class'] ?? '' ),
			'data-select2'       => 'true',
			'data-ajax'          => 'true',
			'data-field-key'     => $field_key,
			'data-field-type'    => 'post_ajax',
			'data-post-type'     => $post_type,
			'data-minimum-input' => $field['min_input'] ?? 2,
			'data-allow-clear'   => 'true',
			'style'              => 'width: 100%; max-width: 400px;',
		];

		if ( $multiple ) {
			$extra['multiple'] = 'multiple';
			$name              .= '[]';
		}

		if ( ! empty( $field['placeholder'] ) ) {
			$extra['data-placeholder'] = $field['placeholder'];
		} else {
			$post_type_obj             = get_post_type_object( $post_type );
			$extra['data-placeholder'] = sprintf( __( 'Search %s...', 'setting-fields' ), $post_type_obj->labels->name ?? $post_type );
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

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
	 * Render a taxonomy select field (static options).
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_taxonomy_select( array $field, string $name, string $id, $value ): void {
		$taxonomy = $field['taxonomy'] ?? 'category';
		$multiple = $field['multiple'] ?? false;

		// Get terms
		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => $field['hide_empty'] ?? false,
			'orderby'    => $field['orderby'] ?? 'name',
			'order'      => $field['order'] ?? 'ASC',
			'number'     => $field['limit'] ?? 0,
		] );

		// Build options (with hierarchy support)
		$options = [];
		if ( ! is_wp_error( $terms ) ) {
			if ( $field['hierarchical'] ?? true ) {
				$options = $this->build_hierarchical_term_options( $terms, $taxonomy );
			} else {
				foreach ( $terms as $term ) {
					$options[ $term->term_id ] = $term->name;
				}
			}
		}

		$field['options']  = $options;
		$field['multiple'] = $multiple;

		$this->render_select( $field, $name, $id, $value );
	}

	/**
	 * Build hierarchical term options.
	 *
	 * @param array  $terms    Terms array.
	 * @param string $taxonomy Taxonomy name.
	 * @param int    $parent   Parent term ID.
	 * @param int    $depth    Current depth.
	 *
	 * @return array
	 */
	protected function build_hierarchical_term_options( array $terms, string $taxonomy, int $parent = 0, int $depth = 0 ): array {
		$options = [];
		$prefix  = str_repeat( '— ', $depth );

		foreach ( $terms as $term ) {
			if ( $term->parent == $parent ) {
				$options[ $term->term_id ] = $prefix . $term->name;

				// Get children
				$children = $this->build_hierarchical_term_options( $terms, $taxonomy, $term->term_id, $depth + 1 );
				$options  = $options + $children;
			}
		}

		return $options;
	}

	/**
	 * Render a taxonomy AJAX select field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_taxonomy_ajax( array $field, string $name, string $id, $value ): void {
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
			'class'              => 'setting-fields-ajax-select ' . ( $field['class'] ?? '' ),
			'data-select2'       => 'true',
			'data-ajax'          => 'true',
			'data-field-key'     => $field_key,
			'data-field-type'    => 'taxonomy_ajax',
			'data-taxonomy'      => $taxonomy,
			'data-minimum-input' => $field['min_input'] ?? 2,
			'data-allow-clear'   => 'true',
			'style'              => 'width: 100%; max-width: 400px;',
		];

		if ( $multiple ) {
			$extra['multiple'] = 'multiple';
			$name              .= '[]';
		}

		if ( ! empty( $field['placeholder'] ) ) {
			$extra['data-placeholder'] = $field['placeholder'];
		} else {
			$taxonomy_obj              = get_taxonomy( $taxonomy );
			$extra['data-placeholder'] = sprintf( __( 'Search %s...', 'setting-fields' ), $taxonomy_obj->labels->name ?? $taxonomy );
		}

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );

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
	 * Render a user select field (static options).
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_user_select( array $field, string $name, string $id, $value ): void {
		$multiple = $field['multiple'] ?? false;

		// Get users
		$args = [
			'orderby' => $field['orderby'] ?? 'display_name',
			'order'   => $field['order'] ?? 'ASC',
			'number'  => $field['limit'] ?? 100,
		];

		if ( ! empty( $field['role'] ) ) {
			$args['role__in'] = (array) $field['role'];
		}

		$users = get_users( $args );

		// Build options
		$options = [];
		foreach ( $users as $user ) {
			$label = $user->display_name;
			if ( $field['show_email'] ?? false ) {
				$label .= ' (' . $user->user_email . ')';
			}
			$options[ $user->ID ] = $label;
		}

		$field['options']  = $options;
		$field['multiple'] = $multiple;

		$this->render_select( $field, $name, $id, $value );
	}

	/**
	 * Render a user AJAX select field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_user_ajax( array $field, string $name, string $id, $value ): void {
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
			'class'              => 'setting-fields-ajax-select ' . ( $field['class'] ?? '' ),
			'data-select2'       => 'true',
			'data-ajax'          => 'true',
			'data-field-key'     => $field_key,
			'data-field-type'    => 'user_ajax',
			'data-role'          => is_array( $role ) ? implode( ',', $role ) : $role,
			'data-minimum-input' => $field['min_input'] ?? 2,
			'data-allow-clear'   => 'true',
			'style'              => 'width: 100%; max-width: 400px;',
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

}
