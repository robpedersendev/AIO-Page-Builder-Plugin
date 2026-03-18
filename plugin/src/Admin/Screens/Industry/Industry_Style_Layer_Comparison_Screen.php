<?php
/**
 * Read-only comparison of parent (industry), goal overlay, and combined style layers (Prompt 549).
 * Shows token and component differences; supports fallback when layers are missing.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Bootstrap\Industry_Packs_Module;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Style_Layer_Diff_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders style layer comparison: parent industry preset, subtype (when available), goal overlay, combined.
 */
final class Industry_Style_Layer_Comparison_Screen {

	public const SLUG = 'aio-page-builder-industry-style-layer-comparison';

	/** GET param: preset_key to compare. */
	public const PARAM_PRESET_KEY = 'preset_key';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Style layer comparison', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Builds state: profile industry/goal, presets for industry, selected preset, diff result.
	 *
	 * @return array<string, mixed>
	 */
	private function get_state(): array {
		$primary_industry = '';
		$conversion_goal  = '';
		$presets          = array();
		$selected_preset  = '';
		$diff_result      = array(
			Industry_Style_Layer_Diff_Service::RESULT_PARENT => array( 'present' => false ),
			Industry_Style_Layer_Diff_Service::RESULT_GOAL => array( 'present' => false ),
			Industry_Style_Layer_Diff_Service::RESULT_COMBINED => array(),
			Industry_Style_Layer_Diff_Service::RESULT_TOKEN_DIFF_ROWS => array(),
			Industry_Style_Layer_Diff_Service::RESULT_COMPONENT_DIFF_ROWS => array(),
		);

		if ( $this->container instanceof Service_Container ) {
			$store = null;
			if ( $this->container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
				$s = $this->container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
				if ( $s instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ) {
					$store = $s;
				}
			}
			if ( $store !== null ) {
				$profile          = $store->get_profile();
				$primary_industry = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
					? trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
					: '';
				$conversion_goal  = isset( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] )
					? trim( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] )
					: '';
			}

			$preset_registry  = null;
			$overlay_registry = null;
			if ( $this->container->has( 'industry_style_preset_registry' ) ) {
				$r               = $this->container->get( 'industry_style_preset_registry' );
				$preset_registry = $r instanceof Industry_Style_Preset_Registry ? $r : null;
			}
			if ( $this->container->has( 'goal_style_preset_overlay_registry' ) ) {
				$r                = $this->container->get( 'goal_style_preset_overlay_registry' );
				$overlay_registry = $r instanceof \AIOPageBuilder\Domain\Industry\Registry\Goal_Style_Preset_Overlay_Registry ? $r : null;
			}

			if ( $preset_registry !== null && $primary_industry !== '' ) {
				$presets = $preset_registry->list_by_industry( $primary_industry );
				$presets = array_values(
					array_filter(
						$presets,
						function ( $p ) {
							return ( $p[ Industry_Style_Preset_Registry::FIELD_STATUS ] ?? '' ) === Industry_Style_Preset_Registry::STATUS_ACTIVE;
						}
					)
				);
			}

			if ( isset( $_GET[ self::PARAM_PRESET_KEY ] ) && is_string( $_GET[ self::PARAM_PRESET_KEY ] ) ) {
				$selected_preset = trim( sanitize_text_field( wp_unslash( $_GET[ self::PARAM_PRESET_KEY ] ) ) );
			}
			if ( $selected_preset === '' && ! empty( $presets ) ) {
				$first           = $presets[0];
				$selected_preset = (string) ( $first[ Industry_Style_Preset_Registry::FIELD_STYLE_PRESET_KEY ] ?? '' );
			}

