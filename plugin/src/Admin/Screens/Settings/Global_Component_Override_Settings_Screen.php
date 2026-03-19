<?php
/**
 * Admin screen for editing global component style overrides (Prompt 248).
 * Component-spec driven; capability-gated; save via repository; no arbitrary CSS or selectors.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Settings;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Forms\Global_Component_Override_Form_Builder;
use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Repository;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders the global component override settings form and handles save/reset.
 */
final class Global_Component_Override_Settings_Screen {

	public const SLUG = 'aio-page-builder-global-component-overrides';

	private const SAVE_ACTION  = 'aio_global_component_overrides_save';
	private const RESET_ACTION = 'aio_global_component_overrides_reset';
	private const NONCE_SAVE   = 'aio_global_component_overrides_save';
	private const NONCE_RESET  = 'aio_global_component_overrides_reset';
	private const QUERY_MSG    = 'aio_override_msg';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Global Component Overrides', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::MANAGE_SETTINGS;
	}

	/**
	 * Renders the screen. Enforces capability; processes POST save/reset then redirects or outputs form.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to manage global component overrides.', 'aio-page-builder' ), 403 );
		}

		$repo         = $this->get_repository();
		$form_builder = $this->get_form_builder();

		if ( $repo === null || $form_builder === null ) {
			$diagnostics_url = \admin_url( 'admin.php?page=aio-page-builder-diagnostics' );
			echo '<div class="wrap aio-page-builder-screen"><h1>' . \esc_html( $this->get_title() ) . '</h1>';
			echo '<div class="notice notice-warning inline"><p>' . \esc_html__( 'Global component override settings are unavailable. The style repository or form builder could not be loaded.', 'aio-page-builder' ) . '</p>';
			echo '<p>' . \sprintf(
				/* translators: 1: link to diagnostics, 2: link text */
				\esc_html__( 'You can try reloading this page or check the %1$s screen for dependency status.', 'aio-page-builder' ),
				'<a href="' . \esc_url( $diagnostics_url ) . '">' . \esc_html__( 'Diagnostics', 'aio-page-builder' ) . '</a>'
			) . '</p></div></div>';
			return;
		}

		if ( isset( $_POST['action'] ) && \sanitize_text_field( \wp_unslash( $_POST['action'] ) ) === self::SAVE_ACTION ) {
			if ( isset( $_POST[ self::NONCE_SAVE ] ) && \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST[ self::NONCE_SAVE ] ) ), self::NONCE_SAVE ) ) {
				$raw_overrides = isset( $_POST[ Global_Component_Override_Form_Builder::FORM_OVERRIDES_KEY ] ) && is_array( $_POST[ Global_Component_Override_Form_Builder::FORM_OVERRIDES_KEY ] )
					? \wp_unslash( $_POST[ Global_Component_Override_Form_Builder::FORM_OVERRIDES_KEY ] )
					: array();
				$overrides     = $this->collect_overrides_from_raw( $raw_overrides );
				$ok            = $repo->set_global_component_overrides( $overrides );
				$msg           = $ok ? 'success' : 'error';
				\wp_safe_redirect( \add_query_arg( self::QUERY_MSG, $msg, $this->get_settings_url() ) );
				exit;
			}
		}

		if ( isset( $_POST['action'] ) && \sanitize_text_field( \wp_unslash( $_POST['action'] ) ) === self::RESET_ACTION ) {
			if ( isset( $_POST[ self::NONCE_RESET ] ) && \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST[ self::NONCE_RESET ] ) ), self::NONCE_RESET ) ) {
				$repo->set_global_component_overrides( array() );
				\wp_safe_redirect( \add_query_arg( self::QUERY_MSG, 'reset', $this->get_settings_url() ) );
				exit;
			}
		}

		$message = isset( $_GET[ self::QUERY_MSG ] ) ? \sanitize_text_field( \wp_unslash( $_GET[ self::QUERY_MSG ] ) ) : '';
		$by_comp = $form_builder->get_fields_by_component();
		?>
		<div class="wrap aio-page-builder-screen aio-global-component-overrides" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>

			<?php if ( $message === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Settings saved.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $message === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Some values could not be saved. Invalid entries were omitted.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $message === 'reset' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Component overrides reset to defaults.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<p class="description"><?php \esc_html_e( 'Set component-specific token overrides. Only approved components and token names from the style spec are shown. No raw CSS or selectors.', 'aio-page-builder' ); ?></p>

			<form method="post" action="<?php echo \esc_url( $this->get_settings_url() ); ?>" id="aio-global-component-overrides-form">
				<input type="hidden" name="action" value="<?php echo \esc_attr( self::SAVE_ACTION ); ?>" />
				<?php \wp_nonce_field( self::NONCE_SAVE, self::NONCE_SAVE ); ?>

				<?php foreach ( $by_comp as $component_id => $fields ) : ?>
					<section class="aio-component-override-group" aria-labelledby="aio-component-<?php echo \esc_attr( $component_id ); ?>">
						<h2 id="aio-component-<?php echo \esc_attr( $component_id ); ?>"><?php echo \esc_html( \ucfirst( \str_replace( array( '-', '_' ), ' ', $component_id ) ) ); ?></h2>
						<table class="form-table" role="presentation">
							<tbody>
								<?php foreach ( $fields as $field ) : ?>
									<tr>
										<th scope="row">
											<label for="aio_co_<?php echo \esc_attr( $field['component_id'] . '_' . \str_replace( array( '--', '-' ), array( '_', '_' ), $field['token_var_name'] ) ); ?>"><?php echo \esc_html( $field['label'] ); ?></label>
										</th>
										<td>
											<input type="text"
												id="aio_co_<?php echo \esc_attr( $field['component_id'] . '_' . \str_replace( array( '--', '-' ), array( '_', '_' ), $field['token_var_name'] ) ); ?>"
												name="<?php echo \esc_attr( $field['name_attr'] ); ?>"
												value="<?php echo \esc_attr( $field['value'] ); ?>"
												maxlength="<?php echo \esc_attr( (string) $field['max_length'] ); ?>"
												class="regular-text"
											/>
											<?php if ( $field['max_length'] > 0 ) : ?>
												<p class="description"><?php echo \esc_html( sprintf( /* translators: %d: max length */ __( 'Max %d characters.', 'aio-page-builder' ), $field['max_length'] ) ); ?></p>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</section>
				<?php endforeach; ?>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php \esc_html_e( 'Save overrides', 'aio-page-builder' ); ?></button>
				</p>
			</form>

			<form method="post" action="<?php echo \esc_url( $this->get_settings_url() ); ?>" id="aio-global-component-overrides-reset" style="margin-top: 1.5em;">
				<input type="hidden" name="action" value="<?php echo \esc_attr( self::RESET_ACTION ); ?>" />
				<?php \wp_nonce_field( self::NONCE_RESET, self::NONCE_RESET ); ?>
				<button type="submit" class="button" onclick="return confirm('<?php echo \esc_js( __( 'Reset all component overrides to defaults?', 'aio-page-builder' ) ); ?>');"><?php \esc_html_e( 'Reset to defaults', 'aio-page-builder' ); ?></button>
			</form>
		</div>
		<?php
	}

	private function get_settings_url(): string {
		return \admin_url( 'admin.php?page=' . self::SLUG );
	}

	private function get_repository(): ?Global_Style_Settings_Repository {
		if ( $this->container === null || ! $this->container->has( 'global_style_settings_repository' ) ) {
			return null;
		}
		$repo = $this->container->get( 'global_style_settings_repository' );
		return $repo instanceof Global_Style_Settings_Repository ? $repo : null;
	}

	private function get_form_builder(): ?Global_Component_Override_Form_Builder {
		if ( $this->container === null
			|| ! $this->container->has( 'global_style_settings_repository' )
			|| ! $this->container->has( 'component_override_registry' ) ) {
			return null;
		}
		$repo      = $this->container->get( 'global_style_settings_repository' );
		$comp_reg  = $this->container->get( 'component_override_registry' );
		$token_reg = $this->container->has( 'style_token_registry' ) ? $this->container->get( 'style_token_registry' ) : null;
		if ( ! $repo instanceof Global_Style_Settings_Repository ) {
			return null;
		}
		$comp_class = \AIOPageBuilder\Domain\Styling\Component_Override_Registry::class;
		if ( ! $comp_reg instanceof $comp_class ) {
			return null;
		}
		$token_class = \AIOPageBuilder\Domain\Styling\Style_Token_Registry::class;
		$token_reg   = ( $token_reg instanceof $token_class ) ? $token_reg : null;
		return new Global_Component_Override_Form_Builder( $comp_reg, $repo, $token_reg );
	}

	/**
	 * Builds override array from unslashed raw POST data. Only scalar string values; structure filtered by repository on write.
	 *
	 * @param array<string, mixed> $raw Unslashed POST overrides key (e.g. from wp_unslash( $_POST[ FORM_OVERRIDES_KEY ] )).
	 * @return array<string, array<string, string>>
	 */
	private function collect_overrides_from_raw( array $raw ): array {
		$out = array();
		foreach ( $raw as $component_id => $pairs ) {
			if ( ! is_string( $component_id ) || ! is_array( $pairs ) ) {
				continue;
			}
			$out[ $component_id ] = array();
			foreach ( $pairs as $var_name => $value ) {
				if ( is_string( $var_name ) && is_scalar( $value ) ) {
					$out[ $component_id ][ $var_name ] = \sanitize_text_field( (string) $value );
				}
			}
		}
		return $out;
	}
}
