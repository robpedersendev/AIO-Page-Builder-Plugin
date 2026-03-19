<?php
/**
 * Builds view-state payloads for the Import / Export admin screen (spec §49.4, §52, §59.13).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ExportRestore\Contracts\Export_Mode_Keys;
use AIOPageBuilder\Domain\ExportRestore\Import\Conflict_Resolution_Service;
use AIOPageBuilder\Domain\Lifecycle\Template_Library_Lifecycle_Summary_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager;

/**
 * Produces stable view-state for export mode selection, export history, import validation, and restore flows.
 * No writes; no exposure of full server paths or raw extracted content.
 * When Template_Library_Lifecycle_Summary_Builder is provided, includes template_library_lifecycle_summary (Prompt 213).
 */
final class Import_Export_State_Builder {

	/** @var Plugin_Path_Manager */
	private Plugin_Path_Manager $path_manager;

	/** @var Template_Library_Lifecycle_Summary_Builder|null */
	private ?Template_Library_Lifecycle_Summary_Builder $lifecycle_summary_builder;

	/** Maximum number of export history rows to return. */
	private const EXPORT_HISTORY_LIMIT = 50;

	public function __construct( Plugin_Path_Manager $path_manager, ?Template_Library_Lifecycle_Summary_Builder $lifecycle_summary_builder = null ) {
		$this->path_manager              = $path_manager;
		$this->lifecycle_summary_builder = $lifecycle_summary_builder;
	}

	/**
	 * Builds full screen state. Validation and restore result payloads are passed from the screen (e.g. from transients).
	 *
	 * @param array<string, mixed>|null $validation_payload   Import_Validation_Result::to_payload() or null.
	 * @param array<string, mixed>|null $restore_result_payload Restore_Result::to_payload() or null.
	 * @return array{
	 *   export_mode_options: array<int, array{value: string, label: string}>,
	 *   export_history_rows: array<int, array{filename: string, size_bytes: int, modified_at: string}>,
	 *   import_validation_summary: array{validation_passed: bool, blocking_failures: array<int, string>, conflicts: array<int, array>, warnings: array<int, string>, checksum_verified: bool}|null,
	 *   restore_conflict_rows: array<int, array{category: string, key: string, message: string}>,
	 *   restore_action_state: array{can_restore: bool, resolution_modes: array<int, array{value: string, label: string}>, message: string, last_restore_payload: array|null},
	 *   can_export: bool,
	 *   can_import: bool,
	 *   privacy_screen_url: string
	 * }
	 */
	public function build( ?array $validation_payload = null, ?array $restore_result_payload = null ): array {
		$can_export = \current_user_can( Capabilities::EXPORT_DATA );
		$can_import = \current_user_can( Capabilities::IMPORT_DATA );

		$import_summary   = null;
		$conflict_rows    = array();
		$can_restore      = false;
		$resolution_modes = $this->resolution_mode_options();
		$message          = '';

		if ( $validation_payload !== null ) {
			$import_summary = array(
				'validation_passed' => (bool) ( $validation_payload['validation_passed'] ?? false ),
				'blocking_failures' => isset( $validation_payload['blocking_failures'] ) && is_array( $validation_payload['blocking_failures'] )
					? $validation_payload['blocking_failures']
					: array(),
				'conflicts'         => isset( $validation_payload['conflicts'] ) && is_array( $validation_payload['conflicts'] )
					? $validation_payload['conflicts']
					: array(),
				'warnings'          => isset( $validation_payload['warnings'] ) && is_array( $validation_payload['warnings'] )
					? $validation_payload['warnings']
					: array(),
				'checksum_verified' => (bool) ( $validation_payload['checksum_verified'] ?? false ),
			);
			$conflict_rows  = $import_summary['conflicts'];
			$can_restore    = $import_summary['validation_passed'] && $can_import;
			if ( $import_summary['validation_passed'] && ! empty( $conflict_rows ) ) {
				$message = __( 'Validation passed. Resolve conflicts below and confirm restore.', 'aio-page-builder' );
			} elseif ( $import_summary['validation_passed'] ) {
				$message = __( 'Validation passed. You may run restore.', 'aio-page-builder' );
			} elseif ( ! empty( $import_summary['blocking_failures'] ) ) {
				$message = __( 'Validation failed. Fix blocking issues before restore.', 'aio-page-builder' );
			}
		}

		if ( $restore_result_payload !== null ) {
			$message = isset( $restore_result_payload['message'] ) ? (string) $restore_result_payload['message'] : $message;
		}

		$privacy_screen_url = \add_query_arg( 'page', 'aio-page-builder-privacy-reporting', \admin_url( 'admin.php' ) );

		$state = array(
			'export_mode_options'       => $this->export_mode_options(),
			'export_history_rows'       => $this->export_history_rows(),
			'import_validation_summary' => $import_summary,
			'restore_conflict_rows'     => $conflict_rows,
			'restore_action_state'      => array(
				'can_restore'          => $can_restore,
				'resolution_modes'     => $resolution_modes,
				'message'              => $message,
				'last_restore_payload' => $restore_result_payload,
			),
			'can_export'                => $can_export,
			'can_import'                => $can_import,
			'privacy_screen_url'        => $privacy_screen_url,
		);
		if ( $this->lifecycle_summary_builder !== null ) {
			$state['template_library_lifecycle_summary'] = $this->lifecycle_summary_builder->build();
		}
		return $state;
	}

