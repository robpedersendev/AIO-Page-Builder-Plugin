<?php
/**
 * Registry for subtype-scoped compliance and caution rules (subtype-compliance-rule-schema.md, Prompt 446).
 * Read-only after load; supports get(subtype_rule_key), get_for_subtype(parent_industry_key, subtype_key), get_all().
 * Rules are advisory only; composition with parent rules per subtype-compliance-rule-contract.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Subtype compliance/caution rule registry. Read-only after load. Invalid entries skipped.
 */
final class Subtype_Compliance_Rule_Registry {

	/** Subtype rule object: subtype rule key. */
	public const FIELD_SUBTYPE_RULE_KEY = 'subtype_rule_key';

	/** Subtype rule object: subtype key. */
	public const FIELD_SUBTYPE_KEY = 'subtype_key';

	/** Subtype rule object: parent industry pack key. */
	public const FIELD_PARENT_INDUSTRY_KEY = 'parent_industry_key';

	/** Subtype rule object: severity. */
	public const FIELD_SEVERITY = 'severity';

	/** Subtype rule object: caution summary. */
	public const FIELD_CAUTION_SUMMARY = 'caution_summary';

	/** Subtype rule object: guidance text. */
	public const FIELD_GUIDANCE_TEXT = 'guidance_text';

	/** Subtype rule object: optional parent rule key this refines. */
	public const FIELD_REFINEMENT_OF_RULE_KEY = 'refinement_of_rule_key';

	/** Subtype rule object: optional additive note. */
	public const FIELD_ADDITIVE_NOTE = 'additive_note';

	/** Subtype rule object: status. */
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

	/** Pattern for keys. */
	private const KEY_PATTERN = '#^[a-z0-9_-]+$#';

	/** Max length for keys. */
	private const KEY_MAX_LENGTH = 64;

	/** Max length for caution_summary. */
	private const CAUTION_SUMMARY_MAX_LENGTH = 256;

	/** @var array<string, array<string, mixed>> Map of subtype_rule_key => definition. */
	private array $by_key = array();

	/** @var list<array<string, mixed>> All valid rules in load order. */
	private array $all = array();

	/**
	 * Returns built-in subtype compliance rule definitions from SubtypeComplianceRules/ (Prompt 447).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_definitions(): array {
		$path = __DIR__ . '/SubtypeComplianceRules/subtype-compliance-rule-definitions.php';
		if ( ! is_readable( $path ) ) {
			return array();
		}
		$loaded = require $path;
		return is_array( $loaded ) ? $loaded : array();
	}

	/**
	 * Loads subtype rule definitions. Skips invalid or duplicate subtype_rule_key (first wins). Safe: no throw.
	 *
	 * @param array<int, array<string, mixed>> $rules List of subtype rule objects.
	 * @return void
	 */
	public function load( array $rules ): void {
		$this->by_key = array();
		$this->all    = array();
		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}
			$subtype_rule_key = isset( $rule[ self::FIELD_SUBTYPE_RULE_KEY ] ) && is_string( $rule[ self::FIELD_SUBTYPE_RULE_KEY ] )
				? trim( $rule[ self::FIELD_SUBTYPE_RULE_KEY ] )
				: '';
			if ( $subtype_rule_key === '' || strlen( $subtype_rule_key ) > self::KEY_MAX_LENGTH || ! preg_match( self::KEY_PATTERN, $subtype_rule_key ) ) {
				continue;
			}
			$subtype_key = isset( $rule[ self::FIELD_SUBTYPE_KEY ] ) && is_string( $rule[ self::FIELD_SUBTYPE_KEY ] )
				? trim( $rule[ self::FIELD_SUBTYPE_KEY ] )
				: '';
			if ( $subtype_key === '' || strlen( $subtype_key ) > self::KEY_MAX_LENGTH || ! preg_match( self::KEY_PATTERN, $subtype_key ) ) {
				continue;
			}
			$parent_industry_key = isset( $rule[ self::FIELD_PARENT_INDUSTRY_KEY ] ) && is_string( $rule[ self::FIELD_PARENT_INDUSTRY_KEY ] )
				? trim( $rule[ self::FIELD_PARENT_INDUSTRY_KEY ] )
				: '';
			if ( $parent_industry_key === '' || strlen( $parent_industry_key ) > self::KEY_MAX_LENGTH || ! preg_match( self::KEY_PATTERN, $parent_industry_key ) ) {
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
			if ( isset( $this->by_key[ $subtype_rule_key ] ) ) {
				continue;
			}
			$this->by_key[ $subtype_rule_key ] = $rule;
			$this->all[]                       = $rule;
		}
	}

	/**
	 * Returns subtype rule definition by subtype_rule_key, or null if not found.
	 *
	 * @param string $subtype_rule_key Subtype rule key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $subtype_rule_key ): ?array {
		$key = trim( $subtype_rule_key );
		return $this->by_key[ $key ] ?? null;
	}

	/**
	 * Returns all loaded subtype rules.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_all(): array {
		return $this->all;
	}

	/**
	 * Returns subtype rules for the given parent industry and subtype (active only when status is used by consumers).
	 *
	 * @param string $parent_industry_key Parent industry pack key.
	 * @param string $subtype_key         Subtype key.
	 * @return list<array<string, mixed>>
	 */
	public function get_for_subtype( string $parent_industry_key, string $subtype_key ): array {
		$parent = trim( $parent_industry_key );
		$sub    = trim( $subtype_key );
		if ( $parent === '' || $sub === '' ) {
			return array();
		}
		$out = array();
		foreach ( $this->all as $rule ) {
			$p = isset( $rule[ self::FIELD_PARENT_INDUSTRY_KEY ] ) && is_string( $rule[ self::FIELD_PARENT_INDUSTRY_KEY ] )
				? trim( $rule[ self::FIELD_PARENT_INDUSTRY_KEY ] )
				: '';
			$s = isset( $rule[ self::FIELD_SUBTYPE_KEY ] ) && is_string( $rule[ self::FIELD_SUBTYPE_KEY ] )
				? trim( $rule[ self::FIELD_SUBTYPE_KEY ] )
				: '';
			if ( $p === $parent && $s === $sub ) {
				$out[] = $rule;
			}
		}
		return $out;
	}
}
