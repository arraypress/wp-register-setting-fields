<?php
/**
 * REST API Handler for Ajax Fields
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class RestApi
 */
class RestApi {

	/**
	 * The REST namespace.
	 *
	 * @var string
	 */
	private string $namespace = 'setting-fields/v1';

	/**
	 * Whether routes have been registered.
	 *
	 * @var bool
	 */
	private static bool $routes_registered = false;

	/**
	 * Register the REST API routes.
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

		// Ajax field search endpoint
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
			],
		] );

		// Email preview endpoint
		register_rest_route( $instance->namespace, '/email/preview', [
			'methods'             => 'POST',
			'callback'            => [ $instance, 'handle_email_preview' ],
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
				'subject'     => [
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'title'       => [
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'subtitle'    => [
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'message'     => [
					'type'    => 'string',
					'default' => '',
				],
			],
		] );

		// Email send test endpoint
		register_rest_route( $instance->namespace, '/email/send-test', [
			'methods'             => 'POST',
			'callback'            => [ $instance, 'handle_email_send_test' ],
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
				'email'       => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_email',
				],
				'subject'     => [
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'title'       => [
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'subtitle'    => [
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'message'     => [
					'type'    => 'string',
					'default' => '',
				],
			],
		] );

		// Action button endpoint
		register_rest_route( $instance->namespace, '/action', [
			'methods'             => 'POST',
			'callback'            => [ $instance, 'handle_action_button' ],
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
				'input_value' => [
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
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
		$capability = apply_filters( 'setting_fields_rest_capability', 'manage_options' );

		return current_user_can( $capability );
	}

	/**
	 * Handle the AJAX request.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_request( WP_REST_Request $request ) {
		$field_type = $request->get_param( 'field_type' );

		return match ( $field_type ) {
			'post', 'page' => $this->handle_post_search( $request ),
			'taxonomy' => $this->handle_taxonomy_search( $request ),
			'user' => $this->handle_user_search( $request ),
			default => $this->handle_custom_ajax( $request ),
		};
	}

	/**
	 * Handle action button request.
	 *
	 * Executes the action_callback defined in the action_button field config.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_action_button( WP_REST_Request $request ) {
		$settings_id = $request->get_param( 'settings_id' );
		$field_key   = $request->get_param( 'field_key' );
		$input_value = $request->get_param( 'input_value' );

		$field = $this->get_field_config( $settings_id, $field_key );

		if ( ! $field ) {
			return new WP_Error(
				'invalid_field',
				__( 'Invalid field configuration.', 'setting-fields' ),
				[ 'status' => 400 ]
			);
		}

		if ( ( $field['type'] ?? '' ) !== 'action_button' ) {
			return new WP_Error(
				'invalid_field_type',
				__( 'Field is not an action_button type.', 'setting-fields' ),
				[ 'status' => 400 ]
			);
		}

		$callback = $field['action_callback'] ?? null;

		if ( ! is_callable( $callback ) ) {
			return new WP_Error(
				'invalid_callback',
				__( 'No action callback defined for this field.', 'setting-fields' ),
				[ 'status' => 500 ]
			);
		}

		try {
			$data = [
				'settings_id' => $settings_id,
				'field_key'   => $field_key,
				'input_value' => $input_value,
			];

			$result = call_user_func( $callback, $data );

			// Normalize result
			if ( is_bool( $result ) ) {
				return new WP_REST_Response( [
					'success' => $result,
					'message' => $result
						? __( 'Action completed successfully.', 'setting-fields' )
						: __( 'Action failed.', 'setting-fields' ),
				], $result ? 200 : 500 );
			}

			if ( is_string( $result ) ) {
				return new WP_REST_Response( [
					'success' => true,
					'message' => $result,
				], 200 );
			}

			if ( is_array( $result ) ) {
				// Ensure success and message keys exist
				$result = wp_parse_args( $result, [
					'success' => true,
					'message' => __( 'Action completed.', 'setting-fields' ),
				] );

				return new WP_REST_Response( $result, $result['success'] ? 200 : 500 );
			}

			if ( $result instanceof WP_Error ) {
				return $result;
			}

			return new WP_REST_Response( [
				'success' => true,
				'message' => __( 'Action completed.', 'setting-fields' ),
			], 200 );

		} catch ( Exception $e ) {
			return new WP_Error(
				'action_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Handle email preview request.
	 *
	 * Calls the preview_callback defined in the email_editor field config.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_email_preview( WP_REST_Request $request ) {
		$settings_id = $request->get_param( 'settings_id' );
		$field_key   = $request->get_param( 'field_key' );
		$subject     = $request->get_param( 'subject' );
		$title       = $request->get_param( 'title' );
		$subtitle    = $request->get_param( 'subtitle' );
		$message     = wp_kses_post( $request->get_param( 'message' ) );

		$field = $this->get_field_config( $settings_id, $field_key );

		if ( ! $field ) {
			return new WP_Error( 'invalid_field', __( 'Invalid field configuration.', 'setting-fields' ), [ 'status' => 400 ] );
		}

		if ( ( $field['type'] ?? '' ) !== 'email_editor' ) {
			return new WP_Error( 'invalid_field_type', __( 'Field is not an email_editor type.', 'setting-fields' ), [ 'status' => 400 ] );
		}

		// Route through wp-register-emails if configured
		$email_group    = $field['email_group'] ?? '';
		$email_template = $field['email_template'] ?? '';

		if ( $email_group && $email_template && function_exists( 'get_email_preview_html' ) ) {
			try {
				$html = get_email_preview_html( $email_group, $email_template, [
					'subject'  => $subject,
					'title'    => $title,
					'subtitle' => $subtitle,
					'message'  => $message,
				] );

				if ( $html ) {
					return new WP_REST_Response( [ 'html' => $html ], 200 );
				}

				return new WP_Error( 'preview_error', __( 'Email template preview returned empty.', 'setting-fields' ), [ 'status' => 500 ] );
			} catch ( Exception $e ) {
				return new WP_Error( 'preview_error', $e->getMessage(), [ 'status' => 500 ] );
			}
		}

		// Legacy: use preview_callback if provided
		$callback = $field['preview_callback'] ?? null;

		if ( ! is_callable( $callback ) ) {
			// Fallback: return simple HTML preview
			$html = sprintf(
				'<!DOCTYPE html><html><head><meta charset="utf-8"><title>%s</title></head><body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; padding: 40px; max-width: 600px; margin: 0 auto;"><h2 style="margin-bottom: 20px;">%s</h2><hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">%s</body></html>',
				esc_html( $subject ),
				esc_html( $subject ),
				wpautop( $message )
			);

			return new WP_REST_Response( [ 'html' => $html ], 200 );
		}

		try {
			$data = [
				'subject'     => $subject,
				'title'       => $title,
				'subtitle'    => $subtitle,
				'message'     => $message,
				'settings_id' => $settings_id,
				'field_key'   => $field_key,
				'field'       => $field,
			];

			$result = call_user_func( $callback, $data );

			if ( is_string( $result ) ) {
				return new WP_REST_Response( [ 'html' => $result ], 200 );
			}

			if ( is_array( $result ) ) {
				if ( isset( $result['html'] ) ) {
					return new WP_REST_Response( $result, 200 );
				}
				if ( isset( $result['subject'] ) && isset( $result['message'] ) ) {
					$html = sprintf(
						'<!DOCTYPE html><html><head><meta charset="utf-8"><title>%s</title></head><body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; padding: 40px; max-width: 600px; margin: 0 auto;"><h2 style="margin-bottom: 20px;">%s</h2><hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">%s</body></html>',
						esc_html( $result['subject'] ),
						esc_html( $result['subject'] ),
						$result['message']
					);

					return new WP_REST_Response( [ 'html' => $html ], 200 );
				}

				return new WP_REST_Response( $result, 200 );
			}

			return new WP_REST_Response( [ 'html' => (string) $result ], 200 );

		} catch ( Exception $e ) {
			return new WP_Error( 'preview_error', $e->getMessage(), [ 'status' => 500 ] );
		}
	}

	/**
	 * Handle email send test request.
	 *
	 * Calls the send_callback defined in the email_editor field config.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_email_send_test( WP_REST_Request $request ) {
		$settings_id = $request->get_param( 'settings_id' );
		$field_key   = $request->get_param( 'field_key' );
		$email       = $request->get_param( 'email' );
		$subject     = $request->get_param( 'subject' );
		$title       = $request->get_param( 'title' );
		$subtitle    = $request->get_param( 'subtitle' );
		$message     = wp_kses_post( $request->get_param( 'message' ) );

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'Invalid email address.', 'setting-fields' ), [ 'status' => 400 ] );
		}

		$field = $this->get_field_config( $settings_id, $field_key );

		if ( ! $field ) {
			return new WP_Error( 'invalid_field', __( 'Invalid field configuration.', 'setting-fields' ), [ 'status' => 400 ] );
		}

		if ( ( $field['type'] ?? '' ) !== 'email_editor' ) {
			return new WP_Error( 'invalid_field_type', __( 'Field is not an email_editor type.', 'setting-fields' ), [ 'status' => 400 ] );
		}

		// Route through wp-register-emails if configured
		$email_group    = $field['email_group'] ?? '';
		$email_template = $field['email_template'] ?? '';

		if ( $email_group && $email_template && function_exists( 'send_email_template' ) ) {
			try {
				$sent = send_email_template( $email_group, $email_template, [
					'to'       => $email,
					'context'  => 'test',
					'preview'  => true,
					'subject'  => $subject,
					'title'    => $title,
					'subtitle' => $subtitle,
					'message'  => $message,
				] );

				if ( $sent ) {
					return new WP_REST_Response( [
						'success' => true,
						'message' => sprintf( __( 'Test email sent to %s', 'setting-fields' ), $email ),
					], 200 );
				}

				return new WP_Error( 'send_failed', __( 'Failed to send test email.', 'setting-fields' ), [ 'status' => 500 ] );
			} catch ( Exception $e ) {
				return new WP_Error( 'send_error', $e->getMessage(), [ 'status' => 500 ] );
			}
		}

		// Legacy: use send_callback if provided
		$callback = $field['send_callback'] ?? null;

		if ( ! is_callable( $callback ) ) {
			// Fallback: send email using wp_mail
			$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
			$sent    = wp_mail( $email, $subject, wpautop( $message ), $headers );

			if ( $sent ) {
				return new WP_REST_Response( [
					'success' => true,
					'message' => sprintf( __( 'Test email sent to %s', 'setting-fields' ), $email ),
				], 200 );
			}

			return new WP_Error( 'send_failed', __( 'Failed to send test email. Check your server mail configuration.', 'setting-fields' ), [ 'status' => 500 ] );
		}

		try {
			$data = [
				'to'          => $email,
				'subject'     => $subject,
				'title'       => $title,
				'subtitle'    => $subtitle,
				'message'     => $message,
				'settings_id' => $settings_id,
				'field_key'   => $field_key,
				'field'       => $field,
			];

			$result = call_user_func( $callback, $data );

			if ( $result === true || ( is_array( $result ) && ! empty( $result['success'] ) ) ) {
				$message = is_array( $result ) && isset( $result['message'] )
					? $result['message']
					: sprintf( __( 'Test email sent to %s', 'setting-fields' ), $email );

				return new WP_REST_Response( [
					'success' => true,
					'message' => $message,
				], 200 );
			}

			$message = is_array( $result ) && isset( $result['message'] ) ? $result['message'] : __( 'Failed to send test email.', 'setting-fields' );

			return new WP_Error( 'send_failed', $message, [ 'status' => 500 ] );

		} catch ( Exception $e ) {
			return new WP_Error( 'send_error', $e->getMessage(), [ 'status' => 500 ] );
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

		$field = $this->get_field_config( $settings_id, $field_key );

		if ( ! $field ) {
			return new WP_Error( 'invalid_field', __( 'Invalid field configuration.', 'setting-fields' ), [ 'status' => 400 ] );
		}

		if ( ( $field['type'] ?? '' ) !== 'ajax' ) {
			return new WP_Error( 'invalid_field_type', __( 'Field is not an ajax type.', 'setting-fields' ), [ 'status' => 400 ] );
		}

		$callback = $field['ajax_callback'] ?? null;

		if ( ! is_callable( $callback ) ) {
			return new WP_Error( 'invalid_callback', __( 'Invalid ajax callback.', 'setting-fields' ), [ 'status' => 500 ] );
		}

		// Parse include IDs if provided (for hydration)
		$ids = null;
		if ( ! empty( $include ) ) {
			$ids = array_map( 'trim', explode( ',', $include ) );
			$ids = array_filter( $ids );
		}

		try {
			$results = call_user_func( $callback, $search, $ids );

			return $this->format_results( $results );
		} catch ( Exception $e ) {
			return new WP_Error( 'callback_error', $e->getMessage(), [ 'status' => 500 ] );
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
			'posts_per_page' => 20,
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
			$args['s'] = $search;
		}

		$posts   = get_posts( $args );
		$results = [];

		foreach ( $posts as $post ) {
			$results[] = [
				'value' => $post->ID,
				'label' => $post->post_title,
			];
		}

		return new WP_REST_Response( $results, 200 );
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

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.', 'setting-fields' ), [ 'status' => 400 ] );
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
			'number'     => 20,
			'orderby'    => 'name',
			'order'      => 'ASC',
		];

		// If we have specific IDs to include (hydration), fetch those
		if ( ! empty( $include_ids ) ) {
			$args['include'] = $include_ids;
			$args['number']  = count( $include_ids );
		} elseif ( ! empty( $search ) ) {
			$args['search'] = $search;
		}

		$terms   = get_terms( $args );
		$results = [];

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$results[] = [
					'value' => $term->term_id,
					'label' => $term->name,
				];
			}
		}

		return new WP_REST_Response( $results, 200 );
	}

	/**
	 * Handle user search request.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response
	 */
	protected function handle_user_search( WP_REST_Request $request ): WP_REST_Response {
		$search  = $request->get_param( 'search' );
		$include = $request->get_param( 'include' );
		$role    = $request->get_param( 'role' );

		// Parse include IDs if provided (for hydration)
		$include_ids = null;
		if ( ! empty( $include ) ) {
			$include_ids = array_map( 'absint', explode( ',', $include ) );
			$include_ids = array_filter( $include_ids );
		}

		// Build query args
		$args = [
			'number'  => 20,
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
		} elseif ( ! empty( $search ) ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = [ 'user_login', 'user_email', 'display_name' ];
		}

		$user_query = new \WP_User_Query( $args );
		$results    = [];

		foreach ( $user_query->get_results() as $user ) {
			$results[] = [
				'value' => $user->ID,
				'label' => $user->display_name,
			];
		}

		return new WP_REST_Response( $results, 200 );
	}

