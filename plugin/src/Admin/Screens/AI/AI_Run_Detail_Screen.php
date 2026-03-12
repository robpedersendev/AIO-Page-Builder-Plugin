<?php
/**
 * AI Run detail: metadata and artifact summaries with redaction and access gating (spec §29.8, §29.11).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders a single AI run: run metadata (redacted) and artifact summaries. Raw content only if user has VIEW_SENSITIVE_DIAGNOSTICS.
 */
final class AI_Run_Detail_Screen {

	public const SLUG = 'aio-page-builder-ai-runs';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'AI Run Details', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_AI_RUNS;
	}

	/**
	 * Renders detail for the given run_id (internal key).
	 *
	 * @param string $run_id Run ID (internal_key).
	 * @return void
	 */
	public function render( string $run_id ): void {
		$run = null;
		$artifact_summary = array();
		$can_view_raw = \current_user_can( Capabilities::VIEW_SENSITIVE_DIAGNOSTICS );

		if ( $this->container && $this->container->has( 'ai_run_service' ) && $this->container->has( 'ai_run_artifact_service' ) ) {
			try {
				$svc = $this->container->get( 'ai_run_service' );
				$run = $svc->get_run_by_id( $run_id );
				if ( $run !== null && isset( $run['id'] ) ) {
					$artifact_svc = $this->container->get( 'ai_run_artifact_service' );
					$artifact_summary = $artifact_svc->get_artifact_summary_for_review( (int) $run['id'], $can_view_raw );
				}
			} catch ( \Throwable $e ) {
				$run = null;
			}
		}

		$meta = $run['run_metadata'] ?? array();
		$meta_safe = AI_Run_Artifact_Service::redact_sensitive_values( $meta );
		$list_url = \admin_url( 'admin.php?page=' . self::SLUG );
		?>
		<div class="wrap aio-page-builder-screen aio-ai-run-detail" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p><a href="<?php echo \esc_url( $list_url ); ?>"><?php \esc_html_e( '&larr; Back to AI Runs', 'aio-page-builder' ); ?></a></p>
			<?php if ( $run === null ) : ?>
				<p class="aio-admin-notice"><?php \esc_html_e( 'Run not found.', 'aio-page-builder' ); ?></p>
				<?php return; ?>
			<?php endif; ?>

			<section class="aio-run-meta" aria-labelledby="aio-run-meta-heading">
				<h2 id="aio-run-meta-heading"><?php \esc_html_e( 'Run metadata', 'aio-page-builder' ); ?></h2>
				<table class="widefat striped">
					<tbody>
						<tr><th scope="row"><?php \esc_html_e( 'Run ID', 'aio-page-builder' ); ?></th><td><code><?php echo \esc_html( (string) ( $run['internal_key'] ?? $run_id ) ); ?></code></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) ( $run['status'] ?? '' ) ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Actor', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) ( $meta_safe['actor'] ?? '' ) ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Created', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) ( $meta_safe['created_at'] ?? '' ) ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Completed', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) ( $meta_safe['completed_at'] ?? '' ) ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Provider', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) ( $meta_safe['provider_id'] ?? '' ) ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Model', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) ( $meta_safe['model_used'] ?? '' ) ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Prompt pack', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) ( $meta_safe['prompt_pack_ref'] ?? '' ) ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Retry count', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) ( $meta_safe['retry_count'] ?? '' ) ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Build plan ref', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) ( $meta_safe['build_plan_ref'] ?? '' ) ); ?></td></tr>
					</tbody>
				</table>
			</section>

			<section class="aio-artifact-summary" aria-labelledby="aio-artifact-heading">
				<h2 id="aio-artifact-heading"><?php \esc_html_e( 'Artifact summary', 'aio-page-builder' ); ?></h2>
				<?php if ( ! $can_view_raw ) : ?>
					<p class="description"><?php \esc_html_e( 'Raw prompt and provider response content is hidden. Users with sensitive diagnostics permission can see full content.', 'aio-page-builder' ); ?></p>
				<?php endif; ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'Category', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Present', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Redacted', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Summary', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $artifact_summary as $cat => $info ) : ?>
							<tr>
								<td><code><?php echo \esc_html( $cat ); ?></code></td>
								<td><?php echo $info['present'] ? \esc_html__( 'Yes', 'aio-page-builder' ) : \esc_html__( 'No', 'aio-page-builder' ); ?></td>
								<td><?php echo $info['redacted'] ? \esc_html__( 'Yes', 'aio-page-builder' ) : \esc_html__( 'No', 'aio-page-builder' ); ?></td>
								<td>
									<?php
									$sum = $info['summary'];
									if ( is_array( $sum ) ) {
										echo \esc_html( wp_json_encode( $sum ) );
									} else {
										echo \esc_html( (string) $sum );
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</section>
		</div>
		<?php
	}
}