			if ( $selected_preset !== '' && $preset_registry !== null ) {
				$service     = new Industry_Style_Layer_Diff_Service( $preset_registry, $overlay_registry );
				$diff_result = $service->compare( $selected_preset, $conversion_goal );
			}
		}

		return array(
			'primary_industry' => $primary_industry,
			'conversion_goal'  => $conversion_goal,
			'presets'          => $presets,
			'selected_preset'  => $selected_preset,
			'diff_result'      => $diff_result,
			'profile_url'      => admin_url( 'admin.php?page=' . Industry_Profile_Settings_Screen::SLUG ),
			'style_preset_url' => admin_url( 'admin.php?page=' . Industry_Style_Preset_Screen::SLUG ),
			'current_url'      => admin_url( 'admin.php?page=' . self::SLUG ),
		);
	}

	/**
	 * Renders the screen. Capability enforced at menu registration.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( $this->get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access the style layer comparison screen.', 'aio-page-builder' ), 403 );
		}
		$state            = $this->get_state();
		$primary_industry = $state['primary_industry'];
		$presets          = $state['presets'];
		$selected_preset  = $state['selected_preset'];
		$diff             = $state['diff_result'];
		$parent           = $diff[ Industry_Style_Layer_Diff_Service::RESULT_PARENT ];
		$goal             = $diff[ Industry_Style_Layer_Diff_Service::RESULT_GOAL ];
		$token_rows       = $diff[ Industry_Style_Layer_Diff_Service::RESULT_TOKEN_DIFF_ROWS ];
		$component_rows   = $diff[ Industry_Style_Layer_Diff_Service::RESULT_COMPONENT_DIFF_ROWS ];
		?>
		<div class="wrap aio-page-builder-screen aio-industry-style-layer-comparison" role="main" aria-label="<?php echo esc_attr( $this->get_title() ); ?>">
			<h1><?php echo esc_html( $this->get_title() ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Compare parent (industry) preset, goal overlay, and combined style outcome. Read-only; no style changes are applied from this screen.', 'aio-page-builder' ); ?>
			</p>

			<?php if ( $primary_industry === '' ) : ?>
				<p class="notice notice-info">
					<?php esc_html_e( 'Set your Industry Profile (primary industry) to see presets and compare layers.', 'aio-page-builder' ); ?>
					<a href="<?php echo esc_url( $state['profile_url'] ); ?>"><?php esc_html_e( 'Industry Profile', 'aio-page-builder' ); ?></a>
				</p>
			<?php elseif ( empty( $presets ) ) : ?>
				<p class="notice notice-info">
					<?php esc_html_e( 'No style presets are available for your current industry.', 'aio-page-builder' ); ?>
				</p>
			<?php else : ?>
				<form method="get" action="<?php echo esc_url( $state['current_url'] ); ?>" class="aio-style-layer-comparison-form" style="margin: 1em 0;">
					<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>" />
					<label for="aio-preset-key"><?php esc_html_e( 'Preset to compare', 'aio-page-builder' ); ?></label>
					<select id="aio-preset-key" name="<?php echo esc_attr( self::PARAM_PRESET_KEY ); ?>">
						<?php
						foreach ( $presets as $p ) :
							$key   = $p[ Industry_Style_Preset_Registry::FIELD_STYLE_PRESET_KEY ] ?? '';
							$label = $p[ Industry_Style_Preset_Registry::FIELD_LABEL ] ?? $key;
							?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected_preset, $key ); ?>><?php echo esc_html( $label ); ?> (<?php echo esc_html( $key ); ?>)</option>
						<?php endforeach; ?>
					</select>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Compare', 'aio-page-builder' ); ?></button>
				</form>

				<?php if ( $selected_preset !== '' && $parent['present'] ) : ?>
					<p class="description">
						<?php
						printf(
							/* translators: 1: preset label/key, 2: conversion goal or "none" */
							esc_html__( 'Preset: %1$s. Conversion goal: %2$s.', 'aio-page-builder' ),
							esc_html( $parent['label'] ?: $selected_preset ),
							$state['conversion_goal'] !== '' ? esc_html( $state['conversion_goal'] ) : esc_html__( 'none', 'aio-page-builder' )
						);
						?>
					</p>

					<?php if ( ! empty( $token_rows ) ) : ?>
						<h2 class="aio-style-diff-section"><?php esc_html_e( 'Token differences', 'aio-page-builder' ); ?></h2>
						<table class="wp-list-table widefat fixed striped aio-style-layer-diff-table">
							<thead>
								<tr>
									<th scope="col" style="width: 20%;"><?php esc_html_e( 'Token', 'aio-page-builder' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Parent (industry)', 'aio-page-builder' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Goal overlay', 'aio-page-builder' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Combined', 'aio-page-builder' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $token_rows as $row ) : ?>
									<tr class="<?php echo $row['changed'] ? 'aio-diff-row-changed' : ''; ?>">
										<td><code><?php echo esc_html( $row['token_key'] ); ?></code><?php echo $row['changed'] ? ' <span class="aio-diff-badge" aria-label="' . esc_attr__( 'Differs', 'aio-page-builder' ) . '">*</span>' : ''; ?></td>
										<td><?php echo esc_html( $row['parent'] ); ?></td>
										<td><?php echo esc_html( $row['goal'] ); ?></td>
										<td><?php echo esc_html( $row['combined'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>

					<?php if ( ! empty( $component_rows ) ) : ?>
						<h2 class="aio-style-diff-section"><?php esc_html_e( 'Component override refs', 'aio-page-builder' ); ?></h2>
						<table class="wp-list-table widefat fixed striped aio-style-layer-diff-table">
							<thead>
								<tr>
									<th scope="col" style="width: 25%;"><?php esc_html_e( 'Ref', 'aio-page-builder' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Parent', 'aio-page-builder' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Goal overlay', 'aio-page-builder' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Combined', 'aio-page-builder' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $component_rows as $row ) : ?>
									<tr class="<?php echo $row['changed'] ? 'aio-diff-row-changed' : ''; ?>">
										<td><code><?php echo esc_html( $row['ref'] ); ?></code><?php echo $row['changed'] ? ' <span class="aio-diff-badge" aria-label="' . esc_attr__( 'Differs', 'aio-page-builder' ) . '">*</span>' : ''; ?></td>
										<td><?php echo $row['parent'] ? esc_html__( 'Yes', 'aio-page-builder' ) : '—'; ?></td>
										<td><?php echo $row['goal'] ? esc_html__( 'Yes', 'aio-page-builder' ) : '—'; ?></td>
										<td><?php echo $row['combined'] ? esc_html__( 'Yes', 'aio-page-builder' ) : '—'; ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>

					<?php if ( empty( $token_rows ) && empty( $component_rows ) ) : ?>
						<p class="description"><?php esc_html_e( 'No token or component overrides in this preset (or combined).', 'aio-page-builder' ); ?></p>
					<?php endif; ?>

					<p class="description" style="margin-top: 1em;">
						<a href="<?php echo esc_url( $state['style_preset_url'] ); ?>"><?php esc_html_e( 'Industry Style Preset', 'aio-page-builder' ); ?></a>
						<?php esc_html_e( '— apply a preset there. Subtype-specific presets are shown when available in the registry.', 'aio-page-builder' ); ?>
					</p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}
}
