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
 * Registry of industry packs with deterministic merge layers:
 * - builtin packs
 * - applied bundle overlays (in apply order)
 * - local overrides (only if such a layer exists)
 */
final class Industry_Pack_Registry {

	public const SOURCE_BUILTIN = 'builtin';
	public const SOURCE_APPLIED = 'applied';

	/** @var array<string, array<string, mixed>> Effective pack record by pack_key. */
	private array $effective_by_key = array();

	/** @var list<array<string, mixed>> Effective pack records in deterministic order. */
	private array $effective_all = array();

	/** @var list<array<string, mixed>> Builtin pack records. */
	private array $builtin_all = array();

	/** @var list<array<string, mixed>> Applied pack records. */
	private array $applied_all = array();

	private Industry_Pack_Validator $validator;

	/** @var \AIOPageBuilder\Infrastructure\Settings\Settings_Service|null */
	private $settings;

	/** @var \AIOPageBuilder\Admin\Screens\Industry\Industry_Pack_Toggle_Controller|null */
	private $toggle_controller;

	public function __construct(
		?Industry_Pack_Validator $validator = null,
		?\AIOPageBuilder\Infrastructure\Settings\Settings_Service $settings = null,
		?\AIOPageBuilder\Admin\Screens\Industry\Industry_Pack_Toggle_Controller $toggle_controller = null
	) {
		$this->validator         = $validator ?? new Industry_Pack_Validator();
		$this->settings          = $settings;
		$this->toggle_controller = $toggle_controller;
		$this->refresh();
	}

	/**
	 * Returns built-in industry pack definitions from Packs/*.php. Used by bootstrap to load seed packs (Prompts 349–351).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_pack_definitions(): array {
		$dir   = __DIR__ . '/Packs';
		$files = array(
			$dir . '/industry-pack-cosmetology-nail.php',
			$dir . '/industry-pack-realtor.php',
			$dir . '/industry-pack-plumber.php',
			$dir . '/industry-pack-disaster-recovery.php',
		);
		$out   = array();
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
	 * Rebuilds the effective registry view from merge layers. Safe: no throw.
	 */
	public function refresh(): void {
		$this->builtin_all        = $this->build_records_from_definitions( self::get_builtin_pack_definitions(), self::SOURCE_BUILTIN, null );
		$this->applied_all        = $this->build_applied_records();
		$this->effective_by_key   = array();
		$this->effective_all      = array();

		// Deterministic merge: builtins first, then applied overlays (later bundles override earlier when same key exists).
		foreach ( $this->builtin_all as $record ) {
			$key = isset( $record['pack_key'] ) && is_string( $record['pack_key'] ) ? $record['pack_key'] : '';
			if ( $key === '' ) {
				continue;
			}
			$this->effective_by_key[ $key ] = $record;
		}
		foreach ( $this->applied_all as $record ) {
			$key = isset( $record['pack_key'] ) && is_string( $record['pack_key'] ) ? $record['pack_key'] : '';
			if ( $key === '' ) {
				continue;
			}
			$this->effective_by_key[ $key ] = $record;
		}

		// Preserve deterministic order: builtins in file order, then applied in apply order (dedup by final winner).
		$seen = array();
		foreach ( array_merge( $this->builtin_all, $this->applied_all ) as $record ) {
			$key = isset( $record['pack_key'] ) && is_string( $record['pack_key'] ) ? $record['pack_key'] : '';
			if ( $key === '' || isset( $seen[ $key ] ) ) {
				continue;
			}
			$winner = $this->effective_by_key[ $key ] ?? null;
			if ( is_array( $winner ) ) {
				$this->effective_all[] = $winner;
				$seen[ $key ]          = true;
			}
		}
	}

	/**
	 * Loads and validates pack definitions provided by callers (unit tests, imports).
	 * This overrides the effective view to only include the loaded packs.
	 *
	 * @param array<int, array<string, mixed>> $packs Pack definition list.
	 * @return void
	 */
	public function load( array $packs ): void {
		$this->builtin_all      = array();
		$this->applied_all      = array();
		$this->effective_by_key = array();
		$this->effective_all    = array();

		$this->applied_all = $this->build_records_from_definitions( $packs, self::SOURCE_APPLIED, null );
		foreach ( $this->applied_all as $record ) {
			$key = isset( $record['pack_key'] ) && is_string( $record['pack_key'] ) ? $record['pack_key'] : '';
			if ( $key === '' ) {
				continue;
			}
			$this->effective_by_key[ $key ] = $record;
		}

		// Deterministic: keep insertion order from $packs after validation/dup-skipping.
		$this->effective_all = $this->applied_all;
	}

	/**
	 * Returns whether the registry has any effective packs.
	 *
	 * @return bool
	 */
	public function has_any(): bool {
		return $this->effective_all !== array();
	}

	/**
	 * Returns pack definition by industry_key, or null if not found.
	 *
	 * @param string $industry_key Industry pack key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $pack_key ): ?array {
		$key = trim( $pack_key );
		return $this->effective_by_key[ $key ] ?? null;
	}

	/**
	 * Returns all effective pack records.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function all(): array {
		return $this->effective_all;
	}

	/**
	 * Builtin pack records.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function builtin(): array {
		return $this->builtin_all;
	}

	/**
	 * Applied bundle overlay pack records.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function applied(): array {
		return $this->applied_all;
	}

	/**
	 * Returns effective pack record for the given key.
	 */
	public function get_pack( string $pack_key ): ?array {
		return $this->get( $pack_key );
	}

