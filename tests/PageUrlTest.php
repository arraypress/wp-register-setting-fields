<?php
/**
 * The URL a settings screen links to itself with.
 *
 * @package ArrayPress\RegisterSettingFields
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Tests;

use ArrayPress\RegisterSettingFields\Registry;
use ArrayPress\RegisterSettingFields\SettingFields;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * A parent slug is only a filename when the page hangs off one of core's
 * menus. A plugin with its own top-level menu passes that menu's slug, and
 * passing that to admin_url() builds /wp-admin/my-plugin?page=... -- which is
 * not an admin screen at all, so every tab link and every post-save redirect
 * lands on the front end.
 *
 * Reached by reflection because both callers -- the tab links and the
 * redirect after saving -- are private, and the bug is in the URL rather than
 * in either of them.
 */
final class PageUrlTest extends TestCase {

	protected function tearDown(): void {
		foreach ( array_keys( Registry::instance()->all() ) as $id ) {
			Registry::instance()->unregister( (string) $id );
		}

		parent::tearDown();
	}

	/**
	 * Build a page and read the URL it would link to.
	 *
	 * @param string $parent_slug What the page hangs off.
	 * @param array  $args        Extra query arguments.
	 *
	 * @return string
	 */
	private function url( string $parent_slug, array $args = [] ): string {
		$page = new SettingFields(
			'sf_url_' . md5( $parent_slug ),
			[
				'option_name' => 'sf_url',
				'menu_slug'   => 'my-plugin-settings',
				'parent_slug' => $parent_slug,
				'tabs'        => [ 'one' => 'One' ],
				'fields'      => [ 'first' => [ 'type' => 'text', 'tab' => 'one' ] ],
			]
		);

		// No setAccessible(): private methods have been reflectively
		// callable without it since PHP 8.1, and the call is deprecated.
		return (string) ( new ReflectionMethod( $page, 'page_url' ) )->invoke( $page, $args );
	}

	public function test_a_top_level_menu_parent_resolves_to_admin_php(): void {
		$url = $this->url( 'my-plugin' );

		$this->assertStringContainsString(
			'admin.php',
			$url,
			'A parent that is not a file must resolve to admin.php, where add_submenu_page() puts it.'
		);
		$this->assertStringNotContainsString(
			'/wp-admin/my-plugin',
			$url,
			'The parent slug was used as a path, which is a front end URL.'
		);
		$this->assertStringContainsString( 'page=my-plugin-settings', $url );
	}

	public function test_a_core_menu_parent_keeps_its_filename(): void {
		$this->assertStringContainsString( 'options-general.php', $this->url( 'options-general.php' ) );
		$this->assertStringContainsString( 'edit.php', $this->url( 'edit.php' ) );
	}

	public function test_no_parent_resolves_to_admin_php(): void {
		$this->assertStringContainsString( 'admin.php', $this->url( '' ) );
	}

	public function test_extra_arguments_survive(): void {
		$url = $this->url( 'my-plugin', [ 'tab' => 'payments' ] );

		$this->assertStringContainsString( 'tab=payments', $url );
		$this->assertStringContainsString( 'page=my-plugin-settings', $url );
	}
}
