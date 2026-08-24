<?php
/**
 * PHPUnit bootstrap.
 *
 * @package ArrayPress\RegisterSettingFields
 */

declare( strict_types=1 );

require_once __DIR__ . '/stubs.php';

/*
 * The kit's own stubs cover everything the field layer touches. Ours are
 * required first so that where the two overlap — update_option above all —
 * this file's version is the one that gets defined, since every stub is
 * guarded by function_exists().
 */
require_once dirname( __DIR__ ) . '/vendor/arraypress/wp-field-kit/tests/stubs.php';

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
