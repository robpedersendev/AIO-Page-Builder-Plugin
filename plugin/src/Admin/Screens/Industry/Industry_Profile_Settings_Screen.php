<?php
/**
 * Industry Profile settings screen (industry-admin-screen-contract).
 * View/edit primary and secondary industry, readiness, active pack status; save via admin_post handler.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Forms\Industry_Profile_Form_Builder;
use AIOPageBuilder\Admin\Forms\Industry_Subtype_Form_Field;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Readiness_Result;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders Industry Profile form and readiness/pack status. Save is handled by Admin_Menu::handle_save_industry_profile().
 */
final class Industry_Profile_Settings_Screen {

	public const SLUG = 'aio-page-builder-industry-profile';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Industry Profile', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::MANAGE_SETTINGS;
	}

	/**
	 * Builds state for the screen: profile, readiness, active_pack, warnings, form config.
	 *
	 * @return array<string, mixed>
	 */
	private function get_state(): array {
		$profile      = array();
		$readiness    = null;
		$active_pack  = null;
		$warnings     = array();
		$form_builder = null;

		if ( $this->container instanceof Service_Container ) {
			$repo = $this->get_profile_repository();
			if ( $repo !== null ) {
				$profile = $repo->get_profile();
			}
			$validator     = $this->get_validator();
			$pack_registry = $this->get_pack_registry();
			$qp_registry   = $this->container->has( 'industry_question_pack_registry' )
				? $this->container->get( 'industry_question_pack_registry' )
				: null;
			if ( $validator !== null ) {
				$readiness = $validator->get_readiness( $profile, $pack_registry, $qp_registry );
				$warnings  = $readiness->get_validation_warnings();
			}
			$primary = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
				? trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
				: '';
			if ( $primary !== '' && $pack_registry !== null ) {
				$pack = $pack_registry->get( $primary );
				if ( $pack !== null ) {
					$active_pack = array(
						'key'    => $primary,
						'name'   => (string) ( $pack[ Industry_Pack_Schema::FIELD_NAME ] ?? $primary ),
						'status' => (string) ( $pack[ Industry_Pack_Schema::FIELD_STATUS ] ?? '' ),
					);
				}
			}
			$form_builder             = new Industry_Profile_Form_Builder( $pack_registry );
			$subtype_registry         = $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_SUBTYPE_REGISTRY )
				? $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_SUBTYPE_REGISTRY )
				: null;
			$subtype_form_field       = new Industry_Subtype_Form_Field( $subtype_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry ? $subtype_registry : null );
			$bundle_registry          = $this->get_starter_bundle_registry();
			$starter_bundle_assistant = new Industry_Starter_Bundle_Assistant( $bundle_registry, $subtype_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry ? $subtype_registry : null );
			$starter_bundle_state     = $starter_bundle_assistant->build_state( $profile );
			$toggle_controller        = $this->get_pack_toggle_controller();
			$primary_pack_is_disabled = $primary !== '' && $toggle_controller !== null && ! $toggle_controller->is_pack_active( $primary );
		} else {
			$form_builder             = new Industry_Profile_Form_Builder( null );
			$subtype_form_field       = new Industry_Subtype_Form_Field( null );
			$starter_bundle_assistant = null;
			$starter_bundle_state     = array(
				'has_primary'          => false,
				'primary_industry_key' => '',
				'bundles'              => array(),
				'selected_key'         => '',
				'field_name'           => Industry_Starter_Bundle_Assistant::FIELD_NAME,
			);
			$toggle_controller        = null;
			$primary_pack_is_disabled = false;
		}

		return array(
			'profile'                  => $profile,
			'readiness'                => $readiness,
			'active_pack'              => $active_pack,
			'warnings'                 => $warnings,
			'form_builder'             => $form_builder,
			'form_action'              => \admin_url( 'admin-post.php' ),
			'save_action'              => 'aio_save_industry_profile',
			'starter_bundle_state'     => $starter_bundle_state,
			'starter_bundle_assistant' => isset( $starter_bundle_assistant ) ? $starter_bundle_assistant : null,
			'toggle_controller'        => $toggle_controller,
			'primary_pack_is_disabled' => $primary_pack_is_disabled,
			'primary_industry_key'     => isset( $primary ) ? $primary : '',
			'subtype_form_field'       => $subtype_form_field,
		);
	}

	private function get_pack_toggle_controller(): ?\AIOPageBuilder\Admin\Screens\Industry\Industry_Pack_Toggle_Controller {
		if ( $this->container === null || ! $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER ) ) {
			return null;
		}
		$c = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER );
		return $c instanceof \AIOPageBuilder\Admin\Screens\Industry\Industry_Pack_Toggle_Controller ? $c : null;
	}

	private function get_starter_bundle_registry(): ?\AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry {
		if ( $this->container === null || ! $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) ) {
			return null;
		}
		$r = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY );
		return $r instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry ? $r : null;
	}

	private function get_profile_repository(): ?Industry_Profile_Repository {
		if ( $this->container === null || ! $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
			return null;
		}
		$store = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
		return $store instanceof Industry_Profile_Repository ? $store : null;
	}

	private function get_validator(): ?\AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Validator {
		if ( $this->container === null || ! $this->container->has( 'industry_profile_validator' ) ) {
			return new \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Validator();
		}
		$v = $this->container->get( 'industry_profile_validator' );
		return $v instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Validator ? $v : null;
	}

	private function get_pack_registry(): ?\AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry {
		if ( $this->container === null || ! $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ) {
			return null;
		}
		$r = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY );
		return $r instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ? $r : null;
	}

	/**
	 * Renders the screen. Capability enforced by menu; screen checks again.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to manage industry profile.', 'aio-page-builder' ), 403 );
		}
		$state = $this->get_state();
		/** @var Industry_Profile_Form_Builder $form_builder */
		$form_builder   = $state['form_builder'];
		$field_config   = $form_builder->get_field_config();
		$profile        = $state['profile'];
		$readiness      = $state['readiness'];
		$active_pack    = $state['active_pack'];
		$warnings       = $state['warnings'];
		$result_message = isset( $_GET['aio_industry_result'] ) ? \sanitize_text_field( \wp_unslash( $_GET['aio_industry_result'] ) ) : '';
		?>
		<div class="wrap aio-page-builder-screen aio-industry-profile-settings" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>

			<?php if ( $result_message === 'saved' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Industry profile saved.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $result_message === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Industry profile could not be saved. Check validation messages below.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $result_message === 'toggled' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Industry pack setting updated.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $result_message === 'toggle_error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Industry pack toggle failed. Try again or check permissions.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<section class="aio-readiness" aria-labelledby="aio-industry-readiness-heading">
				<h2 id="aio-industry-readiness-heading"><?php \esc_html_e( 'Readiness', 'aio-page-builder' ); ?></h2>
				<?php if ( $readiness instanceof Industry_Profile_Readiness_Result ) : ?>
					<p>
						<strong><?php \esc_html_e( 'State:', 'aio-page-builder' ); ?></strong>
						<?php echo \esc_html( $readiness->get_state() ); ?>
						(<?php echo \esc_html( (string) $readiness->get_score() ); ?>%)
					</p>
					<?php if ( $readiness->get_validation_errors() !== array() ) : ?>
						<ul class="aio-validation-errors">
							<?php foreach ( $readiness->get_validation_errors() as $err ) : ?>
								<li><?php echo \esc_html( $err ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				<?php else : ?>
					<p><?php \esc_html_e( 'Unable to compute readiness.', 'aio-page-builder' ); ?></p>
				<?php endif; ?>
			</section>

			<?php if ( $active_pack === null ) : ?>
			<section class="aio-neutral-mode-notice" aria-live="polite">
				<p class="description"><?php \esc_html_e( 'No industry selected. Template and section recommendations use the generic library; set a primary industry below for industry-specific presets and guidance.', 'aio-page-builder' ); ?></p>
			</section>
			<?php endif; ?>

			<?php if ( $active_pack !== null ) : ?>
			<section class="aio-active-pack" aria-labelledby="aio-active-pack-heading">
				<h2 id="aio-active-pack-heading"><?php \esc_html_e( 'Active industry pack', 'aio-page-builder' ); ?></h2>
				<p><strong><?php echo \esc_html( $active_pack['name'] ); ?></strong> (<?php echo \esc_html( $active_pack['key'] ); ?>) — <?php echo \esc_html( $active_pack['status'] ); ?></p>
				<?php if ( ! empty( $state['primary_pack_is_disabled'] ) ) : ?>
				<div class="notice notice-warning inline" role="alert">
					<p><?php \esc_html_e( 'This industry pack is currently disabled. Recommendations and overlays will use generic behavior until you re-enable it. Your profile selection is preserved.', 'aio-page-builder' ); ?></p>
				</div>
					<?php
					$toggle_controller = $state['toggle_controller'] ?? null;
					$primary_key       = $state['primary_industry_key'] ?? '';
					if ( $toggle_controller !== null && $primary_key !== '' ) :
						$toggle_action = 'aio_toggle_industry_pack';
						$toggle_nonce  = \wp_create_nonce( $toggle_action );
						?>
				<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 0.5rem;">
					<input type="hidden" name="action" value="<?php echo \esc_attr( $toggle_action ); ?>" />
					<input type="hidden" name="aio_industry_pack_key" value="<?php echo \esc_attr( $primary_key ); ?>" />
					<input type="hidden" name="aio_industry_pack_disable" value="0" />
						<?php \wp_nonce_field( $toggle_action, 'aio_toggle_industry_pack_nonce', true ); ?>
					<button type="submit" class="button button-secondary"><?php \esc_html_e( 'Re-enable this pack', 'aio-page-builder' ); ?></button>
				</form>
				<?php endif; ?>
				<?php elseif ( ( $state['toggle_controller'] ?? null ) !== null && ( $state['primary_industry_key'] ?? '' ) !== '' ) : ?>
				<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 0.5rem;">
					<input type="hidden" name="action" value="aio_toggle_industry_pack" />
					<input type="hidden" name="aio_industry_pack_key" value="<?php echo \esc_attr( $state['primary_industry_key'] ); ?>" />
					<input type="hidden" name="aio_industry_pack_disable" value="1" />
					<?php \wp_nonce_field( 'aio_toggle_industry_pack', 'aio_toggle_industry_pack_nonce', true ); ?>
					<button type="submit" class="button button-secondary"><?php \esc_html_e( 'Disable this pack', 'aio-page-builder' ); ?></button>
				</form>
				<?php endif; ?>
			</section>
			<?php endif; ?>

			<?php if ( $warnings !== array() ) : ?>
			<section class="aio-warnings" aria-labelledby="aio-industry-warnings-heading">
				<h2 id="aio-industry-warnings-heading"><?php \esc_html_e( 'Warnings', 'aio-page-builder' ); ?></h2>
				<ul>
					<?php foreach ( $warnings as $w ) : ?>
						<li><?php echo \esc_html( $w ); ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
			<?php endif; ?>

			<section class="aio-industry-form" aria-labelledby="aio-industry-form-heading">
				<h2 id="aio-industry-form-heading"><?php \esc_html_e( 'Industry selection', 'aio-page-builder' ); ?></h2>
				<form method="post" action="<?php echo \esc_url( $state['form_action'] ); ?>">
					<input type="hidden" name="action" value="<?php echo \esc_attr( $state['save_action'] ); ?>" />
					<?php \wp_nonce_field( $form_builder->get_nonce_action(), $form_builder->get_nonce_name(), true ); ?>

					<table class="form-table" role="presentation">
						<?php
						$primary_key      = Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY;
						$secondary_key    = Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS;
						$primary_config   = $field_config['primary_industry_key'] ?? null;
						$secondary_config = $field_config['secondary_industry_keys'] ?? null;
						if ( $primary_config !== null ) :
							$current_primary = isset( $profile[ $primary_key ] ) && is_string( $profile[ $primary_key ] ) ? $profile[ $primary_key ] : '';
							?>
						<tr>
							<th scope="row"><label for="aio-primary-industry"><?php echo \esc_html( $primary_config['label'] ); ?></label></th>
							<td>
								<select name="<?php echo \esc_attr( $primary_config['name'] ); ?>" id="aio-primary-industry">
									<?php foreach ( $primary_config['options'] as $value => $label ) : ?>
										<option value="<?php echo \esc_attr( $value ); ?>" <?php selected( $current_primary, $value ); ?>><?php echo \esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<?php endif; ?>
						<?php
						$subtype_form_field  = $state['subtype_form_field'] ?? null;
						$primary_for_subtype = isset( $profile[ $primary_key ] ) && is_string( $profile[ $primary_key ] ) ? $profile[ $primary_key ] : '';
						if ( $subtype_form_field instanceof Industry_Subtype_Form_Field && $primary_for_subtype !== '' && $subtype_form_field->industry_has_subtypes( $primary_for_subtype ) ) :
							$subtype_config  = $subtype_form_field->get_field_config();
							$subtype_options = $subtype_form_field->get_options_for_industry( $primary_for_subtype );
							$current_subtype = isset( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ) ? $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] : '';
							?>
						<tr>
							<th scope="row"><label for="aio-industry-subtype"><?php echo \esc_html( $subtype_config['label'] ); ?></label></th>
							<td>
								<select name="<?php echo \esc_attr( $subtype_config['name'] ); ?>" id="aio-industry-subtype">
									<?php foreach ( $subtype_options as $value => $label ) : ?>
										<option value="<?php echo \esc_attr( $value ); ?>" <?php selected( $current_subtype, $value ); ?>><?php echo \esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
								<?php if ( ! empty( $subtype_config['description'] ) ) : ?>
									<p class="description"><?php echo \esc_html( $subtype_config['description'] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
						<?php endif; ?>
						<?php if ( $secondary_config !== null ) : ?>
							<?php
							$current_secondary = isset( $profile[ $secondary_key ] ) && is_array( $profile[ $secondary_key ] ) ? $profile[ $secondary_key ] : array();
							?>
						<tr>
							<th scope="row"><label for="aio-secondary-industries"><?php echo \esc_html( $secondary_config['label'] ); ?></label></th>
							<td>
								<select name="<?php echo \esc_attr( $secondary_config['name'] ); ?>" id="aio-secondary-industries" multiple="multiple" size="5">
									<?php foreach ( $secondary_config['options'] as $value => $label ) : ?>
										<option value="<?php echo \esc_attr( $value ); ?>" <?php echo \in_array( $value, $current_secondary, true ) ? ' selected="selected"' : ''; ?>><?php echo \esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php \esc_html_e( 'Hold Ctrl/Cmd to select multiple.', 'aio-page-builder' ); ?></p>
							</td>
						</tr>
						<?php endif; ?>
						<?php
						$assistant = $state['starter_bundle_assistant'] ?? null;
						$sb_state  = $state['starter_bundle_state'] ?? array();
						if ( $assistant instanceof Industry_Starter_Bundle_Assistant && ! empty( $sb_state ) ) {
							$assistant->render( $sb_state );
						}
						?>
					</table>
					<p class="submit">
						<button type="submit" class="button button-primary"><?php \esc_html_e( 'Save industry profile', 'aio-page-builder' ); ?></button>
					</p>
				</form>
			</section>
		</div>
		<?php
	}
}
