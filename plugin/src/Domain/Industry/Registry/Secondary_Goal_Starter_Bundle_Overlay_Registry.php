<?php
/**
 * Read-only registry of secondary-goal starter-bundle overlays (secondary-goal-starter-bundle-schema.md, Prompt 541).
 * Keyed by (primary_goal_key, secondary_goal_key) and optional target_bundle_ref. Invalid definitions skipped at load.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of secondary-goal starter-bundle overlay definitions. Primary-goal overlays remain authoritative.
 */
final class Secondary_Goal_Starter_Bundle_Overlay_Registry {

	public const FIELD_OVERLAY_KEY             = 'overlay_key';
	public const FIELD_PRIMARY_GOAL_KEY        = 'primary_goal_key';
	public const FIELD_SECONDARY_GOAL_KEY      = 'secondary_goal_key';
	public const FIELD_TARGET_BUNDLE_REF       = 'target_bundle_ref';
	public const FIELD_ALLOWED_OVERLAY_REGIONS = 'allowed_overlay_regions';
	public const FIELD_SECTION_EMPHASIS        = 'section_emphasis';
	public const FIELD_CTA_POSTURE             = 'cta_posture';
	public const FIELD_FUNNEL_SHAPE            = 'funnel_shape';
	public const FIELD_PAGE_FAMILY_EMPHASIS    = 'page_family_emphasis';
	public const FIELD_PRECEDENCE_MARKER       = 'precedence_marker';
	public const FIELD_STATUS                  = 'status';
	public const FIELD_VERSION_MARKER          = 'version_marker';

	public const PRECEDENCE_SECONDARY = 'secondary';
	public const STATUS_ACTIVE        = 'active';
	public const STATUS_DRAFT         = 'draft';
	public const STATUS_DEPRECATED    = 'deprecated';

	public const SUPPORTED_SCHEMA_VERSION = '1';
	private const KEY_PATTERN             = '#^[a-z0-9_-]+$#';
	private const KEY_MAX_LEN             = 64;
	private const ALLOWED_REGIONS         = array( 'section_emphasis', 'cta_posture', 'funnel_shape', 'page_family_emphasis' );

	/** @var array<string, array<string, mixed>> Composite "primary|secondary|bundle" => overlay. */
	private array $by_composite = array();

	/** @var list<array<string, mixed>> All valid overlays in load order. */
	private array $all = array();

