<?php
/**
 * Preview-only screen for industry pack bundle (JSON) contents and conflict analysis (Prompt 419, SPR-007, Prompt 639).
 * In v1, direct apply of JSON bundles is out of scope. This screen supports preview and conflict inspection only.
 * State restoration is only supported via Import / Export using a full backup ZIP (spec §52).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Bootstrap\Industry_Packs_Module;
use AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Bundle_Service;
use AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Import_Conflict_Service;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders industry bundle preview (contents and conflicts). V1: preview-only; direct apply not supported. Use Import / Export (ZIP) for restore.
 */
final class Industry_Bundle_Import_Preview_Screen {

	public const SLUG = 'aio-page-builder-industry-bundle-import-preview';

	private const TRANSIENT_PREVIEW    = 'aio_industry_bundle_preview_%d';
	private const NONCE_ACTION_PREVIEW = 'aio_industry_bundle_preview';
	/** Nonce action for clear-preview only (clears transient; no bundle apply or state write). */
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
		return Capabilities::MANAGE_SETTINGS;
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
			'site_profile'                              => false,
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
			$repo                  = $this->container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
			$profile               = $repo && \method_exists( $repo, 'get_profile' ) ? $repo->get_profile() : array();
			$state['site_profile'] = ! empty( $profile['primary_industry_key'] );
		}
		return $state;
	}

	/**
	 * Returns preview state from transient or default state for upload form.
	 *
	 * @return array<string, mixed>
	 */
	private function get_state(): array {
		$uid           = \get_current_user_id();
		$transient_key = \sprintf( self::TRANSIENT_PREVIEW, $uid );
		// Clear preview only (transient delete); no bundle apply.
		$clear_nonce = isset( $_GET['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( isset( $_GET['aio_bundle_cancel'] ) && $clear_nonce !== '' && \wp_verify_nonce( $clear_nonce, self::NONCE_ACTION_CLEAR ) && \get_transient( $transient_key ) ) {
			\delete_transient( $transient_key );
			\wp_safe_redirect( \remove_query_arg( array( 'aio_bundle_cancel', '_wpnonce' ), \wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) );
			exit;
		}
		$preview = \get_transient( $transient_key );
		if ( \is_array( $preview ) && ! empty( $preview['bundle'] ) ) {
			return array(
				'preview'       => true,
				'bundle'        => $preview['bundle'],
				'conflicts'     => $preview['conflicts'] ?? array(),
				'included'      => $preview['included'] ?? array(),
				'summary'       => $preview['summary'] ?? array(),
				'transient_key' => $transient_key,
			);
		}
		return array(
			'preview' => false,
			'error'   => isset( $_GET['aio_bundle_preview_error'] ) ? \sanitize_text_field( \wp_unslash( $_GET['aio_bundle_preview_error'] ) ) : '',
		);
	}

	/**
	 * Renders the screen.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access the industry bundle import preview.', 'aio-page-builder' ), 403 );
		}
		$state = $this->get_state();
		?>
		<div class="wrap aio-page-builder-screen aio-industry-bundle-import-preview" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p class="description">
				<?php \esc_html_e( 'Upload an industry pack bundle (JSON) to preview contents and conflicts. Direct apply of JSON bundles is not supported in this version—this screen is preview only. To restore plugin data (including industry profile), use Import / Export and upload a full backup ZIP.', 'aio-page-builder' ); ?>
			</p>

			<?php if ( ! empty( $state['error'] ) ) : ?>
				<div class="notice notice-error inline"><p><?php echo \esc_html( $state['error'] ); ?></p></div>
			<?php endif; ?>

			<?php if ( ! empty( $state['preview'] ) ) : ?>
				<?php $this->render_preview( $state ); ?>
			<?php else : ?>
				<?php $this->render_upload_form(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed> $state
	 * @return void
	 */
	private function render_preview( array $state ): void {
		$conflicts         = $state['conflicts'];
		$included          = $state['included'];
		$summary           = $state['summary'];
		$bundle            = $state['bundle'];
		$preview_url       = \admin_url( 'admin.php?page=' . self::SLUG );
		$cancel_url        = \wp_nonce_url( \add_query_arg( 'aio_bundle_cancel', '1', $preview_url ), self::NONCE_ACTION_CLEAR );
		$import_export_url = \admin_url( 'admin.php?page=' . \AIOPageBuilder\Admin\Screens\ImportExport\Import_Export_Screen::SLUG );
		?>
		<div class="notice notice-info inline" style="margin: 1em 0;">
			<p>
				<?php \esc_html_e( 'This is a preview only. Direct apply of JSON bundles is not supported in this version. To restore plugin state, use', 'aio-page-builder' ); ?>
				<a href="<?php echo \esc_url( $import_export_url ); ?>"><?php \esc_html_e( 'Import / Export', 'aio-page-builder' ); ?></a>
				<?php \esc_html_e( 'and upload a full backup ZIP.', 'aio-page-builder' ); ?>
			</p>
		</div>
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
							<th scope="col"><?php \esc_html_e( 'Proposed resolution', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Message', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $conflicts as $c ) : ?>
							<tr>
								<td><?php echo \esc_html( $c[ Industry_Pack_Import_Conflict_Service::RESULT_CATEGORY ] ?? '' ); ?></td>
								<td><code><?php echo \esc_html( $c[ Industry_Pack_Import_Conflict_Service::RESULT_OBJECT_KEY ] ?? '' ); ?></code></td>
								<td><?php echo \esc_html( $c[ Industry_Pack_Import_Conflict_Service::RESULT_CONFLICT_TYPE ] ?? '' ); ?></td>
								<td><?php echo \esc_html( $c[ Industry_Pack_Import_Conflict_Service::RESULT_PROPOSED_RESOLUTION ] ?? '' ); ?></td>
								<td><?php echo \esc_html( $c[ Industry_Pack_Import_Conflict_Service::RESULT_MESSAGE ] ?? '' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</section>
		<?php else : ?>
			<p><?php \esc_html_e( 'No conflicts detected with current site state.', 'aio-page-builder' ); ?></p>
		<?php endif; ?>

		<section class="aio-bundle-preview-actions" style="margin: 1.5em 0;">
			<p>
				<a href="<?php echo \esc_url( $cancel_url ); ?>" class="button"><?php \esc_html_e( 'Clear preview', 'aio-page-builder' ); ?></a>
				<a href="<?php echo \esc_url( $import_export_url ); ?>" class="button button-secondary"><?php \esc_html_e( 'Import / Export (ZIP restore)', 'aio-page-builder' ); ?></a>
			</p>
			<p class="description"><?php \esc_html_e( 'Only ZIP-based restore via Import / Export can write plugin state. This preview does not apply or import the bundle.', 'aio-page-builder' ); ?></p>
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
				<button type="submit" class="button button-primary"><?php \esc_html_e( 'Preview bundle', 'aio-page-builder' ); ?></button>
			</p>
		</form>
		<?php
	}
}
