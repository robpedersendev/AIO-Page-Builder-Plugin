<?php
/**
 * Read-only registry for industry SEO and entity-guidance rules (industry-seo-guidance-schema.md).
 * Advisory only; no mutation of third-party SEO plugins.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of industry SEO guidance rule objects. Read-only after load. Invalid entries skipped.
 */
final class Industry_SEO_Guidance_Registry {

	public const FIELD_GUIDANCE_RULE_KEY   = 'guidance_rule_key';
	public const FIELD_INDUSTRY_KEY         = 'industry_key';
	public const FIELD_VERSION_MARKER       = 'version_marker';
	public const FIELD_STATUS               = 'status';
	public const FIELD_PAGE_FAMILY          = 'page_family';
	public const FIELD_TITLE_PATTERNS       = 'title_patterns';
	public const FIELD_H1_PATTERNS          = 'h1_patterns';
	public const FIELD_INTERNAL_LINK_GUIDANCE = 'internal_link_guidance';
	public const FIELD_LOCAL_SEO_POSTURE    = 'local_seo_posture';
	public const FIELD_FAQ_EMPHASIS         = 'faq_emphasis';
	public const FIELD_REVIEW_EMPHASIS      = 'review_emphasis';
	public const FIELD_ENTITY_CAUTIONS      = 'entity_cautions';
	public const FIELD_METADATA             = 'metadata';

	public const STATUS_ACTIVE    = 'active';
	public const STATUS_DRAFT     = 'draft';
	public const STATUS_DEPRECATED = 'deprecated';

	public const SUPPORTED_SCHEMA_VERSION = '1';
	private const KEY_PATTERN = '#^[a-z0-9_-]+$#';
	private const KEY_MAX_LENGTH = 64;

	/** @var array<string, array<string, mixed>> Map of guidance_rule_key => rule. */
	private array $by_key = array();

	/** @var list<array<string, mixed>> All valid rules in load order. */
	private array $all = array();

	/**
	 * Loads guidance rule definitions. Skips invalid or duplicate keys (first wins). Safe: no throw.
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
			$key = trim( (string) ( $rule[ self::FIELD_GUIDANCE_RULE_KEY ] ?? '' ) );
			if ( $key !== '' && ! isset( $this->by_key[ $key ] ) ) {
				$this->by_key[ $key ] = $this->normalize_rule( $rule );
				$this->all[]         = $this->by_key[ $key ];
			}
		}
	}

	/**
	 * Returns rule by guidance_rule_key, or null if not found.
	 *
	 * @param string $guidance_rule_key Rule key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $guidance_rule_key ): ?array {
		$key = trim( $guidance_rule_key );
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
	 * Returns rules for the given industry_key. Empty array if none.
	 *
	 * @param string $industry_key Industry pack key.
	 * @return list<array<string, mixed>>
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
	 * @return list<array<string, mixed>>
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
	 * @return list<string>
	 */
	public function validate_rule( array $rule ): array {
		$errors = array();
		$key = isset( $rule[ self::FIELD_GUIDANCE_RULE_KEY ] ) && is_string( $rule[ self::FIELD_GUIDANCE_RULE_KEY ] )
			? trim( $rule[ self::FIELD_GUIDANCE_RULE_KEY ] )
			: '';
		if ( $key === '' ) {
			$errors[] = 'missing_guidance_rule_key';
		} elseif ( strlen( $key ) > self::KEY_MAX_LENGTH || ! preg_match( self::KEY_PATTERN, $key ) ) {
			$errors[] = 'invalid_guidance_rule_key';
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
		return $errors;
	}

	/**
	 * Normalizes rule for storage. Call only after validate_rule passed.
	 *
	 * @param array<string, mixed> $rule Valid rule.
	 * @return array<string, mixed>
	 */
	private function normalize_rule( array $rule ): array {
		$out = array(
			self::FIELD_GUIDANCE_RULE_KEY => trim( (string) ( $rule[ self::FIELD_GUIDANCE_RULE_KEY ] ?? '' ) ),
			self::FIELD_INDUSTRY_KEY      => trim( (string) ( $rule[ self::FIELD_INDUSTRY_KEY ] ?? '' ) ),
			self::FIELD_VERSION_MARKER    => trim( (string) ( $rule[ self::FIELD_VERSION_MARKER ] ?? '' ) ),
			self::FIELD_STATUS             => (string) ( $rule[ self::FIELD_STATUS ] ?? '' ),
		);
		$optional = array(
			self::FIELD_PAGE_FAMILY,
			self::FIELD_TITLE_PATTERNS,
			self::FIELD_H1_PATTERNS,
			self::FIELD_INTERNAL_LINK_GUIDANCE,
			self::FIELD_LOCAL_SEO_POSTURE,
			self::FIELD_FAQ_EMPHASIS,
			self::FIELD_REVIEW_EMPHASIS,
			self::FIELD_ENTITY_CAUTIONS,
			self::FIELD_METADATA,
		);
		foreach ( $optional as $field ) {
			if ( array_key_exists( $field, $rule ) ) {
				$out[ $field ] = $rule[ $field ];
			}
		}
		return $out;
	}
}
