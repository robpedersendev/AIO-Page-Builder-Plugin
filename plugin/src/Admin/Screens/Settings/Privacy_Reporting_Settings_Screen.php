<?php
/**
 * Privacy, Reporting & Settings admin screen (spec §49.12, §46.11, §47).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Settings;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Reporting\UI\Privacy_Settings_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

/**
 * Renders reporting disclosure, retention, uninstall/export, environment, version, destination, privacy helper.
 * No secrets. Mutating actions (if any) must be nonce-protected at handler level.
 */
final class Privacy_Reporting_Settings_Screen {

	public const SLUG = 'aio-page-builder-privacy-reporting';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Privacy, Reporting & Settings', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::MANAGE_REPORTING_AND_PRIVACY;
	}

	/**
	 * Renders the screen. Capability enforced by menu; screen checks again.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to manage privacy and reporting settings.', 'aio-page-builder' ), 403 );
		}
		$state = $this->get_state();
		?>
		<div class="wrap aio-page-builder-screen aio-privacy-reporting" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>

			<section class="aio-disclosure" aria-labelledby="aio-reporting-disclosure-heading">
				<h2 id="aio-reporting-disclosure-heading"><?php \esc_html_e( 'Reporting disclosure', 'aio-page-builder' ); ?></h2>
				<?php foreach ( $state['reporting_disclosure'] as $block ) : ?>
					<div class="aio-disclosure-block notice notice-info inline" style="margin: 1em 0;">
						<p><strong><?php echo \esc_html( $block['heading'] ); ?></strong></p>
						<p><?php echo \esc_html( $block['content'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</section>

			<section class="aio-retention" aria-labelledby="aio-retention-heading">
				<h2 id="aio-retention-heading"><?php \esc_html_e( 'Retention', 'aio-page-builder' ); ?></h2>
				<p><?php echo \esc_html( $state['retention_state']['reporting_log_summary'] ); ?></p>
				<p><?php echo \esc_html( $state['retention_state']['retention_note'] ); ?></p>
			</section>

			<section class="aio-uninstall-export" aria-labelledby="aio-uninstall-heading">
				<h2 id="aio-uninstall-heading"><?php \esc_html_e( 'Uninstall / export behavior', 'aio-page-builder' ); ?></h2>
				<p><?php echo \esc_html( $state['uninstall_export_state']['prefs_summary'] ); ?></p>
				<p><?php echo \esc_html( $state['uninstall_export_state']['built_pages_message'] ); ?></p>
				<p><?php \esc_html_e( 'Uninstall choices:', 'aio-page-builder' ); ?></p>
				<ul>
					<?php foreach ( $state['uninstall_export_state']['choices'] as $choice ) : ?>
						<li><strong><?php echo \esc_html( $choice['label'] ); ?></strong> — <?php echo \esc_html( $choice['description'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			</section>

			<section class="aio-environment-version" aria-labelledby="aio-env-heading">
				<h2 id="aio-env-heading"><?php \esc_html_e( 'Environment & version', 'aio-page-builder' ); ?></h2>
				<p><?php \esc_html_e( 'Plugin version:', 'aio-page-builder' ); ?> <strong><?php echo \esc_html( $state['version_summary']['plugin_version'] ); ?></strong></p>
				<p><?php \esc_html_e( 'PHP:', 'aio-page-builder' ); ?> <?php echo \esc_html( $state['environment_summary']['php_version'] ); ?> — <?php \esc_html_e( 'WordPress:', 'aio-page-builder' ); ?> <?php echo \esc_html( $state['environment_summary']['wp_version'] ); ?></p>
			</section>

			<section class="aio-report-destination" aria-labelledby="aio-destination-heading">
				<h2 id="aio-destination-heading"><?php \esc_html_e( 'Report destination', 'aio-page-builder' ); ?></h2>
				<p><strong><?php echo \esc_html( $state['report_destination_summary']['transport_type'] ); ?></strong>: <?php echo \esc_html( $state['report_destination_summary']['description'] ); ?></p>
			</section>

			<section class="aio-privacy-helper" aria-labelledby="aio-privacy-helper-heading">
				<h2 id="aio-privacy-helper-heading"><?php \esc_html_e( 'Privacy policy helper text', 'aio-page-builder' ); ?></h2>
				<p><?php \esc_html_e( 'You may use or adapt the following when drafting your site’s privacy policy:', 'aio-page-builder' ); ?></p>
				<blockquote style="margin: 1em 0; padding: 1em; background: #f5f5f5; border-left: 4px solid #0073aa;"><?php echo \esc_html( $state['privacy_helper_text'] ); ?></blockquote>
			</section>

			<?php if ( $state['diagnostics_verbosity_allowed'] ) : ?>
			<section class="aio-diagnostics-verbosity" aria-labelledby="aio-verbosity-heading">
				<h2 id="aio-verbosity-heading"><?php \esc_html_e( 'Diagnostics verbosity', 'aio-page-builder' ); ?></h2>
				<p><?php \esc_html_e( 'Controls are available when policy allows.', 'aio-page-builder' ); ?></p>
			</section>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_state(): array {
		$builder = $this->get_state_builder();
		return $builder->build();
	}

	private function get_state_builder(): Privacy_Settings_State_Builder {
		if ( $this->container && $this->container->has( 'privacy_settings_state_builder' ) ) {
			return $this->container->get( 'privacy_settings_state_builder' );
		}
		$settings = $this->container && $this->container->has( 'settings' ) ? $this->container->get( 'settings' ) : new Settings_Service();
		return new Privacy_Settings_State_Builder( $settings );
	}
}
