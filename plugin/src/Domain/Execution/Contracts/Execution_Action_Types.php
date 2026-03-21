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
 * ALL lists types valid for execution except UPDATE_PAGE_METADATA (recommendation-only; no dedicated write handler yet).
 * ASSIGN_PAGE_HIERARCHY and CREATE_MENU are executable via Assign_Page_Hierarchy_Handler and Create_Menu_Handler.
 */
final class Execution_Action_Types {

	public const CREATE_PAGE  = 'create_page';
	public const REPLACE_PAGE = 'replace_page';
	/** Not executable in v1; metadata is recommendation-only. Excluded from ALL. */
	public const UPDATE_PAGE_METADATA = 'update_page_metadata';
	/**
	 * Reassigns an existing page’s parent (`post_parent`) to a target page ID (or 0 for top-level).
	 * Handled by Assign_Page_Hierarchy_Handler after executor validation; used for hierarchy plan items
	 * and batch hierarchy execution from the Build Plan workspace.
	 */
	public const ASSIGN_PAGE_HIERARCHY = 'assign_page_hierarchy';
	/**
	 * Executable in v2 via Create_Menu_Handler. Net-new menu creation with optional location assignment
	 * and item seeding. Distinct from UPDATE_MENU (rename/replace/update_existing) flows.
	 * Triggered by ITEM_TYPE_MENU_NEW plan items.
	 */
	public const CREATE_MENU     = 'create_menu';
	public const UPDATE_MENU     = 'update_menu';
	public const APPLY_TOKEN_SET = 'apply_token_set';
	public const FINALIZE_PLAN   = 'finalize_plan';
	public const ROLLBACK_ACTION = 'rollback_action';

	/**
	 * Action types valid for execution. UPDATE_PAGE_METADATA is excluded (recommendation-only).
	 *
	 * @var array<int, string>
	 */
	public const ALL = array(
		self::CREATE_PAGE,
		self::REPLACE_PAGE,
		self::ASSIGN_PAGE_HIERARCHY,
		self::CREATE_MENU,
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
