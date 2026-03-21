<?php
/**
 * Import / Export admin screen (spec §49.4, §52, §59.13). Create export, export history, validate package, restore with confirmation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\ImportExport;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ExportRestore\Contracts\Export_Mode_Keys;
use AIOPageBuilder\Domain\ExportRestore\Contracts\Restore_Scope_Keys;
use AIOPageBuilder\Domain\ExportRestore\Import\Import_Validation_Result;
use AIOPageBuilder\Domain\ExportRestore\Import\Import_Validator;
use AIOPageBuilder\Domain\ExportRestore\Import\Restore_Pipeline;
use AIOPageBuilder\Domain\ExportRestore\Export\Export_Generator;
use AIOPageBuilder\Domain\ExportRestore\UI\Import_Export_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager;

/**
 * Renders export mode selection, export history, package upload/validation, conflict summary, and restore confirmation.
 * Validation before restore; no silent overwrite. All state-changing actions are nonce- and capability-gated.
 */
final class Import_Export_Screen {

	public const SLUG = 'aio-page-builder-export-restore';

	/** Maximum allowed size for Import/Export ZIP upload (50 MB). Enforced before move (import-export-zip-size-limit-decision.md). */
	public const MAX_ZIP_UPLOAD_BYTES = 52_428_800;

	/** Error code for redirect when ZIP exceeds max size. */
	public const ERROR_CODE_FILE_TOO_LARGE = 'file_too_large';

	/** Error code for redirect when uploaded file MIME type is not an allowed ZIP type (spec §43.11). */
	public const ERROR_CODE_INVALID_MIME = 'invalid_mime';

	/** Allowed MIME types for ZIP package upload (server-side finfo check). */
	private const ALLOWED_ZIP_MIME_TYPES = array( 'application/zip', 'application/x-zip-compressed' );

