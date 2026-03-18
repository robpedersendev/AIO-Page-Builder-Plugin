<?php
/**
 * Uninstall export prompt flow: four choices, export then cleanup, built pages preserved (spec §52.11, §53.6, §53.9).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Uninstall;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ExportRestore\Contracts\Export_Mode_Keys;
use AIOPageBuilder\Domain\ExportRestore\Export\Export_Generator;

/**
 * Orchestrates uninstall choices (full backup, settings/profile only, skip export, cancel), runs export when chosen, then cleanup.
 * Built pages are never deleted. For use from admin Uninstall screen; uninstall.php runs cleanup only.
 */
final class Uninstall_Export_Prompt_Service {

	/** @var Export_Generator */
	private Export_Generator $export_generator;

	/** @var Uninstall_Cleanup_Service */
	private Uninstall_Cleanup_Service $cleanup_service;

	public function __construct( Export_Generator $export_generator, Uninstall_Cleanup_Service $cleanup_service ) {
		$this->export_generator = $export_generator;
		$this->cleanup_service  = $cleanup_service;
	}

	/**
	 * Returns the four required uninstall export choices for UI (spec §52.11).
	 *
	 * @return list<array{value: string, label: string, description: string}>
	 */
	public function get_choices(): array {
		return array(
			array(
				'value'       => Uninstall_Result::CHOICE_FULL_BACKUP,
				'label'       => \__( 'Export full backup', 'aio-page-builder' ),
				'description' => \__( 'Download a full backup ZIP, then remove plugin data. Built pages will remain.', 'aio-page-builder' ),
			),
			array(
				'value'       => Uninstall_Result::CHOICE_SETTINGS_PROFILE_ONLY,
				'label'       => \__( 'Export settings and profile only', 'aio-page-builder' ),
				'description' => \__( 'Download a reduced bundle (settings, profiles, restore metadata), then remove plugin data. Built pages will remain.', 'aio-page-builder' ),
			),
			array(
				'value'       => Uninstall_Result::CHOICE_SKIP_EXPORT,
				'label'       => \__( 'Skip export and continue', 'aio-page-builder' ),
				'description' => \__( 'Remove plugin data without exporting. Built pages will remain.', 'aio-page-builder' ),
			),
			array(
				'value'       => Uninstall_Result::CHOICE_CANCEL,
				'label'       => \__( 'Cancel uninstall', 'aio-page-builder' ),
				'description' => \__( 'Do nothing and keep the plugin installed.', 'aio-page-builder' ),
			),
		);
	}

	/**
	 * Message stating that built pages will remain (spec §52.11, §53.9). Shown on uninstall screen.
	 *
	 * @return string
	 */
	public static function built_pages_remain_message(): string {
		return \__( 'Built pages (content created with the builder) will remain on your site. Only plugin-owned data (settings, templates, plans, logs) will be removed when you continue.', 'aio-page-builder' );
	}

	/**
	 * Runs the uninstall flow for the chosen option: export (if applicable) then cleanup. Cancel returns without cleanup.
	 *
	 * @param string $choice One of Uninstall_Result::CHOICE_*.
	 * @param string $log_reference Optional log reference for this run.
	 * @return Uninstall_Result
	 */
	public function run_uninstall_flow( string $choice, string $log_reference = '' ): Uninstall_Result {
		$log_ref = $log_reference !== '' ? $log_reference : 'uninstall_' . gmdate( 'Y-m-d\TH-i-s\Z' );

		if ( $choice === Uninstall_Result::CHOICE_CANCEL ) {
			return Uninstall_Result::cancelled( $log_ref );
		}

		$export_result_reference = '';

		if ( $choice === Uninstall_Result::CHOICE_FULL_BACKUP ) {
			$export_result           = $this->export_generator->generate( Export_Mode_Keys::PRE_UNINSTALL_BACKUP );
			$export_result_reference = $export_result->get_package_path() !== ''
				? $export_result->get_package_path()
				: ( $export_result->get_package_filename() !== '' ? $export_result->get_package_filename() : '' );
			// * Continue with cleanup even if export failed; user chose to proceed with full backup attempt.
		} elseif ( $choice === Uninstall_Result::CHOICE_SETTINGS_PROFILE_ONLY ) {
			$export_result           = $this->export_generator->generate( Export_Mode_Keys::UNINSTALL_SETTINGS_PROFILE_ONLY );
			$export_result_reference = $export_result->get_package_path() !== ''
				? $export_result->get_package_path()
				: ( $export_result->get_package_filename() !== '' ? $export_result->get_package_filename() : '' );
		}

		$cleanup_result = $this->cleanup_service->cleanup_plugin_owned_data( Uninstall_Cleanup_Service::SCOPE_FULL );

		$message = self::built_pages_remain_message();
		if ( $export_result_reference !== '' ) {
			$message = \__( 'Export completed. Plugin data has been removed. Built pages remain.', 'aio-page-builder' );
		}

		return Uninstall_Result::completed(
			$choice,
			$export_result_reference,
			Uninstall_Cleanup_Service::SCOPE_FULL,
			$cleanup_result['scheduled_removed'],
			$cleanup_result['options_removed'] > 0 || $cleanup_result['tables_dropped'] > 0 || $cleanup_result['cpt_posts_removed'] > 0,
			$log_ref,
			$message
		);
	}
}
