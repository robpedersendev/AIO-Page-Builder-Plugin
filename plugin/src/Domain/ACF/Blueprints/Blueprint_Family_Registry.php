<?php
/**
 * Registry of blueprint families for large-scale reuse (large-scale-acf-lpagery-binding-contract §2.2, §2.3, §6.1).
 * Maps variation_family_key to base_blueprint_ref and optional variant-level overrides (add/hide fields).
 * Does not store blueprint content; section definitions still own embedded blueprints.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Blueprints;

defined( 'ABSPATH' ) || exit;

/**
 * Holds family-level metadata: base blueprint ref and variant overrides for additive registration.
 * Explicit and documented; no opaque inheritance.
 */
final class Blueprint_Family_Registry {

	/** Key for base blueprint ref in family config. */
	public const KEY_BASE_BLUEPRINT_REF = 'base_blueprint_ref';

	/** Key for variant overrides in family config. */
	public const KEY_VARIANT_OVERRIDES = 'variant_overrides';

	/** Key for additive fields in a variant override. */
	public const KEY_ADD_FIELDS = 'add_fields';

	/** Key for field names to hide in a variant override. */
	public const KEY_HIDE_FIELD_NAMES = 'hide_field_names';

	/** @var array<string, array{base_blueprint_ref: string, variant_overrides: array<string, array{add_fields?: array, hide_field_names?: list<string>}>}> */
	private array $families = array();

	/**
	 * Registers a blueprint family. Overwrites existing entry for the same key.
	 *
	 * @param string $family_key variation_family_key (e.g. hero_primary, proof_cards).
	 * @param string $base_blueprint_ref Shared field_blueprint_ref for sections in this family.
	 * @param array<string, array{add_fields?: array, hide_field_names?: list<string>}> $variant_overrides Optional. variant_key => add_fields (array of field defs), hide_field_names (list of field names to omit).
	 * @return void
	 */
	public function register_family( string $family_key, string $base_blueprint_ref, array $variant_overrides = array() ): void {
		$family_key = \sanitize_key( $family_key );
		if ( $family_key === '' ) {
			return;
		}
		$this->families[ $family_key ] = array(
			self::KEY_BASE_BLUEPRINT_REF => \sanitize_key( $base_blueprint_ref ) ?: $base_blueprint_ref,
			self::KEY_VARIANT_OVERRIDES   => $this->sanitize_variant_overrides( $variant_overrides ),
		);
	}

	/**
	 * Returns family config or null if not registered.
	 *
	 * @param string $family_key
	 * @return array{base_blueprint_ref: string, variant_overrides: array}|null
	 */
	public function get_family( string $family_key ): ?array {
		$family_key = \sanitize_key( $family_key );
		return isset( $this->families[ $family_key ] ) ? $this->families[ $family_key ] : null;
	}

	/**
	 * Returns base blueprint ref for the family, or empty string if not registered.
	 *
	 * @param string $family_key
	 * @return string
	 */
	public function get_base_blueprint_ref( string $family_key ): string {
		$fam = $this->get_family( $family_key );
		return $fam !== null ? (string) ( $fam[ self::KEY_BASE_BLUEPRINT_REF ] ?? '' ) : '';
	}

	/**
	 * Returns variant overrides for the family (variant_key => add_fields / hide_field_names).
	 *
	 * @param string $family_key
	 * @return array<string, array{add_fields?: array, hide_field_names?: list<string>}>
	 */
	public function get_variant_overrides( string $family_key ): array {
		$fam = $this->get_family( $family_key );
		return $fam !== null ? (array) ( $fam[ self::KEY_VARIANT_OVERRIDES ] ?? array() ) : array();
	}

	/**
	 * Returns whether the family is registered.
	 *
	 * @param string $family_key
	 * @return bool
	 */
	public function has_family( string $family_key ): bool {
		return $this->get_family( \sanitize_key( $family_key ) ) !== null;
	}

	/**
	 * Returns all registered family keys (deterministic order).
	 *
	 * @return list<string>
	 */
	public function get_registered_family_keys(): array {
		$keys = array_keys( $this->families );
		sort( $keys, SORT_STRING );
		return $keys;
	}

	/**
	 * @param array<string, mixed> $variant_overrides
	 * @return array<string, array{add_fields?: array, hide_field_names?: list<string>}>
	 */
	private function sanitize_variant_overrides( array $variant_overrides ): array {
		$out = array();
		foreach ( $variant_overrides as $variant_key => $config ) {
			if ( ! \is_string( $variant_key ) || $variant_key === '' || ! \is_array( $config ) ) {
				continue;
			}
			$v = \sanitize_key( $variant_key );
			if ( $v === '' ) {
				continue;
			}
			$add = isset( $config[ self::KEY_ADD_FIELDS ] ) && \is_array( $config[ self::KEY_ADD_FIELDS ] ) ? $config[ self::KEY_ADD_FIELDS ] : array();
			$hide = isset( $config[ self::KEY_HIDE_FIELD_NAMES ] ) && \is_array( $config[ self::KEY_HIDE_FIELD_NAMES ] ) ? $config[ self::KEY_HIDE_FIELD_NAMES ] : array();
			$hide = array_values( array_filter( array_map( function ( $n ) {
				return \is_string( $n ) ? $n : '';
			}, $hide ) ) );
			$out[ $v ] = array();
			if ( ! empty( $add ) ) {
				$out[ $v ][ self::KEY_ADD_FIELDS ] = $add;
			}
			if ( ! empty( $hide ) ) {
				$out[ $v ][ self::KEY_HIDE_FIELD_NAMES ] = $hide;
			}
		}
		return $out;
	}
}
