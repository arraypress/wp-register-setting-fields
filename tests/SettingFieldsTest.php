<?php
/**
 * Settings page tests.
 *
 * @package ArrayPress\RegisterSettingFields
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Tests;

use ArrayPress\FieldKit\Context\EncryptedContext;
use ArrayPress\RegisterSettingFields\Registry;
use ArrayPress\RegisterSettingFields\SettingFields;
use PHPUnit\Framework\TestCase;
use SF_Redirect;

/**
 * The field layer is the kit's and is tested there. What is tested here is
 * what this library still decides: which fields a submission governs, what
 * survives a write it did not come from, and what an export is allowed to
 * contain.
 */
final class SettingFieldsTest extends TestCase {

	/**
	 * Reset the stubbed globals and the registry.
	 */
	protected function setUp(): void {
		sf_reset_globals();

		$_POST  = [];
		$_GET   = [];
		$_FILES = [];

		foreach ( array_keys( Registry::instance()->all() ) as $id ) {
			Registry::instance()->unregister( (string) $id );
		}
	}

	/**
	 * Build a settings page over two tabs.
	 *
	 * @param array<string, mixed> $config Configuration overrides.
	 *
	 * @return SettingFields
	 */
	private function page( array $config = [] ): SettingFields {
		return new SettingFields(
			'sf_demo',
			array_merge(
				[
					'option_name' => 'sf_demo',
					'menu_slug'   => 'sf-demo',
					'tabs'        => [
						'one' => 'One',
						'two' => 'Two',
					],
					'fields'      => [
						'first'  => [
							'type' => 'text',
							'tab'  => 'one',
						],
						'count'  => [
							'type' => 'number',
							'tab'  => 'one',
							'min'  => 0,
						],
						'agreed' => [
							'type' => 'checkbox',
							'tab'  => 'one',
						],
						'second' => [
							'type' => 'text',
							'tab'  => 'two',
						],
					],
				],
				$config
			)
		);
	}

	/**
	 * Submit a tab, the way options.php would.
	 *
	 * @param SettingFields        $page  The page.
	 * @param string               $tab   Tab being saved.
	 * @param array<string, mixed> $input Submitted values, already unslashed.
	 *
	 * @return void
	 */
	private function submit( SettingFields $page, string $tab, array $input ): void {
		$_POST[ 'sf_demo_tab' ] = $tab;

		update_option( $page->get_option_name(), $input );
	}

	/**
	 * Saving one tab leaves the other tab's values alone.
	 *
	 * A submission only carries the fields on the screen it came from, so a
	 * pass over every field would read the rest as cleared. This is the bug
	 * a tabbed settings page has if nobody thinks about it, and it presents
	 * as "my other settings keep disappearing".
	 */
	public function test_saving_one_tab_leaves_the_other_alone(): void {
		$page = $this->page();

		$this->submit( $page, 'two', [ 'second' => 'kept' ] );
		$this->submit( $page, 'one', [ 'first' => 'set' ] );

		$this->assertSame( 'kept', $page->get_value( 'second' ) );
		$this->assertSame( 'set', $page->get_value( 'first' ) );
	}

	/**
	 * An unticked checkbox stores off rather than reverting to its default.
	 */
	public function test_an_unticked_checkbox_stores_off(): void {
		$page = $this->page(
			[
				'fields' => [
					'agreed' => [
						'type'    => 'checkbox',
						'tab'     => 'one',
						'default' => 1,
					],
				],
			]
		);

		$this->submit( $page, 'one', [ 'agreed' => '1' ] );
		$this->assertSame( 1, $page->get_value( 'agreed' ) );

		// Unticked: absent from the submission entirely.
		$this->submit( $page, 'one', [] );

		$this->assertSame( 0, $page->get_value( 'agreed' ) );
	}

	/**
	 * A value is sanitized by its own field type, not stored as sent.
	 */
	public function test_a_value_is_sanitized_by_its_type(): void {
		$page = $this->page();

		$this->submit( $page, 'one', [ 'count' => '42' ] );

		$this->assertSame( 42, $page->get_value( 'count' ) );

		// Not a number at all: the type falls back to its own minimum rather
		// than storing the string.
		$this->submit( $page, 'one', [ 'count' => 'not a number' ] );

		$this->assertSame( 0, $page->get_value( 'count' ) );
	}

