<?php
/**
 * Provider dependency health dashboard (Prompt 239, spec §0.10.11, §49.11, §59.12).
 * Observational: provider availability, section/page counts using forms, links to template diagnostics.
 * Internal-only; no secrets; capability-gated.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Diagnostics;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Reporting\FormProvider\Form_Provider_Health_Summary_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders provider-backed form health summary. Read-only; no mutation.
 */
final class Form_Provider_Health_Screen {

	public const SLUG = 'aio-page-builder-form-provider-health';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Form Provider Health', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Renders the screen. Capability enforced by menu registration.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access form provider health.', 'aio-page-builder' ), 403 );
		}
		$summary = $this->build_summary();
		?>
		<div class="wrap aio-page-builder-screen aio-form-provider-health" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p class="aio-form-provider-health-description">
				<?php \esc_html_e( 'Provider-backed form usage: availability, section and page template counts, and links to template diagnostics. Observational only.', 'aio-page-builder' ); ?>
			</p>
			<?php $this->render_provider_availability( $summary['provider_availability'] ?? array() ); ?>
			<?php $this->render_registered_providers( $summary['registered_provider_ids'] ?? array() ); ?>
			<?php $this->render_usage_counts( $summary ); ?>
			<?php $this->render_recent_failures( $summary['recent_failures_summary'] ?? array() ); ?>
			<?php $this->render_links( $summary ); ?>
			<p class="aio-form-provider-health-built-at" style="margin-top: 1.5em; color: #666;">
				<?php echo \esc_html( sprintf( __( 'Summary built at %s (UTC).', 'aio-page-builder' ), $summary['built_at'] ?? '' ) ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * @return array<string, mixed>
	 */
	private function build_summary(): array {
		$service = $this->container && $this->container->has( 'form_provider_health_summary_service' )
			? $this->container->get( 'form_provider_health_summary_service' )
			: null;
		if ( ! $service instanceof Form_Provider_Health_Summary_Service ) {
			return array(
				'provider_availability'              => array(),
				'registered_provider_ids'            => array(),
				'section_templates_with_forms_count' => 0,
				'page_templates_using_forms_count'   => 0,
				'recent_failures_summary'            => array(),
				'built_at'                           => gmdate( 'Y-m-d\TH:i:s\Z' ),
			);
		}
		return $service->build_summary();
	}

	/**
	 * @param list<array{provider_key: string, status: string, message: string|null}> $rows Provider availability rows.
	 * @return void
	 */
	private function render_provider_availability( array $rows ): void {
		?>
		<div class="aio-form-provider-health-card card" style="max-width: 800px; padding: 1em; margin: 1em 0;">
			<h2 class="aio-form-provider-health-section-title"><?php \esc_html_e( 'Provider availability', 'aio-page-builder' ); ?></h2>
			<?php if ( $rows === array() ) : ?>
				<p><?php \esc_html_e( 'No provider availability data (availability service not configured).', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php \esc_html_e( 'Provider', 'aio-page-builder' ); ?></th>
							<th><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
							<th><?php \esc_html_e( 'Message', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $row ) : ?>
							<tr>
								<td><?php echo \esc_html( (string) ( $row['provider_key'] ?? '' ) ); ?></td>
								<td><span class="aio-fp-status aio-fp-status-<?php echo \esc_attr( (string) ( $row['status'] ?? '' ) ); ?>"><?php echo \esc_html( (string) ( $row['status'] ?? '' ) ); ?></span></td>
								<td><?php echo $row['message'] !== null ? \esc_html( $row['message'] ) : '—'; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @param array<int, string> $ids Provider IDs to display.
	 * @return void
	 */
	private function render_registered_providers( array $ids ): void {
		?>
		<div class="aio-form-provider-health-card card" style="max-width: 800px; padding: 1em; margin: 1em 0;">
			<h2 class="aio-form-provider-health-section-title"><?php \esc_html_e( 'Registered providers', 'aio-page-builder' ); ?></h2>
			<p><?php echo \esc_html( implode( ', ', $ids ) ?: __( 'None', 'aio-page-builder' ) ); ?></p>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed> $summary Summary counts (section_templates_with_forms_count, etc.).
	 * @return void
	 */
	private function render_usage_counts( array $summary ): void {
		$section_count = (int) ( $summary['section_templates_with_forms_count'] ?? 0 );
		$page_count    = (int) ( $summary['page_templates_using_forms_count'] ?? 0 );
		?>
		<div class="aio-form-provider-health-card card" style="max-width: 800px; padding: 1em; margin: 1em 0;">
			<h2 class="aio-form-provider-health-section-title"><?php \esc_html_e( 'Templates using provider-backed forms', 'aio-page-builder' ); ?></h2>
			<table class="widefat striped">
				<tbody>
					<tr>
						<td><strong><?php \esc_html_e( 'Section templates (form_embed)', 'aio-page-builder' ); ?></strong></td>
						<td><?php echo \esc_html( (string) $section_count ); ?></td>
					</tr>
					<tr>
						<td><strong><?php \esc_html_e( 'Page templates using form sections', 'aio-page-builder' ); ?></strong></td>
						<td><?php echo \esc_html( (string) $page_count ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * @param list<array{domain: string, count: int, link_label: string}> $failures Failure items to display.
	 * @return void
	 */
	private function render_recent_failures( array $failures ): void {
		if ( $failures === array() ) {
			return;
		}
		?>
		<div class="aio-form-provider-health-card card" style="max-width: 800px; padding: 1em; margin: 1em 0;">
			<h2 class="aio-form-provider-health-section-title"><?php \esc_html_e( 'Provider-related attention', 'aio-page-builder' ); ?></h2>
			<ul>
				<?php foreach ( $failures as $f ) : ?>
					<li><?php echo \esc_html( (string) ( $f['count'] ?? 0 ) ); ?> <?php echo \esc_html( (string) ( $f['link_label'] ?? '' ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed> $summary Summary data for link context.
	 * @return void
	 */
	private function render_links( array $summary ): void {
		$base        = \admin_url( 'admin.php' );
		$section_url = \add_query_arg( array( 'page' => 'aio-page-builder-section-templates' ), $base );
		$page_url    = \add_query_arg( array( 'page' => 'aio-page-builder-page-templates' ), $base );
		?>
		<div class="aio-form-provider-health-links card" style="max-width: 800px; padding: 1em; margin: 1em 0;">
			<h2 class="aio-form-provider-health-section-title"><?php \esc_html_e( 'Related screens', 'aio-page-builder' ); ?></h2>
			<ul>
				<li><a href="<?php echo \esc_url( $section_url ); ?>"><?php \esc_html_e( 'Section Templates', 'aio-page-builder' ); ?></a> — <?php \esc_html_e( 'Browse section templates (filter by form_embed for form sections).', 'aio-page-builder' ); ?></li>
				<li><a href="<?php echo \esc_url( $page_url ); ?>"><?php \esc_html_e( 'Page Templates', 'aio-page-builder' ); ?></a> — <?php \esc_html_e( 'Browse page templates (request-form and compositions with form sections).', 'aio-page-builder' ); ?></li>
			</ul>
		</div>
		<?php
	}
}
