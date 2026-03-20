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
 * ALL lists types valid for execution. One type remains deferred to v2:
 * - UPDATE_PAGE_METADATA: recommendation-only; v2 will add a dedicated metadata-write handler.
 * ASSIGN_PAGE_HIERARCHY (v2) and CREATE_MENU (v2) are now executable.
 */
final class Execution_Action_Types {

	public const CREATE_PAGE  = 'create_page';
	public const REPLACE_PAGE = 'replace_page';
	/** Not executable in v1; metadata is recommendation-only. Excluded from ALL. */
	public const UPDATE_PAGE_METADATA = 'update_page_metadata';
	/**
	 * Deferred to v2. Standalone post-parent reassignment handler not yet implemented.
	 * In v1, hierarchy is set inline during CREATE_PAGE via Template_Page_Build_Service.
	 * v2 target: dedicated handler that reassigns post_parent for existing pages,
	 * supporting batch hierarchy corrections and Build Plan hierarchy step execution.
	 * Excluded from ALL until handler is implemented.
	 */
	public const ASSIGN_PAGE_HIERARCHY = 'assign_page_hierarchy';
	/**
	 * Executable in v2 via Create_Menu_Handler. Net-new menu creation with optional location assignment
	 * and item seeding. Distinct from UPDATE_MENU (rename/replace/update_existing) flows.
	 * Triggered by ITEM_TYPE_MENU_NEW plan items.
	 */
	public const CREATE_MENU           = 'create_menu';
	public const UPDATE_MENU           = 'update_menu';
	public const APPLY_TOKEN_SET       = 'apply_token_set';
	public const FINALIZE_PLAN         = 'finalize_plan';
	public const ROLLBACK_ACTION       = 'rollback_action';

	/**
	 * Action types valid for execution. UPDATE_PAGE_METADATA remains deferred to v2.
	 * ASSIGN_PAGE_HIERARCHY and CREATE_MENU are executable as of v2.
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
