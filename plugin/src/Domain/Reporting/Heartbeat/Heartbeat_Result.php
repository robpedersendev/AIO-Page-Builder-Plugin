<?php
/**
 * Result of a heartbeat run (spec §46.4, §46.10, §46.12).
 *
 * Carries due_month, last_successful_month, heartbeat_status, delivery_status, log_reference, failure_reason.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Heartbeat;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of heartbeat eligibility check and/or send attempt.
 */
final class Heartbeat_Result {

	/** Status: already sent for this month (skip). */
	public const HEARTBEAT_ALREADY_SENT = 'already_sent';

	/** Status: sent in this run. */
	public const HEARTBEAT_SENT = 'sent';

	/** Status: send attempted but failed. */
	public const HEARTBEAT_FAILED = 'failed';

	/** Status: skipped (ineligible or not attempted). */
	public const HEARTBEAT_SKIPPED = 'skipped';

	/** @var string YYYY-MM for which the run was evaluated. */
	private string $due_month;

	/** @var string Last month a successful heartbeat was sent (YYYY-MM). */
	private string $last_successful_month;

	/** @var string One of HEARTBEAT_* */
	private string $heartbeat_status;

	/** @var string One of pending, sent, failed, skipped. */
	private string $delivery_status;

	/** @var string Local log entry id for this attempt. */
	private string $log_reference;

	/** @var string Sanitized failure reason if delivery failed. */
	private string $failure_reason;

	private function __construct(
		string $due_month,
		string $last_successful_month,
		string $heartbeat_status,
		string $delivery_status,
		string $log_reference,
		string $failure_reason
	) {
		$this->due_month              = $due_month;
		$this->last_successful_month  = $last_successful_month;
		$this->heartbeat_status       = $heartbeat_status;
		$this->delivery_status        = $delivery_status;
		$this->log_reference           = $log_reference;
		$this->failure_reason         = $failure_reason;
	}

	public static function already_sent( string $due_month, string $last_successful_month, string $log_reference = '' ): self {
		return new self( $due_month, $last_successful_month, self::HEARTBEAT_ALREADY_SENT, 'skipped', $log_reference, '' );
	}

	public static function sent( string $due_month, string $log_reference = '' ): self {
		return new self( $due_month, $due_month, self::HEARTBEAT_SENT, 'sent', $log_reference, '' );
	}

	public static function failed( string $due_month, string $last_successful_month, string $failure_reason, string $log_reference = '' ): self {
		return new self( $due_month, $last_successful_month, self::HEARTBEAT_FAILED, 'failed', $log_reference, $failure_reason );
	}

	public static function skipped( string $due_month, string $reason = '' ): self {
		return new self( $due_month, '', self::HEARTBEAT_SKIPPED, 'skipped', '', $reason );
	}

	public function get_due_month(): string {
		return $this->due_month;
	}

	public function get_last_successful_month(): string {
		return $this->last_successful_month;
	}

	public function get_heartbeat_status(): string {
		return $this->heartbeat_status;
	}

	public function get_delivery_status(): string {
		return $this->delivery_status;
	}

	public function get_log_reference(): string {
		return $this->log_reference;
	}

	public function get_failure_reason(): string {
		return $this->failure_reason;
	}

	/** @return array<string, mixed> */
	public function to_array(): array {
		return array(
			'due_month'              => $this->due_month,
			'last_successful_month'  => $this->last_successful_month,
			'heartbeat_status'       => $this->heartbeat_status,
			'delivery_status'        => $this->delivery_status,
			'log_reference'          => $this->log_reference,
			'failure_reason'         => $this->failure_reason,
		);
	}
}
