<?php
/**
 * Builds stable export-ready fragments for registry-owned objects (spec §52.2, §52.3, §52.4, §52.6).
 * Produces deterministic array/JSON-ready structures. Strips prohibited fields.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Export;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\Documentation\Documentation_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Snapshots\Version_Snapshot_Schema;
use AIOPageBuilder\Infrastructure\Config\Versions;

/**
 * Fragment shape: export_schema_version, object_type, object_key, object_status, object_version,
 * payload, relationships, deprecation, source_metadata (spec §12).
 */
final class Registry_Export_Fragment_Builder {

	/** Fragment keys (stable). */
	public const KEY_EXPORT_SCHEMA_VERSION = 'export_schema_version';
	public const KEY_OBJECT_TYPE           = 'object_type';
	public const KEY_OBJECT_KEY            = 'object_key';
	public const KEY_OBJECT_STATUS         = 'object_status';
	public const KEY_OBJECT_VERSION        = 'object_version';
	public const KEY_PAYLOAD               = 'payload';
	public const KEY_RELATIONSHIPS         = 'relationships';
	public const KEY_DEPRECATION           = 'deprecation';
	public const KEY_SOURCE_METADATA       = 'source_metadata';

	/** Object type identifiers. */
	public const OBJECT_TYPE_SECTION       = 'section_template';
	public const OBJECT_TYPE_PAGE          = 'page_template';
	public const OBJECT_TYPE_COMPOSITION   = 'composition';
	public const OBJECT_TYPE_DOCUMENTATION = 'documentation';
	public const OBJECT_TYPE_SNAPSHOT      = 'version_snapshot';

	/** Prohibited field names (spec §52.6): secrets, credentials, runtime data. */
	private const PROHIBITED_KEYS = array(
		'api_key',
		'api_secret',
		'password',
		'auth_token',
		'session_token',
		'secret',
		'credential',
		'transient',
		'cache_key',
		'runtime_lock',
		'lock_row',
		'corrupted',
	);

