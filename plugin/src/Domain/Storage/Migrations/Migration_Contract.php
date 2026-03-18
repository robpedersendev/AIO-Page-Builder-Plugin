<?php
/**
 * Migration interface and result object (spec §8.10, §11.9, §58.4, §58.5). No migration execution in this file.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Migrations;

defined( 'ABSPATH' ) || exit;

/**
 * Result of a single migration run. Sanitized for logging; no secrets in message or notes.
 */
final class Migration_Result {

	public const STATUS_SUCCESS = 'success';
	public const STATUS_WARNING = 'warning';
	public const STATUS_FAILURE = 'failure';
	public const STATUS_SKIPPED = 'skipped';

	/** @var string One of STATUS_* */
	public readonly string $status;

	/** @var string Human-readable message; must be sanitized. */
	public readonly string $message;

	/** @var list<string> Additional notes; must be sanitized. */
	public readonly array $notes;

	/** @var bool Whether the migration is safe to retry (idempotent or recoverable). */
	public readonly bool $safe_retry;

	/** @var string|null Migration identifier that produced this result. */
	public readonly ?string $migration_id;

	public function __construct(
		string $status,
		string $message = '',
		array $notes = array(),
		bool $safe_retry = false,
		?string $migration_id = null
	) {
		$this->status       = $status;
		$this->message      = $message;
		$this->notes        = $notes;
		$this->safe_retry   = $safe_retry;
		$this->migration_id = $migration_id;
	}

	public function is_success(): bool {
		return $this->status === self::STATUS_SUCCESS;
	}

	public function is_failure(): bool {
		return $this->status === self::STATUS_FAILURE;
	}

	/** @return array{status: string, message: string, notes: array, safe_retry: bool, migration_id: string|null} */
	public function to_array(): array {
		return array(
			'status'       => $this->status,
			'message'      => $this->message,
			'notes'        => $this->notes,
			'safe_retry'   => $this->safe_retry,
			'migration_id' => $this->migration_id,
		);
	}
}

/**
 * Contract for a single migration. Implementations determine applicability and perform one upgrade step.
 */
interface Migration_Contract {

	/**
	 * Unique identifier for this migration (e.g. table_schema_1_to_2).
	 *
	 * @return string
	 */
	public function id(): string;

	/**
	 * Version key this migration applies to (e.g. table_schema, registry_schema).
	 *
	 * @return string
	 */
	public function version_key(): string;

	/**
	 * From-version that this migration upgrades from (e.g. "1").
	 *
	 * @return string
	 */
	public function from_version(): string;

	/**
	 * To-version after this migration runs (e.g. "2").
	 *
	 * @return string
	 */
	public function to_version(): string;

	/**
	 * Whether this migration applies given the current installed version for its version_key.
	 *
	 * @param string $current_installed_version Installed version for version_key().
	 * @return bool
	 */
	public function applies_to( string $current_installed_version ): bool;

	/**
	 * Runs the migration. Must be safe to retry when applicable (idempotent or documented recoverable).
	 * Must not log or include secrets in result.
	 *
	 * @return Migration_Result
	 */
	public function run(): Migration_Result;
}