	/**
	 * @return array<int, array{value: string, label: string}>
	 */
	private function export_mode_options(): array {
		$labels = array(
			Export_Mode_Keys::FULL_OPERATIONAL_BACKUP => __( 'Full operational backup', 'aio-page-builder' ),
			Export_Mode_Keys::PRE_UNINSTALL_BACKUP    => __( 'Pre-uninstall backup', 'aio-page-builder' ),
			Export_Mode_Keys::SUPPORT_BUNDLE          => __( 'Support bundle', 'aio-page-builder' ),
			Export_Mode_Keys::TEMPLATE_ONLY_EXPORT    => __( 'Template only', 'aio-page-builder' ),
			Export_Mode_Keys::PLAN_ARTIFACT_EXPORT    => __( 'Plan / artifact export', 'aio-page-builder' ),
			Export_Mode_Keys::UNINSTALL_SETTINGS_PROFILE_ONLY => __( 'Uninstall settings/profile only', 'aio-page-builder' ),
		);
		$out    = array();
		foreach ( Export_Mode_Keys::all() as $mode ) {
			$out[] = array(
				'value' => $mode,
				'label' => $labels[ $mode ] ?? $mode,
			);
		}
		return $out;
	}

	/**
	 * Lists recent export packages (filename, size, mtime). No server paths exposed.
	 *
	 * @return array<int, array{filename: string, size_bytes: int, modified_at: string}>
	 */
	private function export_history_rows(): array {
		$dir = $this->path_manager->get_exports_dir();
		if ( $dir === '' || ! is_dir( $dir ) ) {
			return array();
		}
		$files = @scandir( $dir );
		if ( ! is_array( $files ) ) {
			return array();
		}
		$rows = array();
		foreach ( $files as $file ) {
			if ( $file === '.' || $file === '..' ) {
				continue;
			}
			if ( substr( $file, -4 ) !== '.zip' || strpos( $file, 'aio-export-' ) !== 0 ) {
				continue;
			}
			$full_path = $this->path_manager->get_export_package_path( $file );
			if ( $full_path === '' || ! is_file( $full_path ) ) {
				continue;
			}
			$size   = (int) @filesize( $full_path );
			$mtime  = @filemtime( $full_path );
			$rows[] = array(
				'filename'    => $file,
				'size_bytes'  => $size,
				'modified_at' => $mtime ? gmdate( 'Y-m-d H:i:s', $mtime ) : '',
			);
		}
		usort(
			$rows,
			function ( array $a, array $b ): int {
				return strcmp( $b['modified_at'], $a['modified_at'] );
			}
		);
		return array_slice( $rows, 0, self::EXPORT_HISTORY_LIMIT );
	}

	/**
	 * @return array<int, array{value: string, label: string}>
	 */
	private function resolution_mode_options(): array {
		return array(
			array(
				'value' => Conflict_Resolution_Service::MODE_OVERWRITE,
				'label' => __( 'Overwrite current with package', 'aio-page-builder' ),
			),
			array(
				'value' => Conflict_Resolution_Service::MODE_KEEP_CURRENT,
				'label' => __( 'Keep current; skip conflicting', 'aio-page-builder' ),
			),
			array(
				'value' => Conflict_Resolution_Service::MODE_DUPLICATE,
				'label' => __( 'Import as duplicate where allowed', 'aio-page-builder' ),
			),
			array(
				'value' => Conflict_Resolution_Service::MODE_CANCEL,
				'label' => __( 'Cancel restore', 'aio-page-builder' ),
			),
		);
	}
}
