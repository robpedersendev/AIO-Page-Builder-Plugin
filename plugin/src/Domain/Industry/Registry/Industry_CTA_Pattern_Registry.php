<?php
/**
 * Registry of CTA patterns for industry packs (industry-cta-pattern-contract.md).
 * Read-only after load; supports get(pattern_key) and get_all().
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * CTA pattern registry. Deterministic, read-only after load. Invalid or duplicate entries are skipped.
 */
final class Industry_CTA_Pattern_Registry {

	/** Pattern key in definition array. */
	public const FIELD_PATTERN_KEY = 'pattern_key';

	/** Human-readable name. */
	public const FIELD_NAME = 'name';

	/** Optional description. */
	public const FIELD_DESCRIPTION = 'description';

	/** Optional urgency framing notes. */
	public const FIELD_URGENCY_NOTES = 'urgency_notes';

	/** Optional trust notes. */
	public const FIELD_TRUST_NOTES = 'trust_notes';

	/** Optional action framing. */
	public const FIELD_ACTION_FRAMING = 'action_framing';

	/** Pattern for pattern_key (alphanumeric and underscore). */
	public const PATTERN_KEY_REGEX = '#^[a-z0-9_]+$#';

	/** Max length for pattern_key. */
	public const PATTERN_KEY_MAX_LENGTH = 64;

	/** @var array<string, array<string, mixed>> Map of pattern_key => definition. */
	private array $by_key = array();

	/** @var list<array<string, mixed>> All valid patterns in load order. */
	private array $all = array();

	/**
	 * Returns built-in CTA pattern definitions from CTAPatterns/ (Prompt 358).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_definitions(): array {
		$path = __DIR__ . '/CTAPatterns/cta-pattern-definitions.php';
		if ( ! is_readable( $path ) ) {
			return array();
		}
		$loaded = require $path;
		return is_array( $loaded ) ? $loaded : array();
	}

	/**
	 * Loads CTA pattern definitions. Skips invalid or duplicate keys (first wins). Safe: no throw.
	 *
	 * @param array<int, array<string, mixed>> $definitions List of pattern definitions.
	 * @return void
	 */
	public function load( array $definitions ): void {
		$this->by_key = array();
		$this->all    = array();
		foreach ( $definitions as $def ) {
			if ( ! is_array( $def ) ) {
				continue;
			}
			$key = isset( $def[ self::FIELD_PATTERN_KEY ] ) && is_string( $def[ self::FIELD_PATTERN_KEY ] )
				? trim( $def[ self::FIELD_PATTERN_KEY ] )
				: '';
			if ( $key === '' || strlen( $key ) > self::PATTERN_KEY_MAX_LENGTH || ! preg_match( self::PATTERN_KEY_REGEX, $key ) ) {
				continue;
			}
			$name = isset( $def[ self::FIELD_NAME ] ) && is_string( $def[ self::FIELD_NAME ] )
				? trim( $def[ self::FIELD_NAME ] )
				: '';
			if ( $name === '' ) {
				continue;
			}
			if ( isset( $this->by_key[ $key ] ) ) {
				continue;
			}
			$normalized = array(
				self::FIELD_PATTERN_KEY   => $key,
				self::FIELD_NAME          => $name,
				self::FIELD_DESCRIPTION   => isset( $def[ self::FIELD_DESCRIPTION ] ) && is_string( $def[ self::FIELD_DESCRIPTION ] ) ? trim( $def[ self::FIELD_DESCRIPTION ] ) : '',
				self::FIELD_URGENCY_NOTES => isset( $def[ self::FIELD_URGENCY_NOTES ] ) && is_string( $def[ self::FIELD_URGENCY_NOTES ] ) ? trim( $def[ self::FIELD_URGENCY_NOTES ] ) : '',
				self::FIELD_TRUST_NOTES   => isset( $def[ self::FIELD_TRUST_NOTES ] ) && is_string( $def[ self::FIELD_TRUST_NOTES ] ) ? trim( $def[ self::FIELD_TRUST_NOTES ] ) : '',
				self::FIELD_ACTION_FRAMING => isset( $def[ self::FIELD_ACTION_FRAMING ] ) && is_string( $def[ self::FIELD_ACTION_FRAMING ] ) ? trim( $def[ self::FIELD_ACTION_FRAMING ] ) : '',
			);
			$this->by_key[ $key ] = $normalized;
			$this->all[]          = $normalized;
		}
	}

	/**
	 * Returns pattern definition by pattern_key, or null if not found.
	 *
	 * @param string $pattern_key CTA pattern key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $pattern_key ): ?array {
		$key = trim( $pattern_key );
		return $this->by_key[ $key ] ?? null;
	}

	/**
	 * Returns all loaded patterns.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_all(): array {
		return $this->all;
	}

	/**
	 * Returns whether the given pattern_key is registered.
	 */
	public function has( string $pattern_key ): bool {
		return $this->get( $pattern_key ) !== null;
	}

	/**
	 * Resolves a list of pattern keys to definitions; unknown keys are skipped. Deterministic order.
	 *
	 * @param list<string> $pattern_keys List of pattern keys (e.g. from industry pack preferred_cta_patterns).
	 * @return list<array<string, mixed>>
	 */
	public function resolve_keys( array $pattern_keys ): array {
		$out = array();
		foreach ( $pattern_keys as $k ) {
			if ( ! is_string( $k ) ) {
				continue;
			}
			$def = $this->get( trim( $k ) );
			if ( $def !== null ) {
				$out[] = $def;
			}
		}
		return $out;
	}
}
