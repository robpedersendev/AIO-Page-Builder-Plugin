<?php
/**
 * Settings admin screen. Plugin settings and reporting disclosure.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens;

defined( 'ABSPATH' ) || exit;

/**
 * Placeholder Settings screen. Renders title and not-yet-implemented notice.
 */
final class Settings_Screen {

	public const SLUG = 'aio-page-builder-settings';

	/** Placeholder until capability mapping is finalized. */
	private const CAPABILITY = 'manage_options';

	public function get_title(): string {
		return __( 'Settings', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return self::CAPABILITY;
	}

	/**
	 * Renders the screen. No business logic; markup only.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<div class="wrap aio-page-builder-screen aio-page-builder-settings">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p class="aio-page-builder-notice"><?php \esc_html_e( 'Not yet implemented. This screen will show plugin settings and reporting disclosure.', 'aio-page-builder' ); ?></p>
		</div>
		<?php
	}
}
