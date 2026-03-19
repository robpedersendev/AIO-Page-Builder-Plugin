<?php
/**
 * Read-only registry of combined subtype+goal starter-bundle overlays (subtype-goal-starter-bundle-schema.md, Prompt 551).
 * Keyed by (subtype_key, goal_key) and optional target_bundle_ref. Invalid definitions skipped at load.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of combined subtype+goal starter-bundle overlay definitions. Exceptional; used after goal overlays.
 */
final class Subtype_Goal_Starter_Bundle_Overlay_Registry {

	public const FIELD_OVERLAY_KEY             = 'overlay_key';
	public const FIELD_SUBTYPE_KEY             = 'subtype_key';
	public const FIELD_GOAL_KEY                = 'goal_key';
	public const FIELD_TARGET_BUNDLE_REF       = 'target_bundle_ref';
	public const FIELD_ALLOWED_OVERLAY_REGIONS = 'allowed_overlay_regions';
	public const FIELD_SECTION_EMPHASIS        = 'section_emphasis';
	public const FIELD_CTA_POSTURE             = 'cta_posture';
	public const FIELD_FUNNEL_SHAPE            = 'funnel_shape';
	public const FIELD_PAGE_FAMILY_EMPHASIS    = 'page_family_emphasis';
	public const FIELD_STATUS                  = 'status';
	public const FIELD_VERSION_MARKER          = 'version_marker';

	public const STATUS_ACTIVE     = 'active';
	public const STATUS_DRAFT      = 'draft';
	public const STATUS_DEPRECATED = 'deprecated';

	public const SUPPORTED_SCHEMA_VERSION = '1';
	private const KEY_PATTERN             = '#^[a-z0-9_-]+$#';
	private const KEY_MAX_LEN             = 64;
	private const ALLOWED_REGIONS         = array( 'section_emphasis', 'cta_posture', 'funnel_shape', 'page_family_emphasis' );
	private const ALLOWED_GOAL_KEYS       = array( 'calls', 'bookings', 'estimates', 'consultations', 'valuations', 'lead_capture' );

	/** @var array<string, array<string, mixed>> Composite "subtype|goal|bundle" => overlay. */
	private array $by_composite = array();

	/** @var array<int, array<string, mixed>> All valid overlays in load order. */
	private array $all = array();

	private static function composite_key( string $subtype, string $goal, string $bundle ): string {
		$b = $bundle !== '' ? $bundle : '_';
		return $subtype . '|' . $goal . '|' . $b;
	}

	/**
	 * Returns built-in combined subtype+goal overlay definitions (Prompt 552).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_definitions(): array {
		return \AIOPageBuilder\Domain\Industry\Registry\StarterBundles\SubtypeGoalOverlays\Builtin_Subtype_Goal_Starter_Bundle_Overlays::get_definitions();
	}

	/**
	 * Loads overlay definitions. Skips invalid or duplicate. Safe: no throw.
	 *
	 * @param array<int, array<string, mixed>> $definitions List of overlay objects.
	 * @return void
	 */
	public function load( array $definitions ): void {
		$this->by_composite = array();
		$this->all          = array();
		foreach ( $definitions as $ov ) {
			if ( ! \is_array( $ov ) ) {
				continue;
			}
			$errors = $this->validate_overlay( $ov );
			if ( $errors !== array() ) {
				continue;
			}
			$subtype      = \trim( (string) ( $ov[ self::FIELD_SUBTYPE_KEY ] ?? '' ) );
			$goal         = \trim( (string) ( $ov[ self::FIELD_GOAL_KEY ] ?? '' ) );
			$bundle       = isset( $ov[ self::FIELD_TARGET_BUNDLE_REF ] ) && \is_string( $ov[ self::FIELD_TARGET_BUNDLE_REF ] )
				? \trim( $ov[ self::FIELD_TARGET_BUNDLE_REF ] )
				: '';
			$key_specific = self::composite_key( $subtype, $goal, $bundle );
			$key_any      = self::composite_key( $subtype, $goal, '' );
			if ( ! isset( $this->by_composite[ $key_specific ] ) ) {
				$normalized                          = $this->normalize_overlay( $ov );
				$this->by_composite[ $key_specific ] = $normalized;
				$this->all[]                         = $normalized;
			}
			if ( $bundle !== '' && ! isset( $this->by_composite[ $key_any ] ) ) {
				$normalized                     = $this->normalize_overlay( $ov );
				$this->by_composite[ $key_any ] = $normalized;
			}
		}
	}

