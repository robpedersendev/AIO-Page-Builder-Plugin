<?php
/**
 * Admin screen for selecting and applying industry style presets (industry-style-preset-application-contract.md).
 * Lists presets for active industry; apply routes through existing styling storage.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders industry style preset list and apply forms. Apply handler in Admin_Menu.
 */
final class Industry_Style_Preset_Screen {

	public const SLUG = 'aio-page-builder-industry-style-preset';

	private const APPLY_ACTION = 'aio_apply_industry_style_preset';
	private const NONCE_ACTION = 'aio_apply_industry_style_preset';
	private const NONCE_NAME   = 'aio_industry_style_preset_nonce';
	private const QUERY_MSG    = 'aio_style_preset_msg';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Industry Style Preset', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::MANAGE_SETTINGS;
	}

	/**
	 * Builds state: primary industry, presets for that industry, applied preset, form action.
	 *
	 * @return array<string, mixed>
	 */
	private function get_state(): array {
		$primary             = '';
		$presets             = array();
		$applied             = null;
		$profile_repo        = null;
		$preset_registry     = null;
		$application_service = null;

		if ( $this->container instanceof Service_Container ) {
			if ( $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
				$store = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
				if ( $store instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ) {
					$profile_repo = $store;
				}
			}
			if ( $this->container->has( 'industry_style_preset_registry' ) ) {
				$preset_registry = $this->container->get( 'industry_style_preset_registry' );
			}
			if ( $this->container->has( 'industry_style_preset_application_service' ) ) {
				$application_service = $this->container->get( 'industry_style_preset_application_service' );
			}
		}

		if ( $profile_repo !== null ) {
			$profile = $profile_repo->get_profile();
			$primary = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
				? trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
				: '';
		}
		if ( $preset_registry !== null && $primary !== '' ) {
			$presets = $preset_registry->list_by_industry( $primary );
			$presets = array_filter(
				$presets,
				function ( $p ) {
					return ( $p[ Industry_Style_Preset_Registry::FIELD_STATUS ] ?? '' ) === Industry_Style_Preset_Registry::STATUS_ACTIVE;
				}
			);
		}
		if ( $application_service !== null ) {
			$applied = $application_service->get_applied_preset();
		}

		return array(
			'primary_industry' => $primary,
			'presets'          => array_values( $presets ),
			'applied_preset'   => $applied,
			'form_action'      => \admin_url( 'admin-post.php' ),
			'apply_action'     => self::APPLY_ACTION,
			'nonce_action'     => self::NONCE_ACTION,
			'nonce_name'       => self::NONCE_NAME,
		);
	}

	/**
	 * Renders the screen. Capability enforced at menu; screen checks again.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to manage style presets.', 'aio-page-builder' ), 403 );
		}
		$state             = $this->get_state();
		$message           = isset( $_GET[ self::QUERY_MSG ] ) ? \sanitize_text_field( \wp_unslash( $_GET[ self::QUERY_MSG ] ) ) : '';
		$global_tokens_url = \admin_url( 'admin.php?page=' . \AIOPageBuilder\Admin\Screens\Settings\Global_Style_Token_Settings_Screen::SLUG );
		?>
		<div class="wrap aio-page-builder-screen aio-industry-style-preset" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>

			<?php if ( $message === 'applied' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Preset applied. Token values have been merged into global style settings.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $message === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Preset could not be applied. Invalid preset or styling unavailable.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<p class="description">
				<?php \esc_html_e( 'Apply an industry style preset to set global design token values in one step. Presets are optional and reversible via Global Style Tokens.', 'aio-page-builder' ); ?>
				<a href="<?php echo \esc_url( $global_tokens_url ); ?>"><?php \esc_html_e( 'Edit Global Style Tokens', 'aio-page-builder' ); ?></a>
			</p>

			<?php if ( $state['primary_industry'] === '' ) : ?>
				<p><?php \esc_html_e( 'Set your Industry Profile (primary industry) to see presets for your industry.', 'aio-page-builder' ); ?></p>
			<?php elseif ( empty( $state['presets'] ) ) : ?>
				<p><?php \esc_html_e( 'No style presets are available for your current industry.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<?php if ( $state['applied_preset'] !== null ) : ?>
					<p><strong><?php \esc_html_e( 'Currently applied:', 'aio-page-builder' ); ?></strong> <?php echo \esc_html( ( $state['applied_preset']['label'] !== '' && $state['applied_preset']['label'] !== null ) ? $state['applied_preset']['label'] : $state['applied_preset']['preset_key'] ); ?></p>
				<?php endif; ?>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'Preset', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Description', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Action', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $state['presets'] as $preset ) :
							$key         = $preset[ Industry_Style_Preset_Registry::FIELD_STYLE_PRESET_KEY ] ?? '';
							$label       = $preset[ Industry_Style_Preset_Registry::FIELD_LABEL ] ?? $key;
							$description = $preset[ Industry_Style_Preset_Registry::FIELD_DESCRIPTION ] ?? '';
							?>
							<tr>
								<td><strong><?php echo \esc_html( $label ); ?></strong></td>
								<td><?php echo \esc_html( $description ); ?></td>
								<td>
									<form method="post" action="<?php echo \esc_url( $state['form_action'] ); ?>" style="display:inline;">
										<input type="hidden" name="action" value="<?php echo \esc_attr( $state['apply_action'] ); ?>" />
										<input type="hidden" name="preset_key" value="<?php echo \esc_attr( $key ); ?>" />
										<?php \wp_nonce_field( $state['nonce_action'], $state['nonce_name'], true ); ?>
										<button type="submit" class="button"><?php \esc_html_e( 'Apply', 'aio-page-builder' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
