<?php
/**
 * Result of import validation (spec §52.7). No writes occur until validation passes.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Import;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable validation result: passed, blocking failures, conflicts, warnings, manifest.
 */
final class Import_Validation_Result {

	/** @var bool */
	private bool $validation_passed;

	/** @var array<int, string> Blocking failure messages (permission, ZIP, manifest, schema, checksum, prohibited file). */
	private array $blocking_failures;

	/** @var array<int, array{category: string, key: string, message: string}> Conflict entries for pre-scan. */
	private array $conflicts;

	/** @var array<int, string> Non-blocking warnings. */
	private array $warnings;

	/** @var array<string, mixed> Decoded manifest (empty if not read). */
	private array $manifest;

	/** @var string Package path validated. */
	private string $package_path;

	/** @var bool Whether checksums were verified for all listed files. */
	private bool $checksum_verified;

	/**
	 * @param bool                        $validation_passed
	 * @param array<int, string>                $blocking_failures
	 * @param array<int, array<string, string>> $conflicts
	 * @param array<int, string>                $warnings
	 * @param array<string, mixed>        $manifest
	 * @param string                      $package_path
	 * @param bool                        $checksum_verified
	 */
	public function __construct(
		bool $validation_passed,
		array $blocking_failures,
		array $conflicts,
		array $warnings,
		array $manifest,
		string $package_path,
		bool $checksum_verified = false
	) {
		$this->validation_passed = $validation_passed;
		$this->blocking_failures = $blocking_failures;
		$this->conflicts         = $conflicts;
		$this->warnings          = $warnings;
		$this->manifest          = $manifest;
		$this->package_path      = $package_path;
		$this->checksum_verified = $checksum_verified;
	}

	public function validation_passed(): bool {
		return $this->validation_passed;
	}

	/** @return array<int, string> */
	public function get_blocking_failures(): array {
		return $this->blocking_failures;
	}

	/** @return array<int, array{category: string, key: string, message: string}> */
	public function get_conflicts(): array {
		return $this->conflicts;
	}

	/** @return array<int, string> */
	public function get_warnings(): array {
		return $this->warnings;
	}

	/** @return array<string, mixed> */
	public function get_manifest(): array {
		return $this->manifest;
	}

	public function get_package_path(): string {
		return $this->package_path;
	}

	public function is_checksum_verified(): bool {
		return $this->checksum_verified;
	}

	/**
	 * Rebuilds instance from stored payload and manifest (e.g. transient for restore flow). Server-side only.
	 *
	 * @param array{payload: array{validation_passed: bool, blocking_failures: array<int, string>, conflicts: array<int, array>, warnings: array<int, string>, package_path: string, checksum_verified: bool}, manifest: array<string, mixed>} $stored
	 * @return self
	 */
	public static function from_stored( array $stored ): self {
		$p        = $stored['payload'] ?? array();
		$manifest = isset( $stored['manifest'] ) && is_array( $stored['manifest'] ) ? $stored['manifest'] : array();
		return new self(
			(bool) ( $p['validation_passed'] ?? false ),
			isset( $p['blocking_failures'] ) && is_array( $p['blocking_failures'] ) ? $p['blocking_failures'] : array(),
			isset( $p['conflicts'] ) && is_array( $p['conflicts'] ) ? $p['conflicts'] : array(),
			isset( $p['warnings'] ) && is_array( $p['warnings'] ) ? $p['warnings'] : array(),
			$manifest,
			isset( $p['package_path'] ) ? (string) $p['package_path'] : '',
			(bool) ( $p['checksum_verified'] ?? false )
		);
	}

	/**
	 * Payload for UI/API (no secrets). Omit package_path when sending to client.
	 *
	 * @return array{validation_passed: bool, blocking_failures: array<int, string>, conflicts: array<int, array>, warnings: array<int, string>, package_path: string, checksum_verified: bool}
	 */
	public function to_payload(): array {
		return array(
			'validation_passed' => $this->validation_passed,
			'blocking_failures' => $this->blocking_failures,
			'conflicts'         => $this->conflicts,
			'warnings'          => $this->warnings,
			'package_path'      => $this->package_path,
			'checksum_verified' => $this->checksum_verified,
		);
	}
}