	/**
	 * Returns overlay for (subtype_key, goal_key, bundle_key). Prefers bundle-specific, then generic.
	 *
	 * @param string $subtype_key Industry subtype key.
	 * @param string $goal_key    Conversion goal key.
	 * @param string $bundle_key  Optional bundle key; empty = generic overlay only.
	 * @return array<string, mixed>|null
	 */
	public function get( string $subtype_key, string $goal_key, string $bundle_key = '' ): ?array {
		$s = \trim( $subtype_key );
		$g = \trim( $goal_key );
		$b = \trim( $bundle_key );
		if ( $s === '' || $g === '' ) {
			return null;
		}
		$key_bundle = self::composite_key( $s, $g, $b );
		$key_any    = self::composite_key( $s, $g, '' );
		if ( isset( $this->by_composite[ $key_bundle ] ) ) {
			return $this->by_composite[ $key_bundle ];
		}
		if ( $b !== '' && isset( $this->by_composite[ $key_any ] ) ) {
			return $this->by_composite[ $key_any ];
		}
		return null;
	}

	/**
	 * Returns all overlays for (subtype_key, goal_key).
	 *
	 * @param string $subtype_key Industry subtype key.
	 * @param string $goal_key    Conversion goal key.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_for_subtype_goal( string $subtype_key, string $goal_key ): array {
		$s = \trim( $subtype_key );
		$g = \trim( $goal_key );
		if ( $s === '' || $g === '' ) {
			return array();
		}
		$out = array();
		foreach ( $this->all as $ov ) {
			$sk = isset( $ov[ self::FIELD_SUBTYPE_KEY ] ) && \is_string( $ov[ self::FIELD_SUBTYPE_KEY ] )
				? \trim( $ov[ self::FIELD_SUBTYPE_KEY ] ) : '';
			$gk = isset( $ov[ self::FIELD_GOAL_KEY ] ) && \is_string( $ov[ self::FIELD_GOAL_KEY ] )
				? \trim( $ov[ self::FIELD_GOAL_KEY ] ) : '';
			if ( $sk === $s && $gk === $g ) {
				$out[] = $ov;
			}
		}
		return $out;
	}

	/**
	 * Returns all loaded overlays.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function list_all(): array {
		return $this->all;
	}

	/**
	 * Validates an overlay definition. Returns list of error codes; empty when valid.
	 *
	 * @param array<string, mixed> $ov Raw overlay.
	 * @return array<int, string>
	 */
	public function validate_overlay( array $ov ): array {
		$errors = array();

		$overlay_key = isset( $ov[ self::FIELD_OVERLAY_KEY ] ) && \is_string( $ov[ self::FIELD_OVERLAY_KEY ] )
			? \trim( $ov[ self::FIELD_OVERLAY_KEY ] ) : '';
		if ( $overlay_key === '' ) {
			$errors[] = 'missing_overlay_key';
		} elseif ( \strlen( $overlay_key ) > self::KEY_MAX_LEN || ! \preg_match( self::KEY_PATTERN, $overlay_key ) ) {
			$errors[] = 'invalid_overlay_key';
		}

		$subtype = isset( $ov[ self::FIELD_SUBTYPE_KEY ] ) && \is_string( $ov[ self::FIELD_SUBTYPE_KEY ] )
			? \trim( $ov[ self::FIELD_SUBTYPE_KEY ] ) : '';
		if ( $subtype === '' ) {
			$errors[] = 'missing_subtype_key';
		} elseif ( \strlen( $subtype ) > self::KEY_MAX_LEN || ! \preg_match( self::KEY_PATTERN, $subtype ) ) {
			$errors[] = 'invalid_subtype_key';
		}

		$goal = isset( $ov[ self::FIELD_GOAL_KEY ] ) && \is_string( $ov[ self::FIELD_GOAL_KEY ] )
			? \trim( $ov[ self::FIELD_GOAL_KEY ] ) : '';
		if ( $goal === '' ) {
			$errors[] = 'missing_goal_key';
		} elseif ( ! \in_array( $goal, self::ALLOWED_GOAL_KEYS, true ) ) {
			$errors[] = 'invalid_goal_key';
		}

		$regions = $ov[ self::FIELD_ALLOWED_OVERLAY_REGIONS ] ?? null;
		if ( ! \is_array( $regions ) || $regions === array() ) {
			$errors[] = 'missing_allowed_overlay_regions';
		} else {
			foreach ( $regions as $r ) {
				if ( ! \in_array( $r, self::ALLOWED_REGIONS, true ) ) {
					$errors[] = 'invalid_allowed_overlay_region';
					break;
				}
			}
		}

		$status = isset( $ov[ self::FIELD_STATUS ] ) && \is_string( $ov[ self::FIELD_STATUS ] )
			? $ov[ self::FIELD_STATUS ] : '';
		if ( $status !== self::STATUS_ACTIVE && $status !== self::STATUS_DRAFT && $status !== self::STATUS_DEPRECATED ) {
			$errors[] = 'invalid_status';
		}

		$version = isset( $ov[ self::FIELD_VERSION_MARKER ] ) && \is_string( $ov[ self::FIELD_VERSION_MARKER ] )
			? \trim( $ov[ self::FIELD_VERSION_MARKER ] ) : '';
		if ( $version !== self::SUPPORTED_SCHEMA_VERSION ) {
			$errors[] = 'unsupported_version';
		}

		return $errors;
	}

