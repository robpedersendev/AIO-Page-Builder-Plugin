<?php
/**
 * AI Run detail: metadata, artifact summaries, subtabs, and sensitive full prompt (spec §29.8, §29.11).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Actions\Create_Build_Plan_From_AI_Run_Action;
use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Service;
use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders a single AI run with on-page subtabs. Raw prompt and normalized package require VIEW_SENSITIVE_DIAGNOSTICS.
 */
final class AI_Run_Detail_Screen {

	public const SLUG = 'aio-page-builder-ai-runs';

	public const SUBTAB_OVERVIEW    = 'overview';
	public const SUBTAB_ARTIFACTS   = 'artifacts';
	public const SUBTAB_VALIDATION  = 'validation';
	public const SUBTAB_FULL_PROMPT = 'full_prompt';

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
	 * @param string $run_id       Run ID (internal_key).
	 * @param bool   $embed_in_hub When true, outer wrap and h1 are omitted.
	 * @return void
	 */
	public function render( string $run_id, bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( Capabilities::VIEW_AI_RUNS ) ) {
			\wp_die( \esc_html__( 'You do not have permission to view AI run details.', 'aio-page-builder' ) );
		}

		$can_view_raw = Capabilities::current_user_can_for_route( Capabilities::VIEW_SENSITIVE_DIAGNOSTICS );

		$allowed_subtabs = array( self::SUBTAB_OVERVIEW, self::SUBTAB_ARTIFACTS, self::SUBTAB_VALIDATION );
		if ( $can_view_raw ) {
			$allowed_subtabs[] = self::SUBTAB_FULL_PROMPT;
		}
		$current_sub = Admin_Screen_Hub::current_subtab( self::SUBTAB_OVERVIEW, $allowed_subtabs );

		$run               = null;
		$artifact_summary  = array();
		$usage_data        = null;
		$validation_report = null;
		$dropped_report    = null;
		$artifact_svc      = null;

		if ( $this->container && $this->container->has( 'ai_run_service' ) && $this->container->has( 'ai_run_artifact_service' ) ) {
			try {
				$svc = $this->container->get( 'ai_run_service' );
				$run = $svc->get_run_by_id( $run_id );
				if ( $run !== null && isset( $run['id'] ) ) {
					$artifact_svc     = $this->container->get( 'ai_run_artifact_service' );
					$artifact_summary = $artifact_svc->get_artifact_summary_for_review( (int) $run['id'], $can_view_raw );
					$raw_usage        = $artifact_svc->get( (int) $run['id'], Artifact_Category_Keys::USAGE_METADATA );
					if ( is_array( $raw_usage ) ) {
						$usage_data = $raw_usage;
					}
					$vr = $artifact_svc->get( (int) $run['id'], Artifact_Category_Keys::VALIDATION_REPORT );
					if ( is_array( $vr ) ) {
						$validation_report = $vr;
					}
					$dr = $artifact_svc->get( (int) $run['id'], Artifact_Category_Keys::DROPPED_RECORD_REPORT );
					if ( is_array( $dr ) ) {
						$dropped_report = $dr;
					}
				}
			} catch ( \Throwable $e ) {
				$run = null;
			}
		}

		$normalized_for_plan    = null;
		$can_create_build_plan  = false;
		$create_plan_help_text  = '';
		$existing_build_plan_id = '';
		if ( is_array( $run ) && $artifact_svc !== null && isset( $run['id'] ) ) {
			$normalized_for_plan    = $artifact_svc->get( (int) $run['id'], Artifact_Category_Keys::NORMALIZED_OUTPUT );
			$meta_for_plan          = is_array( $run['run_metadata'] ?? null ) ? $run['run_metadata'] : array();
			$existing_build_plan_id = is_string( $meta_for_plan['build_plan_ref'] ?? null )
				? trim( (string) $meta_for_plan['build_plan_ref'] )
				: '';
			if ( Capabilities::current_user_can_for_route( Capabilities::APPROVE_BUILD_PLANS ) ) {
				$st = (string) ( $run['status'] ?? '' );
				if ( $st !== 'completed' ) {
					$create_plan_help_text = __( 'A Build Plan can be created only after the run completes successfully.', 'aio-page-builder' );
				} elseif ( ! is_array( $normalized_for_plan ) ) {
					$create_plan_help_text = __( 'This run has no normalized output, so a Build Plan cannot be generated.', 'aio-page-builder' );
				} else {
					$can_create_build_plan = true;
				}
			}
		}

