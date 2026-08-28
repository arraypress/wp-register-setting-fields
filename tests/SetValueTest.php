<?php
/**
 * Writing a value from code.
 *
 * @package ArrayPress\RegisterSettingFields
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Tests;

use ArrayPress\RegisterSettingFields\Registry;
use ArrayPress\RegisterSettingFields\SettingFields;
use PHPUnit\Framework\TestCase;

final class SetValueTest extends TestCase {

	protected function setUp(): void {
		sf_reset_globals();

		parent::setUp();
	}

	protected function tearDown(): void {
		foreach ( array_keys( Registry::instance()->all() ) as $id ) {
			Registry::instance()->unregister( (string) $id );
		}

		sf_reset_globals();

		parent::tearDown();
	}

	private function page(): SettingFields {
		return new SettingFields(
			'sf_set',
			[
				'option_name' => 'sf_set',
				'menu_slug'   => 'sf-set',
				'tabs'        => [ 'one' => 'One' ],
				'fields'      => [
					'plain'  => [ 'type' => 'text', 'tab' => 'one' ],
					'secret' => [ 'type' => 'password', 'tab' => 'one', 'encrypted' => true ],
					'other'  => [ 'type' => 'text', 'tab' => 'one' ],
				],
			]
		);
	}

	public function test_a_value_round_trips(): void {
		$page = $this->page();

		$this->assertTrue( $page->set_value( 'plain', 'hello' ) );
		$this->assertSame( 'hello', $page->get_value( 'plain' ) );
	}

	/**
	 * The reason this method exists. update_option() would store plaintext
	 * where ciphertext is expected, and the read side authenticates before
	 * decrypting -- so it comes back empty rather than as what was written.
	 */
	public function test_an_encrypted_value_round_trips(): void {
		$page = $this->page();

		$this->assertTrue( $page->set_value( 'secret', 'sk_test_example' ) );
		$this->assertSame( 'sk_test_example', $page->get_value( 'secret' ) );
	}

	/**
	 * Stored encrypted, not merely returned correctly.
	 */
	public function test_an_encrypted_value_is_not_stored_in_the_clear(): void {
		$this->page()->set_value( 'secret', 'sk_test_example' );

		$stored = get_option( 'sf_set', [] );

		$this->assertNotSame( 'sk_test_example', $stored['secret'] ?? null );
		$this->assertNotEmpty( $stored['secret'] ?? '' );
	}

	/**
	 * Targeted, unlike a form save, which deletes every field absent from
	 * its input.
	 */
	public function test_writing_one_field_leaves_the_others_alone(): void {
		$page = $this->page();

		$page->set_value( 'plain', 'first' );
		$page->set_value( 'other', 'second' );
		$page->set_value( 'plain', 'changed' );

		$this->assertSame( 'changed', $page->get_value( 'plain' ) );
		$this->assertSame( 'second', $page->get_value( 'other' ) );
	}

	public function test_an_unknown_field_writes_nothing(): void {
		$this->assertFalse( $this->page()->set_value( 'nothing', 'x' ) );
	}

	public function test_a_value_can_be_removed(): void {
		$page = $this->page();

		$page->set_value( 'plain', 'hello' );

		$this->assertTrue( $page->delete_value( 'plain' ) );
		$this->assertNull( $page->get_value( 'plain' ) );
		$this->assertFalse( $page->delete_value( 'nothing' ) );
	}
}
