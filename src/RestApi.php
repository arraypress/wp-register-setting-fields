<?php
/**
 * REST API Handler for Ajax Fields
 *
 * Handles AJAX search and hydration requests for ajax field types.
 * Supports custom callbacks, post searches, taxonomy term searches, and user searches.
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class RestApi
 *
 * Manages REST API routes for ajax field types.
 */
class RestApi {

	/**
	 * The REST namespace.
	 *
	 * @var string
	 */
	private string $namespace = 'arraypress-setting-fields/v1';

	/**
	 * Whether routes have been registered.
	 *
	 * @var bool
	 */
	private static bool $routes_registered = false;

	/**
	 * Register the REST API routes.
	 *
	 * Call this early (e.g., on 'init' or when registering fields).
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( self::$routes_registered ) {
			return;
		}

		self::$routes_registered = true;

		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		$instance = new self();

		register_rest_route( $instance->namespace, '/ajax', [
			'methods'             => 'GET',
			'callback'            => [ $instance, 'handle_request' ],
			'permission_callback' => [ $instance, 'permission_check' ],
			'args'                => [
				'settings_id' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				],
				'field_key'   => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => [ __CLASS__, 'sanitize_field_key' ],
				],
				'field_type'  => [
					'type'              => 'string',
					'default'           => 'ajax',
					'sanitize_callback' => 'sanitize_key',
				],
				'search'      => [
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'include'     => [
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'post_type'   => [
					'type'              => 'string',
					'default'           => 'post',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'taxonomy'    => [
					'type'              => 'string',
					'default'           => 'category',
					'sanitize_callback' => 'sanitize_key',
				],
				'role'        => [
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'page'        => [
					'type'              => 'integer',
					'default'           => 1,
					'sanitize_callback' => 'absint',
				],
				'per_page'    => [
					'type'              => 'integer',
					'default'           => 20,
					'sanitize_callback' => 'absint',
				],
			],
		] );
	}

	/**
	 * Sanitize field key that may contain dots for nested paths.
	 *
	 * @param string $value The field key value.
	 *
	 * @return string Sanitized field key.
	 */
	public static function sanitize_field_key( string $value ): string {
		// Allow dots for nested field paths (e.g., "resources.resource_person")
		$parts = explode( '.', $value );
		$parts = array_map( 'sanitize_key', $parts );

		return implode( '.', $parts );
	}

	/**
	 * Permission check for REST requests.
	 *
	 * @return bool
	 */
	public function permission_check(): bool {
		/**
		 * Filter the capability required for ajax field REST access.
		 *
		 * @param string $capability The required capability.
		 */
		$capability = apply_filters( 'arraypress_setting_fields_rest_capability', 'manage_options' );

		return current_user_can( $capability );
	}