	/**
	 * Whether the pack is active (not disabled by toggle controller, when available).
	 */
	public function is_active( string $pack_key ): bool {
		$key = trim( $pack_key );
		if ( $key === '' ) {
			return false;
		}
		if ( $this->toggle_controller instanceof \AIOPageBuilder\Admin\Screens\Industry\Industry_Pack_Toggle_Controller ) {
			return $this->toggle_controller->is_pack_active( $key );
		}
		return true;
	}

	/**
	 * Returns deterministic merge order descriptors.
	 *
	 * @return list<array{layer: string, refs: list<string>}>
	 */
	public function merge_order(): array {
		$applied = $this->get_applied_bundle_ids();
		return array(
			array( 'layer' => 'builtin', 'refs' => array() ),
			array( 'layer' => 'applied', 'refs' => $applied ),
		);
	}

	/**
	 * Back-compat: previous API returned raw pack definitions.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_all(): array {
		$defs = array();
		foreach ( $this->effective_all as $record ) {
			if ( isset( $record['payload_ref'] ) && is_array( $record['payload_ref'] ) && isset( $record['payload_ref']['definition'] ) && is_array( $record['payload_ref']['definition'] ) ) {
				$defs[] = $record['payload_ref']['definition'];
			}
		}
		return $defs;
	}

	/**
	 * Back-compat: previous API list_by_status filtered raw definitions.
	 *
	 * @param string $status One of Industry_Pack_Schema::STATUS_*.
	 * @return list<array<string, mixed>>
	 */
	public function list_by_status( string $status ): array {
		$out = array();
		foreach ( $this->effective_all as $record ) {
			$s = isset( $record['status'] ) && is_string( $record['status'] ) ? $record['status'] : '';
			if ( $s === $status && isset( $record['payload_ref']['definition'] ) && is_array( $record['payload_ref']['definition'] ) ) {
				$out[] = $record['payload_ref']['definition'];
			}
		}
		return $out;
	}

	/**
	 * Back-compat: version metadata from raw definition.
	 *
	 * @return array{version_marker?: string, status?: string}|null
	 */
	public function get_version_metadata( string $industry_key ): ?array {
		$record = $this->get( $industry_key );
		if ( $record === null ) {
			return null;
		}
		$def = isset( $record['payload_ref']['definition'] ) && is_array( $record['payload_ref']['definition'] ) ? $record['payload_ref']['definition'] : array();
		return array(
			'version_marker' => isset( $def[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] ) && is_string( $def[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] )
				? $def[ Industry_Pack_Schema::FIELD_VERSION_MARKER ]
				: '',
			'status'         => isset( $def[ Industry_Pack_Schema::FIELD_STATUS ] ) && is_string( $def[ Industry_Pack_Schema::FIELD_STATUS ] )
				? $def[ Industry_Pack_Schema::FIELD_STATUS ]
				: '',
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $definitions
	 * @return list<array<string, mixed>>
	 */
	private function build_records_from_definitions( array $definitions, string $source_type, ?string $bundle_id ): array {
		$out    = array();
		$result = $this->validator->validate_bulk( $definitions );
		foreach ( $result['valid'] as $pack ) {
			$pack_key = isset( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
				? trim( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
				: '';
			if ( $pack_key === '' ) {
				continue;
			}
			$version_marker = isset( $pack[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] )
				? $pack[ Industry_Pack_Schema::FIELD_VERSION_MARKER ]
				: '';

			// Merge the schema definition fields at the top level so both unit-test styles can access them.
			$out[] = array_merge(
				$pack,
				array(
					'pack_key'    => $pack_key,
					'version'     => $version_marker,
					'source_type' => $source_type,
					'conflicts'   => array(),
					'payload_ref' => array(
						'bundle_id'   => $bundle_id,
						'definition'  => $pack,
						'source_hint' => $source_type === self::SOURCE_BUILTIN ? 'builtin' : 'applied_bundle',
					),
				)
			);
		}
		return $out;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function build_applied_records(): array {
		$records = array();
		foreach ( $this->get_applied_bundle_ids() as $bundle_id ) {
			$payload_key = 'aio_pb_industry_bundle_payload_' . sanitize_key( $bundle_id );
			$payload     = get_option( $payload_key, array() );
			if ( ! is_array( $payload ) ) {
				continue;
			}
			$packs = $payload[ \AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Bundle_Service::PAYLOAD_PACKS ] ?? array();
			if ( ! is_array( $packs ) ) {
				continue;
			}
			$records = array_merge( $records, $this->build_records_from_definitions( $packs, self::SOURCE_APPLIED, $bundle_id ) );
		}
		return $records;
	}

	/**
	 * @return list<string>
	 */
	private function get_applied_bundle_ids(): array {
		if ( ! $this->settings instanceof \AIOPageBuilder\Infrastructure\Settings\Settings_Service ) {
			return array();
		}
		$merge_state = $this->settings->get( \AIOPageBuilder\Infrastructure\Config\Option_Names::PB_INDUSTRY_BUNDLE_MERGE_STATE );
		$apply_order = isset( $merge_state['apply_order'] ) && is_array( $merge_state['apply_order'] ) ? $merge_state['apply_order'] : array();
		$out         = array();
		foreach ( $apply_order as $id ) {
			if ( is_string( $id ) && trim( $id ) !== '' ) {
				$out[] = trim( $id );
			}
		}
		return array_values( array_unique( $out ) );
	}
}
