<?php
/**
 * Compact industry status summary card for the admin dashboard (Prompt 410).
 * Shows primary/secondary industry, pack state, starter bundle, readiness, top warnings, and links to industry/health screens.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Widgets;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Screens\Industry\Industry_Health_Report_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Profile_Settings_Screen;
use AIOPageBuilder\Bootstrap\Industry_Packs_Module;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Readiness_Result;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Health_Check_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders a compact industry summary card. Safe when no industry configured; no secrets or raw internals.
 */
final class Industry_Status_Summary_Widget {

	/**
	 * Builds view model for the widget. Safe when dependencies are null.
	 *
	 * @param Service_Container $container Plugin container (industry keys optional).
	 * @return array{
	 *   has_industry: bool,
	 *   primary_label: string,
	 *   secondary_summary: string,
	 *   pack_state: string,
	 *   starter_bundle_label: string,
	 *   readiness_state: string,
	 *   readiness_summary: string,
	 *   warning_count: int,
	 *   error_count: int,
	 *   profile_url: string,
	 *   health_url: string
	 * }
	 */
	public static function build_view_model( Service_Container $container ): array {
		$empty = array(
			'has_industry'        => false,
			'primary_label'       => '',
			'secondary_summary'   => '',
			'pack_state'          => __( 'No pack', 'aio-page-builder' ),
			'starter_bundle_label' => __( 'None', 'aio-page-builder' ),
			'readiness_state'     => Industry_Profile_Readiness_Result::STATE_NONE,
			'readiness_summary'   => __( 'Not configured', 'aio-page-builder' ),
			'warning_count'       => 0,
			'error_count'         => 0,
			'profile_url'         => self::profile_url(),
			'health_url'          => self::health_url(),
		);

		if ( ! $container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
			return $empty;
		}

		/** @var Industry_Profile_Repository $profile_repo */
		$profile_repo = $container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
		$profile     = $profile_repo->get_profile();
		$primary     = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';

		if ( $primary === '' ) {
			$empty['profile_url'] = self::profile_url();
			$empty['health_url']  = self::health_url();
			return $empty;
		}

		$pack_registry   = $container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY )
			? $container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) : null;
		$bundle_registry = $container->has( Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY )
			? $container->get( Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) : null;
		$qp_registry     = $container->has( 'industry_question_pack_registry' ) ? $container->get( 'industry_question_pack_registry' ) : null;

		$primary_pack = $pack_registry instanceof Industry_Pack_Registry ? $pack_registry->get( $primary ) : null;
		$primary_label = $primary_pack !== null && isset( $primary_pack[ Industry_Pack_Schema::FIELD_NAME ] ) && is_string( $primary_pack[ Industry_Pack_Schema::FIELD_NAME ] )
			? trim( $primary_pack[ Industry_Pack_Schema::FIELD_NAME ] )
			: $primary;

		$secondary_keys = isset( $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] ) && is_array( $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] )
			? $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ]
			: array();
		$secondary_labels = array();
		if ( $pack_registry instanceof Industry_Pack_Registry ) {
			foreach ( array_slice( $secondary_keys, 0, 3 ) as $key ) {
				if ( ! is_string( $key ) ) {
					continue;
				}
				$pack = $pack_registry->get( trim( $key ) );
				$secondary_labels[] = $pack !== null && isset( $pack[ Industry_Pack_Schema::FIELD_NAME ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_NAME ] )
					? trim( $pack[ Industry_Pack_Schema::FIELD_NAME ] )
					: trim( $key );
			}
		}
		$secondary_summary = count( $secondary_labels ) > 0 ? implode( ', ', $secondary_labels ) : __( 'None', 'aio-page-builder' );
		if ( count( $secondary_keys ) > 3 ) {
			$secondary_summary .= ' +' . ( count( $secondary_keys ) - 3 );
		}

		$pack_state = __( 'No pack', 'aio-page-builder' );
		if ( $container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER ) ) {
			$toggle = $container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER );
			if ( is_object( $toggle ) && method_exists( $toggle, 'is_pack_active' ) && $toggle->is_pack_active( $primary ) ) {
				$pack_state = $primary_pack !== null && isset( $primary_pack[ Industry_Pack_Schema::FIELD_NAME ] ) && is_string( $primary_pack[ Industry_Pack_Schema::FIELD_NAME ] )
					? trim( $primary_pack[ Industry_Pack_Schema::FIELD_NAME ] )
					: __( 'Active', 'aio-page-builder' );
			} else {
				$pack_state = __( 'Inactive', 'aio-page-builder' );
			}
		} elseif ( $primary_pack !== null ) {
			$pack_state = $primary_label;
		}

		$starter_bundle_label = __( 'None', 'aio-page-builder' );
		$selected_bundle = isset( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
			: '';
		if ( $selected_bundle !== '' && $bundle_registry instanceof Industry_Starter_Bundle_Registry ) {
			$bundle = $bundle_registry->get( $selected_bundle );
			if ( $bundle !== null && isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] ) && is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] ) ) {
				$starter_bundle_label = trim( $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] );
			}
		}

		$readiness = $profile_repo->get_readiness( $pack_registry instanceof Industry_Pack_Registry ? $pack_registry : null, $qp_registry );
		$readiness_state   = $readiness->get_state();
		$readiness_summary = $readiness_state === Industry_Profile_Readiness_Result::STATE_READY
			? __( 'Ready', 'aio-page-builder' )
			: ( $readiness_state === Industry_Profile_Readiness_Result::STATE_PARTIAL
				? __( 'Partial', 'aio-page-builder' )
				: ( $readiness_state === Industry_Profile_Readiness_Result::STATE_MINIMAL ? __( 'Minimal', 'aio-page-builder' ) : __( 'Not configured', 'aio-page-builder' ) ) );

		$warning_count = 0;
		$error_count   = 0;
		if ( $container->has( 'industry_health_check_service' ) ) {
			$service = $container->get( 'industry_health_check_service' );
			if ( $service instanceof Industry_Health_Check_Service ) {
				$health = $service->run();
				$warning_count = isset( $health['warnings'] ) && is_array( $health['warnings'] ) ? count( $health['warnings'] ) : 0;
				$error_count   = isset( $health['errors'] ) && is_array( $health['errors'] ) ? count( $health['errors'] ) : 0;
			}
		}

		return array(
			'has_industry'         => true,
			'primary_label'        => $primary_label,
			'secondary_summary'     => $secondary_summary,
			'pack_state'            => $pack_state,
			'starter_bundle_label'  => $starter_bundle_label,
			'readiness_state'       => $readiness_state,
			'readiness_summary'     => $readiness_summary,
			'warning_count'         => $warning_count,
			'error_count'           => $error_count,
			'profile_url'           => self::profile_url(),
			'health_url'            => self::health_url(),
		);
	}

	private static function profile_url(): string {
		return (string) \add_query_arg( array( 'page' => Industry_Profile_Settings_Screen::SLUG ), \admin_url( 'admin.php' ) );
	}

	private static function health_url(): string {
		return (string) \add_query_arg( array( 'page' => Industry_Health_Report_Screen::SLUG ), \admin_url( 'admin.php' ) );
	}

	/**
	 * Renders the industry status card. Call from dashboard when container has industry profile store.
	 *
	 * @param Service_Container $container Plugin container.
	 * @return void
	 */
	public static function render( Service_Container $container ): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}
		$vm = self::build_view_model( $container );
		?>
		<div class="aio-dashboard-industry-summary aio-card" style="margin: 1em 0; border: 1px solid #ccc; padding: 1em; max-width: 480px;">
			<h2 class="aio-dashboard-section-title" style="margin-top: 0;"><?php \esc_html_e( 'Industry', 'aio-page-builder' ); ?></h2>
			<?php if ( ! $vm['has_industry'] ) : ?>
				<p><?php \esc_html_e( 'Not configured.', 'aio-page-builder' ); ?></p>
				<p><a href="<?php echo \esc_url( $vm['profile_url'] ); ?>"><?php \esc_html_e( 'Set up Industry Profile', 'aio-page-builder' ); ?></a></p>
			<?php else : ?>
				<ul style="list-style: none; margin: 0; padding: 0;">
					<li><strong><?php \esc_html_e( 'Primary', 'aio-page-builder' ); ?>:</strong> <?php echo \esc_html( $vm['primary_label'] ); ?></li>
					<li><strong><?php \esc_html_e( 'Secondary', 'aio-page-builder' ); ?>:</strong> <?php echo \esc_html( $vm['secondary_summary'] ); ?></li>
					<li><strong><?php \esc_html_e( 'Pack', 'aio-page-builder' ); ?>:</strong> <?php echo \esc_html( $vm['pack_state'] ); ?></li>
					<li><strong><?php \esc_html_e( 'Starter bundle', 'aio-page-builder' ); ?>:</strong> <?php echo \esc_html( $vm['starter_bundle_label'] ); ?></li>
					<li><strong><?php \esc_html_e( 'Readiness', 'aio-page-builder' ); ?>:</strong> <?php echo \esc_html( $vm['readiness_summary'] ); ?></li>
					<?php if ( $vm['error_count'] > 0 || $vm['warning_count'] > 0 ) : ?>
						<li><strong><?php \esc_html_e( 'Health', 'aio-page-builder' ); ?>:</strong>
							<?php
							if ( $vm['error_count'] > 0 ) {
								/* translators: %d: error count */
								echo \esc_html( sprintf( __( '%d error(s)', 'aio-page-builder' ), $vm['error_count'] ) );
							}
							if ( $vm['error_count'] > 0 && $vm['warning_count'] > 0 ) {
								echo ' ';
							}
							if ( $vm['warning_count'] > 0 ) {
								/* translators: %d: warning count */
								echo \esc_html( sprintf( __( '%d warning(s)', 'aio-page-builder' ), $vm['warning_count'] ) );
							}
							?>
						</li>
					<?php endif; ?>
				</ul>
				<p style="margin-bottom: 0;">
					<a href="<?php echo \esc_url( $vm['profile_url'] ); ?>"><?php \esc_html_e( 'Industry Profile', 'aio-page-builder' ); ?></a>
					<?php if ( $vm['error_count'] > 0 || $vm['warning_count'] > 0 ) : ?>
						| <a href="<?php echo \esc_url( $vm['health_url'] ); ?>"><?php \esc_html_e( 'Health report', 'aio-page-builder' ); ?></a>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
