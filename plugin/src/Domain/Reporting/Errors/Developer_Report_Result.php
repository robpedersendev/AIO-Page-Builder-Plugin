<?php
/**
 * Result of a developer error report evaluation and/or send attempt (spec §46.6–46.12).
 *
 * Carries report_eligible, threshold_reason, dedupe_key, redaction_applied, delivery_status,
 * report_log_reference, and failure_reason. Used for local logging and diagnostics.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Errors;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of developer error report eligibility check and/or send attempt.
 */
final class Developer_Report_Result {

	/** Delivery status: report was sent. */
	public const DELIVERY_SENT = 'sent';

	/** Delivery status: send attempted but failed. */
	public const DELIVERY_FAILED = 'failed';

	/** Delivery status: report not sent (ineligible or skipped). */
	public const DELIVERY_SKIPPED = 'skipped';

	/** @var bool Whether the error was eligible for outbound reporting. */
	private bool $report_eligible;

	/** @var string Reason when ineligible (e.g. severity below threshold, local log only). */
	private string $threshold_reason;

	/** @var string Dedupe key used or that would be used. */
	private string $dedupe_key;

	/** @var bool Whether redaction was applied before payload build. */
	private bool $redaction_applied;

	/** @var string One of DELIVERY_*. */
	private string $delivery_status;

	/** @var string Local log/report reference for this attempt. */
	private string $report_log_reference;

	/** @var string Sanitized failure reason if delivery failed. */
	private string $failure_reason;

	private function __construct(
		bool $report_eligible,
		string $threshold_reason,
		string $dedupe_key,
		bool $redaction_applied,
		string $delivery_status,
		string $report_log_reference,
		string $failure_reason
	) {
		$this->report_eligible      = $report_eligible;
		$this->threshold_reason     = $threshold_reason;
		$this->dedupe_key           = $dedupe_key;
		$this->redaction_applied    = $redaction_applied;
		$this->delivery_status      = $delivery_status;
		$this->report_log_reference = $report_log_reference;
		$this->failure_reason       = $failure_reason;
	}

	/**
	 * Report was eligible and sent successfully.
	 *
	 * @param string $dedupe_key Dedupe key used.
	 * @param string $report_log_reference Log reference.
	 * @return self
	 */
	public static function eligible_sent( string $dedupe_key, string $report_log_reference ): self {
		return new self( true, '', $dedupe_key, true, self::DELIVERY_SENT, $report_log_reference, '' );
	}

	/**
	 * Report was eligible but delivery failed.
	 *
	 * @param string $dedupe_key Dedupe key used.
	 * @param string $report_log_reference Log reference.
	 * @param string $failure_reason Sanitized reason.
	 * @return self
	 */
	public static function eligible_failed( string $dedupe_key, string $report_log_reference, string $failure_reason ): self {
		return new self( true, '', $dedupe_key, true, self::DELIVERY_FAILED, $report_log_reference, $failure_reason );
	}

	/**
	 * Report was not eligible (local log only or below threshold).
	 *
	 * @param string $threshold_reason Reason (e.g. "local log only", "severity below threshold").
	 * @param string $dedupe_key Dedupe key that would apply if eligible (can be empty).
	 * @return self
	 */
	public static function ineligible( string $threshold_reason, string $dedupe_key = '' ): self {
		return new self( false, $threshold_reason, $dedupe_key, false, self::DELIVERY_SKIPPED, '', '' );
	}

	/**
	 * Report was skipped due to dedupe (same report already sent in window).
	 *
	 * @param string $dedupe_key Dedupe key that was already sent.
	 * @return self
	 */
	public static function skipped_dedupe( string $dedupe_key ): self {
		return new self( true, 'dedupe_suppressed', $dedupe_key, true, self::DELIVERY_SKIPPED, '', '' );
	}

	public function is_report_eligible(): bool {
		return $this->report_eligible;
	}

	public function get_threshold_reason(): string {
		return $this->threshold_reason;
	}

	public function get_dedupe_key(): string {
		return $this->dedupe_key;
	}

	public function was_redaction_applied(): bool {
		return $this->redaction_applied;
	}

	public function get_delivery_status(): string {
		return $this->delivery_status;
	}

	public function get_report_log_reference(): string {
		return $this->report_log_reference;
	}

	public function get_failure_reason(): string {
		return $this->failure_reason;
	}

	/** @return array<string, mixed> For logging and diagnostics. */
	public function to_array(): array {
		return array(
			'report_eligible'      => $this->report_eligible,
			'threshold_reason'     => $this->threshold_reason,
			'dedupe_key'           => $this->dedupe_key,
			'redaction_applied'    => $this->redaction_applied,
			'delivery_status'      => $this->delivery_status,
			'report_log_reference' => $this->report_log_reference,
			'failure_reason'       => $this->failure_reason,
		);
	}
}
