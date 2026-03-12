<?php
/**
 * Operational snapshot schema constants and validation (spec §41.1–41.3, §41.8, §11.5; operational-snapshot-schema.md).
 *
 * Used by future capture, diff, and rollback services. No capture or rollback logic in this file.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Snapshots;

defined( 'ABSPATH' ) || exit;

/**
 * Snapshot type families and object scopes for execution/diff/rollback snapshots.
 */
final class Operational_Snapshot_Schema {

	/** Snapshot type: state before an execution action (pre-change). */
	public const SNAPSHOT_TYPE_PRE_CHANGE = 'pre_change';

	/** Snapshot type: state or result after an execution action (post-change). */
	public const SNAPSHOT_TYPE_POST_CHANGE = 'post_change';

	/** Object family: page post (content, title, slug, status). */
	public const OBJECT_FAMILY_PAGE = 'page';

	/** Object family: page metadata (SEO, custom fields). */
	public const OBJECT_FAMILY_PAGE_METADATA = 'page_metadata';

	/** Object family: hierarchy (parent/child page assignments). */
	public const OBJECT_FAMILY_HIERARCHY = 'hierarchy';

	/** Object family: menu and menu items. */
	public const OBJECT_FAMILY_MENU = 'menu';

	/** Object family: design token set. */
	public const OBJECT_FAMILY_TOKEN_SET = 'token_set';

	/** Object family: Build Plan state transition. */
	public const OBJECT_FAMILY_BUILD_PLAN_TRANSITION = 'build_plan_transition';

	/** Retention class: short (e.g. 7 days). */
	public const RETENTION_CLASS_SHORT = 'short';

	/** Retention class: medium (e.g. 30 days). */
	public const RETENTION_CLASS_MEDIUM = 'medium';

	/** Retention class: long (e.g. 90 days). */
	public const RETENTION_CLASS_LONG = 'long';

	/** Retention class: tied to plan lifecycle. */
	public const RETENTION_CLASS_PLAN_LINKED = 'plan_linked';

	/** Retention class: user-managed cleanup. */
	public const RETENTION_CLASS_USER_MANAGED = 'user_managed';

	/** Rollback status: not applicable or default. */
	public const ROLLBACK_STATUS_NONE = 'none';

	/** Rollback status: available for rollback. */
	public const ROLLBACK_STATUS_AVAILABLE = 'available';

	/** Rollback status: rollback was used. */
	public const ROLLBACK_STATUS_USED = 'used';

	/** Rollback status: expired (retention). */
	public const ROLLBACK_STATUS_EXPIRED = 'expired';

	/** Rollback status: invalidated (e.g. target gone). */
	public const ROLLBACK_STATUS_INVALIDATED = 'invalidated';

	// --- Root fields ---

	public const FIELD_SNAPSHOT_ID       = 'snapshot_id';
	public const FIELD_SNAPSHOT_TYPE     = 'snapshot_type';
	public const FIELD_OBJECT_FAMILY    = 'object_family';
	public const FIELD_TARGET_REF       = 'target_ref';
	public const FIELD_CREATED_AT       = 'created_at';
	public const FIELD_SCHEMA_VERSION   = 'schema_version';
	public const FIELD_PAYLOAD_REF      = 'payload_ref';
	public const FIELD_SCOPE_OBJECTS     = 'scope_objects';
	public const FIELD_EXECUTION_REF    = 'execution_ref';
	public const FIELD_JOB_REF          = 'job_ref';
	public const FIELD_BUILD_PLAN_REF   = 'build_plan_ref';
	public const FIELD_PLAN_ITEM_REF    = 'plan_item_ref';
	public const FIELD_ACTION_TYPE      = 'action_type';
	public const FIELD_RETENTION        = 'retention';
	public const FIELD_ROLLBACK_ELIGIBLE = 'rollback_eligible';
	public const FIELD_ROLLBACK_STATUS  = 'rollback_status';
	public const FIELD_PROVENANCE       = 'provenance';
	public const FIELD_PRE_CHANGE      = 'pre_change';
	public const FIELD_POST_CHANGE     = 'post_change';

	/** @var list<string> Required root field names. */
	private static ?array $required_root_fields = null;

	/** @var list<string> Allowed snapshot types. */
	private static ?array $snapshot_types = null;

	/** @var list<string> Allowed object families. */
	private static ?array $object_families = null;

	/** @var list<string> Allowed retention classes. */
	private static ?array $retention_classes = null;

	/** @var list<string> Allowed rollback status values. */
	private static ?array $rollback_statuses = null;

	/**
	 * Returns required root field names (operational-snapshot-schema.md §4.1).
	 *
	 * @return list<string>
	 */
	public static function get_required_root_fields(): array {
		if ( self::$required_root_fields !== null ) {
			return self::$required_root_fields;
		}
		self::$required_root_fields = array(
			self::FIELD_SNAPSHOT_ID,
			self::FIELD_SNAPSHOT_TYPE,
			self::FIELD_OBJECT_FAMILY,
			self::FIELD_TARGET_REF,
			self::FIELD_CREATED_AT,
			self::FIELD_SCHEMA_VERSION,
		);
		return self::$required_root_fields;
	}

	/**
	 * Returns allowed snapshot_type values.
	 *
	 * @return list<string>
	 */
	public static function get_snapshot_types(): array {
		if ( self::$snapshot_types !== null ) {
			return self::$snapshot_types;
		}
		self::$snapshot_types = array(
			self::SNAPSHOT_TYPE_PRE_CHANGE,
			self::SNAPSHOT_TYPE_POST_CHANGE,
		);
		return self::$snapshot_types;
	}

