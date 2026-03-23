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
require_once __DIR__ . '/src/Infrastructure/Config/Capabilities.php';
require_once __DIR__ . '/src/Bootstrap/Capability_Registrar.php';
require_once __DIR__ . '/src/Bootstrap/Lifecycle_Manager.php';

$aio_page_builder_lifecycle = new \AIOPageBuilder\Bootstrap\Lifecycle_Manager();
$aio_page_builder_lifecycle->uninstall();

\AIOPageBuilder\Bootstrap\Capability_Registrar::remove_from_all_roles();

// Cleanup (scheduled events, plugin options, custom tables, plugin CPTs, ACF section-key cache transients) runs in Lifecycle_Manager::uninstall().
// Built pages (post type 'page') are never deleted. ACF field values (post meta) and handed-off native ACF field groups are preserved; see docs/operations/acf-uninstall-retained-data-matrix.md and docs/contracts/acf-uninstall-retention-contract.md. Export prompt runs from admin Uninstall screen only.
