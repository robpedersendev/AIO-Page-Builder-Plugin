<?php
/**
 * Uninstall handler.
 *
 * Removes plugin-owned operational data only. Preserves built content.
 *
 * @package PrivatePluginBase
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Remove capability from all roles.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';
\PrivatePluginBase\Security\Capabilities::remove_from_all();

// Remove plugin options.
delete_option( 'private_plugin_base_meta' );
delete_option( 'private_plugin_base_options' );

// Intentionally preserve built content. No user-created data removed.
