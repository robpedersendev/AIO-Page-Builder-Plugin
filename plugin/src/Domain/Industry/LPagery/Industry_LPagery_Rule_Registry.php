<?php
/**
 * Read-only registry for industry LPagery rules (industry-lpagery-rule-schema.md).
 * Advisory only; no mutation of LPagery field generation or binding logic.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\LPagery;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of industry LPagery rule objects. Read-only after load. Invalid entries skipped.
 */
final class Industry_LPagery_Rule_Registry {

	public const FIELD_LPAGERY_RULE_KEY    = 'lpagery_rule_key';
	public const FIELD_INDUSTRY_KEY        = 'industry_key';
	public const FIELD_VERSION_MARKER      = 'version_marker';
	public const FIELD_STATUS              = 'status';
	public const FIELD_LPAGERY_POSTURE     = 'lpagery_posture';
	public const FIELD_REQUIRED_TOKEN_REFS = 'required_token_refs';
	public const FIELD_OPTIONAL_TOKEN_REFS = 'optional_token_refs';
	public const FIELD_HIERARCHY_GUIDANCE  = 'hierarchy_guidance';
	public const FIELD_WEAK_PAGE_WARNINGS  = 'weak_page_warnings';
	public const FIELD_NOTES               = 'notes';
	public const FIELD_METADATA            = 'metadata';

	public const POSTURE_CENTRAL     = 'central';
	public const POSTURE_OPTIONAL    = 'optional';
	public const POSTURE_DISCOURAGED = 'discouraged';

	public const STATUS_ACTIVE     = 'active';
	public const STATUS_DRAFT      = 'draft';
	public const STATUS_DEPRECATED = 'deprecated';

	public const SUPPORTED_SCHEMA_VERSION = '1';
	private const KEY_PATTERN             = '#^[a-z0-9_-]+$#';
	private const KEY_MAX_LENGTH          = 64;

	/** @var array<string, array<string, mixed>> Map of lpagery_rule_key => rule. */
	private array $by_key = array();

	/** @var array<int, array<string, mixed>> All valid rules in load order. */
	private array $all = array();

	/**
	 * Returns built-in LPagery rule definitions from Rules/ (Prompt 360).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_definitions(): array {
		$path = __DIR__ . '/Rules/lpagery-rule-definitions.php';
		if ( ! is_readable( $path ) ) {
			return array();
		}
		$loaded = require $path;
		return is_array( $loaded ) ? $loaded : array();
	}

	/**
	 * Loads LPagery rule definitions. Skips invalid or duplicate keys (first wins). Safe: no throw.
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
			$errors = $this->validate_rule( $rule );
			if ( $errors !== array() ) {
				continue;
			}
			$key = trim( (string) ( $rule[ self::FIELD_LPAGERY_RULE_KEY ] ?? '' ) );
			if ( $key !== '' && ! isset( $this->by_key[ $key ] ) ) {
				$this->by_key[ $key ] = $this->normalize_rule( $rule );
				$this->all[]          = $this->by_key[ $key ];
			}
		}
	}

	/**
	 * Returns rule by lpagery_rule_key, or null if not found.
	 *
	 * @param string $lpagery_rule_key Rule key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $lpagery_rule_key ): ?array {
		$key = trim( $lpagery_rule_key );
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
	 * Returns rules for the given industry_key. Empty array if none.
	 *
	 * @param string $industry_key Industry pack key.
	 * @return array<int, array<string, mixed>>
	 */
	public function list_by_industry( string $industry_key ): array {
		$want = trim( $industry_key );
		$out  = array();
		foreach ( $this->all as $rule ) {
			$ik = isset( $rule[ self::FIELD_INDUSTRY_KEY ] ) && is_string( $rule[ self::FIELD_INDUSTRY_KEY ] )
				? trim( $rule[ self::FIELD_INDUSTRY_KEY ] )
				: '';
			if ( $ik === $want ) {
				$out[] = $rule;
			}
		}
		return $out;
	}

	/**
	 * Returns rules with the given status. Empty array if none.
	 *
	 * @param string $status One of STATUS_ACTIVE, STATUS_DRAFT, STATUS_DEPRECATED.
	 * @return array<int, array<string, mixed>>
	 */
	public function list_by_status( string $status ): array {
		$out = array();
		foreach ( $this->all as $rule ) {
			$s = isset( $rule[ self::FIELD_STATUS ] ) && is_string( $rule[ self::FIELD_STATUS ] )
				? $rule[ self::FIELD_STATUS ]
				: '';
			if ( $s === $status ) {
				$out[] = $rule;
			}
		}
		return $out;
	}

