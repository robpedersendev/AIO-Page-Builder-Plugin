<?php
/**
 * Canonical breadth and budget targets for full-site planning runs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Planning;

defined( 'ABSPATH' ) || exit;

/**
 * Minimum new pages, rough cost-planning target, and expand-pass sizing.
 */
final class Planning_Breadth_Constants {

	/** Minimum rows in new_pages_to_create for greenfield / overhaul-class plans. */
	public const MIN_NEW_PAGES_TARGET = 30;

	/**
	 * Rough target USD for a full onboarding planning run (primary + expand when used).
	 * Used for estimates and documentation only — not a hard ceiling; runs may cost less or more.
	 */
	public const DEFAULT_SUGGESTED_PLANNING_BUDGET_USD = 5.0;

	/**
	 * @deprecated Use {@see self::DEFAULT_SUGGESTED_PLANNING_BUDGET_USD}. Name implied a cap; product intent is a soft target.
	 */
	public const DEFAULT_PER_RUN_MAX_BUDGET_USD = self::DEFAULT_SUGGESTED_PLANNING_BUDGET_USD;

	/** Max completion tokens for the secondary expand pass JSON payload. */
	public const EXPAND_PASS_MAX_OUTPUT_TOKENS = 16384;

	/** Template recommendation rows passed into the input artifact for planning (breadth for merges + expand). */
	public const TEMPLATE_RECOMMENDATION_CAP = 120;
}
