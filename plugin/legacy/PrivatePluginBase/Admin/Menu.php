<?php
/**
 * LEGACY — NOT LOADED BY ACTIVE PLUGIN.
 * Old admin menu. Active plugin uses AIOPageBuilder\Admin\Admin_Menu.
 * Quarantined in plugin/legacy/; see legacy/README.md.
 *
 * Admin menu registration.
 *
 * @package PrivatePluginBase
 */

declare(strict_types=1);

namespace PrivatePluginBase\Admin;

use PrivatePluginBase\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers admin menu and pages.
 */
final class Menu {

	/**
	 * Menu slug.
	 *
	 * @var string
	 */
	public const SLUG = 'private-plugin-base';

	/**
	 * Registers the admin menu.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
	}

	/**
	 * Adds the main menu page.
	 *
	 * @return void
	 */
	public static function add_menu_page(): void {
		add_menu_page(
			__( 'Private Plugin Base', 'private-plugin-base' ),
			__( 'Private Plugin Base', 'private-plugin-base' ),
			Capabilities::MANAGE,
			self::SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-admin-generic',
			30
		);
	}

	/**
	 * Renders the main settings page.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p><?php esc_html_e( 'Plugin settings and options.', 'private-plugin-base' ); ?></p>
			<?php settings_errors( Settings\Page::OPTION_GROUP ); ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( Settings\Page::OPTION_GROUP );
				do_settings_sections( self::SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
