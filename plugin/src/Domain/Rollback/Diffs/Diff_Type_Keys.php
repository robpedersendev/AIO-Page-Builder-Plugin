<?php
/**
 * Diff type and level constants for the diff service contract (spec §41.4–41.7; diff-service-contract.md).
 *
 * Used by future diff and rollback UI. No diff generation logic in this file.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Diffs;

defined( 'ABSPATH' ) || exit;

/**
 * Stable diff type and output-level keys for the diff service.
 */
final class Diff_Type_Keys {

	/** Diff family: page-level content and identity (title, slug, status, section-structure, content replacement). */
	public const DIFF_TYPE_CONTENT = 'content';

	/** Diff family: hierarchy, template, section composition, plan structure. */
	public const DIFF_TYPE_STRUCTURE = 'structure';

	/** Diff family: menu and menu-item changes (add/remove, label, order, nesting, location). */
	public const DIFF_TYPE_NAVIGATION = 'navigation';

	/** Diff family: design token value and metadata changes. */
	public const DIFF_TYPE_TOKEN = 'token';

	/** Output level: summary (list views, rollback eligibility, quick scan). */
	public const LEVEL_SUMMARY = 'summary';

	/** Output level: detail (detail view, audit, full family_payload). */
	public const LEVEL_DETAIL = 'detail';

	/** @var list<string> Allowed diff_type values. */
	private static ?array $diff_types = null;

	/** @var list<string> Allowed level values. */
	private static ?array $levels = null;

	/**
	 * Returns allowed diff_type values (diff-service-contract.md §2).
	 *
	 * @return list<string>
	 */
	public static function get_diff_types(): array {
		if ( self::$diff_types !== null ) {
			return self::$diff_types;
		}
		self::$diff_types = array(
			self::DIFF_TYPE_CONTENT,
			self::DIFF_TYPE_STRUCTURE,
			self::DIFF_TYPE_NAVIGATION,
			self::DIFF_TYPE_TOKEN,
		);
		return self::$diff_types;
	}

	/**
	 * Returns whether the given diff_type is allowed.
	 *
	 * @param string $diff_type Diff type value.
	 * @return bool
	 */
	public static function is_valid_diff_type( string $diff_type ): bool {
		return in_array( $diff_type, self::get_diff_types(), true );
	}

	/**
	 * Returns allowed level values (summary, detail).
	 *
	 * @return list<string>
	 */
	public static function get_levels(): array {
		if ( self::$levels !== null ) {
			return self::$levels;
		}
		self::$levels = array(
			self::LEVEL_SUMMARY,
			self::LEVEL_DETAIL,
		);
		return self::$levels;
	}

	/**
	 * Returns whether the given level is allowed.
	 *
	 * @param string $level Level value.
	 * @return bool
	 */
	public static function is_valid_level( string $level ): bool {
		return in_array( $level, self::get_levels(), true );
	}
}
