<?php
/**
 * Preview screen for industry pack bundle (JSON) contents and conflict analysis (Prompt 419, SPR-007).
 * The screen renders conflicts and, when permitted, offers an apply action to import the selected bundle content.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Bootstrap\Industry_Packs_Module;
use AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Bundle_Service;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders industry bundle preview (contents and conflicts) and provides an apply form when permitted.
 */
final class Industry_Bundle_Import_Preview_Screen {

	public const SLUG = 'aio-page-builder-industry-bundle-import-preview';

	private const TRANSIENT_PREVIEW    = 'aio_industry_bundle_preview_%d';
	private const NONCE_ACTION_PREVIEW = 'aio_pb_preview_industry_bundle';
	private const NONCE_ACTION_APPLY   = 'aio_pb_apply_industry_bundle';
	/** Nonce action for clear-preview (state-changing; clears preview before apply). */
	private const NONCE_ACTION_CLEAR = 'aio_industry_bundle_clear_preview';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Industry Bundle Import Preview', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::IMPORT_DATA;
	}

	/**
	 * Builds local state for conflict analysis from current registries. Public for use by admin_post handler.
	 *
	 * @return array<string, mixed>
	 */
	public function get_local_state_for_conflict(): array {
		$state = array(
			Industry_Pack_Bundle_Service::PAYLOAD_PACKS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_STARTER_BUNDLES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_STYLE_PRESETS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_CTA_PATTERNS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SEO_GUIDANCE => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_LPAGERY_RULES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SECTION_HELPER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_PAGE_ONE_PAGER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_QUESTION_PACKS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SITE_PROFILE => array(),
			'pack_versions'                             => array(),
			'has_site_industry_profile'                 => false,
		);
		if ( ! $this->container instanceof Service_Container ) {
			return $state;
		}
		if ( $this->container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ) {
			$reg = $this->container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY );
			if ( $reg instanceof Industry_Pack_Registry ) {
				$all      = $reg->get_all();
				$keys     = array();
				$versions = array();
				foreach ( $all as $pack ) {
					$key = isset( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
						? trim( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
						: '';
					if ( $key !== '' ) {
						$keys[]           = $key;
						$versions[ $key ] = isset( $pack[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] )
							? trim( $pack[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] )
							: '';
					}
				}
				$state[ Industry_Pack_Bundle_Service::PAYLOAD_PACKS ] = $keys;
				$state['pack_versions']                               = $versions;
			}
		}
		if ( $this->container->has( Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) ) {
			$reg = $this->container->get( Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY );
			if ( $reg instanceof Industry_Starter_Bundle_Registry ) {
				$all  = $reg->list_all();
				$keys = array();
				foreach ( $all as $bundle ) {
					$key = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ) && is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] )
						? trim( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] )
						: '';
					if ( $key !== '' ) {
						$keys[] = $key;
					}
				}
				$state[ Industry_Pack_Bundle_Service::PAYLOAD_STARTER_BUNDLES ] = $keys;
			}
		}
		if ( $this->container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
			$repo                               = $this->container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
			$profile                            = $repo && \method_exists( $repo, 'get_profile' ) ? $repo->get_profile() : array();
			$state['has_site_industry_profile'] = ! empty( $profile['primary_industry_key'] );
		}
		return $state;
	}

	/**
	 * Clear preview (GET): redirect URL for admin_init (Admin_Early_Redirect_Coordinator).
	 *
	 * @return string|null
	 */
	public function get_clear_preview_redirect_url(): ?string {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			return null;
		}
		$uid           = \get_current_user_id();
		$transient_key = \sprintf( self::TRANSIENT_PREVIEW, $uid );
		$clear_nonce   = isset( $_GET['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! isset( $_GET['aio_bundle_cancel'] ) || $clear_nonce === '' || ! \wp_verify_nonce( $clear_nonce, self::NONCE_ACTION_CLEAR ) || ! \get_transient( $transient_key ) ) {
			return null;
		}
		\delete_transient( $transient_key );

		return \remove_query_arg( array( 'aio_bundle_cancel', '_wpnonce' ), $this->get_current_request_uri() );
	}

	/**
	 * Returns preview state from transient or default state for upload form.
	 *
	 * @return array<string, mixed>
	 */
	private function get_state(): array {
		$uid           = \get_current_user_id();
		$transient_key = \sprintf( self::TRANSIENT_PREVIEW, $uid );
		$preview       = \get_transient( $transient_key );

		$apply_result  = isset( $_GET['aio_bundle_apply_result'] ) ? \sanitize_key( \wp_unslash( (string) $_GET['aio_bundle_apply_result'] ) ) : '';
		$apply_success = $apply_result === 'applied';

		if ( \is_array( $preview ) && ! empty( $preview['bundle'] ) ) {
			return array(
				'preview'       => true,
				'bundle'        => $preview['bundle'],
				'conflicts'     => $preview['conflicts'] ?? array(),
				'included'      => $preview['included'] ?? array(),
				'summary'       => $preview['summary'] ?? array(),
				'transient_key' => $transient_key,
				'error'         => isset( $_GET['aio_bundle_preview_error'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['aio_bundle_preview_error'] ) ) : '',
				'apply_success' => $apply_success,
			);
		}
		return array(
			'preview'       => false,
			'error'         => isset( $_GET['aio_bundle_preview_error'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['aio_bundle_preview_error'] ) ) : '',
			'apply_success' => $apply_success,
		);
	}

	/**
	 * Renders the screen.
	 *
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access the industry bundle import preview.', 'aio-page-builder' ), 403 );
		}
		$state = $this->get_state();
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-industry-bundle-import-preview" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="description">
				<?php \esc_html_e( 'Upload an industry pack bundle (JSON) to preview contents, review conflicts, choose scope, and apply.', 'aio-page-builder' ); ?>
			</p>

			<?php if ( ! empty( $state['apply_success'] ) ) : ?>
				<div class="notice notice-success inline" role="status">
					<p><?php \esc_html_e( 'Industry bundle applied successfully. You can upload another bundle to preview or import.', 'aio-page-builder' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $state['error'] ) ) : ?>
				<div class="notice notice-error inline"><p><?php echo \esc_html( $state['error'] ); ?></p></div>
			<?php endif; ?>

			<?php if ( ! empty( $state['preview'] ) ) : ?>
				<?php $this->render_preview( $state ); ?>
			<?php else : ?>
				<?php $this->render_upload_form(); ?>
			<?php endif; ?>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * @param array<string, mixed> $state
	 * @return void
	 */
	private function render_preview( array $state ): void {
		$conflicts   = $state['conflicts'];
		$included    = $state['included'];
		$summary     = $state['summary'];
		$bundle      = $state['bundle'];
		$preview_url = Admin_Screen_Hub::tab_url( Industry_Profile_Settings_Screen::SLUG, 'import' );
		$cancel_url  = \wp_nonce_url( \add_query_arg( 'aio_bundle_cancel', '1', $preview_url ), self::NONCE_ACTION_CLEAR );
		$can_apply   = Capabilities::current_user_can_for_route( Capabilities::MANAGE_SETTINGS );
		?>
		<section class="aio-bundle-preview-summary" style="margin: 1.5em 0;">
			<h2><?php \esc_html_e( 'Bundle summary', 'aio-page-builder' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: 1: bundle version, 2: created_at */
					\esc_html__( 'Bundle version: %1$s. Created: %2$s.', 'aio-page-builder' ),
					\esc_html( (string) ( $bundle[ Industry_Pack_Bundle_Service::MANIFEST_BUNDLE_VERSION ] ?? '' ) ),
					\esc_html( (string) ( $bundle[ Industry_Pack_Bundle_Service::MANIFEST_CREATED_AT ] ?? '' ) )
				);
				?>
			</p>
			<p><strong><?php \esc_html_e( 'Included categories:', 'aio-page-builder' ); ?></strong> <?php echo \esc_html( \implode( ', ', $included ) ); ?></p>
			<?php if ( ! empty( $summary ) && \is_array( $summary ) ) : ?>
				<ul>
					<?php foreach ( $summary as $cat => $count ) : ?>
						<li><?php echo \esc_html( $cat . ': ' . $count . ' item(s)' ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>

		<?php if ( $conflicts !== array() ) : ?>
			<section class="aio-bundle-preview-conflicts" style="margin: 1.5em 0;">
				<h2><?php \esc_html_e( 'Conflicts and actions', 'aio-page-builder' ); ?></h2>
				<table class="widefat striped">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'Category', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Object key', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Conflict type', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $conflicts as $c ) : ?>
							<tr>
								<td><?php echo \esc_html( (string) ( $c['category'] ?? '' ) ); ?></td>
								<td><code><?php echo \esc_html( (string) ( $c['object_key'] ?? '' ) ); ?></code></td>
								<td><?php echo \esc_html( (string) ( $c['conflict_type'] ?? '' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</section>
		<?php else : ?>
			<p><?php \esc_html_e( 'No conflicts detected with current site state.', 'aio-page-builder' ); ?></p>
		<?php endif; ?>

		<section class="aio-bundle-preview-apply" style="margin: 1.5em 0;">
			<h2><?php \esc_html_e( 'Apply bundle', 'aio-page-builder' ); ?></h2>
			<?php if ( ! $can_apply ) : ?>
				<div class="notice notice-warning inline"><p><?php \esc_html_e( 'You can preview bundles, but applying requires manage settings permission.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php?action=aio_industry_bundle_apply' ) ); ?>">
				<?php echo \wp_nonce_field( self::NONCE_ACTION_APPLY, 'aio_industry_bundle_apply_nonce', true, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<p>
					<label for="aio_industry_bundle_scope"><strong><?php \esc_html_e( 'Scope', 'aio-page-builder' ); ?></strong></label><br />
					<select name="aio_industry_bundle_scope" id="aio_industry_bundle_scope">
						<option value="settings_only"><?php \esc_html_e( 'Import settings only', 'aio-page-builder' ); ?></option>
						<option value="full_site_package"><?php \esc_html_e( 'Import full site package', 'aio-page-builder' ); ?></option>
					</select>
				</p>
				<?php if ( $conflicts !== array() ) : ?>
					<p><strong><?php \esc_html_e( 'Conflict decisions', 'aio-page-builder' ); ?></strong></p>
					<?php foreach ( $conflicts as $c ) : ?>
						<?php
						$cat  = isset( $c['category'] ) ? (string) $c['category'] : '';
						$key  = isset( $c['object_key'] ) ? (string) $c['object_key'] : '';
						$name = 'conflict_decisions[' . \esc_attr( $cat . '|' . $key ) . ']';
						?>
						<div style="margin: .75em 0; padding: .75em; border: 1px solid #ccd0d4; background: #fff;">
							<p style="margin: 0 0 .5em;">
								<code><?php echo \esc_html( $cat . ' / ' . $key ); ?></code><br />
								<?php \esc_html_e( 'Incoming content differs from local content. Choose replace or skip.', 'aio-page-builder' ); ?>
							</p>
							<label style="margin-right: 1em;">
								<input type="radio" name="<?php echo $name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" value="replace" checked="checked" />
								<?php \esc_html_e( 'Replace', 'aio-page-builder' ); ?>
							</label>
							<label>
								<input type="radio" name="<?php echo $name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" value="skip" />
								<?php \esc_html_e( 'Skip', 'aio-page-builder' ); ?>
							</label>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
				<p>
					<label for="aio_industry_bundle_slug"><?php \esc_html_e( 'Bundle label (optional)', 'aio-page-builder' ); ?></label><br />
					<input type="text" name="aio_industry_bundle_slug" id="aio_industry_bundle_slug" class="regular-text" value="" />
				</p>
				<p>
					<button type="submit" class="button button-primary" data-aio-ux-action="industry_bundle_confirm_apply" data-aio-ux-section="industry_bundle_apply" data-aio-ux-hub="<?php echo \esc_attr( Industry_Profile_Settings_Screen::SLUG ); ?>" data-aio-ux-tab="import" <?php echo $can_apply ? '' : 'disabled'; ?>>
						<?php \esc_html_e( 'Confirm apply', 'aio-page-builder' ); ?>
					</button>
				</p>
			</form>
		</section>

		<section class="aio-bundle-preview-actions" style="margin: 1.5em 0;">
			<p>
				<a href="<?php echo \esc_url( $cancel_url ); ?>" class="button" data-aio-ux-action="industry_bundle_clear_preview" data-aio-ux-section="industry_bundle_actions" data-aio-ux-hub="<?php echo \esc_attr( Industry_Profile_Settings_Screen::SLUG ); ?>" data-aio-ux-tab="import"><?php \esc_html_e( 'Clear preview', 'aio-page-builder' ); ?></a>
			</p>
		</section>
		<?php
	}

	private function render_upload_form(): void {
		$action = \admin_url( 'admin-post.php?action=aio_industry_bundle_preview' );
		$nonce  = \wp_nonce_field( self::NONCE_ACTION_PREVIEW, 'aio_industry_bundle_preview_nonce', true, false );
		?>
		<form method="post" action="<?php echo \esc_url( $action ); ?>" enctype="multipart/form-data" style="max-width: 32em;">
			<?php echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Nonce field HTML from wp_nonce_field(). ?>
			<p>
				<label for="aio_industry_bundle_file"><?php \esc_html_e( 'Industry bundle (JSON file)', 'aio-page-builder' ); ?></label>
				<input type="file" name="aio_industry_bundle_file" id="aio_industry_bundle_file" accept=".json,application/json" />
			</p>
			<p>
				<button type="submit" class="button button-primary" data-aio-ux-action="industry_bundle_preview_upload" data-aio-ux-section="industry_bundle_upload" data-aio-ux-hub="<?php echo \esc_attr( Industry_Profile_Settings_Screen::SLUG ); ?>" data-aio-ux-tab="import"><?php \esc_html_e( 'Preview bundle', 'aio-page-builder' ); ?></button>
			</p>
		</form>
		<?php
	}

	/**
	 * Returns the current request URI as a sanitized string.
	 *
	 * @return string
	 */
	private function get_current_request_uri(): string {
		$request_uri = \filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL );

		return is_string( $request_uri ) ? $request_uri : '';
	}
}