	/**
	 * Validates a single rule. Returns list of error codes; empty when valid.
	 *
	 * @param array<string, mixed> $rule Raw rule.
	 * @return array<int, string>
	 */
	public function validate_rule( array $rule ): array {
		$errors = array();
		$key    = isset( $rule[ self::FIELD_LPAGERY_RULE_KEY ] ) && is_string( $rule[ self::FIELD_LPAGERY_RULE_KEY ] )
			? trim( $rule[ self::FIELD_LPAGERY_RULE_KEY ] )
			: '';
		if ( $key === '' ) {
			$errors[] = 'missing_lpagery_rule_key';
		} elseif ( strlen( $key ) > self::KEY_MAX_LENGTH || ! preg_match( self::KEY_PATTERN, $key ) ) {
			$errors[] = 'invalid_lpagery_rule_key';
		}
		$industry = isset( $rule[ self::FIELD_INDUSTRY_KEY ] ) && is_string( $rule[ self::FIELD_INDUSTRY_KEY ] )
			? trim( $rule[ self::FIELD_INDUSTRY_KEY ] )
			: '';
		if ( $industry === '' ) {
			$errors[] = 'missing_industry_key';
		} elseif ( strlen( $industry ) > self::KEY_MAX_LENGTH || ! preg_match( self::KEY_PATTERN, $industry ) ) {
			$errors[] = 'invalid_industry_key';
		}
		$version = isset( $rule[ self::FIELD_VERSION_MARKER ] ) && is_string( $rule[ self::FIELD_VERSION_MARKER ] )
			? trim( $rule[ self::FIELD_VERSION_MARKER ] )
			: '';
		if ( $version !== self::SUPPORTED_SCHEMA_VERSION ) {
			$errors[] = 'unsupported_version';
		}
		$status = isset( $rule[ self::FIELD_STATUS ] ) && is_string( $rule[ self::FIELD_STATUS ] )
			? $rule[ self::FIELD_STATUS ]
			: '';
		if ( ! in_array( $status, array( self::STATUS_ACTIVE, self::STATUS_DRAFT, self::STATUS_DEPRECATED ), true ) ) {
			$errors[] = 'invalid_status';
		}
		$posture = isset( $rule[ self::FIELD_LPAGERY_POSTURE ] ) && is_string( $rule[ self::FIELD_LPAGERY_POSTURE ] )
			? $rule[ self::FIELD_LPAGERY_POSTURE ]
			: '';
		if ( ! in_array( $posture, array( self::POSTURE_CENTRAL, self::POSTURE_OPTIONAL, self::POSTURE_DISCOURAGED ), true ) ) {
			$errors[] = 'invalid_lpagery_posture';
		}
		return $errors;
	}

	/**
	 * Normalizes rule for storage. Call only after validate_rule passed.
	 *
	 * @param array<string, mixed> $rule Valid rule.
	 * @return array<string, mixed>
	 */
	private function normalize_rule( array $rule ): array {
		$out      = array(
			self::FIELD_LPAGERY_RULE_KEY => trim( (string) ( $rule[ self::FIELD_LPAGERY_RULE_KEY ] ?? '' ) ),
			self::FIELD_INDUSTRY_KEY     => trim( (string) ( $rule[ self::FIELD_INDUSTRY_KEY ] ?? '' ) ),
			self::FIELD_VERSION_MARKER   => trim( (string) ( $rule[ self::FIELD_VERSION_MARKER ] ?? '' ) ),
			self::FIELD_STATUS           => (string) ( $rule[ self::FIELD_STATUS ] ?? '' ),
			self::FIELD_LPAGERY_POSTURE  => (string) ( $rule[ self::FIELD_LPAGERY_POSTURE ] ?? '' ),
		);
		$optional = array(
			self::FIELD_REQUIRED_TOKEN_REFS,
			self::FIELD_OPTIONAL_TOKEN_REFS,
			self::FIELD_HIERARCHY_GUIDANCE,
			self::FIELD_WEAK_PAGE_WARNINGS,
			self::FIELD_NOTES,
			self::FIELD_METADATA,
		);
		foreach ( $optional as $field ) {
			if ( array_key_exists( $field, $rule ) ) {
				$val = $rule[ $field ];
				if ( in_array( $field, array( self::FIELD_REQUIRED_TOKEN_REFS, self::FIELD_OPTIONAL_TOKEN_REFS ), true ) && is_array( $val ) ) {
					$out[ $field ] = array_values(
						array_filter(
							array_map(
								function ( $v ) {
									return is_string( $v ) ? trim( $v ) : '';
								},
								$val
							)
						)
					);
				} else {
					$out[ $field ] = $val;
				}
			}
		}
		return $out;
	}
}
