<?php
/**
 * Result of support package generation (spec §52.1, support-package-contract.md §7).
 *
 * Stable shape for UI and logging; no secrets.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Export;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable support package result: success, package reference, included categories, redaction summary, log reference.
 */
final class Support_Package_Result {

	public const SUPPORT_PACKAGE_TYPE = 'support_bundle';

	/** @var bool */
	private bool $success;

	/** @var string */
	private string $message;

	/** @var string Package absolute path (empty when exposing to client). */
	private string $package_path;

	/** @var string Package filename only. */
	private string $package_filename;

	/** @var array<int, string> */
	private array $included_support_categories;

	/** @var array<int, string> Excluded categories (audit). */
	private array $excluded_categories;

	/** @var array{applied: bool, keys_redacted: array<int, string>} */
	private array $redaction_summary;

	/** @var string Safe identifier for admin/UI (e.g. filename). */
	private string $package_reference;

	/** @var string Log reference for this generation run. */
	private string $generation_log_reference;

	/** @var int */
	private int $checksum_count;

	/** @var int */
	private int $package_size_bytes;

	/**
	 * @param bool                                                    $success
	 * @param string                                                  $message
	 * @param string                                                  $package_path
	 * @param string                                                  $package_filename
	 * @param array<int, string>                                      $included_support_categories
	 * @param array<int, string>                                      $excluded_categories
	 * @param array{applied: bool, keys_redacted: array<int, string>} $redaction_summary
	 * @param string                                                  $package_reference
	 * @param string                                                  $generation_log_reference
	 * @param int                                                     $checksum_count
	 * @param int                                                     $package_size_bytes
	 */
	public function __construct(
		bool $success,
		string $message,
		string $package_path,
		string $package_filename,
		array $included_support_categories,
		array $excluded_categories,
		array $redaction_summary,
		string $package_reference,
		string $generation_log_reference,
		int $checksum_count,
		int $package_size_bytes
	) {
		$this->success                     = $success;
		$this->message                     = $message;
		$this->package_path                = $package_path;
		$this->package_filename            = $package_filename;
		$this->included_support_categories = $included_support_categories;
		$this->excluded_categories         = $excluded_categories;
		$this->redaction_summary           = $redaction_summary;
		$this->package_reference           = $package_reference;
		$this->generation_log_reference    = $generation_log_reference;
		$this->checksum_count              = $checksum_count;
		$this->package_size_bytes          = $package_size_bytes;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_message(): string {
		return $this->message;
	}

	public function get_package_path(): string {
		return $this->package_path;
	}

	public function get_package_filename(): string {
		return $this->package_filename;
	}

	/** @return array<int, string> */
	public function get_included_support_categories(): array {
		return $this->included_support_categories;
	}

	/** @return array<int, string> */
	public function get_excluded_categories(): array {
		return $this->excluded_categories;
	}

	/** @return array{applied: bool, keys_redacted: array<int, string>} */
	public function get_redaction_summary(): array {
		return $this->redaction_summary;
	}

	public function get_package_reference(): string {
		return $this->package_reference;
	}

	public function get_generation_log_reference(): string {
		return $this->generation_log_reference;
	}

	public function get_checksum_count(): int {
		return $this->checksum_count;
	}

	public function get_package_size_bytes(): int {
		return $this->package_size_bytes;
	}

	/**
	 * Payload for UI/API (no secrets). Omit or empty package_path when sending to client.
	 *
	 * @return array{success: bool, message: string, package_filename: string, support_package_type: string, included_support_categories: array<int, string>, excluded_categories: array<int, string>, redaction_summary: array, package_reference: string, generation_log_reference: string, checksum_count: int, package_size_bytes: int}
	 */
	public function to_payload(): array {
		return array(
			'success'                     => $this->success,
			'message'                     => $this->message,
			'package_filename'            => $this->package_filename,
			'support_package_type'        => self::SUPPORT_PACKAGE_TYPE,
			'included_support_categories' => $this->included_support_categories,
			'excluded_categories'         => $this->excluded_categories,
			'redaction_summary'           => $this->redaction_summary,
			'package_reference'           => $this->package_reference,
			'generation_log_reference'    => $this->generation_log_reference,
			'checksum_count'              => $this->checksum_count,
			'package_size_bytes'          => $this->package_size_bytes,
		);
	}

	/**
	 * @param string                                                  $package_path
	 * @param string                                                  $package_filename
	 * @param array<int, string>                                      $included_support_categories
	 * @param array<int, string>                                      $excluded_categories
	 * @param array{applied: bool, keys_redacted: array<int, string>} $redaction_summary
	 * @param int                                                     $checksum_count
	 * @param int                                                     $package_size_bytes
	 * @param string                                                  $generation_log_reference
	 * @return self
	 */
	public static function success(
		string $package_path,
		string $package_filename,
		array $included_support_categories,
		array $excluded_categories,
		array $redaction_summary,
		int $checksum_count,
		int $package_size_bytes,
		string $generation_log_reference = ''
	): self {
		$ref = $generation_log_reference !== '' ? $generation_log_reference : 'support-' . gmdate( 'Y-m-d\TH:i:s\Z' );
		return new self(
			true,
			__( 'Support package generated successfully.', 'aio-page-builder' ),
			$package_path,
			$package_filename,
			$included_support_categories,
			$excluded_categories,
			$redaction_summary,
			$package_filename,
			$ref,
			$checksum_count,
			$package_size_bytes
		);
	}

	/**
	 * @param string $message
	 * @param string $generation_log_reference
	 * @return self
	 */
	public static function failure( string $message, string $generation_log_reference = '' ): self {
		return new self(
			false,
			$message,
			'',
			'',
			array(),
			array(),
			array(
				'applied'       => false,
				'keys_redacted' => array(),
			),
			'',
			$generation_log_reference,
			0,
			0
		);
	}
}
