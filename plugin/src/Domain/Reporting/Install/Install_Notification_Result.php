<?php
/**
 * Result of an install notification attempt (spec §46.2, §46.10, §46.12).
 *
 * Carries eligible, attempted, delivery_status, dedupe_state, log_reference, failure_reason.
 * Used for lifecycle phase result and diagnostics; never blocks activation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Install;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of install notification eligibility check and/or send attempt.
 */
final class Install_Notification_Result {

	/** Dedupe state: notice already sent for this site (skip). */
	public const DEDUPE_ALREADY_SENT = 'already_sent';

	/** Dedupe state: sent in this run. */
	public const DEDUPE_SENT = 'sent';

	/** Dedupe state: send attempted but failed. */
	public const DEDUPE_FAILED = 'failed';

	/** Dedupe state: skipped (ineligible or not attempted). */
	public const DEDUPE_SKIPPED = 'skipped';

	/** @var bool Whether install notification was eligible to send (pre-dedupe). */
	private bool $eligible;

	/** @var bool Whether a send was attempted. */
	private bool $attempted;

	/** @var string One of Reporting_Delivery_Status (pending, sent, failed, skipped). */
	private string $delivery_status;

	/** @var string One of DEDUPE_* */
	private string $dedupe_state;

	/** @var string Local log entry id for this attempt (for diagnostics). */
	private string $log_reference;

	/** @var string Sanitized failure reason if delivery failed. */
	private string $failure_reason;

	private function __construct(
		bool $eligible,
		bool $attempted,
		string $delivery_status,
		string $dedupe_state,
		string $log_reference,
		string $failure_reason
	) {
		$this->eligible        = $eligible;
		$this->attempted       = $attempted;
		$this->delivery_status = $delivery_status;
		$this->dedupe_state    = $dedupe_state;
		$this->log_reference   = $log_reference;
		$this->failure_reason  = $failure_reason;
	}

	/**
	 * Creates a result for already-sent (duplicate suppressed).
	 *
	 * @param string $log_reference Optional log id for the skip decision.
	 * @return self
	 */
	public static function already_sent( string $log_reference = '' ): self {
		return new self(
			false,
			false,
			'skipped',
			self::DEDUPE_ALREADY_SENT,
			$log_reference,
			''
		);
	}

	/**
	 * Creates a result for successful send.
	 *
	 * @param string $log_reference Log entry id for this attempt.
	 * @return self
	 */
	public static function sent( string $log_reference = '' ): self {
		return new self(
			true,
			true,
			'sent',
			self::DEDUPE_SENT,
			$log_reference,
			''
		);
	}

	/**
	 * Creates a result for failed delivery.
	 *
	 * @param string $failure_reason Sanitized reason (no secrets).
	 * @param string $log_reference  Log entry id for this attempt.
	 * @return self
	 */
	public static function failed( string $failure_reason, string $log_reference = '' ): self {
		return new self(
			true,
			true,
			'failed',
			self::DEDUPE_FAILED,
			$log_reference,
			$failure_reason
		);
	}

	/**
	 * Creates a result for skipped (ineligible, e.g. missing site reference).
	 *
	 * @param string $reason Optional reason.
	 * @return self
	 */
	public static function skipped( string $reason = '' ): self {
		return new self(
			false,
			false,
			'skipped',
			self::DEDUPE_SKIPPED,
			'',
			$reason
		);
	}

	public function is_eligible(): bool {
		return $this->eligible;
	}

	public function was_attempted(): bool {
		return $this->attempted;
	}

	public function get_delivery_status(): string {
		return $this->delivery_status;
	}

	public function get_dedupe_state(): string {
		return $this->dedupe_state;
	}

	public function get_log_reference(): string {
		return $this->log_reference;
	}

	public function get_failure_reason(): string {
		return $this->failure_reason;
	}

	/**
	 * Array for logging and diagnostics (redaction-safe).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'eligible'        => $this->eligible,
			'attempted'       => $this->attempted,
			'delivery_status' => $this->delivery_status,
			'dedupe_state'    => $this->dedupe_state,
			'log_reference'   => $this->log_reference,
			'failure_reason'  => $this->failure_reason,
		);
	}
}
