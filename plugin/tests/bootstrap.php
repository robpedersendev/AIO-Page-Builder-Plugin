<?php
/**
 * PHPUnit bootstrap.
 *
 * @package PrivatePluginBase
 */

// Composer autoload.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Define ABSPATH if not in WordPress context.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/wordpress/' );
}
