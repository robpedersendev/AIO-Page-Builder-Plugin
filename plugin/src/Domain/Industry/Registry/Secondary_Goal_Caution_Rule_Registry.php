<?php
/**
 * Registry for secondary-goal caution rules (secondary-goal-caution-rule-schema.md, Prompt 547).
 * Read-only after load; get(secondary_goal_rule_key), get_for_primary_secondary(primary_goal_key, secondary_goal_key), get_all().
 * Rules are advisory only; composition with industry, subtype, and primary-goal rules per secondary-goal-caution-rule-contract.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Secondary-goal caution rule registry. Read-only after load. Invalid entries skipped.
 */
final class Secondary_Goal_Caution_Rule_Registry {

	public const FIELD_SECONDARY_GOAL_RULE_KEY = 'secondary_goal_rule_key';
	public const FIELD_PRIMARY_GOAL_KEY        = 'primary_goal_key';
	public const FIELD_SECONDARY_GOAL_KEY      = 'secondary_goal_key';
	public const FIELD_SEVERITY                = 'severity';
	public const FIELD_CAUTION_SUMMARY         = 'caution_summary';
	public const FIELD_GUIDANCE_TEXT           = 'guidance_text';
	public const FIELD_STATUS                  = 'status';

	public const SEVERITY_INFO    = 'info';
	public const SEVERITY_CAUTION = 'caution';
	public const SEVERITY_WARNING = 'warning';
	public const STATUS_ACTIVE    = 'active';

	private const SEVERITIES                 = array( self::SEVERITY_INFO, self::SEVERITY_CAUTION, self::SEVERITY_WARNING );
	private const ALLOWED_GOAL_KEYS          = array( 'calls', 'bookings', 'estimates', 'consultations', 'valuations', 'lead_capture' );
	private const KEY_PATTERN                = '#^[a-z0-9_-]+$#';
	private const KEY_MAX_LENGTH             = 64;
	private const CAUTION_SUMMARY_MAX_LENGTH = 256;

	/** @var array<string, array<string, mixed>> Map of secondary_goal_rule_key => definition. */
	private array $by_key = array();

	/** @var array<int, array<string, mixed>> All valid rules in load order. */
	private array $all = array();