	/**
	 * Handle the AJAX request.
	 *
	 * Routes to appropriate handler based on field type.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_request( WP_REST_Request $request ) {
		$field_type = $request->get_param( 'field_type' );

		switch ( $field_type ) {
			case 'post_ajax':
				return $this->handle_post_search( $request );

			case 'taxonomy_ajax':
				return $this->handle_taxonomy_search( $request );

			case 'user_ajax':
				return $this->handle_user_search( $request );

			case 'ajax':
			default:
				return $this->handle_custom_ajax( $request );
		}
	}

	/**
	 * Handle custom AJAX callback request.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	protected function handle_custom_ajax( WP_REST_Request $request ) {
		$settings_id = $request->get_param( 'settings_id' );
		$field_key   = $request->get_param( 'field_key' );
		$search      = $request->get_param( 'search' );
		$include     = $request->get_param( 'include' );

		// Get the field configuration - handle nested field paths
		$field = $this->get_field_config( $settings_id, $field_key );

		if ( ! $field ) {
			return new WP_Error(
				'invalid_field',
				__( 'Invalid field configuration.', 'setting-fields' ),
				[ 'status' => 400 ]
			);
		}

		// Ensure it's an ajax type with a callback
		if ( ( $field['type'] ?? '' ) !== 'ajax' ) {
			return new WP_Error(
				'invalid_field_type',
				__( 'Field is not an ajax type.', 'setting-fields' ),
				[ 'status' => 400 ]
			);
		}

		$callback = $field['ajax_callback'] ?? null;

		if ( ! is_callable( $callback ) ) {
			return new WP_Error(
				'invalid_callback',
				__( 'Invalid ajax callback.', 'setting-fields' ),
				[ 'status' => 500 ]
			);
		}

		// Parse include IDs if provided (for hydration)
		$ids = null;
		if ( ! empty( $include ) ) {
			$ids = array_map( 'trim', explode( ',', $include ) );
			$ids = array_filter( $ids );
		}

		try {
			$results = call_user_func( $callback, $search, $ids );

			return $this->normalize_results( $results );

		} catch ( Exception $e ) {
			return new WP_Error(
				'callback_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Handle post search request.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response
	 */
	protected function handle_post_search( WP_REST_Request $request ): WP_REST_Response {
		$search    = $request->get_param( 'search' );
		$include   = $request->get_param( 'include' );
		$post_type = $request->get_param( 'post_type' );
		$page      = $request->get_param( 'page' );
		$per_page  = min( $request->get_param( 'per_page' ), 100 );

		// Parse post types (can be comma-separated)
		$post_types = array_map( 'trim', explode( ',', $post_type ) );
		$post_types = array_filter( $post_types );

		if ( empty( $post_types ) ) {
			$post_types = [ 'post' ];
		}

		// Parse include IDs if provided (for hydration)
		$include_ids = null;
		if ( ! empty( $include ) ) {
			$include_ids = array_map( 'absint', explode( ',', $include ) );
			$include_ids = array_filter( $include_ids );
		}

		// Build query args
		$args = [
			'post_type'      => $post_types,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		];

		// If we have specific IDs to include (hydration), fetch those
		if ( ! empty( $include_ids ) ) {
			$args['post__in']       = $include_ids;
			$args['posts_per_page'] = count( $include_ids );
			$args['orderby']        = 'post__in';
		} elseif ( ! empty( $search ) ) {
			// Otherwise search by title
			$args['s'] = $search;
		}

		$query   = new \WP_Query( $args );
		$results = [];

		foreach ( $query->posts as $post ) {
			$results[] = [
				'value' => $post->ID,
				'label' => $post->post_title,
			];
		}

		return new WP_REST_Response( [
			'results'    => $results,
			'pagination' => [
				'more' => $query->max_num_pages > $page,
			],
		], 200 );
	}

	/**
	 * Handle taxonomy term search request.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	protected function handle_taxonomy_search( WP_REST_Request $request ) {
		$search   = $request->get_param( 'search' );
		$include  = $request->get_param( 'include' );
		$taxonomy = $request->get_param( 'taxonomy' );
		$page     = $request->get_param( 'page' );
		$per_page = min( $request->get_param( 'per_page' ), 100 );
		$offset   = ( $page - 1 ) * $per_page;

		// Validate taxonomy exists
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new WP_Error(
				'invalid_taxonomy',
				__( 'Invalid taxonomy.', 'setting-fields' ),
				[ 'status' => 400 ]
			);
		}

		// Parse include IDs if provided (for hydration)
		$include_ids = null;
		if ( ! empty( $include ) ) {
			$include_ids = array_map( 'absint', explode( ',', $include ) );
			$include_ids = array_filter( $include_ids );
		}

		// Build query args
		$args = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'number'     => $per_page,
			'offset'     => $offset,
			'orderby'    => 'name',
			'order'      => 'ASC',
		];

		// If we have specific IDs to include (hydration), fetch those
		if ( ! empty( $include_ids ) ) {
			$args['include'] = $include_ids;
			$args['number']  = count( $include_ids );
			$args['offset']  = 0;
		} elseif ( ! empty( $search ) ) {
			// Otherwise search by name
			$args['search'] = $search;
		}

		$terms = get_terms( $args );

		// Get total for pagination
		$total_args          = $args;
		$total_args['number'] = 0;
		$total_args['offset'] = 0;
		$total_args['fields'] = 'count';
		$total_terms          = get_terms( $total_args );

		$results = [];

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$results[] = [
					'value' => $term->term_id,
					'label' => $term->name,
				];
			}
		}

		return new WP_REST_Response( [
			'results'    => $results,
			'pagination' => [
				'more' => ( $offset + $per_page ) < $total_terms,
			],
		], 200 );
	}

	/**
	 * Handle user search request.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response
	 */
	protected function handle_user_search( WP_REST_Request $request ): WP_REST_Response {
		$search   = $request->get_param( 'search' );
		$include  = $request->get_param( 'include' );
		$role     = $request->get_param( 'role' );
		$page     = $request->get_param( 'page' );
		$per_page = min( $request->get_param( 'per_page' ), 100 );
		$offset   = ( $page - 1 ) * $per_page;

		// Parse include IDs if provided (for hydration)
		$include_ids = null;
		if ( ! empty( $include ) ) {
			$include_ids = array_map( 'absint', explode( ',', $include ) );
			$include_ids = array_filter( $include_ids );
		}

		// Build query args
		$args = [
			'number'  => $per_page,
			'offset'  => $offset,
			'orderby' => 'display_name',
			'order'   => 'ASC',
		];

		// Filter by role if specified
		if ( ! empty( $role ) ) {
			$roles = array_map( 'trim', explode( ',', $role ) );
			$roles = array_filter( $roles );
			if ( ! empty( $roles ) ) {
				$args['role__in'] = $roles;
			}
		}

		// If we have specific IDs to include (hydration), fetch those
		if ( ! empty( $include_ids ) ) {
			$args['include'] = $include_ids;
			$args['number']  = count( $include_ids );
			$args['offset']  = 0;
		} elseif ( ! empty( $search ) ) {
			// Search by name or email
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = [ 'user_login', 'user_email', 'display_name' ];
		}

		$user_query  = new \WP_User_Query( $args );
		$total_users = $user_query->get_total();
		$results     = [];

		foreach ( $user_query->get_results() as $user ) {
			$results[] = [
				'value' => $user->ID,
				'label' => $user->display_name,
			];
		}

		return new WP_REST_Response( [
			'results'    => $results,
			'pagination' => [
				'more' => ( $offset + $per_page ) < $total_users,
			],
		], 200 );
	}

