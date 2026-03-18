<?php
/**
 * Registry for shared cross-industry fragments (Prompt 475, industry-shared-fragment-schema.md).
 * Read-only after load; get(fragment_key), get_all(), get_by_type(fragment_type).
 * Invalid entries skipped at load.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Shared fragment registry. Bounded, typed; no public mutation.
 */
final class Industry_Shared_Fragment_Registry {

	/** Fragment object: fragment key. */
	public const FIELD_FRAGMENT_KEY = 'fragment_key';

	/** Fragment object: fragment type. */
	public const FIELD_FRAGMENT_TYPE = 'fragment_type';

	/** Fragment object: allowed consumers. */
	public const FIELD_ALLOWED_CONSUMERS = 'allowed_consumers';

	/** Fragment object: content. */
	public const FIELD_CONTENT = 'content';

	/** Fragment object: status. */
	public const FIELD_STATUS = 'status';

	/** Fragment object: version marker. */
	public const FIELD_VERSION_MARKER = 'version_marker';

	/** Status: active fragments are used at resolution. */
	public const STATUS_ACTIVE = 'active';

	/** Fragment types (per schema). */
	public const TYPE_CTA_NOTES       = 'cta_notes';
	public const TYPE_SEO_SEGMENT     = 'seo_segment';
	public const TYPE_CAUTION_SNIPPET = 'caution_snippet';
	public const TYPE_HELPER_GUIDANCE = 'helper_guidance';
	public const TYPE_PAGE_GUIDANCE   = 'page_guidance';

	/** Allowed fragment types. */
	private const TYPES = array(
		self::TYPE_CTA_NOTES,
		self::TYPE_SEO_SEGMENT,
		self::TYPE_CAUTION_SNIPPET,
		self::TYPE_HELPER_GUIDANCE,
		self::TYPE_PAGE_GUIDANCE,
	);

	/** Key pattern. */
	private const KEY_PATTERN = '#^[a-z0-9_-]+$#';

	/** Max lengths. */
	private const KEY_MAX_LENGTH     = 64;
	private const CONTENT_MAX_LENGTH = 2048;
	private const VERSION_MAX_LENGTH = 32;

	/** @var array<string, array<string, mixed>> */
	private array $by_key = array();

	/** @var list<array<string, mixed>> */
	private array $all = array();

	/**
	 * Returns built-in fragment definitions (from SharedFragments/ when present).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_definitions(): array {
		$path = __DIR__ . '/SharedFragments/builtin-fragments.php';
		if ( ! is_readable( $path ) ) {
			return array();
		}
		$loaded = require $path;
		return is_array( $loaded ) ? $loaded : array();
	}

	/**
	 * Loads fragment definitions. Skips invalid or duplicate fragment_key (first wins).
	 *
	 * @param array<int, array<string, mixed>> $fragments List of fragment objects.
	 * @return void
	 */
	public function load( array $fragments ): void {
		$this->by_key = array();
		$this->all    = array();
		foreach ( $fragments as $frag ) {
			if ( ! is_array( $frag ) ) {
				continue;
			}
			$key = isset( $frag[ self::FIELD_FRAGMENT_KEY ] ) && is_string( $frag[ self::FIELD_FRAGMENT_KEY ] )
				? trim( $frag[ self::FIELD_FRAGMENT_KEY ] )
				: '';
			if ( $key === '' || strlen( $key ) > self::KEY_MAX_LENGTH || ! preg_match( self::KEY_PATTERN, $key ) ) {
				continue;
			}
			$type = isset( $frag[ self::FIELD_FRAGMENT_TYPE ] ) && is_string( $frag[ self::FIELD_FRAGMENT_TYPE ] )
				? trim( $frag[ self::FIELD_FRAGMENT_TYPE ] )
				: '';
			if ( ! in_array( $type, self::TYPES, true ) ) {
				continue;
			}
			$consumers = isset( $frag[ self::FIELD_ALLOWED_CONSUMERS ] ) && is_array( $frag[ self::FIELD_ALLOWED_CONSUMERS ] )
				? $frag[ self::FIELD_ALLOWED_CONSUMERS ]
				: array();
			$consumers = array_values(
				array_filter(
					array_map(
						function ( $c ) {
							return is_string( $c ) ? trim( $c ) : '';
						},
						$consumers
					)
				)
			);
			if ( count( $consumers ) === 0 ) {
				continue;
			}
			$content = isset( $frag[ self::FIELD_CONTENT ] ) && is_string( $frag[ self::FIELD_CONTENT ] )
				? $frag[ self::FIELD_CONTENT ]
				: '';
			if ( strlen( $content ) > self::CONTENT_MAX_LENGTH ) {
				continue;
			}
			$status = isset( $frag[ self::FIELD_STATUS ] ) && is_string( $frag[ self::FIELD_STATUS ] )
				? trim( $frag[ self::FIELD_STATUS ] )
				: '';
			if ( $status === '' ) {
				continue;
			}
			if ( isset( $this->by_key[ $key ] ) ) {
				continue;
			}
			$this->by_key[ $key ] = array(
				self::FIELD_FRAGMENT_KEY      => $key,
				self::FIELD_FRAGMENT_TYPE     => $type,
				self::FIELD_ALLOWED_CONSUMERS => $consumers,
				self::FIELD_CONTENT           => $content,
				self::FIELD_STATUS            => $status,
				self::FIELD_VERSION_MARKER    => isset( $frag[ self::FIELD_VERSION_MARKER ] ) && is_string( $frag[ self::FIELD_VERSION_MARKER ] )
					? substr( trim( $frag[ self::FIELD_VERSION_MARKER ] ), 0, self::VERSION_MAX_LENGTH )
					: '',
			);
			$this->all[]          = $this->by_key[ $key ];
		}
	}

	/**
	 * Returns fragment definition by fragment_key, or null if not found.
	 *
	 * @param string $fragment_key Fragment key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $fragment_key ): ?array {
		$key = trim( $fragment_key );
		return $this->by_key[ $key ] ?? null;
	}

	/**
	 * Returns all loaded fragments.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_all(): array {
		return $this->all;
	}

	/**
	 * Returns fragments of the given type.
	 *
	 * @param string $fragment_type One of TYPE_* constants.
	 * @return list<array<string, mixed>>
	 */
	public function get_by_type( string $fragment_type ): array {
		$type = trim( $fragment_type );
		$out  = array();
		foreach ( $this->all as $frag ) {
			$t = isset( $frag[ self::FIELD_FRAGMENT_TYPE ] ) ? $frag[ self::FIELD_FRAGMENT_TYPE ] : '';
			if ( $t === $type ) {
				$out[] = $frag;
			}
		}
		return $out;
	}
}
