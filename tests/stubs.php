<?php
/**
 * WordPress stubs specific to a settings page.
 *
 * The kit's stubs cover rendering and sanitizing. These cover the parts a
 * settings page uses and the kit does not: the Settings API, the menu, the
 * redirect and the nonce.
 *
 * One of them carries the weight of this library's whole design.
 * `update_option()` runs `sanitize_option()` *before* it compares the new
 * value with the old — verified in core, not assumed — which is what makes
 * the registered sanitize callback the single gate every write passes
 * through, whether it comes from the form, from an import, from a reset or
 * from a plugin calling update_option() itself. A stub that skipped that
 * would let this library's tests pass while the real thing wrote unsanitized
 * values, so it is modelled in the same order core uses.
 *
 * @package ArrayPress\RegisterSettingFields
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/**
 * Reset every stubbed global between tests.
 *
 * @return void
 */
function sf_reset_globals(): void {
	$GLOBALS['fk_options']    = [];
	$GLOBALS['fk_filters']    = [];
	$GLOBALS['fk_actions']    = [];
	$GLOBALS['sf_settings']   = [];
	$GLOBALS['sf_errors']     = [];
	$GLOBALS['sf_redirects']  = [];
	$GLOBALS['sf_transients'] = [];
	$GLOBALS['sf_menus']      = [];
	$GLOBALS['fk_can']        = true;
}

sf_reset_globals();

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['fk_filters'][ $hook ][] = $callback;

		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value, ...$args ) {
		foreach ( $GLOBALS['fk_filters'][ $hook ] ?? [] as $callback ) {
			$value = $callback( $value, ...$args );
		}

		return $value;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['fk_actions'][ $hook ][] = $callback;

		return true;
	}
}

if ( ! function_exists( 'register_setting' ) ) {
	function register_setting( $group, $name, $args = [] ) {
		$GLOBALS['sf_settings'][ $name ] = $args;

		if ( isset( $args['sanitize_callback'] ) ) {
			add_filter( "sanitize_option_{$name}", $args['sanitize_callback'] );
		}
	}
}

if ( ! function_exists( 'sanitize_option' ) ) {
	function sanitize_option( $option, $value ) {
		return apply_filters( "sanitize_option_{$option}", $value, $option, '' );
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		// Core's order: sanitize, then compare. Reversing the two is what
		// would let an unsanitized value through on a write that happens to
		// match what is already stored.
		$value = sanitize_option( $option, $value );

		$old = $GLOBALS['fk_options'][ $option ] ?? false;

		if ( $value === $old ) {
			return false;
		}

		$GLOBALS['fk_options'][ $option ] = $value;

		return true;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default_value = false ) {
		return $GLOBALS['fk_options'][ $option ] ?? $default_value;
	}
}

if ( ! function_exists( 'add_settings_error' ) ) {
	function add_settings_error( $setting, $code, $message, $type = 'error' ) {
		$GLOBALS['sf_errors'][] = compact( 'setting', 'code', 'message', 'type' );
	}
}

if ( ! function_exists( 'get_settings_errors' ) ) {
	function get_settings_errors( $setting = '', $sanitize = false ) {
		return $GLOBALS['sf_errors'];
	}
}

if ( ! function_exists( 'settings_errors' ) ) {
	function settings_errors( $setting = '', $sanitize = false, $hide_on_update = false ) {
		foreach ( $GLOBALS['sf_errors'] as $error ) {
			printf( '<div class="notice notice-%s"><p>%s</p></div>', esc_attr( $error['type'] ), esc_html( $error['message'] ) );
		}
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $key, $value, $expiration = 0 ) {
		$GLOBALS['sf_transients'][ $key ] = $value;

		return true;
	}
}

/**
 * Thrown in place of the redirect a settings action ends with.
 *
 * The real method redirects and exits. A stub that threw nothing would let
 * the test run on past the point the request really ends, and a stub that
 * exited would take PHPUnit with it.
 */
class SF_Redirect extends \Exception {

	/**
	 * Where the redirect was headed.
	 *
	 * @var string
	 */
	public string $location;

	/**
	 * Construct.
	 *
	 * @param string $location Redirect target.
	 */
	public function __construct( string $location ) {
		parent::__construct( 'Redirected to ' . $location );

		$this->location = $location;
	}
}

