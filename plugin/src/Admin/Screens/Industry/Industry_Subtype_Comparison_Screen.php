<?php
/**
 * Internal comparison screen: parent-industry vs subtype starter bundles and recommendations (Prompt 442).
 * Read-only; helps users understand what subtype selection changes before committing. Admin-only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Bootstrap\Industry_Packs_Module;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Subtype_Comparison_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders side-by-side comparison of parent vs subtype bundles and recommendation highlights.
 */
final class Industry_Subtype_Comparison_Screen {

	public const SLUG = 'aio-page-builder-industry-subtype-comparison';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Subtype comparison', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Builds comparison state from current profile and comparison service.
	 *
	 * @return array<string, mixed>
	 */
	private function get_state(): array {
		$primary     = '';
		$subtype_key = '';
		if ( $this->container instanceof Service_Container && $this->container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
			$repo = $this->container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
			if ( $repo instanceof Industry_Profile_Repository ) {
				$profile     = $repo->get_profile();
				$primary     = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && \is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
					? \trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
					: '';
				$subtype_key = isset( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ) && \is_string( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
					? \trim( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
					: '';
			}
		}
		$comparison = array(
			'primary_industry_key'      => '',
			'subtype_key'               => '',
			'subtype_label'             => '',
			'parent_bundles'            => array(),
			'subtype_bundles'           => array(),
			'parent_top_template_keys'  => array(),
			'parent_top_section_keys'   => array(),
			'subtype_top_template_keys' => array(),
			'subtype_top_section_keys'  => array(),
			'pack_found'                => false,
			'has_subtype'               => false,
		);
		if ( $primary !== '' && $this->container instanceof Service_Container && $this->container->has( 'industry_subtype_comparison_service' ) ) {
			$service = $this->container->get( 'industry_subtype_comparison_service' );
			if ( $service instanceof Industry_Subtype_Comparison_Service ) {
				$comparison = $service->get_comparison( $primary, $subtype_key );
			}
		}
		return array(
			'primary_industry_key' => $primary,
			'subtype_key'          => $subtype_key,
			'comparison'           => $comparison,
			'profile_url'          => Admin_Screen_Hub::tab_url( Industry_Profile_Settings_Screen::SLUG, 'profile' ),
		);
	}

	/**
	 * Renders the screen. Capability enforced by menu registration.
	 *
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access the subtype comparison screen.', 'aio-page-builder' ), 403 );
		}
		$state         = $this->get_state();
		$c             = $state['comparison'];
		$primary       = $state['primary_industry_key'];
		$parent_label  = $primary !== '' ? \ucfirst( \str_replace( array( '_', '-' ), ' ', $primary ) ) : __( 'Parent industry', 'aio-page-builder' );
		$subtype_label = isset( $c['subtype_label'] ) && $c['subtype_label'] !== '' ? $c['subtype_label'] : __( 'Subtype', 'aio-page-builder' );
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-industry-subtype-comparison" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="description">
				<?php \esc_html_e( 'Compare parent-industry vs subtype starter bundles and recommendation highlights. Read-only; no changes are applied.', 'aio-page-builder' ); ?>
			</p>

			<?php if ( $primary === '' ) : ?>
				<div class="notice notice-warning inline" style="margin: 1em 0;">
					<p><?php \esc_html_e( 'Set a primary industry in Industry Profile to see comparison data.', 'aio-page-builder' ); ?>
						<a href="<?php echo \esc_url( $state['profile_url'] ); ?>"><?php \esc_html_e( 'Go to Industry Profile', 'aio-page-builder' ); ?></a>
					</p>
				</div>
				<?php return; ?>
			<?php endif; ?>

			<?php if ( ! empty( $c['has_subtype'] ) ) : ?>
				<p class="aio-comparison-context">
					<?php
					echo \esc_html(
						sprintf(
							/* translators: 1: parent industry label, 2: subtype label */
							__( 'Comparing %1$s (parent) with subtype: %2$s.', 'aio-page-builder' ),
							$parent_label,
							$subtype_label
						)
					);
					?>
				</p>
			<?php else : ?>
				<div class="notice notice-info inline" style="margin: 1em 0;">
					<p><?php \esc_html_e( 'Select a subtype in Industry Profile to compare parent vs subtype bundles and recommendations.', 'aio-page-builder' ); ?>
						<a href="<?php echo \esc_url( $state['profile_url'] ); ?>"><?php \esc_html_e( 'Industry Profile', 'aio-page-builder' ); ?></a>
					</p>
				</div>
			<?php endif; ?>

