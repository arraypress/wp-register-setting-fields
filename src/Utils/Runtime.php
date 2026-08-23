<?php
/**
 * Runtime Key Derivation
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.1.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Utils;

/**
 * Class Runtime
 *
 * Derives every runtime string this library registers — REST namespace,
 * script handles, JS object names, transient keys — from its own PHP
 * namespace.
 *
 * Strauss rewrites class namespaces but leaves string literals alone. Two
 * plugins each bundling a prefixed copy of this library therefore get
 * distinct classes but would otherwise register identical REST routes,
 * identical script handles and identical transient keys.
 *
 * That is not merely wasteful:
 *
 * - `WP_REST_Server::register_route()` merges same-path registrations with
 *   `array_merge()` over a numerically-indexed handler list, so handlers are
 *   appended rather than replaced and dispatch runs the first whose methods
 *   match. The plugin that registered first answers the other's requests,
 *   under its own capability and its own report registry.
 * - Every callback on a shared `wp_ajax_*` action runs in turn, and the
 *   download handler `exit`s after `readfile()`. Whichever plugin registered
 *   second could never serve an export at all.
 * - A shared transient prefix lets one plugin read and delete the other's
 *   export session.
 *
 * The derivation exploits the one thing Strauss *does* rewrite: this file's
 * namespace. In a prefixed build `__NAMESPACE__` begins with the consumer's
 * prefix ("EDDFF\ArrayPress\RegisterSettingFields\Utils"), unique per plugin by
 * construction, so every key comes out distinct with no configuration.
 */
final class Runtime {

	/**
	 * This library's own identifier, used when running unprefixed.
	 */
	private const LIBRARY = 'setting-fields';

	/**
	 * Get the per-build prefix.
	 *
	 * Returns "reports" for a plain Composer install (development, or a
	 * single consumer that does not use Strauss) and "{prefix}-reports"
	 * for a prefixed build.
	 *
	 * @return string
	 */
	public static function prefix(): string {
		$segments = explode( '\\', __NAMESPACE__ );
		$root     = $segments[0] ?? '';

		if ( '' === $root || 'ArrayPress' === $root ) {
			return self::LIBRARY;
		}

		return self::slug( $root ) . '-' . self::LIBRARY;
	}

	/**
	 * Get the REST namespace for this build.
	 *
	 * @return string
	 */
	public static function rest_namespace(): string {
		return self::prefix() . '/v1';
	}

	/**
	 * Get a script or style handle for this build.
	 *
	 * @param string $suffix Optional handle suffix.
	 *
	 * @return string
	 */
	public static function handle( string $suffix = '' ): string {
		return '' === $suffix ? self::prefix() : self::prefix() . '-' . $suffix;
	}

	/**
	 * Get an option, transient or nonce key for this build.
	 *
	 * @param string $suffix Optional key suffix.
	 *
	 * @return string
	 */
	public static function key( string $suffix = '' ): string {
		$base = str_replace( '-', '_', self::prefix() );

		return '' === $suffix ? $base : $base . '_' . $suffix;
	}

	/**
	 * Get the JavaScript object name for this build.
	 *
	 * `wp_localize_script()` writes `var <name> = {...}`, so this has to be a
	 * valid JS identifier — hyphens are not allowed.
	 *
	 * @param string $suffix Optional name suffix.
	 *
	 * @return string
	 */
	public static function js_object( string $suffix = '' ): string {
		$parts = preg_split( '/[^A-Za-z0-9]+/', self::prefix(), -1, PREG_SPLIT_NO_EMPTY ) ?: [];
		$name  = implode( '', array_map( 'ucfirst', $parts ) );

		return $name . $suffix;
	}

	/**
	 * Get a hook name for this build.
	 *
	 * @param string $suffix Hook suffix.
	 *
	 * @return string
	 */
	public static function hook( string $suffix ): string {
		return self::key() . '_' . $suffix;
	}

	/**
	 * Reduce a namespace segment to a lowercase slug.
	 *
	 * `sanitize_title()` is not used here: this runs from `__NAMESPACE__` at
	 * class-load time, which can precede WordPress being fully loaded.
	 *
	 * @param string $value Value to slug.
	 *
	 * @return string
	 */
	private static function slug( string $value ): string {
		$value = preg_replace( '/[^A-Za-z0-9]+/', '-', $value ) ?? '';

		return strtolower( trim( $value, '-' ) );
	}
}
