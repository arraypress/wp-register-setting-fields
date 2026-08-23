<?php
/**
 * PHPUnit bootstrap.
 *
 * @package ArrayPress\RegisterSettingFields
 */

declare( strict_types=1 );

/*
 * Dependencies guard their files-autoloaded entrypoints with an ABSPATH
 * check. Composer runs those on require of the autoloader, so the constant
 * has to exist before it or their helpers are never declared.
 */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
