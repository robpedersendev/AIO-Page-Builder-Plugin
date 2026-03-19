<?php
/**
 * Registry for conversion-goal style preset overlays (conversion-goal-style-preset-schema.md, Prompt 512).
 * Read-only after load; get(goal_preset_key), get_for_goal(goal_key), get_overlays_for_preset(target_preset_ref), get_all().
 * Overlays refine industry presets by conversion goal; no raw CSS.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Goal style preset overlay registry. Read-only after load. Invalid entries skipped.
 */
final class Goal_Style_Preset_Overlay_Registry {

	/** Overlay object: goal preset key. */
	public const FIELD_GOAL_PRESET_KEY = 'goal_preset_key';

	/** Overlay object: conversion goal key. */
	public const FIELD_GOAL_KEY = 'goal_key';

	/** Overlay object: target style_preset_key this overlay refines. */
	public const FIELD_TARGET_PRESET_REF = 'target_preset_ref';

	/** Overlay object: optional token value overrides. */
	public const FIELD_TOKEN_VALUES = 'token_values';

	/** Overlay object: optional component override refs. */
	public const FIELD_COMPONENT_OVERRIDE_REFS = 'component_override_refs';

	/** Overlay object: status. */
	public const FIELD_STATUS = 'status';

	/** Status: active overlays are used at resolution. */
	public const STATUS_ACTIVE = 'active';

	/** Allowed goal keys (launch set). */
	private const ALLOWED_GOAL_KEYS = array( 'calls', 'bookings', 'estimates', 'consultations', 'valuations', 'lead_capture' );

	/** Pattern for keys. */
	private const KEY_PATTERN = '#^[a-z0-9_-]+$#';

	/** Max length for keys. */
	private const KEY_MAX_LENGTH = 64;

	/** @var array<string, array<string, mixed>> Map of goal_preset_key => overlay. */
	private array $by_key = array();

	/** @var list<array<string, mixed>> All valid overlays in load order. */
	private array $all = array();

	/**
	 * Returns built-in goal style preset overlay definitions from StylePresets/GoalOverlays/ (Prompt 512).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_definitions(): array {
		$path = __DIR__ . '/StylePresets/GoalOverlays/goal-style-preset-overlay-definitions.php';
		if ( ! is_readable( $path ) ) {
			return array();
		}
		$loaded = require $path;
		return is_array( $loaded ) ? $loaded : array();
	}

	/**
	 * Loads overlay definitions. Skips invalid or duplicate goal_preset_key (first wins). Safe: no throw.
	 *
	 * @param array<int, array<string, mixed>> $overlays List of overlay objects.
	 * @return void
	 */
	public function load( array $overlays ): void {
		$this->by_key = array();
		$this->all    = array();
		foreach ( $overlays as $ov ) {
			if ( ! is_array( $ov ) ) {
				continue;
			}
			$goal_preset_key = isset( $ov[ self::FIELD_GOAL_PRESET_KEY ] ) && is_string( $ov[ self::FIELD_GOAL_PRESET_KEY ] )
				? trim( $ov[ self::FIELD_GOAL_PRESET_KEY ] )
				: '';
			if ( $goal_preset_key === '' || strlen( $goal_preset_key ) > self::KEY_MAX_LENGTH || ! preg_match( self::KEY_PATTERN, $goal_preset_key ) ) {
				continue;
			}
			$goal_key = isset( $ov[ self::FIELD_GOAL_KEY ] ) && is_string( $ov[ self::FIELD_GOAL_KEY ] )
				? trim( $ov[ self::FIELD_GOAL_KEY ] )
				: '';
			if ( $goal_key === '' || strlen( $goal_key ) > self::KEY_MAX_LENGTH || ! in_array( $goal_key, self::ALLOWED_GOAL_KEYS, true ) ) {
				continue;
			}
			$target = isset( $ov[ self::FIELD_TARGET_PRESET_REF ] ) && is_string( $ov[ self::FIELD_TARGET_PRESET_REF ] )
				? trim( $ov[ self::FIELD_TARGET_PRESET_REF ] )
				: '';
			if ( $target === '' || strlen( $target ) > self::KEY_MAX_LENGTH ) {
				continue;
			}
			$status = isset( $ov[ self::FIELD_STATUS ] ) && is_string( $ov[ self::FIELD_STATUS ] )
				? trim( $ov[ self::FIELD_STATUS ] )
				: '';
			if ( $status === '' ) {
				continue;
			}
			if ( isset( $this->by_key[ $goal_preset_key ] ) ) {
				continue;
			}
			$this->by_key[ $goal_preset_key ] = $ov;
			$this->all[]                      = $ov;
		}
	}

	/**
	 * Returns overlay by goal_preset_key, or null if not found.
	 *
	 * @param string $goal_preset_key Goal preset overlay key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $goal_preset_key ): ?array {
		$key = trim( $goal_preset_key );
		return $this->by_key[ $key ] ?? null;
	}

	/**
	 * Returns all loaded overlays.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_all(): array {
		return $this->all;
	}

	/**
	 * Returns overlays for the given conversion goal.
	 *
	 * @param string $goal_key Conversion goal key.
	 * @return list<array<string, mixed>>
	 */
	public function get_for_goal( string $goal_key ): array {
		$g   = trim( $goal_key );
		$out = array();
		foreach ( $this->all as $ov ) {
			$gk = isset( $ov[ self::FIELD_GOAL_KEY ] ) && is_string( $ov[ self::FIELD_GOAL_KEY ] )
				? trim( $ov[ self::FIELD_GOAL_KEY ] )
				: '';
			if ( $gk === $g ) {
				$out[] = $ov;
			}
		}
		return $out;
	}

	/**
	 * Returns overlays that refine the given target_preset_ref.
	 *
	 * @param string $target_preset_ref style_preset_key (e.g. realtor_warm, plumber_trust).
	 * @return list<array<string, mixed>>
	 */
	public function get_overlays_for_preset( string $target_preset_ref ): array {
		$t   = trim( $target_preset_ref );
		$out = array();
		foreach ( $this->all as $ov ) {
			$ref = isset( $ov[ self::FIELD_TARGET_PRESET_REF ] ) && is_string( $ov[ self::FIELD_TARGET_PRESET_REF ] )
				? trim( $ov[ self::FIELD_TARGET_PRESET_REF ] )
				: '';
			if ( $ref === $t ) {
				$out[] = $ov;
			}
		}
		return $out;
	}
}