	/**
	 * A backslash a user typed survives the save.
	 *
	 * The Settings API unslashes before it hands the value over and the field
	 * set unslashes again at its own boundary, so without a re-slash between
	 * them the value is unslashed twice. It presents as a Windows path or a
	 * regular expression quietly losing its backslashes on every save.
	 */
	public function test_a_backslash_survives_the_save(): void {
		$page = $this->page();

		$this->submit( $page, 'one', [ 'first' => 'C:\\Users\\name' ] );

		$this->assertSame( 'C:\\Users\\name', $page->get_value( 'first' ) );
	}

	/**
	 * A field whose condition is not met is deleted rather than kept.
	 */
	public function test_a_hidden_conditional_field_is_dropped(): void {
		$page = $this->page(
			[
				'fields' => [
					'flag'   => [
						'type' => 'checkbox',
						'tab'  => 'one',
					],
					'detail' => [
						'type'      => 'text',
						'tab'       => 'one',
						'show_when' => [ 'flag' => 1 ],
					],
				],
			]
		);

		$this->submit(
			$page,
			'one',
			[
				'flag'   => '1',
				'detail' => 'visible',
			]
		);

		$this->assertSame( 'visible', $page->get_value( 'detail' ) );

		// The script hides the field; nothing stops the browser submitting it.
		$this->submit( $page, 'one', [ 'detail' => 'stale' ] );

		$this->assertNull( $page->get_value( 'detail' ) );
	}

	/**
	 * A key that is not a field on this page cannot be written through it.
	 */
	public function test_a_key_that_is_not_a_field_is_not_stored(): void {
		$page = $this->page();

		$this->submit(
			$page,
			'one',
			[
				'first'          => 'set',
				'active_plugins' => [ 'evil/evil.php' ],
			]
		);

		$this->assertArrayNotHasKey( 'active_plugins', $page->get_values() );
	}

	/**
	 * A write that is not a form submission governs every field.
	 *
	 * update_option() replaces a value; it does not merge one. A caller that
	 * hands over an array without the tab marker means all of it.
	 */
	public function test_a_direct_write_governs_every_field(): void {
		$page = $this->page();

		$this->submit( $page, 'two', [ 'second' => 'kept' ] );

		// No marker: this is not a form submission.
		$_POST = [];

		update_option( 'sf_demo', [ 'first' => 'only-this' ] );

		$this->assertSame( 'only-this', $page->get_value( 'first' ) );
		$this->assertNull( $page->get_value( 'second' ) );
	}

	/**
	 * A write that is not a submission invents nothing.
	 *
	 * A form submission is missing its unticked checkboxes, so absence has to
	 * mean off there. Nothing about a cron job calling update_option() with
	 * one key says the same, and reading it that way wrote a 0 for every
	 * checkbox and number field on the page — found on the live site, where a
	 * page of 48 fields grew three keys every time anything wrote to it.
	 */
	public function test_a_write_that_is_not_a_submission_invents_nothing(): void {
		$page = $this->page();

		$_POST = [];

		update_option( 'sf_demo', [ 'first' => 'only-this' ] );

		$this->assertSame( [ 'first' => 'only-this' ], $page->get_values() );
	}

	/**
	 * A key the option holds that is not a field is left alone.
	 *
	 * A filter on another plugin's hook may have put it there, and a write to
	 * one field is not the place to decide it should not exist.
	 */
	public function test_a_stored_key_that_is_not_a_field_survives(): void {
		$page = $this->page();

		$GLOBALS['fk_options']['sf_demo'] = [ 'set_by_a_filter' => 'kept' ];

		$_POST = [];

		update_option( 'sf_demo', [ 'first' => 'set' ] );

		$this->assertSame(
			[
				'set_by_a_filter' => 'kept',
				'first'           => 'set',
			],
			$page->get_values()
		);
	}

