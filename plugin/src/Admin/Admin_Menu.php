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

use AIOPageBuilder\Admin\Screens\AI\AI_Runs_Screen;
use AIOPageBuilder\Admin\Screens\AI\Onboarding_Screen;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Comparison_Screen;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Sessions_Screen;
use AIOPageBuilder\Admin\Screens\Dashboard_Screen;
use AIOPageBuilder\Admin\Screens\Diagnostics_Screen;
use AIOPageBuilder\Admin\Screens\Settings_Screen;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Registers admin menu and submenus. Screen rendering is delegated to screen classes.
 */
final class Admin_Menu {

	private const PARENT_SLUG = 'aio-page-builder';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	/**
	 * Registers the top-level menu and Dashboard, Settings, Diagnostics, Crawler submenus.
	 * Call from admin_menu action. Capability-aware; no mutation actions.
	 *
	 * @return void
	 */
	public function register(): void {
		$dashboard   = new Dashboard_Screen();
		$settings    = new Settings_Screen();
		$diagnostics = new Diagnostics_Screen();
		$onboarding  = new Onboarding_Screen( $this->container );
		$crawler_sessions  = new Crawler_Sessions_Screen( $this->container );
		$crawler_comparison = new Crawler_Comparison_Screen( $this->container );
		$ai_runs            = new AI_Runs_Screen( $this->container );

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

		add_submenu_page(
			self::PARENT_SLUG,
			$onboarding->get_title(),
			__( 'Onboarding & Profile', 'aio-page-builder' ),
			$onboarding->get_capability(),
			Onboarding_Screen::SLUG,
			array( $onboarding, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$crawler_sessions->get_title(),
			__( 'Crawl Sessions', 'aio-page-builder' ),
			$crawler_sessions->get_capability(),
			Crawler_Sessions_Screen::SLUG,
			array( $crawler_sessions, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$crawler_comparison->get_title(),
			__( 'Crawl Comparison', 'aio-page-builder' ),
			$crawler_comparison->get_capability(),
			Crawler_Comparison_Screen::SLUG,
			array( $crawler_comparison, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$ai_runs->get_title(),
			__( 'AI Runs', 'aio-page-builder' ),
			$ai_runs->get_capability(),
			AI_Runs_Screen::SLUG,
			array( $ai_runs, 'render' )
		);
	}
}
