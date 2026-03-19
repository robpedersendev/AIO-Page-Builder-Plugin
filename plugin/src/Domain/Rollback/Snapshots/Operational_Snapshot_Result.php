<?php
/**
 * Result DTO for operational snapshot capture (spec §41.2, §41.3; operational-snapshot-schema.md).
 *
 * Immutable: success, snapshot_id, message, errors. Used by Operational_Snapshot_Service.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Snapshots;

defined( 'ABSPATH' ) || exit;

/**
 * Result of a single pre-change or post-change snapshot capture.
 */
final class Operational_Snapshot_Result {

	/** @var bool */
	private $success;

	/** @var string Snapshot ID when success; empty otherwise. */
	private $snapshot_id;

	/** @var string */
	private $message;

	/** @var array<int, string> */
	private $errors;

	/** @var array<string, mixed> Optional full snapshot for logging/tests. */
	private $snapshot;

	public function __construct(
		bool $success,
		string $snapshot_id = '',
		string $message = '',
		array $errors = array(),
		array $snapshot = array()
	) {
		$this->success     = $success;
		$this->snapshot_id = $snapshot_id;
		$this->message     = $message;
		$this->errors      = $errors;
		$this->snapshot    = $snapshot;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_snapshot_id(): string {
		return $this->snapshot_id;
	}

	public function get_message(): string {
		return $this->message;
	}

	/** @return array<int, string> */
	public function get_errors(): array {
		return $this->errors;
	}

	/** @return array<string, mixed> */
	public function get_snapshot(): array {
		return $this->snapshot;
	}

	public static function success( string $snapshot_id, string $message = '', array $snapshot = array() ): self {
		return new self( true, $snapshot_id, $message !== '' ? $message : __( 'Snapshot captured.', 'aio-page-builder' ), array(), $snapshot );
	}

	public static function failure( string $message, array $errors = array() ): self {
		return new self( false, '', $message, $errors, array() );
	}
}
