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
 * ALL lists only types valid for execution in v1; UPDATE_PAGE_METADATA is recommendation-only (Prompt 641).
 */
final class Execution_Action_Types {

	public const CREATE_PAGE           = 'create_page';
	public const REPLACE_PAGE          = 'replace_page';
	/** Not executable in v1; metadata is recommendation-only. Excluded from ALL (Prompt 641). */
	public const UPDATE_PAGE_METADATA  = 'update_page_metadata';
	public const ASSIGN_PAGE_HIERARCHY = 'assign_page_hierarchy';
	public const CREATE_MENU           = 'create_menu';
	public const UPDATE_MENU           = 'update_menu';
	public const APPLY_TOKEN_SET       = 'apply_token_set';
	public const FINALIZE_PLAN         = 'finalize_plan';
	public const ROLLBACK_ACTION       = 'rollback_action';

	/** @var array<int, string> Action types valid for execution in v1. Excludes UPDATE_PAGE_METADATA (recommendation-only). */
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
