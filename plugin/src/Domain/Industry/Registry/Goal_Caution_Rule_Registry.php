<?php
/**
 * Registry for conversion-goal caution rules (conversion-goal-caution-rule-schema.md, Prompt 510).
 * Read-only after load; supports get(goal_rule_key), get_for_goal(goal_key), get_all().
 * Rules are advisory only; composition with industry and subtype rules per conversion-goal-caution-rule-contract.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Goal caution rule registry. Read-only after load. Invalid entries skipped.
 */
final class Goal_Caution_Rule_Registry {

	/** Rule object: goal rule key. */
	public const FIELD_GOAL_RULE_KEY = 'goal_rule_key';

	/** Rule object: conversion goal key. */
	public const FIELD_GOAL_KEY = 'goal_key';

	/** Rule object: severity. */
	public const FIELD_SEVERITY = 'severity';

	/** Rule object: caution summary. */
	public const FIELD_CAUTION_SUMMARY = 'caution_summary';

	/** Rule object: guidance text. */
	public const FIELD_GUIDANCE_TEXT = 'guidance_text';

	/** Rule object: optional fragment key for appending to caution_summary at display (Prompt 514). */
	public const FIELD_GUIDANCE_TEXT_FRAGMENT_REF = 'guidance_text_fragment_ref';

	/** Rule object: status. */
	public const FIELD_STATUS = 'status';

	/** Severity: info. */
	public const SEVERITY_INFO = 'info';

	/** Severity: caution. */
	public const SEVERITY_CAUTION = 'caution';

	/** Severity: warning. */
	public const SEVERITY_WARNING = 'warning';

	/** Status: active rules are used at resolution. */
	public const STATUS_ACTIVE = 'active';

	/** Allowed severity values. */
	private const SEVERITIES = array( self::SEVERITY_INFO, self::SEVERITY_CAUTION, self::SEVERITY_WARNING );

	/** Launch goal set (allowed goal_key values). */
	private const ALLOWED_GOAL_KEYS = array( 'calls', 'bookings', 'estimates', 'consultations', 'valuations', 'lead_capture' );

	/** Pattern for goal_rule_key and goal_key. */
	private const KEY_PATTERN = '#^[a-z0-9_-]+$#';

	/** Max length for keys. */
	private const KEY_MAX_LENGTH = 64;

	/** Max length for caution_summary. */
	private const CAUTION_SUMMARY_MAX_LENGTH = 256;

	/** @var array<string, array<string, mixed>> Map of goal_rule_key => definition. */
	private array $by_key = array();

	/** @var list<array<string, mixed>> All valid rules in load order. */
	private array $all = array();

	/**
	 * Returns built-in goal caution rule definitions from GoalCautionRules/ (Prompt 510).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_definitions(): array {
		$path = __DIR__ . '/GoalCautionRules/goal-caution-rule-definitions.php';
		if ( ! is_readable( $path ) ) {
			return array();
		}
		$loaded = require $path;
		return is_array( $loaded ) ? $loaded : array();
	}

	/**
	 * Loads rule definitions. Skips invalid or duplicate goal_rule_key (first wins). Safe: no throw.
	 *
	 * @param array<int, array<string, mixed>> $rules List of rule objects.
	 * @return void
	 */
	public function load( array $rules ): void {
		$this->by_key = array();
		$this->all    = array();
		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}
			$goal_rule_key = isset( $rule[ self::FIELD_GOAL_RULE_KEY ] ) && is_string( $rule[ self::FIELD_GOAL_RULE_KEY ] )
				? trim( $rule[ self::FIELD_GOAL_RULE_KEY ] )
				: '';
			if ( $goal_rule_key === '' || strlen( $goal_rule_key ) > self::KEY_MAX_LENGTH || ! preg_match( self::KEY_PATTERN, $goal_rule_key ) ) {
				continue;
			}
			$goal_key = isset( $rule[ self::FIELD_GOAL_KEY ] ) && is_string( $rule[ self::FIELD_GOAL_KEY ] )
				? trim( $rule[ self::FIELD_GOAL_KEY ] )
				: '';
			if ( $goal_key === '' || strlen( $goal_key ) > self::KEY_MAX_LENGTH || ! preg_match( self::KEY_PATTERN, $goal_key ) ) {
				continue;
			}
			if ( ! in_array( $goal_key, self::ALLOWED_GOAL_KEYS, true ) ) {
				continue;
			}
			$severity = isset( $rule[ self::FIELD_SEVERITY ] ) && is_string( $rule[ self::FIELD_SEVERITY ] )
				? trim( $rule[ self::FIELD_SEVERITY ] )
				: '';
			if ( ! in_array( $severity, self::SEVERITIES, true ) ) {
				continue;
			}
			$caution_summary = isset( $rule[ self::FIELD_CAUTION_SUMMARY ] ) && is_string( $rule[ self::FIELD_CAUTION_SUMMARY ] )
				? trim( $rule[ self::FIELD_CAUTION_SUMMARY ] )
				: '';
			if ( $caution_summary === '' || strlen( $caution_summary ) > self::CAUTION_SUMMARY_MAX_LENGTH ) {
				continue;
			}
			$status = isset( $rule[ self::FIELD_STATUS ] ) && is_string( $rule[ self::FIELD_STATUS ] )
				? trim( $rule[ self::FIELD_STATUS ] )
				: '';
			if ( $status === '' ) {
				continue;
			}
			if ( isset( $this->by_key[ $goal_rule_key ] ) ) {
				continue;
			}
			$this->by_key[ $goal_rule_key ] = $rule;
			$this->all[]                    = $rule;
		}
	}

	/**
	 * Returns rule definition by goal_rule_key, or null if not found.
	 *
	 * @param string $goal_rule_key Goal rule key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $goal_rule_key ): ?array {
		$key = trim( $goal_rule_key );
		return $this->by_key[ $key ] ?? null;
	}

	/**
	 * Returns all loaded rules.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_all(): array {
		return $this->all;
	}

	/**
	 * Returns rules for the given conversion goal.
	 *
	 * @param string $goal_key Conversion goal key.
	 * @return list<array<string, mixed>>
	 */
	public function get_for_goal( string $goal_key ): array {
		$g   = trim( $goal_key );
		$out = array();
		foreach ( $this->all as $rule ) {
			$gk = isset( $rule[ self::FIELD_GOAL_KEY ] ) && is_string( $rule[ self::FIELD_GOAL_KEY ] )
				? trim( $rule[ self::FIELD_GOAL_KEY ] )
				: '';
			if ( $gk === $g ) {
				$out[] = $rule;
			}
		}
		return $out;
	}
}
