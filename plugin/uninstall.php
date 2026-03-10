<?php
/**
 * Uninstall handler.
 *
 * Runs only when the plugin is uninstalled via WordPress (WP_UNINSTALL_PLUGIN defined).
 * Currently performs no data deletion. Preserves built-page survivability.
 * Future: may remove plugin-owned operational data only; must not delete user content or built pages.
 *
 * @package AIOPageBuilder
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Stub: no destructive behavior. Capability removal, option deletion, and table cleanup
// will be implemented in a later prompt with explicit uninstall contract and survivability rules.