	/**
	 * Sanitizing a stored value again changes nothing.
	 *
	 * Reset, import and any plugin calling update_option() all hand back what
	 * they read. update_option() sanitizes before it compares, so every one
	 * of those re-runs this callback over its own previous output — and a
	 * pass that is not idempotent corrupts a value simply by being saved
	 * twice.
	 */
	public function test_sanitizing_stored_values_again_changes_nothing(): void {
		$page = $this->page();

		$this->submit(
			$page,
			'one',
			[
				'first'  => 'set',
				'count'  => '7',
				'agreed' => '1',
			]
		);

		$once = $page->get_values();

		$_POST = [];
		update_option( 'sf_demo', $once );

		$this->assertSame( $once, $page->get_values() );
	}

	/**
	 * Re-saving an unchanged value is not reported as a change.
	 *
	 * update_option() compares before it writes, and an array comparison is
	 * order-sensitive. A sanitize pass that returned the same values in a
	 * different order made every save look like a change: a write, a cache
	 * invalidation and an updated_option firing each time anything touched
	 * the option.
	 */
	public function test_re_saving_an_unchanged_value_writes_nothing(): void {
		$page = $this->page();

		$this->submit(
			$page,
			'one',
			[
				'first' => 'set',
				'count' => '3',
			]
		);

		$stored = $page->get_values();
		$_POST  = [];

		$this->assertFalse( update_option( 'sf_demo', $stored ) );
		$this->assertSame( $stored, $page->get_values() );
	}

	/**
	 * An encrypted value never reaches storage in the clear.
	 */
	public function test_an_encrypted_value_is_not_stored_in_the_clear(): void {
		if ( ! EncryptedContext::available() ) {
			$this->markTestSkipped( 'OpenSSL or the salts are unavailable.' );
		}

		$page = $this->page(
			[
				'fields' => [
					'api_key' => [
						'type'      => 'text',
						'tab'       => 'one',
						'encrypted' => true,
					],
				],
			]
		);

		$this->submit( $page, 'one', [ 'api_key' => 'sk-live-DO-NOT-LEAK' ] );

		$this->assertStringNotContainsString( 'sk-live-DO-NOT-LEAK', (string) wp_json_encode( $page->get_values() ) );
		$this->assertSame( 'sk-live-DO-NOT-LEAK', $page->get_value( 'api_key' ) );
	}

	/**
	 * Saving again does not destroy an encrypted value.
	 *
	 * The stored form is ciphertext, and a whole-value write hands that
	 * ciphertext back. Encrypting it a second time leaves a value that
	 * decrypts to a `fkenc:` string, which is how a working licence key
	 * becomes nonsense on an unrelated save.
	 */
	public function test_an_encrypted_value_survives_a_whole_value_write(): void {
		if ( ! EncryptedContext::available() ) {
			$this->markTestSkipped( 'OpenSSL or the salts are unavailable.' );
		}

		$page = $this->page(
			[
				'fields' => [
					'api_key' => [
						'type'      => 'text',
						'tab'       => 'one',
						'encrypted' => true,
					],
				],
			]
		);

		$this->submit( $page, 'one', [ 'api_key' => 'sk-live-DO-NOT-LEAK' ] );

		$_POST = [];
		update_option( 'sf_demo', $page->get_values() );

		$this->assertSame( 'sk-live-DO-NOT-LEAK', $page->get_value( 'api_key' ) );
	}

	/**
	 * An export leaves encrypted values out.
	 */
	public function test_an_export_omits_encrypted_values(): void {
		if ( ! EncryptedContext::available() ) {
			$this->markTestSkipped( 'OpenSSL or the salts are unavailable.' );
		}

		$page = $this->page(
			[
				'fields' => [
					'first'   => [
						'type' => 'text',
						'tab'  => 'one',
					],
					'api_key' => [
						'type'      => 'text',
						'tab'       => 'one',
						'encrypted' => true,
					],
				],
			]
		);

		$this->submit(
			$page,
			'one',
			[
				'first'   => 'exported',
				'api_key' => 'sk-live-DO-NOT-LEAK',
			]
		);

		$payload = $page->export_payload();

		$this->assertSame( 'sf_demo', $payload['id'] );
		$this->assertSame( [ 'first' => 'exported' ], $payload['values'] );
	}