	/**
	 * Format results to consistent array structure.
	 *
	 * @param mixed $results Raw results from callback.
	 *
	 * @return WP_REST_Response
	 */
	protected function format_results( $results ): WP_REST_Response {
		if ( ! is_array( $results ) ) {
			return new WP_REST_Response( [], 200 );
		}

		$formatted = [];
		foreach ( $results as $item ) {
			if ( is_array( $item ) && isset( $item['value'] ) ) {
				$formatted[] = [
					'value' => $item['value'],
					'label' => $item['label'] ?? $item['value'],
				];
			}
		}

		return new WP_REST_Response( $formatted, 200 );
	}

	/**
	 * Get field configuration, supporting nested field paths.
	 *
	 * @param string $settings_id The settings ID.
	 * @param string $field_key   The field key (may include dot notation).
	 *
	 * @return array|null The field configuration or null if not found.
	 */
	protected function get_field_config( string $settings_id, string $field_key ): ?array {
		$settings = Registry::instance()->get( $settings_id );

		if ( ! $settings ) {
			return null;
		}

		$fields = $settings->get_fields();

		// Check if this is a nested field path
		if ( str_contains( $field_key, '.' ) ) {
			$parts        = explode( '.', $field_key );
			$parent_key   = $parts[0];
			$child_key    = $parts[1];
			$parent_field = $fields[ $parent_key ] ?? null;

			if ( ! $parent_field || ! isset( $parent_field['sub_fields'] ) ) {
				return null;
			}

			return $parent_field['sub_fields'][ $child_key ] ?? null;
		}

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