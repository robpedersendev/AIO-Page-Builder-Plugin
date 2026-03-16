<?php
/**
 * Registry for industry compliance and caution rules (industry-compliance-rule-schema.md, Prompt 405).
 * Read-only after load; supports get(rule_key), get_for_industry(industry_key), get_all().
 * Rules are advisory only; no legal advice or enforcement.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Industry compliance/caution rule registry. Read-only after load. Invalid entries skipped.
 */
final class Industry_Compliance_Rule_Registry {

	/** Rule object: rule key. */
	public const FIELD_RULE_KEY = 'rule_key';

	/** Rule object: industry pack key. */
	public const FIELD_INDUSTRY_KEY = 'industry_key';

	/** Rule object: severity. */
	public const FIELD_SEVERITY = 'severity';

	/** Rule object: caution summary. */
	public const FIELD_CAUTION_SUMMARY = 'caution_summary';

	/** Rule object: guidance text. */
	public const FIELD_GUIDANCE_TEXT = 'guidance_text';

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

	/** Pattern for rule_key and industry_key. */
	private const KEY_PATTERN = '#^[a-z0-9_-]+$#';

	/** Max length for keys. */
	private const KEY_MAX_LENGTH = 64;

	/** Max length for caution_summary. */
	private const CAUTION_SUMMARY_MAX_LENGTH = 256;

	/** @var array<string, array<string, mixed>> Map of rule_key => definition. */
	private array $by_key = array();

	/** @var list<array<string, mixed>> All valid rules in load order. */
	private array $all = array();

	/**
	 * Returns built-in compliance rule definitions from ComplianceRules/ (Prompt 405).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_definitions(): array {
		$path = __DIR__ . '/ComplianceRules/compliance-rule-definitions.php';
		if ( ! is_readable( $path ) ) {
			return array();
		}
		$loaded = require $path;
		return is_array( $loaded ) ? $loaded : array();
	}

	/**
	 * Loads rule definitions. Skips invalid or duplicate rule_key (first wins). Safe: no throw.
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
			$rule_key = isset( $rule[ self::FIELD_RULE_KEY ] ) && is_string( $rule[ self::FIELD_RULE_KEY ] )
				? trim( $rule[ self::FIELD_RULE_KEY ] )
				: '';
			if ( $rule_key === '' || strlen( $rule_key ) > self::KEY_MAX_LENGTH || ! preg_match( self::KEY_PATTERN, $rule_key ) ) {
				continue;
			}
			$industry_key = isset( $rule[ self::FIELD_INDUSTRY_KEY ] ) && is_string( $rule[ self::FIELD_INDUSTRY_KEY ] )
				? trim( $rule[ self::FIELD_INDUSTRY_KEY ] )
				: '';
			if ( $industry_key === '' || strlen( $industry_key ) > self::KEY_MAX_LENGTH || ! preg_match( self::KEY_PATTERN, $industry_key ) ) {
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
			if ( isset( $this->by_key[ $rule_key ] ) ) {
				continue;
			}
			$this->by_key[ $rule_key ] = $rule;
			$this->all[]              = $rule;
		}
	}

	/**
	 * Returns rule definition by rule_key, or null if not found.
	 *
	 * @param string $rule_key Rule key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $rule_key ): ?array {
		$key = trim( $rule_key );
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
	 * Returns rules for the given industry (active only when status is used by consumers).
	 *
	 * @param string $industry_key Industry pack key.
	 * @return list<array<string, mixed>>
	 */
	public function get_for_industry( string $industry_key ): array {
		$i = trim( $industry_key );
		$out = array();
		foreach ( $this->all as $rule ) {
			$ik = isset( $rule[ self::FIELD_INDUSTRY_KEY ] ) && is_string( $rule[ self::FIELD_INDUSTRY_KEY ] )
				? trim( $rule[ self::FIELD_INDUSTRY_KEY ] )
				: '';
			if ( $ik === $i ) {
				$out[] = $rule;
			}
		}
		return $out;
	}
}
