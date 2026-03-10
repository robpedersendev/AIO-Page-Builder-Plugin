<?php
/**
 * Uninstall handler.
 *
 * Runs only when the plugin is uninstalled via WordPress (WP_UNINSTALL_PLUGIN defined).
 * Delegates to Lifecycle_Manager::uninstall() for orchestration. Currently non-destructive;
 * preserves built-page survivability and export-before-cleanup pathway (see lifecycle-contract.md).
 *
 * @package AIOPageBuilder
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/src/Bootstrap/Constants.php';
\AIOPageBuilder\Bootstrap\Constants::init();
require_once __DIR__ . '/src/Bootstrap/Lifecycle_Manager.php';

$lifecycle = new \AIOPageBuilder\Bootstrap\Lifecycle_Manager();
$lifecycle->uninstall();

// No deletion in this implementation. Cleanup will be added when export/restore contract exists.
