<?php
/**
 * Object type → allowed status families for validation and repository behavior (spec §10.10, §10.11).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Objects;

defined( 'ABSPATH' ) || exit;

/**
 * Maps each object type to its allowed status set. Implementation (not a shell): status sets are
 * authoritative for validation and repository behavior. Custom status registration (e.g. register_post_status)
 * is handled in bootstrap, not in this class. Lifecycle transition rules remain in object-model-schema.md §5.
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

	/** AI chat session (template-lab UX); not AI run workflow. */
	public const CHAT_SESSION_LIFECYCLE = array( 'active', 'idle', 'archived', 'closed' );

	/**
	 * Object type (Object_Type_Keys constant) → list of allowed status slugs.
	 * Custom status registration is managed by the bootstrap layer; these families are the authoritative status set for validation.
	 *
	 * @var array<string, array<int, string>>
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
		Object_Type_Keys::AI_CHAT_SESSION  => self::CHAT_SESSION_LIFECYCLE,
	);

	/**
	 * Returns allowed status slugs for an object type.
	 *
	 * @param string $post_type One of Object_Type_Keys constants.
	 * @return array<int, string> Empty if unknown.
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
