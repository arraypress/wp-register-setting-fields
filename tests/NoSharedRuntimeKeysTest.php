<?php
/**
 * Guard against reintroducing a shared runtime key.
 *
 * @package ArrayPress\RegisterSettingFields
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Strauss rewrites namespaces but not string literals, so any runtime string
 * written as a literal in this library is shared by every plugin that bundles
 * it. This scans the source for the literal forms and requires them to go
 * through {@see \ArrayPress\RegisterSettingFields\Utils\Runtime} instead.
 *
 * It reads source rather than exercising behaviour on purpose: the failure it
 * guards against is invisible with one plugin installed and only appears on a
 * site running two, which no unit test of this library can set up.
 */
final class NoSharedRuntimeKeysTest extends TestCase {

	/**
	 * Handles WordPress itself ships, which are meant to be named literally.
	 *
	 * @var string[]
	 */
	private const CORE_HANDLES = [
		'jquery',
		'jquery-ui-datepicker',
		'wp-color-picker',
		'wp-codemirror',
		'wp-util',
		'wp-i18n',
		'underscore',
		'backbone',
		'thickbox',
		'media-upload',
		'dashicons',
		'common',
		'buttons',
		'list-tables',
	];

	/**
	 * Every PHP file in src/, keyed by repo-relative path.
	 *
	 * @return array<string, string>
	 */
	private function sources(): array {
		$root  = dirname( __DIR__ ) . '/src';
		$files = [];

		$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root ) );

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && 'php' === $file->getExtension() ) {
				// Runtime itself documents the literal forms in its docblock.
				if ( 'Runtime.php' === $file->getBasename() ) {
					continue;
				}

				$files[ str_replace( $root . '/', '', $file->getPathname() ) ] = file_get_contents( $file->getPathname() );
			}
		}

		$this->assertNotEmpty( $files, 'No sources found to scan.' );

		return $files;
	}

	/**
	 * Assert no source matches a literal-argument pattern.
	 *
	 * @param string   $pattern Regex with the offending literal in group 1.
	 * @param string   $label   What the pattern describes.
	 * @param string[] $allowed Literals that are legitimately shared.
	 */
	private function assertNoLiteral( string $pattern, string $label, array $allowed = [] ): void {
		$found = [];

		foreach ( $this->sources() as $path => $code ) {
			if ( ! preg_match_all( $pattern, $code, $matches, PREG_SET_ORDER ) ) {
				continue;
			}

			foreach ( $matches as $match ) {
				if ( in_array( $match[1], $allowed, true ) ) {
					continue;
				}

				$found[] = sprintf( '%s: %s', $path, $match[0] );
			}
		}

		$this->assertSame(
			[],
			$found,
			sprintf(
				"%s must be derived from Runtime, not written as a literal —\nevery plugin bundling this library would register the same one.\n\n%s",
				$label,
				implode( "\n", $found )
			)
		);
	}

	/**
	 * REST namespaces decide which plugin answers a request.
	 */
	public function test_rest_namespace_is_not_a_literal(): void {
		$this->assertNoLiteral(
			"/register_rest_route\(\s*'([^']+)'/",
			'REST namespaces'
		);
	}

	/**
	 * Every callback on one wp_ajax action runs; the first to exit wins.
	 */
	public function test_no_ajax_actions(): void {
		$this->assertNoLiteral(
			"/add_action\(\s*'(wp_ajax_[^']*)'/",
			'AJAX actions'
		);
	}

	/**
	 * Script and style handles decide whose asset and whose config load.
	 */
	public function test_asset_handles_are_not_literals(): void {
		$this->assertNoLiteral(
			"/wp_(?:enqueue|register)_(?:script|style)\(\s*'([^']+)'/",
			'Asset handles',
			self::CORE_HANDLES
		);
		$this->assertNoLiteral(
			"/wp_(?:enqueue|register)_composer_(?:script|style)\(\s*'([^']+)'/",
			'Asset handles',
			self::CORE_HANDLES
		);
	}

	/**
	 * A shared transient prefix lets one plugin read and delete another's
	 * export session.
	 */
	public function test_transient_keys_are_not_literals(): void {
		$this->assertNoLiteral(
			"/(?:get|set|delete)_transient\(\s*'([^']+)'/",
			'Transient keys'
		);
	}

	/**
	 * A localized global is overwritten by whichever copy localizes last.
	 */
	public function test_localized_object_names_are_not_literals(): void {
		$this->assertNoLiteral(
			"/wp_localize_script\(\s*[^,]+,\s*'([^']+)'/",
			'Localized JS object names'
		);
	}

	/**
	 * Hooks fired by one copy would otherwise reach every copy's listeners.
	 */
	public function test_hook_names_are_not_literals(): void {
		$this->assertNoLiteral(
			"/(?:do_action|apply_filters)\(\s*'([^']+)'/",
			'Hook names'
		);
	}

	/**
	 * The REST namespace accessor must delegate, not return a literal.
	 *
	 * Routes are registered through this accessor rather than with an inline
	 * string, so a hardcoded namespace hides inside the method body where the
	 * register_rest_route() check above cannot see it. That is not
	 * hypothetical: it is exactly what this test was written after failing to
	 * catch.
	 */
	public function test_rest_namespace_accessor_delegates_to_runtime(): void {
		$found = false;

		foreach ( $this->sources() as $path => $code ) {
			if ( ! preg_match( '/function rest_namespace\\(\\)[^{]*\\{(.*?)\\n\\t\\}/s', $code, $m ) ) {
				continue;
			}

			$found = true;

			$this->assertStringContainsString(
				'Runtime::rest_namespace()',
				$m[1],
				sprintf(
					'%s::rest_namespace() must return Runtime::rest_namespace(); a literal here '
					. 'is shared by every plugin bundling this library.',
					$path
				)
			);
		}

		if ( ! $found ) {
			$this->addToAssertionCount( 1 );
		}
	}

}
