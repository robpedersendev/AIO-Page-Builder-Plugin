<?php
/**
 * Read-only comparison screen: no-goal vs goal-aware vs alternate-goal bundle/plan posture (Prompt 515).
 * Helps admins compare funnel postures before selecting a conversion goal. Admin-only; no auto-apply.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\ViewModels\Industry\Conversion_Goal_Preview_Influence_View_Model;
use AIOPageBuilder\Bootstrap\Industry_Packs_Module;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_What_If_Simulation_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders side-by-side comparison of no-goal, selected-goal, and optional alternate-goal scenarios.
 */
final class Conversion_Goal_Comparison_Screen {

	public const SLUG = 'aio-page-builder-industry-conversion-goal-comparison';

	/** GET param: alternate conversion goal key to compare (single value). */
	public const PARAM_ALTERNATE_GOAL_KEY = 'alternate_goal_key';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Conversion goal comparison', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Builds comparison state: no-goal, current-goal, and optional alternate-goal simulation results.
	 *
	 * @return array<string, mixed>
	 */
	private function get_state(): array {
		$profile_repo       = null;
		$simulation_service = null;
		$industry_loaded    = false;
		if ( $this->container instanceof Service_Container ) {
			$industry_loaded = $this->container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_LOADED )
				&& $this->container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_LOADED ) === true;
			if ( $this->container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
				$store        = $this->container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
				$profile_repo = $store instanceof Industry_Profile_Repository ? $store : null;
			}
			if ( $this->container->has( 'industry_what_if_simulation_service' ) ) {
				$sim                = $this->container->get( 'industry_what_if_simulation_service' );
				$simulation_service = $sim instanceof Industry_What_If_Simulation_Service ? $sim : null;
			}
		}

		$current_goal = '';
		if ( $profile_repo !== null ) {
			$profile      = $profile_repo->get_profile();
			$current_goal = isset( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] )
				? trim( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] )
				: '';
		}

		$alternate_goal = '';
		if ( isset( $_GET[ self::PARAM_ALTERNATE_GOAL_KEY ] ) && is_string( $_GET[ self::PARAM_ALTERNATE_GOAL_KEY ] ) ) {
			$raw = trim( sanitize_text_field( wp_unslash( $_GET[ self::PARAM_ALTERNATE_GOAL_KEY ] ) ) );
			if ( $raw !== '' && strlen( $raw ) <= 64 && preg_match( '#^[a-z0-9_-]+$#', $raw ) && $raw !== $current_goal ) {
				$alternate_goal = $raw;
			}
		}

		$scenarios = array();
		if ( $simulation_service === null ) {
			return array(
				'scenarios'            => array(),
				'profile_url'          => admin_url( 'admin.php?page=' . Industry_Profile_Settings_Screen::SLUG ),
				'current_url'          => admin_url( 'admin.php?page=' . self::SLUG ),
				'alternate_goal_param' => self::PARAM_ALTERNATE_GOAL_KEY,
				'error'                => 'missing_simulation_service',
				'industry_loaded'      => $industry_loaded,
			);
		}

		// No-goal scenario.
		$no_goal_result = $simulation_service->run_simulation(
			array(
				Industry_What_If_Simulation_Service::PARAM_ALTERNATE_CONVERSION_GOAL => '',
			)
		);
		$scenarios[]    = array(
			'label'        => __( 'No goal', 'aio-page-builder' ),
			'goal_key'     => '',
			'goal_label'   => __( 'No conversion goal', 'aio-page-builder' ),
			'valid'        => $no_goal_result['valid'],
			'invalid_refs' => $no_goal_result['invalid_refs'],
			'summary'      => $no_goal_result['simulated_profile_summary'],
			'comparison'   => $no_goal_result['comparison_simulated'],
			'warnings'     => $no_goal_result['warnings'],
		);

		// Current-goal scenario (only if different from no-goal).
		if ( $current_goal !== '' ) {
			$current_result = $simulation_service->run_simulation(
				array(
					Industry_What_If_Simulation_Service::PARAM_ALTERNATE_CONVERSION_GOAL => $current_goal,
				)
			);
			$scenarios[]    = array(
				'label'        => __( 'Current goal', 'aio-page-builder' ),
				'goal_key'     => $current_goal,
				'goal_label'   => Conversion_Goal_Preview_Influence_View_Model::goal_key_to_label( $current_goal ),
				'valid'        => $current_result['valid'],
				'invalid_refs' => $current_result['invalid_refs'],
				'summary'      => $current_result['simulated_profile_summary'],
				'comparison'   => $current_result['comparison_simulated'],
				'warnings'     => $current_result['warnings'],
			);
		}

		// Alternate-goal scenario (optional).
		if ( $alternate_goal !== '' ) {
			$alt_result  = $simulation_service->run_simulation(
				array(
					Industry_What_If_Simulation_Service::PARAM_ALTERNATE_CONVERSION_GOAL => $alternate_goal,
				)
			);
			$scenarios[] = array(
				'label'        => __( 'Alternate goal', 'aio-page-builder' ),
				'goal_key'     => $alternate_goal,
				'goal_label'   => Conversion_Goal_Preview_Influence_View_Model::goal_key_to_label( $alternate_goal ),
				'valid'        => $alt_result['valid'],
				'invalid_refs' => $alt_result['invalid_refs'],
				'summary'      => $alt_result['simulated_profile_summary'],
				'comparison'   => $alt_result['comparison_simulated'],
				'warnings'     => $alt_result['warnings'],
			);
		}

		return array(
			'scenarios'            => $scenarios,
			'profile_url'          => admin_url( 'admin.php?page=' . Industry_Profile_Settings_Screen::SLUG ),
			'current_url'          => admin_url( 'admin.php?page=' . self::SLUG ),
			'alternate_goal_param' => self::PARAM_ALTERNATE_GOAL_KEY,
			'error'                => null,
		);
	}

	/**
	 * Renders the screen. Capability enforced by menu registration.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( $this->get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access the conversion goal comparison screen.', 'aio-page-builder' ), 403 );
		}
		$state     = $this->get_state();
		$scenarios = $state['scenarios'];
		?>
		<div class="wrap aio-page-builder-screen aio-industry-conversion-goal-comparison" role="main" aria-label="<?php echo esc_attr( $this->get_title() ); ?>">
			<h1><?php echo esc_html( $this->get_title() ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Compare no-goal, current goal, and optional alternate-goal bundle and Build Plan posture. Read-only; no changes are applied. Set or change your conversion goal in Industry Profile.', 'aio-page-builder' ); ?>
			</p>

			<?php if ( isset( $state['error'] ) && $state['error'] === 'missing_simulation_service' ) : ?>
				<div class="notice notice-warning inline" style="margin: 1em 0;" role="alert">
					<p><?php esc_html_e( 'Conversion goal comparison is not available. The industry subsystem or comparison service is not loaded.', 'aio-page-builder' ); ?></p>
					<p><a href="<?php echo esc_url( $state['profile_url'] ?? admin_url( 'admin.php?page=' . Industry_Profile_Settings_Screen::SLUG ) ); ?>"><?php esc_html_e( 'Industry Profile', 'aio-page-builder' ); ?></a></p>
				</div>
				<?php return; ?>
			<?php endif; ?>

			<form method="get" action="<?php echo esc_url( $state['current_url'] ); ?>" class="aio-goal-comparison-form" style="margin: 1em 0;">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>" />
				<label for="aio-alternate-goal-key"><?php esc_html_e( 'Compare with alternate goal key (optional)', 'aio-page-builder' ); ?></label>
				<input type="text" id="aio-alternate-goal-key" name="<?php echo esc_attr( $state['alternate_goal_param'] ); ?>" value="<?php echo esc_attr( isset( $_GET[ self::PARAM_ALTERNATE_GOAL_KEY ] ) && is_string( $_GET[ self::PARAM_ALTERNATE_GOAL_KEY ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::PARAM_ALTERNATE_GOAL_KEY ] ) ) : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. bookings, lead_capture', 'aio-page-builder' ); ?>" style="width: 100%; max-width: 280px; margin-right: 0.5em;" />
				<button type="submit" class="button button-secondary"><?php esc_html_e( 'Compare', 'aio-page-builder' ); ?></button>
			</form>

			<?php if ( empty( $scenarios ) ) : ?>
				<div class="notice notice-info inline" style="margin: 1em 0;">
					<p><?php esc_html_e( 'Set a primary industry in Industry Profile to see goal comparison data.', 'aio-page-builder' ); ?>
						<a href="<?php echo esc_url( $state['profile_url'] ); ?>"><?php esc_html_e( 'Go to Industry Profile', 'aio-page-builder' ); ?></a>
					</p>
				</div>
				<?php return; ?>
			<?php endif; ?>

			<div class="aio-goal-comparison-grid" style="display: grid; grid-template-columns: repeat(<?php echo (int) count( $scenarios ); ?>, 1fr); gap: 1.5em; margin-top: 1.5em;">
				<?php foreach ( $scenarios as $idx => $s ) : ?>
					<section class="aio-goal-comparison-column" aria-labelledby="aio-goal-heading-<?php echo (int) $idx; ?>">
						<h2 id="aio-goal-heading-<?php echo (int) $idx; ?>"><?php echo esc_html( $s['label'] ); ?></h2>
						<p class="aio-goal-label"><strong><?php echo esc_html( $s['goal_label'] ); ?></strong></p>

						<?php if ( ! empty( $s['invalid_refs'] ) ) : ?>
							<div class="notice notice-warning inline" style="margin: 0.5em 0;">
								<p><?php esc_html_e( 'Invalid or unresolved references for this scenario.', 'aio-page-builder' ); ?></p>
								<ul>
									<?php foreach ( $s['invalid_refs'] as $ref ) : ?>
										<li><code><?php echo esc_html( (string) ( $ref['type'] ?? '' ) ); ?></code>: <?php echo esc_html( (string) ( $ref['key'] ?? '' ) ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $s['warnings'] ) && is_array( $s['warnings'] ) ) : ?>
							<ul class="aio-comparison-warnings">
								<?php foreach ( $s['warnings'] as $w ) : ?>
									<li><?php echo esc_html( (string) $w ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>

						<h3 class="aio-comparison-subtitle"><?php esc_html_e( 'Profile summary', 'aio-page-builder' ); ?></h3>
						<?php
						$sum     = $s['summary'] ?? array();
						$primary = $sum['primary'] ?? '';
						$subtype = $sum['subtype'] ?? '';
						$bundle  = $sum['bundle'] ?? '';
						?>
						<ul class="aio-comparison-list">
							<li><?php esc_html_e( 'Primary:', 'aio-page-builder' ); ?> <code><?php echo esc_html( $primary !== '' ? $primary : __( '—', 'aio-page-builder' ) ); ?></code></li>
							<li><?php esc_html_e( 'Subtype:', 'aio-page-builder' ); ?> <code><?php echo esc_html( $subtype !== '' ? $subtype : __( '—', 'aio-page-builder' ) ); ?></code></li>
							<li><?php esc_html_e( 'Bundle:', 'aio-page-builder' ); ?> <code><?php echo esc_html( $bundle !== '' ? $bundle : __( '—', 'aio-page-builder' ) ); ?></code></li>
						</ul>

						<?php if ( $s['goal_key'] !== '' ) : ?>
							<p class="description"><?php esc_html_e( 'This goal emphasizes CTA and page-family posture when generating Build Plans and recommendations.', 'aio-page-builder' ); ?></p>
						<?php endif; ?>

						<?php
						$comp = $s['comparison'];
						if ( is_array( $comp ) && ( ! empty( $comp['parent_bundles'] ) || ! empty( $comp['subtype_bundles'] ) ) ) :
							$bundles = ! empty( $comp['has_subtype'] ) && is_array( $comp['subtype_bundles'] ) ? $comp['subtype_bundles'] : ( is_array( $comp['parent_bundles'] ?? null ) ? $comp['parent_bundles'] : array() );
							?>
							<h3 class="aio-comparison-subtitle"><?php esc_html_e( 'Starter bundles', 'aio-page-builder' ); ?></h3>
							<?php if ( ! empty( $bundles ) ) : ?>
								<ul class="aio-comparison-list">
									<?php foreach ( $bundles as $b ) : ?>
										<li><code><?php echo esc_html( (string) ( $b['bundle_key'] ?? '' ) ); ?></code> <?php echo esc_html( (string) ( $b['label'] ?? '' ) ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php else : ?>
								<p class="description"><?php esc_html_e( 'No bundles for this context.', 'aio-page-builder' ); ?></p>
							<?php endif; ?>
						<?php endif; ?>

						<?php
						if ( is_array( $comp ) && ( ! empty( $comp['parent_top_template_keys'] ) || ! empty( $comp['subtype_top_template_keys'] ) ) ) :
							$templates = is_array( $comp['subtype_top_template_keys'] ?? null ) ? $comp['subtype_top_template_keys'] : ( is_array( $comp['parent_top_template_keys'] ?? null ) ? $comp['parent_top_template_keys'] : array() );
							?>
							<h3 class="aio-comparison-subtitle"><?php esc_html_e( 'Top page templates', 'aio-page-builder' ); ?></h3>
							<?php if ( ! empty( $templates ) ) : ?>
								<ul class="aio-comparison-list">
									<?php foreach ( array_slice( $templates, 0, 10 ) as $key ) : ?>
										<li><code><?php echo esc_html( (string) $key ); ?></code></li>
									<?php endforeach; ?>
								</ul>
							<?php else : ?>
								<p class="description"><?php esc_html_e( 'No data.', 'aio-page-builder' ); ?></p>
							<?php endif; ?>
						<?php endif; ?>

						<?php
						if ( is_array( $comp ) && ( ! empty( $comp['parent_top_section_keys'] ) || ! empty( $comp['subtype_top_section_keys'] ) ) ) :
							$sections = is_array( $comp['subtype_top_section_keys'] ?? null ) ? $comp['subtype_top_section_keys'] : ( is_array( $comp['parent_top_section_keys'] ?? null ) ? $comp['parent_top_section_keys'] : array() );
							?>
							<h3 class="aio-comparison-subtitle"><?php esc_html_e( 'Top sections', 'aio-page-builder' ); ?></h3>
							<?php if ( ! empty( $sections ) ) : ?>
								<ul class="aio-comparison-list">
									<?php foreach ( array_slice( $sections, 0, 10 ) as $key ) : ?>
										<li><code><?php echo esc_html( (string) $key ); ?></code></li>
									<?php endforeach; ?>
								</ul>
							<?php else : ?>
								<p class="description"><?php esc_html_e( 'No data.', 'aio-page-builder' ); ?></p>
							<?php endif; ?>
						<?php endif; ?>
					</section>
				<?php endforeach; ?>
			</div>

			<p class="description" style="margin-top: 1.5em;">
				<a href="<?php echo esc_url( $state['profile_url'] ); ?>"><?php esc_html_e( 'Industry Profile', 'aio-page-builder' ); ?></a>
				<?php esc_html_e( '— set or change your conversion goal there. This screen does not apply changes.', 'aio-page-builder' ); ?>
			</p>
		</div>
		<?php
	}
}
