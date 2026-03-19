<?php
/**
 * Structured error/log record (spec §45.3–45.5). All message content must be pre-sanitized; no secrets.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Support\Logging;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable record for logging and diagnostics. Callers must supply only redacted, non-secret data.
 */
final class Error_Record {

	/** @var string Unique identifier for this record (e.g. UUID or prefixed id). */
	public readonly string $id;

	/** @var string One of Log_Categories. */
	public readonly string $category;

	/** @var string One of Log_Severities. */
	public readonly string $severity;

	/** @var string ISO 8601 or Unix timestamp string. */
	public readonly string $timestamp;

	/** @var string Sanitized message; no secrets, tokens, or raw payloads. */
	public readonly string $message;

	/** @var string Optional actor context (e.g. user id, role, "cron"). */
	public readonly string $actor_context;

	/** @var string Optional target object (e.g. plan id, job id, "settings"). */
	public readonly string $target_object;

	/** @var string Optional remediation hint (spec §45.6). */
	public readonly string $remediation_hint;

	/** @var string Optional related job/plan/run reference. */
	public readonly string $context_reference;

	/**
	 * Constructs a record. Category and severity must be valid per Log_Categories and Log_Severities.
	 *
	 * @param string $id Record id.
	 * @param string $category Log_Categories constant.
	 * @param string $severity Log_Severities constant.
	 * @param string $message Sanitized message only.
	 * @param string $timestamp Timestamp string.
	 * @param string $actor_context Optional.
	 * @param string $target_object Optional.
	 * @param string $remediation_hint Optional.
	 * @param string $context_reference Optional job/plan/run reference.
	 * @throws \InvalidArgumentException When category or severity is invalid.
	 */
	public function __construct(
		string $id,
		string $category,
		string $severity,
		string $message,
		string $timestamp = '',
		string $actor_context = '',
		string $target_object = '',
		string $remediation_hint = '',
		string $context_reference = ''
	) {
		if ( ! Log_Categories::isValid( $category ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message; category is validated.
			throw new \InvalidArgumentException( 'Invalid log category: ' . $category );
		}
		if ( ! Log_Severities::isValid( $severity ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message; severity is validated.
			throw new \InvalidArgumentException( 'Invalid log severity: ' . $severity );
		}
		$this->id                = $id;
		$this->category          = $category;
		$this->severity          = $severity;
		$this->timestamp         = $timestamp !== '' ? $timestamp : gmdate( 'c' );
		$this->message           = $message;
		$this->actor_context     = $actor_context;
		$this->target_object     = $target_object;
		$this->remediation_hint  = $remediation_hint;
		$this->context_reference = $context_reference;
	}

	/**
	 * User-facing message: understandable, no technical noise (spec §45.3).
	 *
	 * @return string
	 */
	public function get_user_facing_message(): string {
		return $this->message;
	}

	/**
	 * Admin-facing detail: category, message, remediation; no secrets (spec §45.4).
	 *
	 * @return string
	 */
	public function get_admin_facing_detail(): string {
		$parts = array( '[' . $this->category . '] ' . $this->message );
		if ( $this->remediation_hint !== '' ) {
			$parts[] = $this->remediation_hint;
		}
		if ( $this->target_object !== '' ) {
			$parts[] = 'Target: ' . $this->target_object;
		}
		return implode( ' ', $parts );
	}

	/**
	 * Structured array for export/filtering (spec §45.5). All fields safe for logging.
	 *
	 * @return array<string, string>
	 */
	public function to_array(): array {
		return array(
			'id'                => $this->id,
			'category'          => $this->category,
			'severity'          => $this->severity,
			'timestamp'         => $this->timestamp,
			'message'           => $this->message,
			'actor_context'     => $this->actor_context,
			'target_object'     => $this->target_object,
			'remediation_hint'  => $this->remediation_hint,
			'context_reference' => $this->context_reference,
		);
	}
}