			<div class="aio-comparison-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5em; margin-top: 1.5em;">
				<section class="aio-comparison-column aio-comparison-parent" aria-labelledby="aio-comparison-parent-heading">
					<h2 id="aio-comparison-parent-heading"><?php echo \esc_html( $parent_label ); ?></h2>
					<h3 class="aio-comparison-subtitle"><?php \esc_html_e( 'Starter bundles', 'aio-page-builder' ); ?></h3>
					<?php if ( \is_array( $c['parent_bundles'] ) && count( $c['parent_bundles'] ) > 0 ) : ?>
						<ul class="aio-comparison-list">
							<?php foreach ( $c['parent_bundles'] as $b ) : ?>
								<li><code><?php echo \esc_html( $b['bundle_key'] ?? '' ); ?></code> <?php echo \esc_html( $b['label'] ?? '' ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="description"><?php \esc_html_e( 'No starter bundles for parent.', 'aio-page-builder' ); ?></p>
					<?php endif; ?>
					<h3 class="aio-comparison-subtitle"><?php \esc_html_e( 'Top page templates', 'aio-page-builder' ); ?></h3>
					<?php if ( \is_array( $c['parent_top_template_keys'] ) && count( $c['parent_top_template_keys'] ) > 0 ) : ?>
						<ul class="aio-comparison-list">
							<?php foreach ( $c['parent_top_template_keys'] as $key ) : ?>
								<li><code><?php echo \esc_html( $key ); ?></code></li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="description"><?php \esc_html_e( 'No data.', 'aio-page-builder' ); ?></p>
					<?php endif; ?>
					<h3 class="aio-comparison-subtitle"><?php \esc_html_e( 'Top sections', 'aio-page-builder' ); ?></h3>
					<?php if ( \is_array( $c['parent_top_section_keys'] ) && count( $c['parent_top_section_keys'] ) > 0 ) : ?>
						<ul class="aio-comparison-list">
							<?php foreach ( $c['parent_top_section_keys'] as $key ) : ?>
								<li><code><?php echo \esc_html( $key ); ?></code></li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="description"><?php \esc_html_e( 'No data.', 'aio-page-builder' ); ?></p>
					<?php endif; ?>
				</section>

				<section class="aio-comparison-column aio-comparison-subtype" aria-labelledby="aio-comparison-subtype-heading">
					<h2 id="aio-comparison-subtype-heading"><?php echo \esc_html( $subtype_label ); ?></h2>
					<?php if ( ! empty( $c['has_subtype'] ) ) : ?>
						<h3 class="aio-comparison-subtitle"><?php \esc_html_e( 'Starter bundles', 'aio-page-builder' ); ?></h3>
						<?php if ( \is_array( $c['subtype_bundles'] ) && count( $c['subtype_bundles'] ) > 0 ) : ?>
							<ul class="aio-comparison-list">
								<?php foreach ( $c['subtype_bundles'] as $b ) : ?>
									<li><code><?php echo \esc_html( $b['bundle_key'] ?? '' ); ?></code> <?php echo \esc_html( $b['label'] ?? '' ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p class="description"><?php \esc_html_e( 'No subtype-specific bundles; parent bundles apply.', 'aio-page-builder' ); ?></p>
						<?php endif; ?>
						<h3 class="aio-comparison-subtitle"><?php \esc_html_e( 'Top page templates', 'aio-page-builder' ); ?></h3>
						<?php if ( \is_array( $c['subtype_top_template_keys'] ) && count( $c['subtype_top_template_keys'] ) > 0 ) : ?>
							<ul class="aio-comparison-list">
								<?php foreach ( $c['subtype_top_template_keys'] as $key ) : ?>
									<li><code><?php echo \esc_html( $key ); ?></code></li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p class="description"><?php \esc_html_e( 'Same as parent or no data.', 'aio-page-builder' ); ?></p>
						<?php endif; ?>
						<h3 class="aio-comparison-subtitle"><?php \esc_html_e( 'Top sections', 'aio-page-builder' ); ?></h3>
						<?php if ( \is_array( $c['subtype_top_section_keys'] ) && count( $c['subtype_top_section_keys'] ) > 0 ) : ?>
							<ul class="aio-comparison-list">
								<?php foreach ( $c['subtype_top_section_keys'] as $key ) : ?>
									<li><code><?php echo \esc_html( $key ); ?></code></li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p class="description"><?php \esc_html_e( 'Same as parent or no data.', 'aio-page-builder' ); ?></p>
						<?php endif; ?>
					<?php else : ?>
						<p class="description"><?php \esc_html_e( 'Select a subtype in Industry Profile to see comparison.', 'aio-page-builder' ); ?></p>
					<?php endif; ?>
				</section>
			</div>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
}