	/**
	 * An import file from another page is refused.
	 */
	public function test_an_import_from_another_page_is_refused(): void {
		$page = $this->page();

		$this->submit( $page, 'one', [ 'first' => 'original' ] );

		$this->import( [ 'id' => 'somewhere_else', 'values' => [ 'first' => 'overwritten' ] ] );

		$this->assertSame( 'original', $page->get_value( 'first' ) );
		$this->assertSame( 'import_failed', $GLOBALS['sf_errors'][0]['code'] );
	}

	/**
	 * An imported value is sanitized by its own field type.
	 */
	public function test_imported_values_are_sanitized(): void {
		$page = $this->page();

		$this->import(
			[
				'id'     => 'sf_demo',
				'values' => [
					'count'          => '9',
					'active_plugins' => [ 'evil/evil.php' ],
				],
			]
		);

		$this->assertSame( 9, $page->get_value( 'count' ) );
		$this->assertArrayNotHasKey( 'active_plugins', $page->get_values() );
	}

	/**
	 * A reset clears the tab it was asked for and no other.
	 */
	public function test_a_reset_clears_only_the_tab_it_was_asked_for(): void {
		$page = $this->page( [ 'reset_button' => true ] );

		$this->submit( $page, 'one', [ 'first' => 'gone' ] );
		$this->submit( $page, 'two', [ 'second' => 'kept' ] );

		$_POST = [ 'tab' => 'one' ];

		try {
			$page->handle_reset();
			$this->fail( 'handle_reset() should have redirected.' );
		} catch ( SF_Redirect $redirect ) {
			$this->assertStringContainsString( 'tab=one', $redirect->location );
		}

		$this->assertNull( $page->get_value( 'first' ) );
		$this->assertSame( 'kept', $page->get_value( 'second' ) );
	}

	/**
	 * A reset restores a field's configured default rather than emptying it.
	 */
	public function test_a_reset_restores_the_default(): void {
		$page = $this->page(
			[
				'fields' => [
					'first' => [
						'type'    => 'text',
						'tab'     => 'one',
						'default' => 'the-default',
					],
				],
			]
		);

		$this->submit( $page, 'one', [ 'first' => 'changed' ] );
		$this->assertSame( 'changed', $page->get_value( 'first' ) );

		$_POST = [ 'tab' => 'one' ];

		try {
			$page->handle_reset();
		} catch ( SF_Redirect $redirect ) {
			unset( $redirect );
		}

		$this->assertSame( 'the-default', $page->get_value( 'first' ) );
	}

	/**
	 * A page under a parent registers as a submenu, and one without does not.
	 */
	public function test_the_menu_matches_the_parent_it_was_given(): void {
		$this->page( [ 'parent_slug' => 'options-general.php' ] )->register_menu();

		$this->assertSame( 'options-general.php', $GLOBALS['sf_menus'][0]['parent'] );

		$GLOBALS['sf_menus'] = [];

		$this->page()->register_menu();

		$this->assertSame( '', $GLOBALS['sf_menus'][0]['parent'] );
	}

	/**
	 * The page renders a row for every field on the tab, and none for the rest.
	 */
	public function test_the_page_renders_only_the_current_tab(): void {
		$page = $this->page();

		$_GET['tab'] = 'two';

		$html = $this->render( $page );

		$this->assertStringContainsString( 'name="sf_demo[second]"', $html );
		$this->assertStringNotContainsString( 'name="sf_demo[first]"', $html );
	}

	/**
	 * The header is core's own, and carries the marker notices are moved to.
	 */
	public function test_the_page_renders_the_core_header(): void {
		$html = $this->render( $this->page() );

		$this->assertStringContainsString( 'privacy-settings-header', $html );
		$this->assertStringContainsString( '<hr class="wp-header-end">', $html );
		$this->assertStringContainsString( 'aria-current="true"', $html );
	}