	/**
	 * Normalize results to consistent format.
	 *
	 * @param mixed $results Raw results from callback.
	 *
	 * @return WP_REST_Response
	 */
	protected function normalize_results( $results ): WP_REST_Response {
		if ( ! is_array( $results ) ) {
			return new WP_REST_Response( [ 'results' => [] ], 200 );
		}

		$normalized = array_values( array_filter( array_map( function ( $item ) {
			if ( is_array( $item ) && isset( $item['value'] ) ) {
				return [
					'value' => $item['value'],
					'label' => $item['label'] ?? $item['value'],
				];
			}

			return null;
		}, $results ) ) );

		return new WP_REST_Response( [ 'results' => $normalized ], 200 );
	}

	/**
	 * Get field configuration, supporting nested field paths.
	 *
	 * Field key can be:
	 * - Simple: "field_name"
	 * - Nested: "parent_field.child_field" (for fields inside repeaters/groups)
	 *
	 * @param string $settings_id The settings ID.
	 * @param string $field_key   The field key (may include dot notation for nesting).
	 *
	 * @return array|null The field configuration or null if not found.
	 */
	protected function get_field_config( string $settings_id, string $field_key ): ?array {
		$settings = Registry::instance()->get( $settings_id );

		if ( ! $settings ) {
			return null;
		}

		// Get all fields from the settings instance
		$fields = $settings->get_fields();

		// Check if this is a nested field path (contains a dot)
		if ( str_contains( $field_key, '.' ) ) {
			$parts      = explode( '.', $field_key );
			$parent_key = $parts[0];
			$child_key  = $parts[1];

			// Get the parent field (repeater or group)
			$parent_field = $fields[ $parent_key ] ?? null;

			if ( ! $parent_field ) {
				return null;
			}

			// Check if parent has sub_fields
			if ( ! isset( $parent_field['sub_fields'] ) || ! is_array( $parent_field['sub_fields'] ) ) {
				return null;
			}

			// Return the nested field config
			return $parent_field['sub_fields'][ $child_key ] ?? null;
		}

		// Simple field path
		return $fields[ $field_key ] ?? null;
	}

	/**
	 * Get the REST namespace.
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		return $this->namespace;
	}

	/**
	 * Get the full REST URL for ajax requests.
	 *
	 * @return string
	 */
	public function get_rest_url(): string {
		return rest_url( $this->namespace . '/ajax' );
	}

}
