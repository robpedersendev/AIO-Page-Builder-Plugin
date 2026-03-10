<?php
/**
 * Registers the single top-level plugin menu and submenu pages.
 * Routes each page to a dedicated screen class (see admin-screen-inventory.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Screens\Dashboard_Screen;
use AIOPageBuilder\Admin\Screens\Diagnostics_Screen;
use AIOPageBuilder\Admin\Screens\Settings_Screen;

/**
 * Registers admin menu and submenus. Screen rendering is delegated to screen classes.
 */
final class Admin_Menu {

	private const PARENT_SLUG = 'aio-page-builder';

	/**
	 * Registers the top-level menu and Dashboard, Settings, Diagnostics submenus.
	 * Call from admin_menu action. Capability-aware; no mutation actions.
	 *
	 * @return void
	 */
	public function register(): void {
		$dashboard  = new Dashboard_Screen();
		$settings  = new Settings_Screen();
		$diagnostics = new Diagnostics_Screen();

		add_menu_page(
			__( 'AIO Page Builder', 'aio-page-builder' ),
			__( 'AIO Page Builder', 'aio-page-builder' ),
			$dashboard->get_capability(),
			self::PARENT_SLUG,
			array( $dashboard, 'render' ),
			'dashicons-admin-generic',
			59
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$dashboard->get_title(),
			__( 'Dashboard', 'aio-page-builder' ),
			$dashboard->get_capability(),
			Dashboard_Screen::SLUG,
			array( $dashboard, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$settings->get_title(),
			__( 'Settings', 'aio-page-builder' ),
			$settings->get_capability(),
			Settings_Screen::SLUG,
			array( $settings, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$diagnostics->get_title(),
			__( 'Diagnostics', 'aio-page-builder' ),
			$diagnostics->get_capability(),
			Diagnostics_Screen::SLUG,
			array( $diagnostics, 'render' )
		);
	}
}
