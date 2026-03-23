<?php
/**
 * ACF field-architecture diagnostics and drift audit screen (spec §20, §20.13–20.15, §21, §59.12; Prompt 223).
 * Observational: health summaries, registration status, assignment mismatches, LPagery support, repair entry points.
 * No direct editing of core field groups; repair links capability-gated and nonce-protected when used.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Diagnostics;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Diagnostics\ACF_Diagnostics_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders ACF field-architecture diagnostics. Permission-gated; observational; links to regeneration tool.
 */
final class ACF_Architecture_Diagnostics_Screen {

	public const SLUG = 'aio-page-builder-acf-diagnostics';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'ACF Field Architecture', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Capability required to see repair/regeneration links and run repair actions.
	 *
	 * @return string
	 */
	public function get_repair_capability(): string {
		return Capabilities::MANAGE_SECTION_TEMPLATES;
	}

	/**
	 * Renders the screen. Capability enforced by menu registration.
	 *
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access ACF field architecture diagnostics.', 'aio-page-builder' ), 403 );
		}
		$state = $this->build_state();
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-acf-diagnostics" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="aio-acf-diagnostics-description">
				<?php \esc_html_e( 'Health of programmatically registered field groups, blueprint-family coverage, page-assignment state, and LPagery compatibility. Observational only; use repair tool to rebuild from registry.', 'aio-page-builder' ); ?>
			</p>
			<?php $this->render_filters( $state ); ?>
			<?php
			$this->render_health_card( $state['field_architecture_health_card'], $state['acf_diagnostics_summary'] );
			$this->render_assignment_mismatches( $state['assignment_mismatch_groups'] );
			$this->render_lpagery_summary( $state['lpagery_field_support_summary'] );
			$this->render_regeneration_summary( $state['regeneration_plan_summary'] );
			$this->render_repair_entry( $state );
			?>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Builds state via ACF_Diagnostics_State_Builder from container.
	 *
	 * @return array<string, mixed>
	 */
	private function build_state(): array {
		$builder = $this->container && $this->container->has( 'acf_diagnostics_state_builder' )
			? $this->container->get( 'acf_diagnostics_state_builder' )
			: null;
		if ( ! $builder instanceof ACF_Diagnostics_State_Builder ) {
			return $this->empty_state();
		}
		$section_family = isset( $_GET['section_family'] ) ? \sanitize_text_field( \wp_unslash( $_GET['section_family'] ) ) : null;
		$pt_family      = isset( $_GET['page_template_family'] ) ? \sanitize_text_field( \wp_unslash( $_GET['page_template_family'] ) ) : null;
		$severity       = isset( $_GET['severity'] ) ? \sanitize_key( \wp_unslash( $_GET['severity'] ) ) : null;
		if ( $section_family === '' ) {
			$section_family = null;
		}
		if ( $pt_family === '' ) {
			$pt_family = null;
		}
		if ( $severity === '' ) {
			$severity = null;
		}
		return $builder->build( $section_family, $pt_family, $severity );
	}

	/** @return array<string, mixed> */
	private function empty_state(): array {
		return array(
			'acf_diagnostics_summary'        => array(
				'acf_present'      => false,
				'overall_status'   => ACF_Diagnostics_State_Builder::OVERALL_BLOCKED,
				'repair_readiness' => 'blocked',
				'lpagery_status'   => ACF_Diagnostics_State_Builder::LPAGERY_ABSENT,
			),
			'field_architecture_health_card' => array(),
			'assignment_mismatch_groups'     => array(),
			'lpagery_field_support_summary'  => array(
				'status'  => ACF_Diagnostics_State_Builder::LPAGERY_ABSENT,
				'summary' => '',
			),
			'regeneration_plan_summary'      => array(),
			'filters_applied'                => array(),
		);
	}

