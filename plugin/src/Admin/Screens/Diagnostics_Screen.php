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
 * Structured logging bootstrap (Logger_Interface, Error_Record) is available via container key `logger` / `diagnostics`; see diagnostics-contract.md.
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
		<div class="wrap aio-page-builder-screen aio-page-builder-diagnostics" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p class="aio-page-builder-notice"><?php \esc_html_e( 'Not yet implemented. This screen will show environment validation and diagnostics. Structured logging is available for internal use.', 'aio-page-builder' ); ?></p>
			<?php /* Future: ACF diagnostics panel surface via acf_diagnostics_service->get_full_payload(). */ ?>
			<div id="aio-diagnostics-acf-placeholder" class="aio-diagnostics-placeholder" data-acf-diagnostics="future" aria-hidden="true"></div>
			<?php /* Future: Rendering diagnostics (render_summary, assembly_summary, instantiation_readiness) via rendering_diagnostics_service. */ ?>
			<div id="aio-diagnostics-rendering-placeholder" class="aio-diagnostics-placeholder" data-rendering-diagnostics="future" aria-hidden="true"></div>
		</div>
		<?php
	}
}