	/**
	 * Normalizes a valid overlay to canonical shape.
	 *
	 * @param array<string, mixed> $ov Validated overlay.
	 * @return array<string, mixed>
	 */
	private function normalize_overlay( array $ov ): array {
		$out                                     = array(
			self::FIELD_OVERLAY_KEY             => \trim( (string) ( $ov[ self::FIELD_OVERLAY_KEY ] ?? '' ) ),
			self::FIELD_SUBTYPE_KEY             => \trim( (string) ( $ov[ self::FIELD_SUBTYPE_KEY ] ?? '' ) ),
			self::FIELD_GOAL_KEY                => \trim( (string) ( $ov[ self::FIELD_GOAL_KEY ] ?? '' ) ),
			self::FIELD_TARGET_BUNDLE_REF       => isset( $ov[ self::FIELD_TARGET_BUNDLE_REF ] ) && \is_string( $ov[ self::FIELD_TARGET_BUNDLE_REF ] )
				? \trim( $ov[ self::FIELD_TARGET_BUNDLE_REF ] )
				: '',
			self::FIELD_ALLOWED_OVERLAY_REGIONS => \is_array( $ov[ self::FIELD_ALLOWED_OVERLAY_REGIONS ] ?? null )
				? array_values( array_filter( array_map( 'strval', $ov[ self::FIELD_ALLOWED_OVERLAY_REGIONS ] ) ) )
				: array(),
			self::FIELD_STATUS                  => (string) ( $ov[ self::FIELD_STATUS ] ?? self::STATUS_ACTIVE ),
			self::FIELD_VERSION_MARKER          => \trim( (string) ( $ov[ self::FIELD_VERSION_MARKER ] ?? self::SUPPORTED_SCHEMA_VERSION ) ),
		);
		$out[ self::FIELD_SECTION_EMPHASIS ]     = isset( $ov[ self::FIELD_SECTION_EMPHASIS ] ) && \is_array( $ov[ self::FIELD_SECTION_EMPHASIS ] )
			? array_values( array_filter( array_map( 'strval', $ov[ self::FIELD_SECTION_EMPHASIS ] ) ) )
			: array();
		$out[ self::FIELD_CTA_POSTURE ]          = isset( $ov[ self::FIELD_CTA_POSTURE ] ) && \is_string( $ov[ self::FIELD_CTA_POSTURE ] )
			? \trim( \substr( $ov[ self::FIELD_CTA_POSTURE ], 0, 128 ) )
			: '';
		$out[ self::FIELD_FUNNEL_SHAPE ]         = isset( $ov[ self::FIELD_FUNNEL_SHAPE ] ) && \is_string( $ov[ self::FIELD_FUNNEL_SHAPE ] )
			? \trim( \substr( $ov[ self::FIELD_FUNNEL_SHAPE ], 0, 128 ) )
			: '';
		$out[ self::FIELD_PAGE_FAMILY_EMPHASIS ] = isset( $ov[ self::FIELD_PAGE_FAMILY_EMPHASIS ] ) && \is_array( $ov[ self::FIELD_PAGE_FAMILY_EMPHASIS ] )
			? array_values( array_filter( array_map( 'strval', $ov[ self::FIELD_PAGE_FAMILY_EMPHASIS ] ) ) )
			: array();
		return $out;
	}
}
