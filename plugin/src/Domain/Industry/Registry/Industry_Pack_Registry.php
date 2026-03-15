<?php
/**
 * Read-only registry of industry packs (industry-pack-extension-contract, industry-pack-schema.md).
 * Loads and validates definitions; exposes lookup by industry_key and filter by status.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of industry pack definitions. Deterministic, read-only after load. Invalid/duplicate packs are skipped.
 */
final class Industry_Pack_Registry {

	/** @var array<string, array<string, mixed>> Map of industry_key => pack definition. */
	private array $by_key = array();

	/** @var list<array<string, mixed>> All valid packs in load order. */
	private array $all = array();

	/** @var Industry_Pack_Validator */
	private Industry_Pack_Validator $validator;

	public function __construct( ?Industry_Pack_Validator $validator = null ) {
		$this->validator = $validator ?? new Industry_Pack_Validator();
	}

	/**
	 * Returns built-in industry pack definitions from Packs/*.php. Used by bootstrap to load seed packs (Prompts 349–351).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_pack_definitions(): array {
		$dir = __DIR__ . '/Packs';
		$files = array(
			$dir . '/industry-pack-cosmetology-nail.php',
			$dir . '/industry-pack-realtor.php',
			$dir . '/industry-pack-plumber.php',
		);
		$out = array();
		foreach ( $files as $path ) {
			if ( is_readable( $path ) ) {
				$loaded = require $path;
				if ( is_array( $loaded ) ) {
					foreach ( $loaded as $pack ) {
						if ( is_array( $pack ) ) {
							$out[] = $pack;
						}
					}
				}
			}
		}
		return $out;
	}

	/**
	 * Loads pack definitions. Validates each; skips invalid and duplicate keys (first wins). Safe: no throw.
	 *
	 * @param array<int, array<string, mixed>> $definitions List of pack definitions.
	 * @return void
	 */
	public function load( array $definitions ): void {
		$this->by_key = array();
		$this->all    = array();
		$result       = $this->validator->validate_bulk( $definitions );
		foreach ( $result['valid'] as $pack ) {
			$key = (string) ( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] ?? '' );
			if ( $key !== '' && ! isset( $this->by_key[ $key ] ) ) {
				$this->by_key[ $key ] = $pack;
				$this->all[]          = $pack;
			}
		}
	}

	/**
	 * Returns pack definition by industry_key, or null if not found.
	 *
	 * @param string $industry_key Industry pack key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $industry_key ): ?array {
		$key = trim( $industry_key );
		return $this->by_key[ $key ] ?? null;
	}

	/**
	 * Returns all loaded packs.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_all(): array {
		return $this->all;
	}

	/**
	 * Returns packs with the given status (e.g. active). Empty array if none.
	 *
	 * @param string $status One of Industry_Pack_Schema::STATUS_*.
	 * @return list<array<string, mixed>>
	 */
	public function list_by_status( string $status ): array {
		$out = array();
		foreach ( $this->all as $pack ) {
			$s = isset( $pack[ Industry_Pack_Schema::FIELD_STATUS ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_STATUS ] )
				? $pack[ Industry_Pack_Schema::FIELD_STATUS ]
				: '';
			if ( $s === $status ) {
				$out[] = $pack;
			}
		}
		return $out;
	}

	/**
	 * Returns whether the registry has any packs.
	 */
	public function has_any(): bool {
		return $this->all !== array();
	}

	/**
	 * Returns version metadata for a pack, or null if not found.
	 *
	 * @param string $industry_key Industry pack key.
	 * @return array{version_marker?: string, status?: string}|null
	 */
	public function get_version_metadata( string $industry_key ): ?array {
		$pack = $this->get( $industry_key );
		if ( $pack === null ) {
			return null;
		}
		return array(
			'version_marker' => isset( $pack[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] )
				? $pack[ Industry_Pack_Schema::FIELD_VERSION_MARKER ]
				: '',
			'status' => isset( $pack[ Industry_Pack_Schema::FIELD_STATUS ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_STATUS ] )
				? $pack[ Industry_Pack_Schema::FIELD_STATUS ]
				: '',
		);
	}
}