	/**
	 * Returns whether the given snapshot_type is allowed.
	 *
	 * @param string $snapshot_type Snapshot type value.
	 * @return bool
	 */
	public static function is_valid_snapshot_type( string $snapshot_type ): bool {
		return in_array( $snapshot_type, self::get_snapshot_types(), true );
	}

	/**
	 * Returns allowed object_family values (spec §41.1).
	 *
	 * @return list<string>
	 */
	public static function get_object_families(): array {
		if ( self::$object_families !== null ) {
			return self::$object_families;
		}
		self::$object_families = array(
			self::OBJECT_FAMILY_PAGE,
			self::OBJECT_FAMILY_PAGE_METADATA,
			self::OBJECT_FAMILY_HIERARCHY,
			self::OBJECT_FAMILY_MENU,
			self::OBJECT_FAMILY_TOKEN_SET,
			self::OBJECT_FAMILY_BUILD_PLAN_TRANSITION,
		);
		return self::$object_families;
	}

	/**
	 * Returns whether the given object_family is allowed.
	 *
	 * @param string $object_family Object family value.
	 * @return bool
	 */
	public static function is_valid_object_family( string $object_family ): bool {
		return in_array( $object_family, self::get_object_families(), true );
	}

	/**
	 * Returns allowed retention_class values (spec §41.8).
	 *
	 * @return list<string>
	 */
	public static function get_retention_classes(): array {
		if ( self::$retention_classes !== null ) {
			return self::$retention_classes;
		}
		self::$retention_classes = array(
			self::RETENTION_CLASS_SHORT,
			self::RETENTION_CLASS_MEDIUM,
			self::RETENTION_CLASS_LONG,
			self::RETENTION_CLASS_PLAN_LINKED,
			self::RETENTION_CLASS_USER_MANAGED,
		);
		return self::$retention_classes;
	}

	/**
	 * Returns allowed rollback_status values (spec §11.5).
	 *
	 * @return list<string>
	 */
	public static function get_rollback_statuses(): array {
		if ( self::$rollback_statuses !== null ) {
			return self::$rollback_statuses;
		}
		self::$rollback_statuses = array(
			self::ROLLBACK_STATUS_NONE,
			self::ROLLBACK_STATUS_AVAILABLE,
			self::ROLLBACK_STATUS_USED,
			self::ROLLBACK_STATUS_EXPIRED,
			self::ROLLBACK_STATUS_INVALIDATED,
		);
		return self::$rollback_statuses;
	}

	/**
	 * Validates root-level required fields and type enums. Does not validate pre_change/post_change blocks.
	 *
	 * @param array<string, mixed> $snapshot Snapshot root.
	 * @return array<int, array{code: string, field?: string}> Empty if valid; list of errors otherwise.
	 */
	public static function validate_root( array $snapshot ): array {
		$errors = array();
		foreach ( self::get_required_root_fields() as $field ) {
			$val = $snapshot[ $field ] ?? null;
			if ( $val === null || ( is_string( $val ) && trim( $val ) === '' ) ) {
				$errors[] = array( 'code' => 'missing_required', 'field' => $field );
			}
		}
		$type = isset( $snapshot[ self::FIELD_SNAPSHOT_TYPE ] ) && is_string( $snapshot[ self::FIELD_SNAPSHOT_TYPE ] ) ? $snapshot[ self::FIELD_SNAPSHOT_TYPE ] : '';
		if ( $type !== '' && ! self::is_valid_snapshot_type( $type ) ) {
			$errors[] = array( 'code' => 'invalid_snapshot_type', 'field' => self::FIELD_SNAPSHOT_TYPE );
		}
		$family = isset( $snapshot[ self::FIELD_OBJECT_FAMILY ] ) && is_string( $snapshot[ self::FIELD_OBJECT_FAMILY ] ) ? $snapshot[ self::FIELD_OBJECT_FAMILY ] : '';
		if ( $family !== '' && ! self::is_valid_object_family( $family ) ) {
			$errors[] = array( 'code' => 'invalid_object_family', 'field' => self::FIELD_OBJECT_FAMILY );
		}
		$st = isset( $snapshot[ self::FIELD_ROLLBACK_STATUS ] ) && is_string( $snapshot[ self::FIELD_ROLLBACK_STATUS ] ) ? $snapshot[ self::FIELD_ROLLBACK_STATUS ] : '';
		if ( $st !== '' && ! in_array( $st, self::get_rollback_statuses(), true ) ) {
			$errors[] = array( 'code' => 'invalid_rollback_status', 'field' => self::FIELD_ROLLBACK_STATUS );
		}
		$pre = $snapshot[ self::FIELD_PRE_CHANGE ] ?? null;
		$post = $snapshot[ self::FIELD_POST_CHANGE ] ?? null;
		if ( $type === self::SNAPSHOT_TYPE_PRE_CHANGE && ( ! is_array( $pre ) || empty( $pre ) ) ) {
			$errors[] = array( 'code' => 'missing_pre_change_block', 'field' => self::FIELD_PRE_CHANGE );
		}
		if ( $type === self::SNAPSHOT_TYPE_POST_CHANGE && ( ! is_array( $post ) || empty( $post ) ) ) {
			$errors[] = array( 'code' => 'missing_post_change_block', 'field' => self::FIELD_POST_CHANGE );
		}
		return $errors;
	}

	/** Max length for snapshot_id. */
	public const SNAPSHOT_ID_MAX_LENGTH = 64;

	/** Max length for target_ref. */
	public const TARGET_REF_MAX_LENGTH = 256;
}
