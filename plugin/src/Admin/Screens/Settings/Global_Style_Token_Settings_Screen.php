<?php
/**
 * Admin screen for editing global style token values (Prompt 247).
 * Token-only; capability-gated; save via repository; no raw CSS.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Settings;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Forms\Global_Style_Token_Form_Builder;
use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Repository;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders the global style token settings form and handles save/reset.
 */
final class Global_Style_Token_Settings_Screen {

	public const SLUG = 'aio-page-builder-global-style-tokens';

	private const SAVE_ACTION  = 'aio_global_style_tokens_save';
	private const RESET_ACTION = 'aio_global_style_tokens_reset';
	private const NONCE_SAVE   = 'aio_global_style_tokens_save';
	private const NONCE_RESET  = 'aio_global_style_tokens_reset';
	private const QUERY_MSG    = 'aio_style_msg';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Global Style Tokens', 'aio-page-builder' );
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
			\wp_die( \esc_html__( 'You do not have permission to manage global style settings.', 'aio-page-builder' ), 403 );
		}

		$repo         = $this->get_repository();
		$form_builder = $this->get_form_builder();

		if ( $repo === null || $form_builder === null ) {
			echo '<div class="wrap"><p>' . \esc_html__( 'Global style settings are unavailable.', 'aio-page-builder' ) . '</p></div>';
			return;
		}

		// * Process save: POST with nonce and SAVE_ACTION.
		if ( isset( $_POST['action'] ) && \sanitize_text_field( \wp_unslash( $_POST['action'] ) ) === self::SAVE_ACTION ) {
			if ( isset( $_POST[ self::NONCE_SAVE ] ) && \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST[ self::NONCE_SAVE ] ) ), self::NONCE_SAVE ) ) {
				$tokens = $this->collect_tokens_from_post();
				$ok     = $repo->set_global_tokens( $tokens );
				$msg    = $ok ? 'success' : 'error';
				\wp_safe_redirect( \add_query_arg( self::QUERY_MSG, $msg, $this->get_settings_url() ) );
				exit;
			}
		}

		// * Process reset: POST with nonce and RESET_ACTION.
		if ( isset( $_POST['action'] ) && \sanitize_text_field( \wp_unslash( $_POST['action'] ) ) === self::RESET_ACTION ) {
			if ( isset( $_POST[ self::NONCE_RESET ] ) && \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST[ self::NONCE_RESET ] ) ), self::NONCE_RESET ) ) {
				$repo->reset_to_defaults();
				\wp_safe_redirect( \add_query_arg( self::QUERY_MSG, 'reset', $this->get_settings_url() ) );
				exit;
			}
		}

		$message  = isset( $_GET[ self::QUERY_MSG ] ) ? \sanitize_text_field( \wp_unslash( $_GET[ self::QUERY_MSG ] ) ) : '';
		$by_group = $form_builder->get_fields_by_group();
		?>
		<div class="wrap aio-page-builder-screen aio-global-style-tokens" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>

			<?php if ( $message === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Settings saved.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $message === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Some values could not be saved. Invalid entries were omitted.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $message === 'reset' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Settings reset to defaults.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<p class="description"><?php \esc_html_e( 'Set global design token values. Only approved tokens from the style spec are shown. No raw CSS.', 'aio-page-builder' ); ?></p>

			<form method="post" action="<?php echo \esc_url( $this->get_settings_url() ); ?>" id="aio-global-style-tokens-form">
				<input type="hidden" name="action" value="<?php echo \esc_attr( self::SAVE_ACTION ); ?>" />
				<?php \wp_nonce_field( self::NONCE_SAVE, self::NONCE_SAVE ); ?>

				<?php foreach ( $by_group as $group => $fields ) : ?>
					<section class="aio-token-group" aria-labelledby="aio-token-group-<?php echo \esc_attr( $group ); ?>">
						<h2 id="aio-token-group-<?php echo \esc_attr( $group ); ?>"><?php echo \esc_html( \ucfirst( $group ) ); ?></h2>
						<table class="form-table" role="presentation">
							<tbody>
								<?php foreach ( $fields as $field ) : ?>
									<tr>
										<th scope="row">
											<label for="aio_gt_<?php echo \esc_attr( $field['group'] . '_' . $field['name'] ); ?>"><?php echo \esc_html( $field['label'] ); ?></label>
										</th>
										<td>
											<input type="text"
												id="aio_gt_<?php echo \esc_attr( $field['group'] . '_' . $field['name'] ); ?>"
												name="<?php echo \esc_attr( $field['name_attr'] ); ?>"
												value="<?php echo \esc_attr( $field['value'] ); ?>"
												maxlength="<?php echo \esc_attr( (string) $field['max_length'] ); ?>"
												class="regular-text"
												<?php echo $field['value_type'] === 'color' ? ' placeholder="#000000"' : ''; ?>
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
					<button type="submit" class="button button-primary"><?php \esc_html_e( 'Save token values', 'aio-page-builder' ); ?></button>
				</p>
			</form>

			<form method="post" action="<?php echo \esc_url( $this->get_settings_url() ); ?>" id="aio-global-style-tokens-reset" style="margin-top: 1.5em;">
				<input type="hidden" name="action" value="<?php echo \esc_attr( self::RESET_ACTION ); ?>" />
				<?php \wp_nonce_field( self::NONCE_RESET, self::NONCE_RESET ); ?>
				<button type="submit" class="button" onclick="return confirm('<?php echo \esc_js( __( 'Reset all global token values to defaults?', 'aio-page-builder' ) ); ?>');"><?php \esc_html_e( 'Reset to defaults', 'aio-page-builder' ); ?></button>
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

	private function get_form_builder(): ?Global_Style_Token_Form_Builder {
		if ( $this->container === null || ! $this->container->has( 'global_style_settings_repository' ) || ! $this->container->has( 'style_token_registry' ) ) {
			return null;
		}
		$repo     = $this->container->get( 'global_style_settings_repository' );
		$registry = $this->container->get( 'style_token_registry' );
		if ( ! $repo instanceof Global_Style_Settings_Repository ) {
			return null;
		}
		$registry_class = \AIOPageBuilder\Domain\Styling\Style_Token_Registry::class;
		if ( ! $registry instanceof $registry_class ) {
			return null;
		}
		return new Global_Style_Token_Form_Builder( $registry, $repo );
	}

	/**
	 * Collects token array from POST. Only scalar string values; structure filtered by repository on write.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function collect_tokens_from_post(): array {
		$key = Global_Style_Token_Form_Builder::FORM_TOKENS_KEY;
		if ( ! isset( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) {
			return array();
		}
		$raw = \wp_unslash( $_POST[ $key ] );
		$out = array();
		foreach ( $raw as $group => $names ) {
			if ( ! is_string( $group ) || ! is_array( $names ) ) {
				continue;
			}
			$out[ $group ] = array();
			foreach ( $names as $name => $value ) {
				if ( is_string( $name ) && is_scalar( $value ) ) {
					$out[ $group ][ $name ] = \sanitize_text_field( (string) $value );
				}
			}
		}
		return $out;
	}
}