if ( ! function_exists( 'wp_safe_redirect' ) ) {
	function wp_safe_redirect( $location, $status = 302 ) {
		$GLOBALS['sf_redirects'][] = $location;

		throw new SF_Redirect( (string) $location );
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '', $scheme = 'admin' ) {
		return 'https://example.test/wp-admin/' . ltrim( (string) $path, '/' );
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( ...$args ) {
		$query = is_array( $args[0] ) ? $args[0] : [ $args[0] => $args[1] ];
		$url   = is_array( $args[0] ) ? ( $args[1] ?? '' ) : ( $args[2] ?? '' );

		$separator = str_contains( (string) $url, '?' ) ? '&' : '?';

		return $url . $separator . http_build_query( $query );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url, $protocols = null, $context = 'display' ) {
		return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $text, $domain = 'default' ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $display = true ) {
		$field = sprintf( '<input type="hidden" name="%s" value="nonce-%s" />', $name, (string) $action );

		if ( $display ) {
			echo $field;
		}

		return $field;
	}
}

if ( ! function_exists( 'check_admin_referer' ) ) {
	function check_admin_referer( $action = -1, $query_arg = '_wpnonce' ) {
		$GLOBALS['sf_checked_referer'] = $action;

		return 1;
	}
}

if ( ! function_exists( 'settings_fields' ) ) {
	function settings_fields( $option_group ) {
		printf( '<input type="hidden" name="option_page" value="%s" />', esc_attr( (string) $option_group ) );
		echo '<input type="hidden" name="action" value="update" />';
		wp_nonce_field( $option_group . '-options' );
	}
}

if ( ! function_exists( 'submit_button' ) ) {
	function submit_button( $text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null ) {
		echo '<p class="submit"><input type="submit" class="button button-primary" value="Save Changes" /></p>';
	}
}

if ( ! function_exists( 'add_menu_page' ) ) {
	function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $icon = '', $position = null ) {
		$GLOBALS['sf_menus'][] = compact( 'page_title', 'menu_title', 'capability', 'menu_slug' ) + [ 'parent' => '' ];

		return 'toplevel_page_' . $menu_slug;
	}
}

if ( ! function_exists( 'add_submenu_page' ) ) {
	function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
		$GLOBALS['sf_menus'][] = compact( 'page_title', 'menu_title', 'capability', 'menu_slug' ) + [ 'parent' => $parent_slug ];

		return $parent_slug . '_page_' . $menu_slug;
	}
}

if ( ! function_exists( 'get_current_screen' ) ) {
	function get_current_screen() {
		return $GLOBALS['sf_screen'] ?? null;
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( $message = '', $title = '', $args = [] ) {
		throw new \RuntimeException( is_string( $message ) ? $message : 'wp_die' );
	}
}

if ( ! function_exists( 'nocache_headers' ) ) {
	function nocache_headers() {}
}

if ( ! function_exists( 'sanitize_file_name' ) ) {
	function sanitize_file_name( $filename ) {
		return (string) preg_replace( '/[^A-Za-z0-9._-]/', '-', (string) $filename );
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = [] ) {
		return array_merge( $defaults, (array) $args );
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, ...$args ) {
		return (bool) ( $GLOBALS['fk_can'] ?? true );
	}
}

/*
 * Real slashing, not an identity stub. The Settings API unslashes before it
 * hands a value over and the field set unslashes again at its own boundary,
 * so the re-slash between them is load-bearing: without it every backslash a
 * user typed is eaten by the save that stores it. An identity stub would let
 * that bug through.
 */
if ( ! function_exists( 'wp_slash' ) ) {
	function wp_slash( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'wp_slash', $value );
		}

		return is_string( $value ) ? addslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'wp_unslash', $value );
		}

		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

/**
 * The parts of WP_Screen a settings page touches.
 */
class SF_Screen {

	/**
	 * The screen id.
	 *
	 * @var string
	 */
	public string $id;

	/**
	 * Help tabs added to it.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public array $help_tabs = [];

	/**
	 * The help sidebar, if one was set.
	 *
	 * @var string
	 */
	public string $help_sidebar = '';

	/**
	 * Construct.
	 *
	 * @param string $id Screen id.
	 */
	public function __construct( string $id ) {
		$this->id = $id;
	}

	/**
	 * Record a help tab.
	 *
	 * @param array<string, mixed> $tab The tab.
	 *
	 * @return void
	 */
	public function add_help_tab( array $tab ): void {
		$this->help_tabs[] = $tab;
	}

	/**
	 * Record the help sidebar.
	 *
	 * @param string $content Sidebar content.
	 *
	 * @return void
	 */
	public function set_help_sidebar( string $content ): void {
		$this->help_sidebar = $content;
	}
}

if ( ! function_exists( 'wp_max_upload_size' ) ) {
	function wp_max_upload_size() {
		return 8 * MB_IN_BYTES;
	}
}

if ( ! function_exists( 'size_format' ) ) {
	function size_format( $bytes, $decimals = 0 ) {
		return round( (int) $bytes / MB_IN_BYTES, $decimals ) . ' MB';
	}
}

if ( ! defined( 'MB_IN_BYTES' ) ) {
	define( 'MB_IN_BYTES', 1024 * 1024 );
}
