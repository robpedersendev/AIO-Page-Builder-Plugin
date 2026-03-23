<?php
/**
 * Post-release health review screen (spec §45, §49.11, §59.15, §60.8; Prompt 131).
 *
 * Internal operational review: reporting health, queue, Build Plan trends, AI run validity,
 * rollback, import/export, support-package. Observational only; links to authoritative screens.
 * Optional export of summary to support-safe format.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Operations;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Analytics\Build_Plan_Analytics_Service;
use AIOPageBuilder\Domain\Reporting\UI\Post_Release_Health_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders post-release health dashboard. Permission-gated; redacted; no mutation.
 */
final class Post_Release_Health_Screen {

	public const SLUG = 'aio-page-builder-post-release-health';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Post-Release Health', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Renders the screen. Capability enforced by menu registration.
	 *
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access the post-release health review.', 'aio-page-builder' ), 403 );
		}

		$date_from     = isset( $_GET['date_from'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['date_from'] ) ) : '';
		$date_to       = isset( $_GET['date_to'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['date_to'] ) ) : '';
		$export_action = isset( $_GET['export_summary'] ) && $_GET['export_summary'] === '1';

		if ( $export_action ) {
			$this->export_summary( $date_from !== '' ? $date_from : null, $date_to !== '' ? $date_to : null );
			return;
		}

		$state   = $this->build_state( $date_from !== '' ? $date_from : null, $date_to !== '' ? $date_to : null );
		$summary = $state['post_release_health_summary'] ?? array();
		$scores  = $state['domain_health_scores'] ?? array();
		$items   = $state['recommended_investigation_items'] ?? array();
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-post-release-health" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="aio-post-release-health-description"><?php \esc_html_e( 'Internal operational review for the selected period. Use links to open authoritative screens. No automatic product changes.', 'aio-page-builder' ); ?></p>

			<form method="get" action="<?php echo \esc_url( \admin_url( 'admin.php' ) ); ?>" class="aio-post-release-health-filter" style="margin: 1em 0;">
				<input type="hidden" name="page" value="<?php echo \esc_attr( self::SLUG ); ?>" />
				<label for="date_from"><?php \esc_html_e( 'From (Y-m-d):', 'aio-page-builder' ); ?></label>
				<input type="date" id="date_from" name="date_from" value="<?php echo \esc_attr( $summary['period_start'] ?? '' ); ?>" />
				<label for="date_to"><?php \esc_html_e( 'To (Y-m-d):', 'aio-page-builder' ); ?></label>
				<input type="date" id="date_to" name="date_to" value="<?php echo \esc_attr( $summary['period_end'] ?? '' ); ?>" />
				<button type="submit" class="button button-primary"><?php \esc_html_e( 'Apply', 'aio-page-builder' ); ?></button>
				<a href="
				<?php
				echo \esc_url(
					\add_query_arg(
						array(
							'page'           => self::SLUG,
							'export_summary' => '1',
							'date_from'      => $summary['period_start'] ?? '',
							'date_to'        => $summary['period_end'] ?? '',
						),
						\admin_url( 'admin.php' )
					)
				);
				?>
							" class="button"><?php \esc_html_e( 'Export summary (JSON)', 'aio-page-builder' ); ?></a>
			</form>

			<section class="aio-post-release-summary" style="margin: 1.5em 0;" aria-labelledby="aio-health-summary-heading">
				<h2 id="aio-health-summary-heading"><?php \esc_html_e( 'Summary', 'aio-page-builder' ); ?></h2>
				<p><strong><?php \esc_html_e( 'Period:', 'aio-page-builder' ); ?></strong> <?php echo \esc_html( (string) ( $summary['period_start'] ?? '' ) ); ?> — <?php echo \esc_html( (string) ( $summary['period_end'] ?? '' ) ); ?></p>
				<p><strong><?php \esc_html_e( 'Overall status:', 'aio-page-builder' ); ?></strong> <?php echo \esc_html( (string) ( $summary['overall_status'] ?? '' ) ); ?></p>
				<p><?php echo \esc_html( (string) ( $summary['summary_message'] ?? '' ) ); ?></p>
			</section>

			<section class="aio-post-release-domains" style="margin: 1.5em 0;" aria-labelledby="aio-health-domains-heading">
				<h2 id="aio-health-domains-heading"><?php \esc_html_e( 'Domain health scores', 'aio-page-builder' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead><tr><th><?php \esc_html_e( 'Domain', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Message', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Link', 'aio-page-builder' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( $scores as $domain => $score ) : ?>
							<tr>
								<td><?php echo \esc_html( $domain ); ?></td>
								<td><?php echo \esc_html( (string) ( $score['score_label'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $score['message'] ?? '' ) ); ?></td>
								<td><a href="<?php echo \esc_url( (string) ( $score['link_url'] ?? '#' ) ); ?>"><?php echo \esc_html( (string) ( $score['link_label'] ?? '' ) ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</section>

			<section class="aio-post-release-recommended" style="margin: 1.5em 0;" aria-labelledby="aio-health-recommended-heading">
				<h2 id="aio-health-recommended-heading"><?php \esc_html_e( 'Recommended investigation items', 'aio-page-builder' ); ?></h2>
				<?php if ( empty( $items ) ) : ?>
					<p><?php \esc_html_e( 'No recommended items for this period.', 'aio-page-builder' ); ?></p>
				<?php else : ?>
					<ul class="aio-post-release-items" style="list-style: none; padding-left: 0;">
						<?php foreach ( $items as $item ) : ?>
							<li class="aio-post-release-item" style="border-left: 4px solid #72aee6; padding: 0.5em 0.75em; margin: 0.25em 0; background: #f0f6fc;">
								<strong><?php echo \esc_html( (string) ( $item['title'] ?? '' ) ); ?></strong> (<?php echo \esc_html( (string) ( $item['priority'] ?? '' ) ); ?>)
								<p style="margin: 0.25em 0;"><?php echo \esc_html( (string) ( $item['message'] ?? '' ) ); ?></p>
								<a href="<?php echo \esc_url( (string) ( $item['link_url'] ?? '#' ) ); ?>"><?php echo \esc_html( (string) ( $item['link_label'] ?? '' ) ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>

			<p><a href="<?php echo \esc_url( \admin_url( 'admin.php?page=aio-page-builder-support-triage' ) ); ?>"><?php \esc_html_e( '&larr; Support Triage', 'aio-page-builder' ); ?></a></p>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * @param string|null $date_from
	 * @param string|null $date_to
	 * @return array<string, mixed>
	 */
	private function build_state( ?string $date_from, ?string $date_to ): array {
		$job_repo  = $this->container && $this->container->has( 'job_queue_repository' ) ? $this->container->get( 'job_queue_repository' ) : null;
		$ai_repo   = $this->container && $this->container->has( 'ai_run_repository' ) ? $this->container->get( 'ai_run_repository' ) : null;
		$plan_repo = $this->container && $this->container->has( 'build_plan_repository' ) ? $this->container->get( 'build_plan_repository' ) : null;
		$analytics = $this->container && $this->container->has( 'build_plan_analytics_service' ) ? $this->container->get( 'build_plan_analytics_service' ) : null;
		if ( ! $analytics instanceof Build_Plan_Analytics_Service ) {
			$analytics = null;
		}
		$builder = new Post_Release_Health_State_Builder( $job_repo, $ai_repo, $plan_repo, $analytics );
		return $builder->build( $date_from, $date_to );
	}

	private function export_summary( ?string $date_from, ?string $date_to ): void {
		$state   = $this->build_state( $date_from, $date_to );
		$payload = array(
			'post_release_health_summary'     => $state['post_release_health_summary'] ?? array(),
			'domain_health_scores'            => $state['domain_health_scores'] ?? array(),
			'recommended_investigation_items' => $state['recommended_investigation_items'] ?? array(),
			'exported_at'                     => gmdate( 'Y-m-d\TH:i:s\Z' ),
		);
		$json    = \wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( $json === false ) {
			return;
		}
		\header( 'Content-Type: application/json; charset=utf-8' );
		\header( 'Content-Disposition: attachment; filename="post-release-health-summary-' . gmdate( 'Y-m-d' ) . '.json"' );
		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}
