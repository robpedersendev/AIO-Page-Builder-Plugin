<?php
/**
 * Diagnostics admin screen. Environment status and validation summary.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens;

defined( 'ABSPATH' ) || exit;

/**
 * Placeholder Diagnostics screen. Renders title and not-yet-implemented notice.
 */
final class Diagnostics_Screen {

	public const SLUG = 'aio-page-builder-diagnostics';

	/** Placeholder until capability mapping is finalized. */
	private const CAPABILITY = 'manage_options';

	public function get_title(): string {
		return __( 'Diagnostics', 'aio-page-builder' );
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
		<div class="wrap aio-page-builder-screen aio-page-builder-diagnostics">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p class="aio-page-builder-notice"><?php \esc_html_e( 'Not yet implemented. This screen will show environment validation and diagnostics.', 'aio-page-builder' ); ?></p>
		</div>
		<?php
	}
}
