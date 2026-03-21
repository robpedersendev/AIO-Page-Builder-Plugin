<?php
/**
 * Evaluates whether an error event is eligible for developer outbound reporting (spec §46.6, §46.7).
 *
 * Applies severity threshold logic, trigger rules, and repetition thresholds.
 * Does not send or build payloads; only returns eligible/ineligible and reason.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Errors;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Support\Logging\Log_Severities;

/**
 * Evaluates error events against trigger and severity rules. Returns eligibility and reason.
 */
final class Reporting_Eligibility_Evaluator {

	/** Trigger: severity is critical (report immediately). */
	public const TRIGGER_CRITICAL = 'critical';

	/** Trigger: same error repeated 3+ times within 24 hours. */
	public const TRIGGER_REPEATED_3_24H = 'repeated_3_24h';

	/** Trigger: page replacement action failed after final retry. */
	public const TRIGGER_PAGE_REPLACEMENT_FINAL_FAIL = 'page_replacement_final_fail';

	/** Trigger: Build Plan finalization failed at publish stage. */
	public const TRIGGER_PLAN_FINALIZATION_FAIL = 'plan_finalization_fail';

	/** Trigger: import/restore failed after validation passed. */
	public const TRIGGER_IMPORT_RESTORE_FAIL = 'import_restore_fail';

	/** Trigger: queue dead/stalled more than 15 minutes. */
	public const TRIGGER_QUEUE_DEAD_15MIN = 'queue_dead_15min';

	/** Trigger: migration failed. */
	public const TRIGGER_MIGRATION_FAIL = 'migration_fail';

	/** Minimum repetition count for warning in 24h to trigger report (spec §46.7). */
	private const WARNING_REPETITION_THRESHOLD = 10;

	/** Minimum repetition count for same error in 24h to trigger report (spec §46.6). */
	private const REPEATED_ERROR_THRESHOLD = 3;

	/**
	 * Evaluates eligibility for outbound developer error report.
	 *
	 * @param string $severity One of Log_Severities (info, warning, error, critical).
	 * @param string $category One of Log_Categories (execution, queue, etc.).
	 * @param int    $repetition_count_24h Number of times same/similar error in last 24 hours (0 if unknown).
	 * @param string $trigger_type Optional. One of TRIGGER_* constants; if set and applicable, can make eligible.
	 * @return array{eligible: bool, reason: string}
	 */
	public function evaluate(
		string $severity,
		string $category,
		int $repetition_count_24h = 0,
		string $trigger_type = ''
	): array {
		// * Spec §46.7: critical => report immediately.
		if ( $severity === Log_Severities::CRITICAL ) {
			return array(
				'eligible' => true,
				'reason'   => '',
			);
		}

		// * Spec §46.6: explicit trigger types (regardless of severity when tied to these events).
		$explicit_triggers = array(
			self::TRIGGER_CRITICAL,
			self::TRIGGER_REPEATED_3_24H,
			self::TRIGGER_PAGE_REPLACEMENT_FINAL_FAIL,
			self::TRIGGER_PLAN_FINALIZATION_FAIL,
			self::TRIGGER_IMPORT_RESTORE_FAIL,
			self::TRIGGER_QUEUE_DEAD_15MIN,
			self::TRIGGER_MIGRATION_FAIL,
		);
		if ( $trigger_type !== '' && in_array( $trigger_type, $explicit_triggers, true ) ) {
			if ( $trigger_type === self::TRIGGER_CRITICAL ) {
				return array(
					'eligible' => true,
					'reason'   => '',
				);
			}
			if ( $trigger_type === self::TRIGGER_REPEATED_3_24H && $repetition_count_24h >= self::REPEATED_ERROR_THRESHOLD ) {
				return array(
					'eligible' => true,
					'reason'   => '',
				);
			}
			// * Other trigger types (plan fail, import fail, queue dead, migration fail) => eligible.
			if ( in_array( $trigger_type, array( self::TRIGGER_PAGE_REPLACEMENT_FINAL_FAIL, self::TRIGGER_PLAN_FINALIZATION_FAIL, self::TRIGGER_IMPORT_RESTORE_FAIL, self::TRIGGER_QUEUE_DEAD_15MIN, self::TRIGGER_MIGRATION_FAIL ), true ) ) {
				return array(
					'eligible' => true,
					'reason'   => '',
				);
			}
		}

		// * Spec §46.6: same error repeats 3+ times within 24 hours (even without explicit trigger_type).
		// * Spec §46.7: info and warning use stricter local-only rules; warning repetition threshold is handled below.
		if ( $repetition_count_24h >= self::REPEATED_ERROR_THRESHOLD
			&& $severity !== Log_Severities::INFO
			&& $severity !== Log_Severities::WARNING
		) {
			return array(
				'eligible' => true,
				'reason'   => '',
			);
		}

		// * Spec §46.7: info => local log only.
		if ( $severity === Log_Severities::INFO ) {
			return array(
				'eligible' => false,
				'reason'   => 'local log only (info)',
			);
		}

		// * Spec §46.7: warning => local log only unless 10+ in 24 hours.
		if ( $severity === Log_Severities::WARNING ) {
			if ( $repetition_count_24h >= self::WARNING_REPETITION_THRESHOLD ) {
				return array(
					'eligible' => true,
					'reason'   => '',
				);
			}
			return array(
				'eligible' => false,
				'reason'   => 'local log only (warning below repetition threshold)',
			);
		}

		// * Spec §46.7: error => report if tied to plan execution, restore, or queue failure.
		if ( $severity === Log_Severities::ERROR ) {
			$plan_restore_queue_triggers = array(
				self::TRIGGER_PLAN_FINALIZATION_FAIL,
				self::TRIGGER_IMPORT_RESTORE_FAIL,
				self::TRIGGER_QUEUE_DEAD_15MIN,
				self::TRIGGER_PAGE_REPLACEMENT_FINAL_FAIL,
			);
			if ( $trigger_type !== '' && in_array( $trigger_type, $plan_restore_queue_triggers, true ) ) {
				return array(
					'eligible' => true,
					'reason'   => '',
				);
			}
			$queue_or_execution = ( $category === 'queue' || $category === 'execution' );
			if ( $queue_or_execution && in_array( $trigger_type, $plan_restore_queue_triggers, true ) ) {
				return array(
					'eligible' => true,
					'reason'   => '',
				);
			}
			return array(
				'eligible' => false,
				'reason'   => 'error severity requires plan/restore/queue trigger',
			);
		}

		return array(
			'eligible' => false,
			'reason'   => 'severity below threshold',
		);
	}
}
