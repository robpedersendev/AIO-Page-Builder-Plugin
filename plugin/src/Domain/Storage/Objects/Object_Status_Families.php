<?php
/**
 * Object type → status families and later custom-status attachment point (spec §10.10, §10.11).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Objects;

defined( 'ABSPATH' ) || exit;

/**
 * Maps each object type to its allowed status set. Implementation (not a shell): status sets are
 * authoritative for validation and repository behavior. Custom status registration (e.g. register_post_status)
 * is not implemented here; this class provides the status sets for validation and for later attachment
 * of status registration. Lifecycle transition rules remain in object-model-schema.md §5.
 */
final class Object_Status_Families {

	/** Section Template, Page Template, Prompt Pack. */
	public const DRAFT_ACTIVE_INACTIVE_DEPRECATED = array( 'draft', 'active', 'inactive', 'deprecated' );

	/** Composition, Documentation. */
	public const DRAFT_ACTIVE_ARCHIVED = array( 'draft', 'active', 'archived' );

	/** Build Plan. */
	public const PLAN_WORKFLOW = array( 'pending_review', 'approved', 'rejected', 'in_progress', 'completed', 'superseded' );

	/** AI Run. */
	public const AI_RUN_WORKFLOW = array( 'pending_generation', 'completed', 'failed_validation', 'failed' );

	/** Version Snapshot. */
	public const SNAPSHOT_WORKFLOW = array( 'active', 'superseded' );

	/**
	 * Object type (Object_Type_Keys constant) → list of allowed status slugs.
	 * Custom statuses are not registered in this prompt; register_post_status (or equivalent) will be
	 * attached in a later prompt using these families.
	 *
	 * @var array<string, list<string>>
	 */
	private const FAMILIES = array(
		Object_Type_Keys::SECTION_TEMPLATE => self::DRAFT_ACTIVE_INACTIVE_DEPRECATED,
		Object_Type_Keys::PAGE_TEMPLATE    => self::DRAFT_ACTIVE_INACTIVE_DEPRECATED,
		Object_Type_Keys::COMPOSITION      => self::DRAFT_ACTIVE_ARCHIVED,
		Object_Type_Keys::BUILD_PLAN       => self::PLAN_WORKFLOW,
		Object_Type_Keys::AI_RUN           => self::AI_RUN_WORKFLOW,
		Object_Type_Keys::PROMPT_PACK      => self::DRAFT_ACTIVE_INACTIVE_DEPRECATED,
		Object_Type_Keys::DOCUMENTATION    => self::DRAFT_ACTIVE_ARCHIVED,
		Object_Type_Keys::VERSION_SNAPSHOT => self::SNAPSHOT_WORKFLOW,
	);

	/**
	 * Returns allowed status slugs for an object type.
	 *
	 * @param string $post_type One of Object_Type_Keys constants.
	 * @return list<string> Empty if unknown.
	 */
	public static function get_statuses_for( string $post_type ): array {
		return self::FAMILIES[ $post_type ] ?? array();
	}

	/**
	 * Returns whether a status is valid for the given object type.
	 *
	 * @param string $post_type Post type key.
	 * @param string $status    Status slug.
	 * @return bool
	 */
	public static function is_valid_status( string $post_type, string $status ): bool {
		$allowed = self::get_statuses_for( $post_type );
		return in_array( $status, $allowed, true );
	}
}
