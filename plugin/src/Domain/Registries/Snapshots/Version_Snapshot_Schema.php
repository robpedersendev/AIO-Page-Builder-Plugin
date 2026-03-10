<?php
/**
 * Canonical version snapshot object schema (spec §10.8, §58.4–58.5, version-snapshot-schema.md).
 * Consumed by future snapshot capture and query services; no capture logic here.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Snapshots;

defined( 'ABSPATH' ) || exit;

/**
 * Snapshot scope types, required/optional field names, status values.
 * Snapshots are preserved records for traceability and reproducibility.
 */
final class Version_Snapshot_Schema {

	/** Template registry state (section/page templates, compositions index). */
	public const SCOPE_REGISTRY = 'registry';

	/** Schema definition snapshot (section registry, page template, profile, table manifest). */
	public const SCOPE_SCHEMA = 'schema';

	/** Compatibility rules or state at a point in time. */
	public const SCOPE_COMPATIBILITY = 'compatibility';

	/** Build plan or execution context. */
	public const SCOPE_BUILD_CONTEXT = 'build_context';

	/** Prompt pack state at a version (future). */
	public const SCOPE_PROMPT_PACK = 'prompt_pack';

	/** Required: stable snapshot identifier. */
	public const FIELD_SNAPSHOT_ID = 'snapshot_id';

	/** Required: scope type (what kind of state is preserved). */
	public const FIELD_SCOPE_TYPE = 'scope_type';

	/** Required: scope identifier. */
	public const FIELD_SCOPE_ID = 'scope_id';

	/** Required: creation datetime (ISO 8601). */
	public const FIELD_CREATED_AT = 'created_at';

	/** Required: schema version. */
	public const FIELD_SCHEMA_VERSION = 'schema_version';

	/** Required: lifecycle status (active | superseded). */
	public const FIELD_STATUS = 'status';

	/** Optional: reference to payload storage. */
	public const FIELD_PAYLOAD_REF = 'payload_ref';

	/** Optional: preserved object references block. */
	public const FIELD_OBJECT_REFS = 'object_refs';

	/** Optional: version metadata block. */
	public const FIELD_VERSION_METADATA = 'version_metadata';

	/** Optional: provenance block. */
	public const FIELD_PROVENANCE = 'provenance';

	/** Optional: compatibility notes block. */
	public const FIELD_COMPATIBILITY_NOTES = 'compatibility_notes';

	/** Optional: eligible for diff/comparison. */
	public const FIELD_DIFF_ELIGIBILITY = 'diff_eligibility';

	/** Optional: export posture. */
	public const FIELD_EXPORTABILITY = 'exportability';

	/** Optional: retention policy notes. */
	public const FIELD_RETENTION_NOTES = 'retention_notes';

	/** Status: current snapshot for scope. */
	public const STATUS_ACTIVE = 'active';

	/** Status: superseded by a newer snapshot. */
	public const STATUS_SUPERSEDED = 'superseded';

	/** @var list<string> Required field names. */
	private static ?array $required_fields = null;

	/** @var list<string> Optional field names. */
	private static ?array $optional_fields = null;

	/** @var list<string> Allowed scope types. */
	private static ?array $scope_types = null;

	/** @var list<string> Allowed status values. */
	private static ?array $statuses = null;

	/**
	 * Returns required field names.
	 *
	 * @return list<string>
	 */
	public static function get_required_fields(): array {
		if ( self::$required_fields !== null ) {
			return self::$required_fields;
		}
		self::$required_fields = array(
			self::FIELD_SNAPSHOT_ID,
			self::FIELD_SCOPE_TYPE,
			self::FIELD_SCOPE_ID,
			self::FIELD_CREATED_AT,
			self::FIELD_SCHEMA_VERSION,
			self::FIELD_STATUS,
		);
		return self::$required_fields;
	}

	/**
	 * Returns optional field names.
	 *
	 * @return list<string>
	 */
	public static function get_optional_fields(): array {
		if ( self::$optional_fields !== null ) {
			return self::$optional_fields;
		}
		self::$optional_fields = array(
			self::FIELD_PAYLOAD_REF,
			self::FIELD_OBJECT_REFS,
			self::FIELD_VERSION_METADATA,
			self::FIELD_PROVENANCE,
			self::FIELD_COMPATIBILITY_NOTES,
			self::FIELD_DIFF_ELIGIBILITY,
			self::FIELD_EXPORTABILITY,
			self::FIELD_RETENTION_NOTES,
		);
		return self::$optional_fields;
	}

	/**
	 * Returns allowed scope_type values (spec §10.8).
	 *
	 * @return list<string>
	 */
	public static function get_scope_types(): array {
		if ( self::$scope_types !== null ) {
			return self::$scope_types;
		}
		self::$scope_types = array(
			self::SCOPE_REGISTRY,
			self::SCOPE_SCHEMA,
			self::SCOPE_COMPATIBILITY,
			self::SCOPE_BUILD_CONTEXT,
			self::SCOPE_PROMPT_PACK,
		);
		return self::$scope_types;
	}

	/**
	 * Returns whether the given scope_type is allowed.
	 *
	 * @param string $scope_type Scope type value.
	 * @return bool
	 */
	public static function is_valid_scope_type( string $scope_type ): bool {
		return in_array( $scope_type, self::get_scope_types(), true );
	}

	/**
	 * Returns allowed status values (object-model §3.8).
	 *
	 * @return list<string>
	 */
	public static function get_statuses(): array {
		if ( self::$statuses !== null ) {
			return self::$statuses;
		}
		self::$statuses = array(
			self::STATUS_ACTIVE,
			self::STATUS_SUPERSEDED,
		);
		return self::$statuses;
	}

	/**
	 * Returns whether the given status is allowed.
	 *
	 * @param string $status Status value.
	 * @return bool
	 */
	public static function is_valid_status( string $status ): bool {
		return in_array( $status, self::get_statuses(), true );
	}

	/** Max length for snapshot_id. */
	public const SNAPSHOT_ID_MAX_LENGTH = 64;

	/** Max length for scope_id. */
	public const SCOPE_ID_MAX_LENGTH = 128;
}