	/**
	 * Returns built-in secondary-goal overlay definitions (Prompt 542).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_definitions(): array {
		return \AIOPageBuilder\Domain\Industry\Registry\StarterBundles\SecondaryGoalOverlays\Builtin_Secondary_Goal_Starter_Bundle_Overlays::get_definitions();
	}

	/**
	 * Builds composite key for lookup. Empty bundle stored as '_' for generic overlay.
	 */
	private static function composite_key( string $primary, string $secondary, string $bundle ): string {
		$b = $bundle !== '' ? $bundle : '_';
		return $primary . '|' . $secondary . '|' . $b;
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
			$primary      = \trim( (string) ( $ov[ self::FIELD_PRIMARY_GOAL_KEY ] ?? '' ) );
			$secondary    = \trim( (string) ( $ov[ self::FIELD_SECONDARY_GOAL_KEY ] ?? '' ) );
			$bundle       = isset( $ov[ self::FIELD_TARGET_BUNDLE_REF ] ) && \is_string( $ov[ self::FIELD_TARGET_BUNDLE_REF ] )
				? \trim( $ov[ self::FIELD_TARGET_BUNDLE_REF ] )
				: '';
			$key_specific = self::composite_key( $primary, $secondary, $bundle );
			$key_any      = self::composite_key( $primary, $secondary, '' );
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
	 * Returns overlay for (primary_goal, secondary_goal, bundle_key). Prefers bundle-specific overlay, then generic (empty target_bundle_ref).
	 *
	 * @param string $primary_goal_key   Primary conversion goal key.
	 * @param string $secondary_goal_key Secondary conversion goal key.
	 * @param string $bundle_key         Optional bundle key; empty = generic overlay only.
	 * @return array<string, mixed>|null
	 */
	public function get( string $primary_goal_key, string $secondary_goal_key, string $bundle_key = '' ): ?array {
		$p = \trim( $primary_goal_key );
		$s = \trim( $secondary_goal_key );
		$b = \trim( $bundle_key );
		if ( $p === '' || $s === '' || $p === $s ) {
			return null;
		}
		$key_bundle = self::composite_key( $p, $s, $b );
		$key_any    = self::composite_key( $p, $s, '' );
		if ( isset( $this->by_composite[ $key_bundle ] ) ) {
			return $this->by_composite[ $key_bundle ];
		}
		if ( $b !== '' && isset( $this->by_composite[ $key_any ] ) ) {
			return $this->by_composite[ $key_any ];
		}
		return null;
	}

	/**
	 * Returns all overlays for (primary_goal, secondary_goal).
	 *
	 * @param string $primary_goal_key   Primary conversion goal key.
	 * @param string $secondary_goal_key Secondary conversion goal key.
	 * @return list<array<string, mixed>>
	 */
	public function get_for_primary_secondary( string $primary_goal_key, string $secondary_goal_key ): array {
		$p = \trim( $primary_goal_key );
		$s = \trim( $secondary_goal_key );
		if ( $p === '' || $s === '' || $p === $s ) {
			return array();
		}
		$out = array();
		foreach ( $this->all as $ov ) {
			$pk = isset( $ov[ self::FIELD_PRIMARY_GOAL_KEY ] ) && \is_string( $ov[ self::FIELD_PRIMARY_GOAL_KEY ] )
				? \trim( $ov[ self::FIELD_PRIMARY_GOAL_KEY ] )
				: '';
			$sk = isset( $ov[ self::FIELD_SECONDARY_GOAL_KEY ] ) && \is_string( $ov[ self::FIELD_SECONDARY_GOAL_KEY ] )
				? \trim( $ov[ self::FIELD_SECONDARY_GOAL_KEY ] )
				: '';
			if ( $pk === $p && $sk === $s ) {
				$out[] = $ov;
			}
		}
		return $out;
	}

	/**
	 * Returns all loaded overlays.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function list_all(): array {
		return $this->all;
	}

	/**
	 * Validates an overlay definition. Returns list of error codes; empty when valid.
	 *
	 * @param array<string, mixed> $ov Raw overlay.
	 * @return list<string>
	 */
	public function validate_overlay( array $ov ): array {
		$errors = array();

		$overlay_key = isset( $ov[ self::FIELD_OVERLAY_KEY ] ) && \is_string( $ov[ self::FIELD_OVERLAY_KEY ] )
			? \trim( $ov[ self::FIELD_OVERLAY_KEY ] )
			: '';
		if ( $overlay_key === '' ) {
			$errors[] = 'missing_overlay_key';
		} elseif ( \strlen( $overlay_key ) > self::KEY_MAX_LEN || ! \preg_match( self::KEY_PATTERN, $overlay_key ) ) {
			$errors[] = 'invalid_overlay_key';
		}

		$primary   = isset( $ov[ self::FIELD_PRIMARY_GOAL_KEY ] ) && \is_string( $ov[ self::FIELD_PRIMARY_GOAL_KEY ] )
			? \trim( $ov[ self::FIELD_PRIMARY_GOAL_KEY ] )
			: '';
		$secondary = isset( $ov[ self::FIELD_SECONDARY_GOAL_KEY ] ) && \is_string( $ov[ self::FIELD_SECONDARY_GOAL_KEY ] )
			? \trim( $ov[ self::FIELD_SECONDARY_GOAL_KEY ] )
			: '';
		if ( $primary === '' ) {
			$errors[] = 'missing_primary_goal_key';
		} elseif ( \strlen( $primary ) > self::KEY_MAX_LEN || ! \preg_match( self::KEY_PATTERN, $primary ) ) {
			$errors[] = 'invalid_primary_goal_key';
		}
		if ( $secondary === '' ) {
			$errors[] = 'missing_secondary_goal_key';
		} elseif ( \strlen( $secondary ) > self::KEY_MAX_LEN || ! \preg_match( self::KEY_PATTERN, $secondary ) ) {
			$errors[] = 'invalid_secondary_goal_key';
		}
		if ( $primary !== '' && $secondary !== '' && $primary === $secondary ) {
			$errors[] = 'primary_equals_secondary';
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

		$precedence = isset( $ov[ self::FIELD_PRECEDENCE_MARKER ] ) && \is_string( $ov[ self::FIELD_PRECEDENCE_MARKER ] )
			? $ov[ self::FIELD_PRECEDENCE_MARKER ] : '';
		if ( $precedence !== self::PRECEDENCE_SECONDARY ) {
			$errors[] = 'invalid_precedence_marker';
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
			self::FIELD_PRIMARY_GOAL_KEY        => \trim( (string) ( $ov[ self::FIELD_PRIMARY_GOAL_KEY ] ?? '' ) ),
			self::FIELD_SECONDARY_GOAL_KEY      => \trim( (string) ( $ov[ self::FIELD_SECONDARY_GOAL_KEY ] ?? '' ) ),
			self::FIELD_TARGET_BUNDLE_REF       => isset( $ov[ self::FIELD_TARGET_BUNDLE_REF ] ) && \is_string( $ov[ self::FIELD_TARGET_BUNDLE_REF ] )
				? \trim( $ov[ self::FIELD_TARGET_BUNDLE_REF ] )
				: '',
			self::FIELD_ALLOWED_OVERLAY_REGIONS => \is_array( $ov[ self::FIELD_ALLOWED_OVERLAY_REGIONS ] ?? null )
				? array_values( array_filter( array_map( 'strval', $ov[ self::FIELD_ALLOWED_OVERLAY_REGIONS ] ) ) )
				: array(),
			self::FIELD_PRECEDENCE_MARKER       => self::PRECEDENCE_SECONDARY,
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
