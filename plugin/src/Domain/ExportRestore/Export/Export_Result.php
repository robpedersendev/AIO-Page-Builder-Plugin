<?php
/**
 * Result of an export generation run (spec §52, export-bundle-structure-contract.md).
 *
 * Stable shape for UI/download integration; no secrets.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Export;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable export result: package path, mode, categories, checksum summary, size, log reference.
 */
final class Export_Result {

	/** @var bool */
	private bool $success;

	/** @var string */
	private string $message;

	/** @var string Package absolute path (empty if failed). */
	private string $package_path;

	/** @var string Export mode key. */
	private string $export_mode;

	/** @var array<int, string> */
	private array $included_categories;

	/** @var array<int, string> */
	private array $excluded_categories;

	/** @var int Number of files in package_checksum_list. */
	private int $checksum_count;

	/** @var int Package size in bytes (0 if not written). */
	private int $package_size_bytes;

	/** @var string Optional log reference (e.g. export log id or message). */
	private string $log_reference;

	/** @var string Package filename only (no path). */
	private string $package_filename;

	/** @var array<string, mixed> Template-library export validation summary (Prompt 185). */
	private array $template_library_export_summary;

	/**
	 * @param bool                 $success              Whether export completed successfully.
	 * @param string               $message              Human-readable outcome message.
	 * @param string               $package_path         Absolute path to ZIP (empty if failed).
	 * @param string               $export_mode          Export mode key.
	 * @param array<int, string>         $included_categories  Categories included in bundle.
	 * @param array<int, string>         $excluded_categories  Categories excluded (for audit).
	 * @param int                  $checksum_count       Number of checksummed files.
	 * @param int                  $package_size_bytes   Size of package file.
	 * @param string               $log_reference        Optional log reference.
	 * @param string               $package_filename     Filename only.
	 * @param array<string, mixed> $template_library_export_summary Optional template-library validation summary.
	 */
	public function __construct(
		bool $success,
		string $message,
		string $package_path,
		string $export_mode,
		array $included_categories,
		array $excluded_categories,
		int $checksum_count,
		int $package_size_bytes,
		string $log_reference,
		string $package_filename,
		array $template_library_export_summary = array()
	) {
		$this->success                         = $success;
		$this->message                         = $message;
		$this->package_path                    = $package_path;
		$this->export_mode                     = $export_mode;
		$this->included_categories             = $included_categories;
		$this->excluded_categories             = $excluded_categories;
		$this->checksum_count                  = $checksum_count;
		$this->package_size_bytes              = $package_size_bytes;
		$this->log_reference                   = $log_reference;
		$this->package_filename                = $package_filename;
		$this->template_library_export_summary = $template_library_export_summary;
	}

	/** @return bool */
	public function is_success(): bool {
		return $this->success;
	}

	/** @return string */
	public function get_message(): string {
		return $this->message;
	}

	/** @return string */
	public function get_package_path(): string {
		return $this->package_path;
	}

	/** @return string */
	public function get_export_mode(): string {
		return $this->export_mode;
	}

	/** @return array<int, string> */
	public function get_included_categories(): array {
		return $this->included_categories;
	}

	/** @return array<int, string> */
	public function get_excluded_categories(): array {
		return $this->excluded_categories;
	}

	/** @return int */
	public function get_checksum_count(): int {
		return $this->checksum_count;
	}

	/** @return int */
	public function get_package_size_bytes(): int {
		return $this->package_size_bytes;
	}

	/** @return string */
	public function get_log_reference(): string {
		return $this->log_reference;
	}

	/** @return string */
	public function get_package_filename(): string {
		return $this->package_filename;
	}

	/** @return array<string, mixed> */
	public function get_template_library_export_summary(): array {
		return $this->template_library_export_summary;
	}

	/**
	 * Returns a payload suitable for UI or API (no secrets).
	 *
	 * @return array{success: bool, message: string, package_path: string, export_mode: string, included_categories: array<int, string>, excluded_categories: array<int, string>, checksum_count: int, package_size_bytes: int, log_reference: string, package_filename: string}
	 */
	public function to_payload(): array {
		return array(
			'success'                         => $this->success,
			'message'                         => $this->message,
			'package_path'                    => $this->package_path,
			'export_mode'                     => $this->export_mode,
			'included_categories'             => $this->included_categories,
			'excluded_categories'             => $this->excluded_categories,
			'checksum_count'                  => $this->checksum_count,
			'package_size_bytes'              => $this->package_size_bytes,
			'log_reference'                   => $this->log_reference,
			'package_filename'                => $this->package_filename,
			'template_library_export_summary' => $this->template_library_export_summary,
		);
	}

	/**
	 * Creates a successful result.
	 *
	 * @param string               $package_path   Absolute path to ZIP.
	 * @param string               $export_mode    Export mode key.
	 * @param array<int, string>         $included       Included categories.
	 * @param array<int, string>         $excluded       Excluded categories.
	 * @param int                  $checksum_count Number of checksummed files.
	 * @param int                  $package_size   Size in bytes.
	 * @param string               $filename       Package filename.
	 * @param string               $log_ref        Optional log reference.
	 * @param array<string, mixed> $template_library_export_summary Optional template-library validation summary.
	 * @return self
	 */
	public static function success(
		string $package_path,
		string $export_mode,
		array $included,
		array $excluded,
		int $checksum_count,
		int $package_size,
		string $filename,
		string $log_ref = '',
		array $template_library_export_summary = array()
	): self {
		return new self(
			true,
			'Export completed successfully.',
			$package_path,
			$export_mode,
			$included,
			$excluded,
			$checksum_count,
			$package_size,
			$log_ref,
			$filename,
			$template_library_export_summary
		);
	}

	/**
	 * Creates a failure result.
	 *
	 * @param string       $message   Error or reason message.
	 * @param string       $mode      Export mode attempted.
	 * @param array<int, string> $included  Categories that would have been included.
	 * @param array<int, string> $excluded  Excluded categories.
	 * @param string       $log_ref   Optional log reference.
	 * @return self
	 */
	public static function failure(
		string $message,
		string $mode,
		array $included = array(),
		array $excluded = array(),
		string $log_ref = ''
	): self {
		return new self(
			false,
			$message,
			'',
			$mode,
			$included,
			$excluded,
			0,
			0,
			$log_ref,
			'',
			array()
		);
	}
}
