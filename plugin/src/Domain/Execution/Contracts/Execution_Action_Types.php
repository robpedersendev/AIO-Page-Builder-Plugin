<?php
/**
 * Execution action type enum (spec §39, §40.1; execution-action-contract.md §3).
 *
 * Stable action types for the execution engine. Executors must only accept these types.
 * No execution logic in this file.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Action types that the execution engine may perform. Governed by execution-action-contract.md.
 * ALL lists only types valid for execution in v1. Three types are excluded from ALL:
 * - UPDATE_PAGE_METADATA: recommendation-only (no mutation handler).
 * - ASSIGN_PAGE_HIERARCHY: hierarchy is applied inline during CREATE_PAGE via Template_Page_Build_Service; no standalone action needed.
 * - CREATE_MENU: menu creation is handled by UPDATE_MENU via Apply_Menu_Change_Handler / Menu_Change_Job_Service::do_create().
 */
final class Execution_Action_Types {

	public const CREATE_PAGE  = 'create_page';
	public const REPLACE_PAGE = 'replace_page';
	/** Not executable in v1; metadata is recommendation-only. Excluded from ALL. */
	public const UPDATE_PAGE_METADATA = 'update_page_metadata';
	/**
	 * Not executable as a standalone action in v1. Hierarchy assignment is embedded in CREATE_PAGE:
	 * Template_Page_Build_Service resolves post_parent from the plan item payload and sets it during page creation.
	 * The Build Plan hierarchy step generates advisory ITEM_TYPE_HIERARCHY_NOTE items, not executable envelopes.
	 * Excluded from ALL.
	 */
	public const ASSIGN_PAGE_HIERARCHY = 'assign_page_hierarchy';
	/**
	 * Not executable as a standalone action in v1. Menu creation is handled by UPDATE_MENU:
	 * Apply_Menu_Change_Handler delegates to Menu_Change_Job_Service::do_create() for new menus and
	 * ::do_replace() for replacements. No separate plan item type or UI surface emits create_menu envelopes.
	 * Excluded from ALL.
	 */
	public const CREATE_MENU           = 'create_menu';
	public const UPDATE_MENU           = 'update_menu';
	public const APPLY_TOKEN_SET       = 'apply_token_set';
	public const FINALIZE_PLAN         = 'finalize_plan';
	public const ROLLBACK_ACTION       = 'rollback_action';

	/**
	 * Action types valid for execution in v1.
	 * Excludes UPDATE_PAGE_METADATA (recommendation-only), ASSIGN_PAGE_HIERARCHY (inline in CREATE_PAGE),
	 * and CREATE_MENU (subsumed by UPDATE_MENU).
	 *
	 * @var array<int, string>
	 */
	public const ALL = array(
		self::CREATE_PAGE,
		self::REPLACE_PAGE,
		self::UPDATE_MENU,
		self::APPLY_TOKEN_SET,
		self::FINALIZE_PLAN,
		self::ROLLBACK_ACTION,
	);

	/**
	 * Returns whether the given string is a valid action type.
	 *
	 * @param string $action_type Candidate action type.
	 * @return bool
	 */
	public static function is_valid( string $action_type ): bool {
		return in_array( $action_type, self::ALL, true );
	}
}