	private const TRANSIENT_VALIDATION     = 'aio_ie_validation_';
	private const TRANSIENT_RESTORE_RESULT = 'aio_ie_restore_result_';
	private const NONCE_CREATE_EXPORT      = 'aio_ie_create_export';
	private const NONCE_VALIDATE           = 'aio_ie_validate';
	private const NONCE_RESTORE            = 'aio_ie_restore';
	private const NONCE_DOWNLOAD           = 'aio_ie_download';
	private const FIELD_CONFIRM_ACK        = 'aio_ie_confirm_ack';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
		\add_action( 'admin_post_aio_import_export_create_export', array( $this, 'handle_create_export' ), 10 );
		\add_action( 'admin_post_aio_import_export_validate', array( $this, 'handle_validate' ), 10 );
		\add_action( 'admin_post_aio_import_export_confirm_restore', array( $this, 'handle_confirm_restore' ), 10 );
		\add_action( 'admin_post_aio_import_export_download', array( $this, 'handle_download_export' ), 10 );
	}

	public function get_title(): string {
		return __( 'Import / Export', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::EXPORT_DATA;
	}

	/**
	 * Renders the screen. Capability enforced by menu; screen checks export/import caps per section.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) && ! \current_user_can( Capabilities::IMPORT_DATA ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access Import / Export.', 'aio-page-builder' ), 403 );
		}

		$user_id           = \get_current_user_id();
		$validation_stored = \get_transient( self::TRANSIENT_VALIDATION . $user_id );
		$restore_stored    = \get_transient( self::TRANSIENT_RESTORE_RESULT . $user_id );
		if ( $restore_stored !== false ) {
			\delete_transient( self::TRANSIENT_RESTORE_RESULT . $user_id );
		}
		$validation_payload  = null;
		$validation_manifest = null;
		if ( is_array( $validation_stored ) && isset( $validation_stored['payload'] ) ) {
			$validation_payload                 = $validation_stored['payload'];
			$validation_payload['package_path'] = '';
			$validation_manifest                = isset( $validation_stored['manifest'] ) && is_array( $validation_stored['manifest'] )
				? $validation_stored['manifest']
				: null;
		}
		$restore_payload = is_array( $restore_stored ) && isset( $restore_stored['payload'] ) ? $restore_stored['payload'] : null;

		$state_builder = $this->get_state_builder();
		$state         = $state_builder->build( $validation_payload, $restore_payload, $validation_manifest );

		$status = isset( $_GET['aio_ie_status'] ) ? \sanitize_text_field( \wp_unslash( $_GET['aio_ie_status'] ) ) : '';
		if ( $status === 'export_created' ) {
			$file = isset( $_GET['aio_ie_file'] ) ? \sanitize_text_field( \wp_unslash( $_GET['aio_ie_file'] ) ) : '';
			echo '<div class="notice notice-success is-dismissible"><p>' . \esc_html__( 'Export created successfully.', 'aio-page-builder' );
			if ( $file !== '' ) {
				$dl = \wp_nonce_url(
					\add_query_arg(
						array(
							'action'   => 'aio_import_export_download',
							'filename' => $file,
						),
						\admin_url( 'admin-post.php' )
					),
					self::NONCE_DOWNLOAD
				);
				echo ' <a href="' . \esc_url( $dl ) . '">' . \esc_html__( 'Download', 'aio-page-builder' ) . '</a>';
			}
			echo '</p></div>';
		} elseif ( $status === 'error' ) {
			$code = isset( $_GET['aio_ie_code'] ) ? \sanitize_text_field( \wp_unslash( $_GET['aio_ie_code'] ) ) : '';
			$msg  = $this->error_message_for_code( $code );
			echo '<div class="notice notice-error is-dismissible"><p>' . \esc_html( $msg ) . '</p></div>';
		}

		$create_export_url = \wp_nonce_url( \admin_url( 'admin-post.php?action=aio_import_export_create_export' ), self::NONCE_CREATE_EXPORT );
		$validate_url      = \admin_url( 'admin-post.php?action=aio_import_export_validate' );
		$restore_url       = \wp_nonce_url( \admin_url( 'admin-post.php?action=aio_import_export_confirm_restore' ), self::NONCE_RESTORE );
		?>
		<div class="wrap aio-page-builder-screen aio-import-export" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>

			<p class="aio-description"><?php \esc_html_e( 'Create exports, inspect export history, validate restore packages, and run restore with explicit conflict resolution.', 'aio-page-builder' ); ?></p>

			<?php if ( $state['can_export'] ) : ?>
			<section class="aio-create-export" aria-labelledby="aio-create-export-heading">
				<h2 id="aio-create-export-heading"><?php \esc_html_e( 'Create export', 'aio-page-builder' ); ?></h2>
				<form method="post" action="<?php echo \esc_url( $create_export_url ); ?>">
					<input type="hidden" name="action" value="aio_import_export_create_export" />
					<?php \wp_nonce_field( self::NONCE_CREATE_EXPORT ); ?>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="aio-export-mode"><?php \esc_html_e( 'Export mode', 'aio-page-builder' ); ?></label></th>
							<td>
								<select name="export_mode" id="aio-export-mode" required>
									<?php foreach ( $state['export_mode_options'] as $opt ) : ?>
										<option value="<?php echo \esc_attr( $opt['value'] ); ?>"><?php echo \esc_html( $opt['label'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					</table>
					<p class="submit">
						<button type="submit" class="button button-primary"><?php \esc_html_e( 'Create export', 'aio-page-builder' ); ?></button>
					</p>
				</form>
			</section>

			<section class="aio-export-history" aria-labelledby="aio-export-history-heading">
				<h2 id="aio-export-history-heading"><?php \esc_html_e( 'Export history', 'aio-page-builder' ); ?></h2>
				<?php if ( empty( $state['export_history_rows'] ) ) : ?>
					<p><?php \esc_html_e( 'No exports yet.', 'aio-page-builder' ); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php \esc_html_e( 'Filename', 'aio-page-builder' ); ?></th>
								<th><?php \esc_html_e( 'Size', 'aio-page-builder' ); ?></th>
								<th><?php \esc_html_e( 'Modified', 'aio-page-builder' ); ?></th>
								<th><?php \esc_html_e( 'Download', 'aio-page-builder' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $state['export_history_rows'] as $row ) : ?>
								<tr>
									<td><code><?php echo \esc_html( $row['filename'] ); ?></code></td>
									<td><?php echo \esc_html( size_format( $row['size_bytes'] ) ); ?></td>
									<td><?php echo \esc_html( $row['modified_at'] ); ?></td>
									<td>
										<?php
										$dl_url = \wp_nonce_url(
											\add_query_arg(
												array(
													'action'   => 'aio_import_export_download',
													'filename' => $row['filename'],
												),
												\admin_url( 'admin-post.php' )
											),
											self::NONCE_DOWNLOAD
										);
										?>
										<a href="<?php echo \esc_url( $dl_url ); ?>"><?php \esc_html_e( 'Download', 'aio-page-builder' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</section>
			<?php endif; ?>

			<?php if ( $state['can_import'] ) : ?>
			<section class="aio-restore" aria-labelledby="aio-restore-heading">
				<h2 id="aio-restore-heading"><?php \esc_html_e( 'Import / Restore', 'aio-page-builder' ); ?></h2>
				<form method="post" action="<?php echo \esc_url( $validate_url ); ?>" enctype="multipart/form-data">
					<input type="hidden" name="action" value="aio_import_export_validate" />
					<?php \wp_nonce_field( self::NONCE_VALIDATE ); ?>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="aio-ie-package"><?php \esc_html_e( 'ZIP package', 'aio-page-builder' ); ?></label></th>
							<td>
								<input type="file" name="aio_ie_package_file" id="aio-ie-package" accept=".zip" required />
								<p class="description"><?php \esc_html_e( 'Upload an AIO Page Builder export package (ZIP) to preview and validate. Maximum size 50 MB. No plugin data is written until you explicitly confirm restore.', 'aio-page-builder' ); ?></p>
							</td>
						</tr>
					</table>
					<p class="submit">
						<button type="submit" class="button button-primary"><?php \esc_html_e( 'Preview import', 'aio-page-builder' ); ?></button>
					</p>
				</form>

				<?php if ( $state['import_validation_summary'] !== null ) : ?>
					<div class="aio-validation-result" aria-live="polite">
						<h3><?php \esc_html_e( 'Preview import', 'aio-page-builder' ); ?></h3>
						<?php
						$sum = $state['import_validation_summary'];
						if ( $sum['validation_passed'] ) {
							echo '<p class="notice notice-success"><strong>' . \esc_html__( 'Validation passed.', 'aio-page-builder' ) . '</strong>';
							if ( $sum['checksum_verified'] ) {
								echo ' ' . \esc_html__( 'Checksums verified.', 'aio-page-builder' );
							}
							echo '</p>';
						} else {
							echo '<p class="notice notice-error"><strong>' . \esc_html__( 'Validation failed.', 'aio-page-builder' ) . '</strong></p>';
						}
						if ( ! empty( $sum['blocking_failures'] ) ) {
							echo '<ul class="aio-blocking-failures">';
							foreach ( $sum['blocking_failures'] as $f ) {
								echo '<li>' . \esc_html( $f ) . '</li>';
							}
							echo '</ul>';
						}
						if ( ! empty( $sum['warnings'] ) ) {
							echo '<p><strong>' . \esc_html__( 'Warnings:', 'aio-page-builder' ) . '</strong></p><ul>';
							foreach ( $sum['warnings'] as $w ) {
								echo '<li>' . \esc_html( $w ) . '</li>';
							}
							echo '</ul>';
						}
						?>
					</div>

					<?php if ( $state['import_package_preview'] !== null ) : ?>
						<?php $p = $state['import_package_preview']; ?>
						<div class="aio-import-package-preview" style="margin: 1em 0;">
							<h3><?php \esc_html_e( 'Package contents', 'aio-page-builder' ); ?></h3>
							<table class="widefat striped" style="max-width: 60em;">
								<tbody>
									<tr>
										<th scope="row"><?php \esc_html_e( 'Export type', 'aio-page-builder' ); ?></th>
										<td><code><?php echo \esc_html( (string) ( $p['export_type'] ?? '' ) ); ?></code></td>
									</tr>
									<tr>
										<th scope="row"><?php \esc_html_e( 'Export timestamp', 'aio-page-builder' ); ?></th>
										<td><?php echo \esc_html( (string) ( $p['export_timestamp'] ?? '' ) ); ?></td>
									</tr>
									<tr>
										<th scope="row"><?php \esc_html_e( 'Plugin version', 'aio-page-builder' ); ?></th>
										<td><?php echo \esc_html( (string) ( $p['plugin_version'] ?? '' ) ); ?></td>
									</tr>
									<tr>
										<th scope="row"><?php \esc_html_e( 'Schema version', 'aio-page-builder' ); ?></th>
										<td><?php echo \esc_html( (string) ( $p['schema_version'] ?? '' ) ); ?></td>
									</tr>
									<tr>
										<th scope="row"><?php \esc_html_e( 'Source site', 'aio-page-builder' ); ?></th>
										<td><?php echo \esc_html( (string) ( $p['source_site_url'] ?? '' ) ); ?></td>
									</tr>
									<tr>
										<th scope="row"><?php \esc_html_e( 'Included categories', 'aio-page-builder' ); ?></th>
										<td><?php echo \esc_html( implode( ', ', (array) ( $p['included_categories'] ?? array() ) ) ); ?></td>
									</tr>
								</tbody>
							</table>
							<p class="description"><?php \esc_html_e( 'This restores only AIO Page Builder plugin-owned data. It does not clone a full WordPress site and does not rewrite built page content unless separately approved.', 'aio-page-builder' ); ?></p>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $state['restore_conflict_rows'] ) ) : ?>
						<div class="aio-conflicts">
							<h3><?php \esc_html_e( 'Conflicts (review before restore)', 'aio-page-builder' ); ?></h3>
							<table class="widefat striped">
								<thead>
									<tr>
										<th><?php \esc_html_e( 'Category', 'aio-page-builder' ); ?></th>
										<th><?php \esc_html_e( 'Key', 'aio-page-builder' ); ?></th>
										<th><?php \esc_html_e( 'Message', 'aio-page-builder' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $state['restore_conflict_rows'] as $c ) : ?>
										<tr>
											<td><?php echo \esc_html( $c['category'] ?? '' ); ?></td>
											<td><code><?php echo \esc_html( $c['key'] ?? '' ); ?></code></td>
											<td><?php echo \esc_html( $c['message'] ?? '' ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>

					<?php if ( $state['restore_action_state']['can_restore'] ) : ?>
						<form method="post" action="<?php echo \esc_url( $restore_url ); ?>">
							<input type="hidden" name="action" value="aio_import_export_confirm_restore" />
							<?php \wp_nonce_field( self::NONCE_RESTORE ); ?>
							<p><?php echo \esc_html( $state['restore_action_state']['message'] ); ?></p>
							<table class="form-table">
								<tr>
									<th scope="row"><?php \esc_html_e( 'Restore scope', 'aio-page-builder' ); ?></th>
									<td>
										<fieldset>
											<?php foreach ( $state['restore_scope_options'] as $opt ) : ?>
												<?php
												$value    = isset( $opt['value'] ) ? (string) $opt['value'] : '';
												$label    = isset( $opt['label'] ) ? (string) $opt['label'] : '';
												$eligible = isset( $opt['eligible_categories'] ) && is_array( $opt['eligible_categories'] ) ? implode( ', ', $opt['eligible_categories'] ) : '';
												?>
												<label style="display:block; margin: 0.25em 0;">
													<input type="radio" name="restore_scope" value="<?php echo \esc_attr( $value ); ?>" <?php checked( $value, Restore_Scope_Keys::FULL_AIO_BACKUP ); ?> required />
													<?php echo \esc_html( $label ); ?>
													<?php if ( $eligible !== '' ) : ?>
														<span class="description"><?php echo \esc_html( ' (' . $eligible . ')' ); ?></span>
													<?php endif; ?>
												</label>
											<?php endforeach; ?>
										</fieldset>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="aio-resolution-mode"><?php \esc_html_e( 'Conflict resolution', 'aio-page-builder' ); ?></label></th>
									<td>
										<select name="resolution_mode" id="aio-resolution-mode" required>
											<?php foreach ( $state['restore_action_state']['resolution_modes'] as $opt ) : ?>
												<option value="<?php echo \esc_attr( $opt['value'] ); ?>"><?php echo \esc_html( $opt['label'] ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php \esc_html_e( 'Confirm restore', 'aio-page-builder' ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="<?php echo \esc_attr( self::FIELD_CONFIRM_ACK ); ?>" value="1" required />
											<?php \esc_html_e( 'I understand this will write AIO Page Builder plugin data to this site. I have reviewed the package preview and conflict handling.', 'aio-page-builder' ); ?>
										</label>
									</td>
								</tr>
							</table>
							<p class="submit">
								<button type="submit" class="button button-primary"><?php \esc_html_e( 'Confirm restore', 'aio-page-builder' ); ?></button>
							</p>
						</form>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( $state['restore_action_state']['last_restore_payload'] !== null ) : ?>
					<?php
					$last = $state['restore_action_state']['last_restore_payload'];
					$ok   = ! empty( $last['success'] );
					?>
					<div class="aio-restore-result notice <?php echo $ok ? 'notice-success' : 'notice-error'; ?>" aria-live="polite">
						<p><strong><?php echo $ok ? \esc_html__( 'Restore completed.', 'aio-page-builder' ) : \esc_html__( 'Restore failed.', 'aio-page-builder' ); ?></strong> <?php echo \esc_html( $last['message'] ?? '' ); ?></p>
						<?php if ( ! empty( $last['restored_categories'] ) ) : ?>
							<p><?php \esc_html_e( 'Restored categories:', 'aio-page-builder' ); ?> <?php echo \esc_html( implode( ', ', $last['restored_categories'] ) ); ?></p>
						<?php endif; ?>
						<?php
						$summary = isset( $last['template_library_restore_summary'] ) && is_array( $last['template_library_restore_summary'] ) ? $last['template_library_restore_summary'] : array();
						$skipped = isset( $summary['skipped_reasons'] ) && is_array( $summary['skipped_reasons'] ) ? $summary['skipped_reasons'] : array();
						if ( $skipped !== array() ) :
							?>
							<p><?php \esc_html_e( 'Skipped (not restored):', 'aio-page-builder' ); ?></p>
							<ul>
								<?php foreach ( $skipped as $item ) : ?>
									<li><strong><?php echo \esc_html( isset( $item['category'] ) ? (string) $item['category'] : '' ); ?></strong>: <?php echo \esc_html( isset( $item['reason'] ) ? (string) $item['reason'] : '' ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<?php if ( ! empty( $last['blocking_failures'] ) ) : ?>
							<ul><li><?php echo \esc_html( implode( '</li><li>', $last['blocking_failures'] ) ); ?></li></ul>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</section>
			<?php endif; ?>

			<section class="aio-uninstall-link" aria-labelledby="aio-uninstall-link-heading">
				<h2 id="aio-uninstall-link-heading"><?php \esc_html_e( 'Uninstall / export behavior', 'aio-page-builder' ); ?></h2>
				<p><a href="<?php echo \esc_url( $state['privacy_screen_url'] ); ?>"><?php \esc_html_e( 'Privacy, Reporting & Settings', 'aio-page-builder' ); ?></a> <?php \esc_html_e( 'describes uninstall choices and export behavior.', 'aio-page-builder' ); ?></p>
				<?php if ( ! empty( $state['template_library_lifecycle_summary'] ) ) : ?>
					<?php $lifecycle = $state['template_library_lifecycle_summary']; ?>
					<p class="aio-lifecycle-summary"><?php echo \esc_html( $lifecycle['built_pages_description'] ?? '' ); ?></p>
					<p class="aio-lifecycle-summary"><?php echo \esc_html( $lifecycle['template_registry_description'] ?? '' ); ?></p>
					<p><a href="<?php echo \esc_url( $state['privacy_screen_url'] ); ?>#aio-lifecycle-heading"><?php \esc_html_e( 'Full template library lifecycle and restore guidance', 'aio-page-builder' ); ?></a></p>
				<?php endif; ?>
			</section>
		</div>
		<?php
	}

	/**
	 * Handles create export: nonce, capability, mode; redirects with message.
	 *
	 * @return void
	 */
	public function handle_create_export(): void {
		if ( ! isset( $_POST['_wpnonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['_wpnonce'] ) ), self::NONCE_CREATE_EXPORT ) ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'nonce' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::EXPORT_DATA ) ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'capability' ) );
			exit;
		}
		$mode = isset( $_POST['export_mode'] ) ? \sanitize_text_field( \wp_unslash( $_POST['export_mode'] ) ) : '';
		if ( $mode === '' || ! Export_Mode_Keys::is_valid( $mode ) ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'invalid_mode' ) );
			exit;
		}
		$container = $this->container;
		if ( ! $container instanceof Service_Container || ! $container->has( 'export_generator' ) ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'service' ) );
			exit;
		}
		/** @var Export_Generator $generator */
		$generator = $container->get( 'export_generator' );
		$result    = $generator->generate( $mode, array() );
		$payload   = $result->to_payload();
		if ( $result->is_success() ) {
			\wp_safe_redirect( $this->screen_url( 'export_created', null, $payload['package_filename'] ?? '' ) );
		} else {
			\wp_safe_redirect( $this->screen_url( 'error', $payload['message'] ?? 'export_failed' ) );
		}
		exit;
	}

	/**
	 * Handles package validation: nonce, capability, upload; stores result in transient; redirects.
	 *
	 * @return void
	 */
	public function handle_validate(): void {
		if ( ! isset( $_POST['_wpnonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['_wpnonce'] ) ), self::NONCE_VALIDATE ) ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'nonce' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::IMPORT_DATA ) ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'capability' ) );
			exit;
		}
		$container = $this->container;
		if ( ! $container instanceof Service_Container || ! $container->has( 'import_validator' ) || ! $container->has( 'plugin_path_manager' ) ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'service' ) );
			exit;
		}
		$path_manager = $container->get( 'plugin_path_manager' );
		if ( ! $path_manager instanceof Plugin_Path_Manager ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'service' ) );
			exit;
		}
		$dir = $path_manager->get_exports_dir();
		if ( $dir === '' || ! is_dir( $dir ) ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'no_exports_dir' ) );
			exit;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name checked with is_uploaded_file(); assignment and usage for MIME check/move only.
		if ( empty( $_FILES['aio_ie_package_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['aio_ie_package_file']['tmp_name'] ) ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'no_file' ) );
			exit;
		}
		$name = isset( $_FILES['aio_ie_package_file']['name'] ) ? \sanitize_file_name( \wp_unslash( $_FILES['aio_ie_package_file']['name'] ) ) : '';
		if ( strtolower( substr( $name, -4 ) ) !== '.zip' ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'not_zip' ) );
			exit;
		}
		$size = isset( $_FILES['aio_ie_package_file']['size'] ) && is_numeric( $_FILES['aio_ie_package_file']['size'] )
			? (int) $_FILES['aio_ie_package_file']['size']
			: 0;
		if ( ! self::is_zip_upload_size_allowed( $size ) ) {
			\wp_safe_redirect( $this->screen_url( 'error', self::ERROR_CODE_FILE_TOO_LARGE ) );
			exit;
		}
		$tmp_path = $_FILES['aio_ie_package_file']['tmp_name']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name validated by is_uploaded_file(); used for MIME check and copy into exports dir.
		$finfo    = \finfo_open( \FILEINFO_MIME_TYPE );
		if ( $finfo === false ) {
			\wp_safe_redirect( $this->screen_url( 'error', self::ERROR_CODE_INVALID_MIME ) );
			exit;
		}
		$detected_mime = \finfo_file( $finfo, $tmp_path );
		\finfo_close( $finfo );
		if ( $detected_mime === false || ! in_array( $detected_mime, self::ALLOWED_ZIP_MIME_TYPES, true ) ) {
			\wp_safe_redirect( $this->screen_url( 'error', self::ERROR_CODE_INVALID_MIME ) );
			exit;
		}
		$temp_name = 'aio-import-validate-' . \get_current_user_id() . '.zip';
		$dest      = rtrim( $dir, '/\\' ) . '/' . $temp_name;
		if ( ! @\copy( (string) $tmp_path, $dest ) ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'move_failed' ) );
			exit;
		}
		/** @var Import_Validator $validator */
		$validator = $container->get( 'import_validator' );
		$result    = $validator->validate( $dest );
		$payload   = $result->to_payload();
		$manifest  = $result->get_manifest();
		\set_transient(
			self::TRANSIENT_VALIDATION . \get_current_user_id(),
			array(
				'payload'  => $payload,
				'manifest' => $manifest,
			),
			3600
		);
		\wp_safe_redirect( $this->screen_url( 'validated' ) );
		exit;
	}

	/**
	 * Handles confirm restore: nonce, capability, resolution mode; runs pipeline; stores result; deletes validation transient; redirects.
	 *
	 * @return void
	 */
	public function handle_confirm_restore(): void {
		if ( ! isset( $_POST['_wpnonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['_wpnonce'] ) ), self::NONCE_RESTORE ) ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'nonce' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::IMPORT_DATA ) ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'capability' ) );
			exit;
		}
		$ack = isset( $_POST[ self::FIELD_CONFIRM_ACK ] ) ? \sanitize_text_field( \wp_unslash( $_POST[ self::FIELD_CONFIRM_ACK ] ) ) : '';
		if ( $ack !== '1' ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'confirm_required' ) );
			exit;
		}
		$scope = isset( $_POST['restore_scope'] ) ? \sanitize_text_field( \wp_unslash( $_POST['restore_scope'] ) ) : '';
		if ( $scope === '' || ! Restore_Scope_Keys::is_valid( $scope ) ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'invalid_scope' ) );
			exit;
		}
		$resolution = isset( $_POST['resolution_mode'] ) ? \sanitize_text_field( \wp_unslash( $_POST['resolution_mode'] ) ) : '';
		if ( $resolution === '' ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'resolution_required' ) );
			exit;
		}
		$user_id   = \get_current_user_id();
		$stored    = \get_transient( self::TRANSIENT_VALIDATION . $user_id );
		$container = $this->container;
		if ( ! is_array( $stored ) || ! $container instanceof Service_Container || ! $container->has( 'restore_pipeline' ) ) {
			\wp_safe_redirect( $this->screen_url( 'error', 'no_validation' ) );
			exit;
		}
		$validation_result = Import_Validation_Result::from_stored( $stored );
		/** @var Restore_Pipeline $pipeline */
		$pipeline = $container->get( 'restore_pipeline' );
		$allowed  = null;
		if ( $scope === Restore_Scope_Keys::SETTINGS_PROFILE_ONLY ) {
			$allowed = array( 'settings', 'styling', 'profiles', 'uninstall_restore_metadata' );
		}
		$result = $pipeline->restore( $validation_result, $resolution, $allowed );
		\delete_transient( self::TRANSIENT_VALIDATION . $user_id );
		$zip_path = $validation_result->get_package_path();
		if ( $zip_path !== '' && is_file( $zip_path ) ) {
			@unlink( $zip_path );
		}
		\set_transient( self::TRANSIENT_RESTORE_RESULT . $user_id, array( 'payload' => $result->to_payload() ), 60 );
		\wp_safe_redirect( $this->screen_url( 'restored' ) );
		exit;
	}

	/**
	 * Serves export package download: nonce, capability, filename; no path exposure.
	 *
	 * @return void
	 */
	public function handle_download_export(): void {
		if ( ! isset( $_GET['_wpnonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_GET['_wpnonce'] ) ), self::NONCE_DOWNLOAD ) ) {
			\wp_die( \esc_html__( 'Invalid request.', 'aio-page-builder' ), 403 );
		}
		if ( ! \current_user_can( Capabilities::EXPORT_DATA ) ) {
			\wp_die( \esc_html__( 'You do not have permission to download exports.', 'aio-page-builder' ), 403 );
		}
		$filename = isset( $_GET['filename'] ) ? \sanitize_file_name( \wp_unslash( $_GET['filename'] ) ) : '';
		if ( $filename === '' ) {
			\wp_die( \esc_html__( 'Missing filename.', 'aio-page-builder' ), 400 );
		}
		if ( ! $this->container instanceof Service_Container || ! $this->container->has( 'plugin_path_manager' ) ) {
			\wp_die( \esc_html__( 'Service unavailable.', 'aio-page-builder' ), 503 );
		}
		$path_manager = $this->container->get( 'plugin_path_manager' );
		if ( ! $path_manager instanceof Plugin_Path_Manager ) {
			\wp_die( \esc_html__( 'Service unavailable.', 'aio-page-builder' ), 503 );
		}
		$path = $path_manager->get_export_package_path( $filename );
		if ( $path === '' || ! is_file( $path ) ) {
			\wp_die( \esc_html__( 'File not found.', 'aio-page-builder' ), 404 );
		}
		\header( 'Content-Type: application/zip' );
		\header( 'Content-Disposition: attachment; filename="' . \esc_attr( $filename ) . '"' );
		\header( 'Content-Length: ' . (string) filesize( $path ) );
		$in = \fopen( $path, 'rb' );
		if ( false === $in ) {
			\wp_die( \esc_html__( 'Could not read file.', 'aio-page-builder' ), 500 );
		}
		\fpassthru( $in );
		\fclose( $in );
		exit;
	}

	private function screen_url( string $status, ?string $code = null, string $filename = '' ): string {
		$args = array(
			'page'          => self::SLUG,
			'aio_ie_status' => $status,
		);
		if ( $code !== null ) {
			$args['aio_ie_code'] = $code;
		}
		if ( $filename !== '' ) {
			$args['aio_ie_file'] = $filename;
		}
		return \add_query_arg( $args, \admin_url( 'admin.php' ) );
	}

	/**
	 * Returns user-facing error message for a given code (no sensitive details).
	 *
	 * @param string $code
	 * @return string
	 */
	private function error_message_for_code( string $code ): string {
		$messages = array(
			'nonce'               => __( 'Security check failed. Please try again.', 'aio-page-builder' ),
			'capability'          => __( 'You do not have permission for this action.', 'aio-page-builder' ),
			'invalid_mode'        => __( 'Invalid export mode.', 'aio-page-builder' ),
			'service'             => __( 'Service unavailable. Please try again later.', 'aio-page-builder' ),
			'no_exports_dir'      => __( 'Exports directory is not available.', 'aio-page-builder' ),
			'no_file'             => __( 'No file was uploaded.', 'aio-page-builder' ),
			'not_zip'             => __( 'Please upload a ZIP file.', 'aio-page-builder' ),
			'file_too_large'      => __( 'Import package is too large. Maximum size is 50 MB.', 'aio-page-builder' ),
			'invalid_mime'        => __( 'Invalid file type. Please upload a ZIP package.', 'aio-page-builder' ),
			'move_failed'         => __( 'Could not save the uploaded file.', 'aio-page-builder' ),
			'no_validation'       => __( 'No validation result found. Please validate a package first.', 'aio-page-builder' ),
			'resolution_required' => __( 'Please choose a conflict resolution.', 'aio-page-builder' ),
			'confirm_required'    => __( 'Please confirm restore before continuing.', 'aio-page-builder' ),
			'invalid_scope'       => __( 'Please choose a restore scope.', 'aio-page-builder' ),
		);
		return $messages[ $code ] ?? __( 'An error occurred.', 'aio-page-builder' );
	}

	/**
	 * Returns whether the given upload size is within the allowed limit (for pre-move check and tests).
	 *
	 * @param int $size File size in bytes.
	 * @return bool
	 */
	public static function is_zip_upload_size_allowed( int $size ): bool {
		return $size >= 0 && $size <= self::MAX_ZIP_UPLOAD_BYTES;
	}

	private function get_state_builder(): Import_Export_State_Builder {
		if ( $this->container && $this->container->has( 'import_export_state_builder' ) ) {
			return $this->container->get( 'import_export_state_builder' );
		}
		$path_manager = $this->container && $this->container->has( 'plugin_path_manager' )
			? $this->container->get( 'plugin_path_manager' )
			: new Plugin_Path_Manager();
		return new Import_Export_State_Builder( $path_manager );
	}
}