	/**
	 * The submitted tab travels with the form.
	 *
	 * Without it the sanitize pass cannot tell a one-tab submission from a
	 * write of the whole value, and every other tab is cleared on save.
	 */
	public function test_the_form_carries_the_tab_it_is_saving(): void {
		$_GET['tab'] = 'two';

		$this->assertStringContainsString(
			'name="sf_demo_tab" value="two"',
			$this->render( $this->page() )
		);
	}

	/**
	 * A field with no section still appears.
	 */
	public function test_a_field_no_section_claimed_still_renders(): void {
		$page = $this->page(
			[
				'sections' => [
					'text' => [
						'title' => 'Text',
						'tab'   => 'one',
					],
				],
				'fields'   => [
					'first'   => [
						'type'    => 'text',
						'tab'     => 'one',
						'section' => 'text',
					],
					'orphan'  => [
						'type'    => 'text',
						'tab'     => 'one',
						'section' => 'typo',
					],
					'no_name' => [
						'type' => 'text',
						'tab'  => 'one',
					],
				],
			]
		);

		$html = $this->render( $page );

		$this->assertStringContainsString( 'name="sf_demo[first]"', $html );
		$this->assertStringContainsString( 'name="sf_demo[orphan]"', $html );
		$this->assertStringContainsString( 'name="sf_demo[no_name]"', $html );
	}

	/**
	 * A layout field spans the row rather than being labelled as a control.
	 */
	public function test_a_layout_field_spans_the_row(): void {
		$page = $this->page(
			[
				'fields' => [
					'intro' => [
						'type'  => 'heading',
						'tab'   => 'one',
						'label' => 'Section heading',
					],
				],
			]
		);

		$html = $this->render( $page );

		$this->assertStringContainsString( '<td colspan="2">', $html );
		$this->assertStringNotContainsString( '<th scope="row">', $html );
	}

	/**
	 * A user without the capability is refused, not shown the page.
	 */
	public function test_a_user_without_the_capability_is_refused(): void {
		$GLOBALS['fk_can'] = false;

		$this->expectException( \RuntimeException::class );

		$this->render( $this->page() );
	}

	/**
	 * The helper functions read through the registry.
	 */
	public function test_the_helper_functions_read_the_registered_page(): void {
		$page = $this->page();

		$this->submit( $page, 'one', [ 'first' => 'via-helper' ] );

		$this->assertSame( $page, get_setting_fields( 'sf_demo' ) );
		$this->assertSame( 'via-helper', get_setting_field_value( 'sf_demo', 'first' ) );
		$this->assertSame( 'fallback', get_setting_field_value( 'sf_demo', 'missing', 'fallback' ) );
		$this->assertSame( $page->get_values(), get_all_setting_values( 'sf_demo' ) );
	}

	/**
	 * A helper write goes through the same gate as the form.
	 */
	public function test_a_helper_write_is_sanitized_too(): void {
		$page = $this->page();

		update_setting_field_value( 'sf_demo', 'count', '13' );

		$this->assertSame( 13, $page->get_value( 'count' ) );

		// And a value its type cannot accept does not get stored as sent.
		update_setting_field_value( 'sf_demo', 'count', 'thirteen' );

		$this->assertSame( 0, $page->get_value( 'count' ) );
	}

	/**
	 * Render the page and return what it printed.
	 *
	 * @param SettingFields $page The page.
	 *
	 * @return string
	 */
	private function render( SettingFields $page ): string {
		ob_start();

		try {
			$page->render_page();
		} finally {
			$html = (string) ob_get_clean();
		}

		return $html;
	}

	/**
	 * Run an import of a decoded payload.
	 *
	 * @param array<string, mixed> $payload What the file contains.
	 *
	 * @return void
	 */
	private function import( array $payload ): void {
		// The decoded payload rather than an uploaded file: handle_import()
		// checks is_uploaded_file(), which is true of nothing a test can
		// create, and every decision worth asserting on is past that point.
		try {
			( Registry::instance()->get( 'sf_demo' ) )->apply_import(
				json_decode( (string) wp_json_encode( $payload ), true )
			);
		} catch ( SF_Redirect $redirect ) {
			unset( $redirect );
		}
	}
}
