<?php
/**
 * Single entry point for registering admin_post_* handlers.
 * wp-admin/admin-post.php runs admin_init then dispatches admin_post_{$action}; it never runs admin_menu.
 * Any handler for forms posting to admin-post.php must be registered here (via admin_init priority 0 in Plugin).
 *
 * Covered groups: Admin_Menu (seeds, industry, bundles, guided repair, overrides, onboarding/build-plan reset), Queue_Logs_Screen,
 * Import_Export_Screen, Profile_Snapshot_History_Panel (via Admin_Menu::register_admin_post_actions).
 * Call only from {@see \AIOPageBuilder\Bootstrap\Plugin::register_admin_post_handlers()} with the container from {@see \AIOPageBuilder\Bootstrap\Plugin::run()}.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Actions\Template_Lab_Canonical_Admin_Actions;
use AIOPageBuilder\Admin\Screens\ImportExport\Import_Export_Screen;
use AIOPageBuilder\Admin\Screens\Logs\Queue_Logs_Screen;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Wires all admin-post.php form handlers before WordPress checks has_action().
 */
final class Admin_Post_Handler_Registrar {

	/**
	 * Registers every admin_post_* callback used by the plugin.
	 *
	 * Requires the real bootstrap {@see \AIOPageBuilder\Bootstrap\Plugin} container. Passing null is not supported;
	 * admin-post handlers would not register and form POSTs to admin-post.php would not dispatch.
	 *
	 * @param Service_Container $container Plugin service container from {@see \AIOPageBuilder\Bootstrap\Plugin::run()}.
	 * @return void
	 */
	public static function register_all( Service_Container $container ): void {
		$menu = new Admin_Menu( $container );
		$menu->register_admin_post_actions();

		$queue_logs = new Queue_Logs_Screen( $container );
		$queue_logs->register_admin_post_handlers();

		$import_export = new Import_Export_Screen( $container );
		$import_export->register_admin_post_handlers();

		\add_action(
			'admin_post_' . Template_Lab_Canonical_Admin_Actions::ACTION_APPROVE,
			static function () use ( $container ): void {
				Template_Lab_Canonical_Admin_Actions::handle_approve( $container );
			},
			10
		);
		\add_action(
			'admin_post_' . Template_Lab_Canonical_Admin_Actions::ACTION_APPLY,
			static function () use ( $container ): void {
				Template_Lab_Canonical_Admin_Actions::handle_apply( $container );
			},
			10
		);
	}
}