	/**
	 * Returns built-in secondary-goal caution rule definitions (Prompt 548).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_definitions(): array {
		$path = __DIR__ . '/SecondaryGoalCautionRules/secondary-goal-caution-rule-definitions.php';
		if ( ! \is_readable( $path ) ) {
			return array();
		}
		$loaded = require $path;
		return \is_array( $loaded ) ? $loaded : array();
	}

	/**
	 * Loads rule definitions. Skips invalid or duplicate secondary_goal_rule_key (first wins). Safe: no throw.
	 *
	 * @param array<int, array<string, mixed>> $rules List of rule objects.
	 * @return void
	 */
	public function load( array $rules ): void {
		$this->by_key = array();
		$this->all    = array();
		foreach ( $rules as $rule ) {
			if ( ! \is_array( $rule ) ) {
				continue;
			}
			$rule_key = isset( $rule[ self::FIELD_SECONDARY_GOAL_RULE_KEY ] ) && \is_string( $rule[ self::FIELD_SECONDARY_GOAL_RULE_KEY ] )
				? \trim( $rule[ self::FIELD_SECONDARY_GOAL_RULE_KEY ] )
				: '';
			if ( $rule_key === '' || \strlen( $rule_key ) > self::KEY_MAX_LENGTH || ! \preg_match( self::KEY_PATTERN, $rule_key ) ) {
				continue;
			}
			$primary   = isset( $rule[ self::FIELD_PRIMARY_GOAL_KEY ] ) && \is_string( $rule[ self::FIELD_PRIMARY_GOAL_KEY ] )
				? \trim( $rule[ self::FIELD_PRIMARY_GOAL_KEY ] )
				: '';
			$secondary = isset( $rule[ self::FIELD_SECONDARY_GOAL_KEY ] ) && \is_string( $rule[ self::FIELD_SECONDARY_GOAL_KEY ] )
				? \trim( $rule[ self::FIELD_SECONDARY_GOAL_KEY ] )
				: '';
			if ( $primary === '' || $secondary === '' || $primary === $secondary ) {
				continue;
			}
			if ( \strlen( $primary ) > self::KEY_MAX_LENGTH || ! \preg_match( self::KEY_PATTERN, $primary ) ) {
				continue;
			}
			if ( \strlen( $secondary ) > self::KEY_MAX_LENGTH || ! \preg_match( self::KEY_PATTERN, $secondary ) ) {
				continue;
			}
			if ( ! \in_array( $primary, self::ALLOWED_GOAL_KEYS, true ) || ! \in_array( $secondary, self::ALLOWED_GOAL_KEYS, true ) ) {
				continue;
			}
			$severity = isset( $rule[ self::FIELD_SEVERITY ] ) && \is_string( $rule[ self::FIELD_SEVERITY ] )
				? \trim( $rule[ self::FIELD_SEVERITY ] )
				: '';
			if ( ! \in_array( $severity, self::SEVERITIES, true ) ) {
				continue;
			}
			$caution_summary = isset( $rule[ self::FIELD_CAUTION_SUMMARY ] ) && \is_string( $rule[ self::FIELD_CAUTION_SUMMARY ] )
				? \trim( $rule[ self::FIELD_CAUTION_SUMMARY ] )
				: '';
			if ( $caution_summary === '' || \strlen( $caution_summary ) > self::CAUTION_SUMMARY_MAX_LENGTH ) {
				continue;
			}
			$status = isset( $rule[ self::FIELD_STATUS ] ) && \is_string( $rule[ self::FIELD_STATUS ] )
				? \trim( $rule[ self::FIELD_STATUS ] )
				: '';
			if ( $status === '' ) {
				continue;
			}
			if ( isset( $this->by_key[ $rule_key ] ) ) {
				continue;
			}
			$this->by_key[ $rule_key ] = $rule;
			$this->all[]               = $rule;
		}
	}

	/**
	 * Returns rule definition by secondary_goal_rule_key, or null if not found.
	 *
	 * @param string $secondary_goal_rule_key Rule key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $secondary_goal_rule_key ): ?array {
		$key = \trim( $secondary_goal_rule_key );
		return $this->by_key[ $key ] ?? null;
	}

	/**
	 * Returns all loaded rules.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all(): array {
		return $this->all;
	}

	/**
	 * Returns rules for the given (primary_goal, secondary_goal) pair.
	 *
	 * @param string $primary_goal_key   Primary conversion goal key.
	 * @param string $secondary_goal_key Secondary conversion goal key.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_for_primary_secondary( string $primary_goal_key, string $secondary_goal_key ): array {
		$p = \trim( $primary_goal_key );
		$s = \trim( $secondary_goal_key );
		if ( $p === '' || $s === '' || $p === $s ) {
			return array();
		}
		$out = array();
		foreach ( $this->all as $rule ) {
			$pk = isset( $rule[ self::FIELD_PRIMARY_GOAL_KEY ] ) && \is_string( $rule[ self::FIELD_PRIMARY_GOAL_KEY ] )
				? \trim( $rule[ self::FIELD_PRIMARY_GOAL_KEY ] )
				: '';
			$sk = isset( $rule[ self::FIELD_SECONDARY_GOAL_KEY ] ) && \is_string( $rule[ self::FIELD_SECONDARY_GOAL_KEY ] )
				? \trim( $rule[ self::FIELD_SECONDARY_GOAL_KEY ] )
				: '';
			if ( $pk === $p && $sk === $s ) {
				$out[] = $rule;
			}
		}
		return $out;
	}
}
