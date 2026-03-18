<?php
/**
 * Result of uninstall flow (spec §52.11, §53.6, §53.9). Built pages preserved.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Uninstall;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable uninstall result: export choice, export reference, cleanup scope, preserved state.
 *
 * Example payload (export-and-cleanup path):
 * {
 *   "success": true,
 *   "message": "Export completed. Plugin data has been removed. Built pages remain.",
 *   "export_choice": "full_backup",
 *   "export_result_reference": "/path/to/uploads/.../aio-export-pre_uninstall_backup-20250715-120000-mysite.zip",
 *   "cleanup_scope": "full_plugin_owned",
 *   "scheduled_events_removed": true,
 *   "plugin_data_removed": true,
 *   "built_pages_preserved": true,
 *   "log_reference": "uninstall_2025-07-15T12:00:00Z"
 * }
 */
final class Uninstall_Result {

	/** User chose to export full backup before cleanup. */
	public const CHOICE_FULL_BACKUP = 'full_backup';

	/** User chose to export settings/profile only. */
	public const CHOICE_SETTINGS_PROFILE_ONLY = 'settings_profile_only';

	/** User chose to skip export and continue with cleanup. */
	public const CHOICE_SKIP_EXPORT = 'skip_export';

	/** User cancelled uninstall. */
	public const CHOICE_CANCEL = 'cancel';

	/** @var bool */
	private bool $success;

	/** @var string */
	private string $message;

	/** @var string One of CHOICE_* */
	private string $export_choice;

	/** @var string Optional reference to export result (e.g. package path or log id). */
	private string $export_result_reference;

	/** @var string Cleanup scope applied (e.g. full_plugin_owned). */
	private string $cleanup_scope;

	/** @var bool Whether scheduled events were removed. */
	private bool $scheduled_events_removed;

	/** @var bool Whether plugin-owned data was removed. */
	private bool $plugin_data_removed;

	/** @var bool Always true: built pages are never deleted by this flow. */
	private bool $built_pages_preserved;

	/** @var string Log reference for this uninstall. */
	private string $log_reference;

	/**
	 * @param bool   $success
	 * @param string $message
	 * @param string $export_choice
	 * @param string $export_result_reference
	 * @param string $cleanup_scope
	 * @param bool   $scheduled_events_removed
	 * @param bool   $plugin_data_removed
	 * @param bool   $built_pages_preserved
	 * @param string $log_reference
	 */
	public function __construct(
		bool $success,
		string $message,
		string $export_choice,
		string $export_result_reference,
		string $cleanup_scope,
		bool $scheduled_events_removed,
		bool $plugin_data_removed,
		bool $built_pages_preserved,
		string $log_reference
	) {
		$this->success                  = $success;
		$this->message                  = $message;
		$this->export_choice            = $export_choice;
		$this->export_result_reference  = $export_result_reference;
		$this->cleanup_scope            = $cleanup_scope;
		$this->scheduled_events_removed = $scheduled_events_removed;
		$this->plugin_data_removed      = $plugin_data_removed;
		$this->built_pages_preserved    = $built_pages_preserved;
		$this->log_reference            = $log_reference;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_message(): string {
		return $this->message;
	}

	public function get_export_choice(): string {
		return $this->export_choice;
	}

	public function get_export_result_reference(): string {
		return $this->export_result_reference;
	}

	public function get_cleanup_scope(): string {
		return $this->cleanup_scope;
	}

	public function scheduled_events_removed(): bool {
		return $this->scheduled_events_removed;
	}

	public function plugin_data_removed(): bool {
		return $this->plugin_data_removed;
	}

	public function built_pages_preserved(): bool {
		return $this->built_pages_preserved;
	}

	public function get_log_reference(): string {
		return $this->log_reference;
	}

	/**
	 * Payload for UI/log (no secrets).
	 *
	 * @return array{success: bool, message: string, export_choice: string, export_result_reference: string, cleanup_scope: string, scheduled_events_removed: bool, plugin_data_removed: bool, built_pages_preserved: bool, log_reference: string}
	 */
	public function to_payload(): array {
		return array(
			'success'                  => $this->success,
			'message'                  => $this->message,
			'export_choice'            => $this->export_choice,
			'export_result_reference'  => $this->export_result_reference,
			'cleanup_scope'            => $this->cleanup_scope,
			'scheduled_events_removed' => $this->scheduled_events_removed,
			'plugin_data_removed'      => $this->plugin_data_removed,
			'built_pages_preserved'    => $this->built_pages_preserved,
			'log_reference'            => $this->log_reference,
		);
	}

	public static function cancelled( string $log_reference = '' ): self {
		return new self(
			false,
			'Uninstall cancelled.',
			self::CHOICE_CANCEL,
			'',
			'',
			false,
			false,
			true,
			$log_reference
		);
	}

	public static function completed(
		string $export_choice,
		string $export_result_reference,
		string $cleanup_scope,
		bool $scheduled_events_removed,
		bool $plugin_data_removed,
		string $log_reference,
		string $message = 'Uninstall completed. Built pages remain.'
	): self {
		return new self(
			true,
			$message,
			$export_choice,
			$export_result_reference,
			$cleanup_scope,
			$scheduled_events_removed,
			$plugin_data_removed,
			true,
			$log_reference
		);
	}
}