	/** @param array<string, mixed> $state */
	private function render_filters( array $state ): void {
		$base    = \add_query_arg( array( 'page' => self::SLUG ), \admin_url( 'admin.php' ) );
		$filters = $state['filters_applied'] ?? array();
		?>
		<div class="aio-acf-diagnostics-filters" style="margin: 1em 0;">
			<span class="filter-label"><?php \esc_html_e( 'Filter:', 'aio-page-builder' ); ?></span>
			<a href="<?php echo \esc_url( $base ); ?>"><?php \esc_html_e( 'All', 'aio-page-builder' ); ?></a>
			| <a href="<?php echo \esc_url( \add_query_arg( 'severity', 'warning', $base ) ); ?>"><?php \esc_html_e( 'Warnings', 'aio-page-builder' ); ?></a>
			| <a href="<?php echo \esc_url( \add_query_arg( 'severity', 'error', $base ) ); ?>"><?php \esc_html_e( 'Errors', 'aio-page-builder' ); ?></a>
			<?php if ( ! empty( $filters ) ) : ?>
				<span class="aio-acf-diagnostics-filters-applied" style="margin-left: 0.5em;">
					<?php \esc_html_e( 'Applied:', 'aio-page-builder' ); ?>
					<?php echo \esc_html( implode( ', ', array_filter( array( $filters['section_family_key'] ?? '', $filters['page_template_family_key'] ?? '', $filters['severity'] ?? '' ) ) ) ); ?>
				</span>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders field architecture health card and overall summary.
	 *
	 * @param array<string, mixed> $health_card
	 * @param array<string, mixed> $summary
	 * @return void
	 */
	private function render_health_card( array $health_card, array $summary ): void {
		$overall           = (string) ( $summary['overall_status'] ?? '' );
		$acf_present       = (bool) ( $health_card['acf_present'] ?? $summary['acf_present'] ?? false );
		$missing           = (int) ( $health_card['missing_group_count'] ?? $summary['missing_count'] ?? 0 );
		$stale             = (int) ( $health_card['version_stale_count'] ?? $summary['version_stale_count'] ?? 0 );
		$ok                = (int) ( $health_card['registered_ok_count'] ?? $summary['registered_count'] ?? 0 );
		$expected          = (int) ( $health_card['expected_group_count'] ?? 0 );
		$pages_assignments = (int) ( $health_card['pages_with_assignments'] ?? 0 );
		$pages_source      = (int) ( $health_card['pages_with_structural_source'] ?? 0 );
		$compat_warnings   = (int) ( $health_card['compatibility_warning_count'] ?? 0 );
		$stale_assignments = (int) ( $health_card['stale_assignment_count'] ?? 0 );
		?>
		<div class="aio-acf-diagnostics-health-card card" style="max-width: 800px; padding: 1em; margin: 1em 0;">
			<h2 class="aio-acf-diagnostics-section-title"><?php \esc_html_e( 'Field architecture health', 'aio-page-builder' ); ?></h2>
			<table class="widefat striped">
				<tbody>
					<tr>
						<td><strong><?php \esc_html_e( 'ACF present & active', 'aio-page-builder' ); ?></strong></td>
						<td><?php echo $acf_present ? \esc_html__( 'Yes', 'aio-page-builder' ) : \esc_html__( 'No', 'aio-page-builder' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php \esc_html_e( 'Overall status', 'aio-page-builder' ); ?></strong></td>
						<td><span class="aio-acf-status aio-acf-status-<?php echo \esc_attr( $overall ); ?>"><?php echo \esc_html( $this->label_for_overall_status( $overall ) ); ?></span></td>
					</tr>
					<tr>
						<td><?php \esc_html_e( 'Expected / registered (OK)', 'aio-page-builder' ); ?></td>
						<td><?php echo \esc_html( (string) $expected ); ?> / <?php echo \esc_html( (string) $ok ); ?></td>
					</tr>
					<tr>
						<td><?php \esc_html_e( 'Missing groups', 'aio-page-builder' ); ?></td>
						<td><?php echo \esc_html( (string) $missing ); ?></td>
					</tr>
					<tr>
						<td><?php \esc_html_e( 'Version-stale groups', 'aio-page-builder' ); ?></td>
						<td><?php echo \esc_html( (string) $stale ); ?></td>
					</tr>
					<tr>
						<td><?php \esc_html_e( 'Pages with assignments / with structural source', 'aio-page-builder' ); ?></td>
						<td><?php echo \esc_html( (string) $pages_assignments ); ?> / <?php echo \esc_html( (string) $pages_source ); ?></td>
					</tr>
					<tr>
						<td><?php \esc_html_e( 'Compatibility warnings', 'aio-page-builder' ); ?></td>
						<td><?php echo \esc_html( (string) $compat_warnings ); ?></td>
					</tr>
					<tr>
						<td><?php \esc_html_e( 'Stale assignment entries', 'aio-page-builder' ); ?></td>
						<td><?php echo \esc_html( (string) $stale_assignments ); ?></td>
					</tr>
				</tbody>
			</table>
			<?php if ( ! empty( $health_card['summary'] ) ) : ?>
				<p class="aio-acf-diagnostics-summary-text" style="margin-top: 0.5em;"><?php echo \esc_html( (string) $health_card['summary'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private function label_for_overall_status( string $status ): string {
		$labels = array(
			ACF_Diagnostics_State_Builder::OVERALL_HEALTHY => __( 'Healthy', 'aio-page-builder' ),
			ACF_Diagnostics_State_Builder::OVERALL_DRIFT   => __( 'Drift (repair available)', 'aio-page-builder' ),
			ACF_Diagnostics_State_Builder::OVERALL_PARTIAL => __( 'Partial (some mismatches)', 'aio-page-builder' ),
			ACF_Diagnostics_State_Builder::OVERALL_STALE   => __( 'Stale (version mismatch)', 'aio-page-builder' ),
			ACF_Diagnostics_State_Builder::OVERALL_BLOCKED => __( 'Blocked (ACF missing)', 'aio-page-builder' ),
		);
		return $labels[ $status ] ?? $status;
	}

	/**
	 * Renders assignment mismatch groups table.
	 *
	 * @param array<int, array<string, mixed>> $groups
	 * @return void
	 */
	private function render_assignment_mismatches( array $groups ): void {
		?>
		<div class="aio-acf-diagnostics-assignments card" style="max-width: 800px; padding: 1em; margin: 1em 0;">
			<h2 class="aio-acf-diagnostics-section-title"><?php \esc_html_e( 'Page-assignment repair candidates', 'aio-page-builder' ); ?></h2>
			<?php if ( empty( $groups ) ) : ?>
				<p><?php \esc_html_e( 'No assignment mismatches or repair candidates in scope.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php \esc_html_e( 'Page ID', 'aio-page-builder' ); ?></th>
							<th><?php \esc_html_e( 'Type', 'aio-page-builder' ); ?></th>
							<th><?php \esc_html_e( 'Key', 'aio-page-builder' ); ?></th>
							<th><?php \esc_html_e( 'Severity', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $groups as $g ) : ?>
							<tr>
								<td><?php echo \esc_html( (string) ( $g['page_id'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $g['type'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $g['key'] ?? '' ) ); ?></td>
								<td><span class="aio-acf-severity-<?php echo \esc_attr( (string) ( $g['severity'] ?? '' ) ); ?>"><?php echo \esc_html( (string) ( $g['severity'] ?? '' ) ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders LPagery field support summary.
	 *
	 * @param array<string, mixed> $lpagery
	 * @return void
	 */
	private function render_lpagery_summary( array $lpagery ): void {
		$status  = (string) ( $lpagery['status'] ?? ACF_Diagnostics_State_Builder::LPAGERY_ABSENT );
		$summary = (string) ( $lpagery['summary'] ?? '' );
		?>
		<div class="aio-acf-diagnostics-lpagery card" style="max-width: 800px; padding: 1em; margin: 1em 0;">
			<h2 class="aio-acf-diagnostics-section-title"><?php \esc_html_e( 'LPagery compatibility', 'aio-page-builder' ); ?></h2>
			<p><strong><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?>:</strong> <span class="aio-acf-lpagery-<?php echo \esc_attr( $status ); ?>"><?php echo \esc_html( $this->label_for_lpagery_status( $status ) ); ?></span></p>
			<?php if ( $summary !== '' ) : ?>
				<p><?php echo \esc_html( $summary ); ?></p>
			<?php endif; ?>
			<?php if ( isset( $lpagery['sections_supported'] ) || isset( $lpagery['sections_unsupported'] ) ) : ?>
				<p>
					<?php \esc_html_e( 'Sections: supported', 'aio-page-builder' ); ?> <?php echo \esc_html( (string) ( $lpagery['sections_supported'] ?? 0 ) ); ?>,
					<?php \esc_html_e( 'unsupported', 'aio-page-builder' ); ?> <?php echo \esc_html( (string) ( $lpagery['sections_unsupported'] ?? 0 ) ); ?>,
					<?php \esc_html_e( 'partial', 'aio-page-builder' ); ?> <?php echo \esc_html( (string) ( $lpagery['sections_partial'] ?? 0 ) ); ?>.
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	private function label_for_lpagery_status( string $status ): string {
		$labels = array(
			ACF_Diagnostics_State_Builder::LPAGERY_ABSENT  => __( 'Absent', 'aio-page-builder' ),
			ACF_Diagnostics_State_Builder::LPAGERY_PRESENT_UNUSED => __( 'Present (unused)', 'aio-page-builder' ),
			ACF_Diagnostics_State_Builder::LPAGERY_SUPPORTED => __( 'Supported', 'aio-page-builder' ),
			ACF_Diagnostics_State_Builder::LPAGERY_PARTIAL => __( 'Partial', 'aio-page-builder' ),
			ACF_Diagnostics_State_Builder::LPAGERY_BLOCKED => __( 'Blocked by unsupported fields', 'aio-page-builder' ),
		);
		return $labels[ $status ] ?? $status;
	}

	/**
	 * Renders regeneration plan summary (dry-run counts, refused cleanup).
	 *
	 * @param array<string, mixed> $plan
	 * @return void
	 */
	private function render_regeneration_summary( array $plan ): void {
		$missing    = (int) ( $plan['missing_count'] ?? 0 );
		$stale      = (int) ( $plan['version_stale_count'] ?? 0 );
		$candidates = (int) ( $plan['candidate_count'] ?? 0 );
		$refused    = $plan['refused_cleanup'] ?? array();
		?>
		<div class="aio-acf-diagnostics-regeneration card" style="max-width: 800px; padding: 1em; margin: 1em 0;">
			<h2 class="aio-acf-diagnostics-section-title"><?php \esc_html_e( 'Regeneration readiness', 'aio-page-builder' ); ?></h2>
			<p>
				<?php \esc_html_e( 'Missing groups', 'aio-page-builder' ); ?>: <?php echo \esc_html( (string) $missing ); ?>;
				<?php \esc_html_e( 'version-stale', 'aio-page-builder' ); ?>: <?php echo \esc_html( (string) $stale ); ?>;
				<?php \esc_html_e( 'page-assignment repair candidates', 'aio-page-builder' ); ?>: <?php echo \esc_html( (string) $candidates ); ?>.
			</p>
			<?php if ( ! empty( $refused ) && is_array( $refused ) ) : ?>
				<p class="aio-acf-diagnostics-refused"><em><?php \esc_html_e( 'Unsafe cleanup is not supported; only regeneration from registry.', 'aio-page-builder' ); ?></em></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders repair entry point (link to regeneration tool or dry-run). Shown only when user has repair capability.
	 *
	 * @param array<string, mixed> $state
	 * @return void
	 */
	private function render_repair_entry( array $state ): void {
		if ( ! \current_user_can( $this->get_repair_capability() ) ) {
			return;
		}
		$summary         = $state['acf_diagnostics_summary'] ?? array();
		$readiness       = (string) ( $summary['repair_readiness'] ?? 'blocked' );
		$diagnostics_url = \add_query_arg( array( 'page' => self::SLUG ), \admin_url( 'admin.php' ) );
		?>
		<div class="aio-acf-diagnostics-repair card" style="max-width: 800px; padding: 1em; margin: 1em 0;">
			<h2 class="aio-acf-diagnostics-section-title"><?php \esc_html_e( 'Repair actions', 'aio-page-builder' ); ?></h2>
			<p>
				<?php \esc_html_e( 'Repair readiness', 'aio-page-builder' ); ?>: <strong><?php echo \esc_html( $readiness ); ?></strong>.
				<?php \esc_html_e( 'Use the regeneration tool to rebuild field groups and page assignments from the plugin registry (Prompt 222). Run a dry-run first to preview changes.', 'aio-page-builder' ); ?>
			</p>
			<p>
				<a href="<?php echo \esc_url( $diagnostics_url ); ?>" class="button button-secondary"><?php \esc_html_e( 'Refresh diagnostics', 'aio-page-builder' ); ?></a>
				<span class="description"><?php \esc_html_e( 'Regeneration is performed via the dedicated repair service (API or support tool).', 'aio-page-builder' ); ?></span>
			</p>
		</div>
		<?php
	}
}