		$meta      = is_array( $run ) ? ( $run['run_metadata'] ?? array() ) : array();
		$meta_safe = AI_Run_Artifact_Service::redact_sensitive_values( $meta );
		$list_url  = Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'ai_runs' );

		$subtabs_def = array(
			self::SUBTAB_OVERVIEW    => array(
				'label' => __( 'Overview', 'aio-page-builder' ),
				'cap'   => Capabilities::VIEW_AI_RUNS,
			),
			self::SUBTAB_ARTIFACTS   => array(
				'label' => __( 'Artifacts', 'aio-page-builder' ),
				'cap'   => Capabilities::VIEW_AI_RUNS,
			),
			self::SUBTAB_VALIDATION  => array(
				'label' => __( 'Validation', 'aio-page-builder' ),
				'cap'   => Capabilities::VIEW_AI_RUNS,
			),
			self::SUBTAB_FULL_PROMPT => array(
				'label' => __( 'Full prompt', 'aio-page-builder' ),
				'cap'   => Capabilities::VIEW_SENSITIVE_DIAGNOSTICS,
			),
		);

		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-ai-run-detail" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p><a href="<?php echo \esc_url( $list_url ); ?>"><?php \esc_html_e( '&larr; Back to AI Runs', 'aio-page-builder' ); ?></a></p>
			<?php
			if ( isset( $_GET[ Create_Build_Plan_From_AI_Run_Action::QUERY_RESULT ] ) ) {
				$bp_flag = \sanitize_key( (string) \wp_unslash( (string) $_GET[ Create_Build_Plan_From_AI_Run_Action::QUERY_RESULT ] ) );
				$bp_msgs = array(
					Create_Build_Plan_From_AI_Run_Action::RESULT_UNAUTHORIZED    => __( 'You do not have permission to create Build Plans.', 'aio-page-builder' ),
					Create_Build_Plan_From_AI_Run_Action::RESULT_BAD_REQUEST    => __( 'The request could not be completed. Try again.', 'aio-page-builder' ),
					Create_Build_Plan_From_AI_Run_Action::RESULT_GENERATION_FAILED => __( 'The Build Plan could not be generated from this run. Check validation and normalized output.', 'aio-page-builder' ),
				);
				if ( isset( $bp_msgs[ $bp_flag ] ) ) {
					$bp_class = ( Create_Build_Plan_From_AI_Run_Action::RESULT_CREATED === $bp_flag ) ? 'notice-success' : 'notice-error';
					?>
					<div class="notice <?php echo \esc_attr( $bp_class ); ?> aio-ai-run-bp-notice" role="status"><p><?php echo \esc_html( $bp_msgs[ $bp_flag ] ); ?></p></div>
					<?php
				}
			}
			?>
			<?php if ( $run === null ) : ?>
				<p class="aio-admin-notice"><?php \esc_html_e( 'Run not found.', 'aio-page-builder' ); ?></p>
				<?php
				if ( ! $embed_in_hub ) :
					?>
					</div><?php endif; ?>
				<?php return; ?>
			<?php endif; ?>

			<p class="description">
				<?php
				echo \esc_html(
					sprintf(
						/* translators: %s: run internal key */
						__( 'Run %s — use the subtabs below for metadata, stored artifacts, validation output, and (with permission) the full prompt sent to the provider.', 'aio-page-builder' ),
						(string) ( $run['internal_key'] ?? $run_id )
					)
				);
				?>
			</p>

			<?php
			Admin_Screen_Hub::render_subnav_tabs(
				AI_Runs_Screen::HUB_PAGE_SLUG,
				'ai_runs',
				$subtabs_def,
				$current_sub,
				null,
				array( 'run_id' => $run_id )
			);
			?>

			<?php
			$failover_attempts = isset( $meta_safe['failover_attempt'] ) && is_array( $meta_safe['failover_attempt'] ) ? $meta_safe['failover_attempt'] : array();
			?>
			<div class="aio-run-detail-subpanel">
			<?php
			switch ( $current_sub ) {
				case self::SUBTAB_ARTIFACTS:
					$this->render_subtab_artifacts( $artifact_svc, (int) $run['id'], $can_view_raw, $artifact_summary );
					break;
				case self::SUBTAB_VALIDATION:
					$this->render_subtab_validation( $validation_report, $dropped_report );
					break;
				case self::SUBTAB_FULL_PROMPT:
					$this->render_subtab_full_prompt( $artifact_svc, (int) $run['id'], $can_view_raw );
					break;
				case self::SUBTAB_OVERVIEW:
				default:
					$this->render_subtab_overview(
						$run,
						$run_id,
						$meta_safe,
						$usage_data,
						$validation_report,
						$failover_attempts,
						$can_create_build_plan,
						$create_plan_help_text,
						$existing_build_plan_id
					);
					break;
			}
			?>
			</div>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * @param array<string, mixed>      $run
	 * @param string                    $run_id
	 * @param array<string, mixed>      $meta_safe
	 * @param array<string, mixed>|null $usage_data
	 * @param array<string, mixed>|null $validation_report
	 * @param array<int, mixed>         $attempts
	 * @param bool                      $can_create_build_plan  User may submit create-plan action.
	 * @param string                    $create_plan_help_text  Shown when create is disabled.
	 * @param string                    $existing_build_plan_id Linked plan id from metadata, if any.
	 * @return void
	 */
	private function render_subtab_overview(
		array $run,
		string $run_id,
		array $meta_safe,
		?array $usage_data,
		?array $validation_report,
		array $attempts,
		bool $can_create_build_plan,
		string $create_plan_help_text,
		string $existing_build_plan_id
	): void {
		$effective = isset( $meta_safe['effective_provider_used'] ) && is_array( $meta_safe['effective_provider_used'] )
			? $meta_safe['effective_provider_used']
			: null;
		$core_keys = array(
			'actor',
			'created_at',
			'completed_at',
			'provider_id',
			'model_used',
			'failover_attempt',
			'effective_provider_used',
			'prompt_pack_ref',
			'retry_count',
			'build_plan_ref',
			'is_experiment',
			'experiment_id',
			'experiment_variant_label',
			'experiment_variant_id',
		);
		?>
			<?php if ( Capabilities::current_user_can_for_route( Capabilities::APPROVE_BUILD_PLANS ) ) : ?>
			<section class="aio-run-build-plan-actions" aria-labelledby="aio-run-bp-heading">
				<h2 id="aio-run-bp-heading"><?php \esc_html_e( 'Build Plan', 'aio-page-builder' ); ?></h2>
				<?php if ( $existing_build_plan_id !== '' ) : ?>
					<p>
						<?php \esc_html_e( 'Linked plan:', 'aio-page-builder' ); ?>
						<a href="<?php echo \esc_url( Admin_Screen_Hub::tab_url( Build_Plans_Screen::SLUG, 'build_plans', array( 'plan_id' => $existing_build_plan_id ) ) ); ?>"><code><?php echo \esc_html( $existing_build_plan_id ); ?></code></a>
					</p>
				<?php endif; ?>
				<?php if ( $can_create_build_plan ) : ?>
					<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="aio-create-bp-from-run-form">
						<input type="hidden" name="action" value="aio_create_build_plan_from_ai_run" />
						<input type="hidden" name="run_id" value="<?php echo \esc_attr( $run_id ); ?>" />
						<?php \wp_nonce_field( Create_Build_Plan_From_AI_Run_Action::NONCE_ACTION, Create_Build_Plan_From_AI_Run_Action::NONCE_NAME ); ?>
						<?php \submit_button( __( 'Create Build Plan from this run', 'aio-page-builder' ), 'primary', 'submit', false ); ?>
					</form>
				<?php elseif ( $create_plan_help_text !== '' ) : ?>
					<p class="description"><?php echo \esc_html( $create_plan_help_text ); ?></p>
				<?php endif; ?>
			</section>
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
						<tr><th scope="row"><?php \esc_html_e( 'Provider', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( AI_Run_Artifact_Service::format_run_metadata_value_for_display( $meta_safe['provider_id'] ?? '' ) ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Model', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( AI_Run_Artifact_Service::format_run_metadata_value_for_display( $meta_safe['model_used'] ?? '' ) ); ?></td></tr>
						<?php if ( count( $attempts ) > 1 && $effective !== null && AI_Run_Artifact_Service::format_run_metadata_value_for_display( $effective['provider_id'] ?? '' ) !== '' ) : ?>
						<tr><th scope="row"><?php \esc_html_e( 'Effective provider used', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( AI_Run_Artifact_Service::format_run_metadata_value_for_display( $effective['provider_id'] ?? '' ) ); ?> (<?php echo \esc_html( AI_Run_Artifact_Service::format_run_metadata_value_for_display( $effective['model_used'] ?? '' ) ); ?>)</td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Failover', 'aio-page-builder' ); ?></th><td><?php \esc_html_e( 'Primary failed; fallback was attempted. See attempt log below.', 'aio-page-builder' ); ?></td></tr>
						<?php endif; ?>
						<tr><th scope="row"><?php \esc_html_e( 'Prompt pack', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) ( $meta_safe['prompt_pack_ref'] ?? '' ) ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Retry count', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) ( $meta_safe['retry_count'] ?? '' ) ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Build plan ref', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) ( $meta_safe['build_plan_ref'] ?? '' ) ); ?></td></tr>
						<?php
						$prompt_tok     = isset( $usage_data['prompt_tokens'] ) ? (int) $usage_data['prompt_tokens'] : null;
						$completion_tok = isset( $usage_data['completion_tokens'] ) ? (int) $usage_data['completion_tokens'] : null;
						$total_tok      = isset( $usage_data['total_tokens'] ) ? (int) $usage_data['total_tokens'] : null;
						$cost_usd       = isset( $usage_data['cost_usd'] ) ? $usage_data['cost_usd'] : null;
						if ( $usage_data !== null ) :
							$token_str = $total_tok !== null
								? sprintf(
									/* translators: 1: prompt token count, 2: completion token count, 3: total token count */
									\__( '%1$d prompt + %2$d completion = %3$d total', 'aio-page-builder' ),
									$prompt_tok ?? 0,
									$completion_tok ?? 0,
									$total_tok
								)
								: \__( 'Not available', 'aio-page-builder' );
							$cost_str = $cost_usd !== null
								? '$' . number_format( (float) $cost_usd, 6 )
								: \__( 'Not available (model not in pricing registry)', 'aio-page-builder' );
							?>
						<tr>
							<th scope="row"><?php \esc_html_e( 'Token usage', 'aio-page-builder' ); ?></th>
							<td><?php echo \esc_html( $token_str ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php \esc_html_e( 'Estimated cost', 'aio-page-builder' ); ?></th>
							<td><?php echo \esc_html( $cost_str ); ?></td>
						</tr>
						<?php endif; ?>
						<?php if ( ! empty( $meta_safe['is_experiment'] ) ) : ?>
						<tr><th scope="row"><?php \esc_html_e( 'Experiment', 'aio-page-builder' ); ?></th><td><span class="aio-run-badge"><?php \esc_html_e( 'Experiment run', 'aio-page-builder' ); ?></span> <?php echo \esc_html( AI_Run_Artifact_Service::format_run_metadata_value_for_display( $meta_safe['experiment_id'] ?? '' ) ); ?> — <?php echo \esc_html( AI_Run_Artifact_Service::format_run_metadata_value_for_display( $meta_safe['experiment_variant_label'] ?? $meta_safe['experiment_variant_id'] ?? '' ) ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $meta_safe as $mk => $mv ) : ?>
							<?php
							if ( in_array( $mk, $core_keys, true ) ) {
								continue;
							}
							$cell = is_scalar( $mv ) ? (string) $mv : (string) \wp_json_encode( $mv );
							if ( strlen( $cell ) > 500 ) {
								$cell = substr( $cell, 0, 500 ) . '…';
							}
							?>
						<tr><th scope="row"><code><?php echo \esc_html( $mk ); ?></code></th><td><?php echo \esc_html( $cell ); ?></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php if ( ! empty( $attempts ) ) : ?>
				<h3 id="aio-failover-heading"><?php \esc_html_e( 'Failover attempt log', 'aio-page-builder' ); ?></h3>
				<table class="widefat striped" aria-describedby="aio-failover-heading">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'Provider', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Model', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Outcome', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Time', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $attempts as $a ) : ?>
							<?php
							if ( ! is_array( $a ) ) {
								continue;
							}
							$p = isset( $a['provider_id'] ) ? (string) $a['provider_id'] : '';
							$m = isset( $a['model_used'] ) ? (string) $a['model_used'] : '';
							$c = isset( $a['category'] ) ? (string) $a['category'] : '';
							$t = isset( $a['attempted_at'] ) ? (string) $a['attempted_at'] : '';
							?>
						<tr>
							<td><code><?php echo \esc_html( $p ); ?></code></td>
							<td><?php echo \esc_html( $m ); ?></td>
							<td><?php echo \esc_html( $c ); ?></td>
							<td><?php echo \esc_html( $t ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
			</section>

			<?php if ( $validation_report !== null ) : ?>
			<section class="aio-run-validation-snapshot" aria-labelledby="aio-run-validation-snapshot-heading">
				<h2 id="aio-run-validation-snapshot-heading"><?php \esc_html_e( 'Validation snapshot', 'aio-page-builder' ); ?></h2>
				<p class="description"><?php \esc_html_e( 'Key fields from the stored validation report. Open the Validation subtab for the full JSON.', 'aio-page-builder' ); ?></p>
				<table class="widefat striped">
					<tbody>
						<?php
						$highlight_keys = array(
							'blocking_failure_stage',
							'final_validation_state',
							'parse_status',
							'top_level_valid',
							'raw_capture_status',
							'repair_attempted',
							'repair_succeeded',
						);
						foreach ( $highlight_keys as $hk ) :
							if ( ! array_key_exists( $hk, $validation_report ) ) {
								continue;
							}
							$hv  = $validation_report[ $hk ];
							$hvs = is_scalar( $hv ) ? (string) $hv : (string) \wp_json_encode( $hv );
							?>
						<tr><th scope="row"><code><?php echo \esc_html( $hk ); ?></code></th><td><?php echo \esc_html( $hvs ); ?></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</section>
			<?php endif; ?>
		<?php
	}

	/**
	 * @param AI_Run_Artifact_Service|null                                               $artifact_svc
	 * @param array<string, array{present: bool, summary: string|array, redacted: bool}> $artifact_summary
	 * @return void
	 */
	private function render_subtab_artifacts( ?AI_Run_Artifact_Service $artifact_svc, int $run_post_id, bool $can_view_raw, array $artifact_summary ): void {
		?>
			<section class="aio-artifact-summary" aria-labelledby="aio-artifact-heading">
				<h2 id="aio-artifact-heading"><?php \esc_html_e( 'Artifacts', 'aio-page-builder' ); ?></h2>
				<?php if ( ! $can_view_raw ) : ?>
					<p class="description"><?php \esc_html_e( 'Raw prompt, normalized prompt package, provider response, and input snapshot are restricted. Use the Full prompt subtab if you have sensitive diagnostics permission.', 'aio-page-builder' ); ?></p>
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
										echo \esc_html( (string) \wp_json_encode( $sum ) );
									} else {
										echo \esc_html( (string) $sum );
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<h3 style="margin-top:1.5em;"><?php \esc_html_e( 'Full payloads', 'aio-page-builder' ); ?></h3>
				<p class="description"><?php \esc_html_e( 'Expand each section to view pretty-printed JSON (or text) stored for this run.', 'aio-page-builder' ); ?></p>
				<?php
				foreach ( Artifact_Category_Keys::all() as $cat ) :
					$payload = null;
					if ( $artifact_svc !== null ) {
						$payload = $artifact_svc->get( $run_post_id, $cat );
					}
					$present   = $payload !== null && $payload !== '';
					$sensitive = in_array( $cat, Artifact_Category_Keys::REDACT_BEFORE_DISPLAY, true );
					$blocked   = $sensitive && ! $can_view_raw;
					?>
				<details class="aio-artifact-details" style="margin-bottom:0.75em;border:1px solid #c3c4c7;padding:0.5em 0.75em;background:#fff;">
					<summary><code><?php echo \esc_html( $cat ); ?></code>
						<?php if ( ! $present ) : ?>
							— <?php \esc_html_e( 'not stored', 'aio-page-builder' ); ?>
						<?php elseif ( $blocked ) : ?>
							— <?php \esc_html_e( 'restricted', 'aio-page-builder' ); ?>
						<?php endif; ?>
					</summary>
					<?php if ( ! $present ) : ?>
						<p class="description"><?php \esc_html_e( 'No artifact row for this category.', 'aio-page-builder' ); ?></p>
					<?php elseif ( $blocked ) : ?>
						<p class="description"><?php \esc_html_e( 'This category may contain prompts or site context. It is visible only to users with the sensitive diagnostics capability.', 'aio-page-builder' ); ?></p>
					<?php else : ?>
						<pre class="aio-json-dump"><?php echo \esc_html( self::format_payload_for_display( $payload ) ); ?></pre>
					<?php endif; ?>
				</details>
				<?php endforeach; ?>
			</section>
		<?php
	}

	/**
	 * @param array<string, mixed>|null $validation_report
	 * @param array<string, mixed>|null $dropped_report
	 * @return void
	 */
	private function render_subtab_validation( ?array $validation_report, ?array $dropped_report ): void {
		?>
			<section class="aio-run-validation-full" aria-labelledby="aio-run-validation-full-heading">
				<h2 id="aio-run-validation-full-heading"><?php \esc_html_e( 'Validation report', 'aio-page-builder' ); ?></h2>
				<?php if ( $validation_report === null ) : ?>
					<p class="aio-admin-notice"><?php \esc_html_e( 'No validation report artifact was stored for this run.', 'aio-page-builder' ); ?></p>
				<?php else : ?>
					<pre class="aio-json-dump"><?php echo \esc_html( self::format_payload_for_display( $validation_report ) ); ?></pre>
				<?php endif; ?>
			</section>
			<section class="aio-run-dropped-records" aria-labelledby="aio-run-dropped-heading" style="margin-top:1.5em;">
				<h2 id="aio-run-dropped-heading"><?php \esc_html_e( 'Dropped record report', 'aio-page-builder' ); ?></h2>
				<?php if ( $dropped_report === null ) : ?>
					<p class="description"><?php \esc_html_e( 'None stored.', 'aio-page-builder' ); ?></p>
				<?php else : ?>
					<pre class="aio-json-dump"><?php echo \esc_html( self::format_payload_for_display( $dropped_report ) ); ?></pre>
				<?php endif; ?>
			</section>
		<?php
	}

	/**
	 * @param AI_Run_Artifact_Service|null $artifact_svc
	 * @return void
	 */
	private function render_subtab_full_prompt( ?AI_Run_Artifact_Service $artifact_svc, int $run_post_id, bool $can_view_raw ): void {
		if ( ! $can_view_raw ) {
			echo '<p class="aio-admin-notice">' . \esc_html__( 'You do not have permission to view full prompt content.', 'aio-page-builder' ) . '</p>';
			return;
		}
		$raw        = $artifact_svc !== null ? $artifact_svc->get( $run_post_id, Artifact_Category_Keys::RAW_PROMPT ) : null;
		$normalized = $artifact_svc !== null ? $artifact_svc->get( $run_post_id, Artifact_Category_Keys::NORMALIZED_PROMPT_PACKAGE ) : null;
		?>
			<section class="aio-full-prompt-raw" aria-labelledby="aio-full-prompt-raw-heading">
				<h2 id="aio-full-prompt-raw-heading"><?php \esc_html_e( 'Raw prompt (capture-ready)', 'aio-page-builder' ); ?></h2>
				<p class="description"><?php \esc_html_e( 'Exact payload captured for provider dispatch (may include site context). Treat as sensitive.', 'aio-page-builder' ); ?></p>
				<?php if ( $raw === null || $raw === '' ) : ?>
					<p class="aio-admin-notice"><?php \esc_html_e( 'No raw prompt artifact was stored. Common causes: JSON encoding or post meta write failed for this payload (check debug log for ARTIFACT_STORE), or the run was created by a code path that does not persist prompts. If input_snapshot exists, the request context was still captured there.', 'aio-page-builder' ); ?></p>
				<?php else : ?>
					<pre class="aio-json-dump"><?php echo \esc_html( self::format_payload_for_display( $raw ) ); ?></pre>
				<?php endif; ?>
			</section>
			<section class="aio-full-prompt-normalized" aria-labelledby="aio-full-prompt-norm-heading" style="margin-top:1.5em;">
				<h2 id="aio-full-prompt-norm-heading"><?php \esc_html_e( 'Normalized prompt package', 'aio-page-builder' ); ?></h2>
				<p class="description"><?php \esc_html_e( 'Structured package assembled before the provider call. The stored copy omits the nested input_artifact when present (same data as the input_snapshot artifact) to keep meta writes reliable.', 'aio-page-builder' ); ?></p>
				<?php if ( $normalized === null || $normalized === '' ) : ?>
					<p class="aio-admin-notice"><?php \esc_html_e( 'No normalized prompt package artifact was stored. See the note for raw prompt above; full site context may still appear under Artifacts → input_snapshot.', 'aio-page-builder' ); ?></p>
				<?php else : ?>
					<pre class="aio-json-dump"><?php echo \esc_html( self::format_payload_for_display( $normalized ) ); ?></pre>
				<?php endif; ?>
			</section>
		<?php
	}

	/**
	 * @param mixed $payload Artifact or metadata fragment.
	 * @return string
	 */
	private static function format_payload_for_display( mixed $payload ): string {
		if ( is_array( $payload ) || is_object( $payload ) ) {
			$json = \wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			return $json !== false ? $json : '[encode_error]';
		}
		return (string) $payload;
	}
}
