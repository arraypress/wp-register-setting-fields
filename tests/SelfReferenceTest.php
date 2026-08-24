<?php
/**
 * Self-reference tests.
 *
 * @package ArrayPress\RegisterSettingFields
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Tests;

use ArrayPress\FieldKit\Tests\SelfReferences;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/vendor/arraypress/wp-field-kit/tests/SelfReferences.php';

/**
 * Does every `self::` in this library point at something that exists?
 *
 * PHP will not tell you, `php -l` will not tell you, and a suite that never
 * reaches the line will not either — it is a fatal at the moment of use. The
 * checker is the kit's, because every one of these libraries wants this and
 * none of them should carry its own copy of it.
 */
final class SelfReferenceTest extends TestCase {

	/**
	 * Every `self::` and `static::` reference in the library resolves.
	 */
	public function test_every_self_reference_resolves(): void {
		$broken = SelfReferences::broken( dirname( __DIR__ ) . '/src' );

		$this->assertSame( [], $broken, "Undeclared:\n  " . implode( "\n  ", $broken ) );
	}
}
