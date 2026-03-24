<?php
/**
 * Registers wp-admin styles for plugin screens.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin;

use AIOPageBuilder\Admin\Screens\Templates\Page_Template_Detail_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Section_Template_Detail_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Template_Compare_Screen;
use AIOPageBuilder\Bootstrap\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues admin CSS on AIO Page Builder admin pages only.
 */
final class Admin_Assets {

	public const STYLE_HANDLE = 'aio-page-builder-admin';

	public const SCRIPT_TEMPLATE_COMPARE = 'aio-template-compare';

	public const SCRIPT_TEMPLATE_LIVE_PREVIEW = 'aio-template-live-preview';

	public const STYLE_TEMPLATE_LIVE_PREVIEW = 'aio-template-live-preview';

	/**
	 * Hooks admin_enqueue_scripts.
	 *
	 * @return void
	 */
	public static function register(): void {
		\add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ), 10 );
		\add_action( 'admin_head', array( self::class, 'print_hidden_template_library_submenu_css' ), 99 );
	}

	/**
	 * Hides detail/doc submenu rows that use empty titles (kept registered so Core can enforce ACCESS_TEMPLATE_LIBRARY).
	 *
	 * @return void
	 */
	public static function print_hidden_template_library_submenu_css(): void {
		if ( ! \is_admin() ) {
			return;
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static CSS; no user input.
		echo '<style id="aio-hidden-template-library-detail-submenus">#adminmenu .wp-submenu li:has(> a[href*="page=aio-page-builder-section-template-detail"]),#adminmenu .wp-submenu li:has(> a[href*="page=aio-page-builder-page-template-detail"]),#adminmenu .wp-submenu li:has(> a[href*="page=aio-page-builder-documentation-detail"]){display:none !important;}</style>';
	}

	/**
	 * Loads the admin stylesheet when the active screen belongs to this plugin.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public static function enqueue( string $hook_suffix ): void {
		unset( $hook_suffix );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing check for asset loading.
		if ( ! isset( $_GET['page'] ) ) {
			return;
		}
		$page = \sanitize_key( (string) \wp_unslash( $_GET['page'] ) );
		if ( $page === '' || \strpos( $page, 'aio-page-builder' ) !== 0 ) {
			return;
		}
		$url = \trailingslashit( Constants::plugin_url() ) . 'assets/css/aio-page-builder-admin.css';
		\wp_enqueue_style(
			self::STYLE_HANDLE,
			$url,
			array(),
			Constants::plugin_version()
		);

		$js = \trailingslashit( Constants::plugin_url() ) . 'assets/js/aio-template-compare.js';
		\wp_enqueue_script(
			self::SCRIPT_TEMPLATE_COMPARE,
			$js,
			array(),
			Constants::plugin_version(),
			true
		);
		\wp_localize_script(
			self::SCRIPT_TEMPLATE_COMPARE,
			'aioTemplateCompare',
			array(
				'ajaxUrl' => \admin_url( 'admin-ajax.php' ),
				'nonce'   => \wp_create_nonce( Template_Compare_Screen::NONCE_AJAX_ACTION ),
				'action'  => 'aio_template_compare_mutate',
				'i18n'    => array(
					'add'    => \__( 'Add to compare', 'aio-page-builder' ),
					'remove' => \__( 'Remove from compare', 'aio-page-builder' ),
					'error'  => \__( 'Could not update the compare list.', 'aio-page-builder' ),
				),
			)
		);

		if ( $page === Page_Template_Detail_Screen::SLUG || $page === Section_Template_Detail_Screen::SLUG ) {
			$pv_css = \trailingslashit( Constants::plugin_url() ) . 'assets/css/admin/template-live-preview.css';
			\wp_enqueue_style(
				self::STYLE_TEMPLATE_LIVE_PREVIEW,
				$pv_css,
				array( self::STYLE_HANDLE ),
				Constants::plugin_version()
			);
			$pv_js = \trailingslashit( Constants::plugin_url() ) . 'assets/js/admin/template-live-preview.js';
			\wp_enqueue_script(
				self::SCRIPT_TEMPLATE_LIVE_PREVIEW,
				$pv_js,
				array(),
				Constants::plugin_version(),
				true
			);
		}
	}
}