	/**
	 * Builds fragment for section template definition.
	 *
	 * @param array<string, mixed> $definition Normalized section definition.
	 * @return array<string, mixed>
	 */
	public static function for_section( array $definition ): array {
		$key     = (string) ( $definition[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$status  = (string) ( $definition[ Section_Schema::FIELD_STATUS ] ?? 'draft' );
		$version = $definition[ Section_Schema::FIELD_VERSION ] ?? array();
		$payload = self::sanitize_payload( $definition );
		$rels    = self::section_relationships( $definition );
		$dep     = self::extract_deprecation( $definition );
		$meta    = array(
			'schema'  => 'section_registry',
			'version' => (string) ( $version['version'] ?? '1' ),
		);
		return self::build( self::OBJECT_TYPE_SECTION, $key, $status, $version, $payload, $rels, $dep, $meta );
	}

	/**
	 * Builds fragment for page template definition.
	 *
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed>
	 */
	public static function for_page_template( array $definition ): array {
		$key     = (string) ( $definition[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$status  = (string) ( $definition[ Page_Template_Schema::FIELD_STATUS ] ?? 'draft' );
		$version = $definition[ Page_Template_Schema::FIELD_VERSION ] ?? array();
		$payload = self::sanitize_payload( $definition );
		$rels    = self::page_template_relationships( $definition );
		$dep     = self::extract_deprecation( $definition );
		$meta    = array(
			'schema'  => 'page_template_registry',
			'version' => (string) ( $version['version'] ?? '1' ),
		);
		return self::build( self::OBJECT_TYPE_PAGE, $key, $status, $version, $payload, $rels, $dep, $meta );
	}

	/**
	 * Builds fragment for composition definition.
	 *
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed>
	 */
	public static function for_composition( array $definition ): array {
		$key     = (string) ( $definition[ Composition_Schema::FIELD_COMPOSITION_ID ] ?? '' );
		$status  = (string) ( $definition[ Composition_Schema::FIELD_STATUS ] ?? 'draft' );
		$version = array( 'version' => '1' );
		$payload = self::sanitize_payload( $definition );
		$rels    = self::composition_relationships( $definition );
		$dep     = array();
		$meta    = array(
			'schema'            => 'composition',
			'validation_status' => (string) ( $definition[ Composition_Schema::FIELD_VALIDATION_STATUS ] ?? '' ),
		);
		return self::build( self::OBJECT_TYPE_COMPOSITION, $key, $status, $version, $payload, $rels, $dep, $meta );
	}

	/**
	 * Builds fragment for documentation object.
	 *
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed>
	 */
	public static function for_documentation( array $definition ): array {
		$key     = (string) ( $definition[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '' );
		$status  = (string) ( $definition[ Documentation_Schema::FIELD_STATUS ] ?? 'draft' );
		$version = array( 'version' => (string) ( $definition[ Documentation_Schema::FIELD_VERSION_MARKER ] ?? '1' ) );
		$payload = self::sanitize_payload( $definition );
		$rels    = self::documentation_relationships( $definition );
		$dep     = array();
		$meta    = array(
			'schema' => 'documentation',
			'type'   => (string) ( $definition[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ?? '' ),
		);
		return self::build( self::OBJECT_TYPE_DOCUMENTATION, $key, $status, $version, $payload, $rels, $dep, $meta );
	}

	/**
	 * Builds fragment for version snapshot definition.
	 *
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed>
	 */
	public static function for_snapshot( array $definition ): array {
		$key     = (string) ( $definition[ Version_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '' );
		$status  = (string) ( $definition[ Version_Snapshot_Schema::FIELD_STATUS ] ?? 'active' );
		$version = array( 'schema_version' => (string) ( $definition[ Version_Snapshot_Schema::FIELD_SCHEMA_VERSION ] ?? '1' ) );
		$payload = self::sanitize_payload( $definition );
		$rels    = self::snapshot_relationships( $definition );
		$dep     = array();
		$meta    = array(
			'schema'     => 'version_snapshot',
			'scope_type' => (string) ( $definition[ Version_Snapshot_Schema::FIELD_SCOPE_TYPE ] ?? '' ),
		);
		return self::build( self::OBJECT_TYPE_SNAPSHOT, $key, $status, $version, $payload, $rels, $dep, $meta );
	}

	/**
	 * @param string               $object_type
	 * @param string               $object_key
	 * @param string               $object_status
	 * @param array<string, mixed> $object_version
	 * @param array<string, mixed> $payload
	 * @param array<string, mixed> $relationships
	 * @param array<string, mixed> $deprecation
	 * @param array<string, mixed> $source_metadata
	 * @return array<string, mixed>
	 */
	private static function build(
		string $object_type,
		string $object_key,
		string $object_status,
		array $object_version,
		array $payload,
		array $relationships,
		array $deprecation,
		array $source_metadata
	): array {
		$version_str = (string) ( $object_version['version'] ?? $object_version['schema_version'] ?? '1' );
		return array(
			self::KEY_EXPORT_SCHEMA_VERSION => Versions::export_schema(),
			self::KEY_OBJECT_TYPE           => $object_type,
			self::KEY_OBJECT_KEY            => $object_key,
			self::KEY_OBJECT_STATUS         => $object_status,
			self::KEY_OBJECT_VERSION        => $version_str,
			self::KEY_PAYLOAD               => $payload,
			self::KEY_RELATIONSHIPS         => $relationships,
			self::KEY_DEPRECATION           => $deprecation,
			self::KEY_SOURCE_METADATA       => $source_metadata,
		);
	}

	/**
	 * Strips prohibited fields from payload (spec §52.6).
	 *
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	public static function sanitize_payload( array $data ): array {
		$out = array();
		foreach ( $data as $k => $v ) {
			$lower   = strtolower( (string) $k );
			$blocked = false;
			foreach ( self::PROHIBITED_KEYS as $prohibited ) {
				if ( strpos( $lower, $prohibited ) !== false ) {
					$blocked = true;
					break;
				}
			}
			if ( $blocked ) {
				continue;
			}
			if ( is_array( $v ) && ! self::is_list_of_primitives( $v ) ) {
				$out[ $k ] = self::sanitize_payload( $v );
			} else {
				$out[ $k ] = $v;
			}
		}
		return $out;
	}

	private static function is_list_of_primitives( array $arr ): bool {
		if ( $arr === array() ) {
			return true;
		}
		$keys = array_keys( $arr );
		if ( array_keys( range( 0, count( $arr ) - 1 ) ) !== $keys ) {
			return false;
		}
		foreach ( $arr as $v ) {
			if ( is_array( $v ) ) {
				return false;
			}
		}
		return true;
	}

	/** @param array<string, mixed> $definition */
	private static function section_relationships( array $definition ): array {
		return array(
			'section_refs'     => array(),
			'helper_ref'       => (string) ( $definition[ Section_Schema::FIELD_HELPER_REF ] ?? '' ),
			'css_contract_ref' => (string) ( $definition[ Section_Schema::FIELD_CSS_CONTRACT_REF ] ?? '' ),
		);
	}

	/** @param array<string, mixed> $definition */
	private static function page_template_relationships( array $definition ): array {
		$ordered      = $definition[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
		$section_keys = array();
		foreach ( (array) $ordered as $item ) {
			if ( is_array( $item ) ) {
				$sk = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
				if ( $sk !== '' ) {
					$section_keys[] = $sk;
				}
			}
		}
		return array( 'section_keys' => array_values( array_unique( $section_keys ) ) );
	}

	/** @param array<string, mixed> $definition */
	private static function composition_relationships( array $definition ): array {
		$ordered      = $definition[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ] ?? array();
		$section_keys = array();
		foreach ( (array) $ordered as $item ) {
			if ( is_array( $item ) ) {
				$sk = (string) ( $item[ Composition_Schema::SECTION_ITEM_KEY ] ?? '' );
				if ( $sk !== '' ) {
					$section_keys[] = $sk;
				}
			}
		}
		return array(
			'section_keys'          => array_values( array_unique( $section_keys ) ),
			'source_template_ref'   => (string) ( $definition[ Composition_Schema::FIELD_SOURCE_TEMPLATE_REF ] ?? '' ),
			'helper_one_pager_ref'  => (string) ( $definition[ Composition_Schema::FIELD_HELPER_ONE_PAGER_REF ] ?? '' ),
			'registry_snapshot_ref' => (string) ( $definition[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] ?? '' ),
		);
	}

	/** @param array<string, mixed> $definition */
	private static function documentation_relationships( array $definition ): array {
		$ref = $definition[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
		$ref = is_array( $ref ) ? $ref : array();
		return array( 'source_reference' => $ref );
	}

	/** @param array<string, mixed> $definition */
	private static function snapshot_relationships( array $definition ): array {
		$refs = $definition[ Version_Snapshot_Schema::FIELD_OBJECT_REFS ] ?? array();
		$refs = is_array( $refs ) ? $refs : array();
		return array( 'object_refs' => $refs );
	}

	/** @param array<string, mixed> $definition */
	private static function extract_deprecation( array $definition ): array {
		$dep = $definition['deprecation'] ?? array();
		if ( ! is_array( $dep ) ) {
			return array();
		}
		$out  = array();
		$safe = array( 'deprecated', 'reason', 'replacement_section_key', 'replacement_template_key', 'replacement_key', 'deprecated_at', 'deprecated_reason', 'eligible_for_new_use', 'historical_reference_allowed' );
		foreach ( $safe as $k ) {
			if ( array_key_exists( $k, $dep ) ) {
				$out[ $k ] = $dep[ $k ];
			}
		}
		return $out;
	}
}
