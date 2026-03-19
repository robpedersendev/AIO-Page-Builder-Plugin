<?php
/**
 * Resolves import conflicts per user choice (spec §52.9). No silent overwrite.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Import;

defined( 'ABSPATH' ) || exit;

/**
 * Applies conflict-resolution mode to conflict list; returns resolved actions for pipeline.
 */
final class Conflict_Resolution_Service {

	/** Overwrite current with incoming. */
	public const ACTION_OVERWRITE = 'overwrite';

	/** Keep current; skip importing this object. */
	public const ACTION_KEEP_CURRENT = 'keep_current';

	/** Import as duplicate/new where allowed (e.g. new key/id). */
	public const ACTION_DUPLICATE = 'duplicate';

	/** Skip (e.g. not applicable for this category). */
	public const ACTION_SKIP = 'skip';

	/** User cancelled restore. */
	public const ACTION_CANCEL = 'cancel';

	/** Resolution mode: apply overwrite to all conflicts. */
	public const MODE_OVERWRITE = 'overwrite';

	/** Resolution mode: keep current for all. */
	public const MODE_KEEP_CURRENT = 'keep_current';

	/** Resolution mode: duplicate where allowed. */
	public const MODE_DUPLICATE = 'duplicate';

	/** Resolution mode: cancel restore. */
	public const MODE_CANCEL = 'cancel';

	/**
	 * Resolves conflicts with the given mode. Returns list of actions per conflict; if mode is cancel, returns cancelled flag.
	 *
	 * @param array<int, array{category: string, key: string, message: string}> $conflicts From validation.
	 * @param string                                                      $mode One of MODE_OVERWRITE, MODE_KEEP_CURRENT, MODE_DUPLICATE, MODE_CANCEL.
	 * @return array{resolved: array<int, array{category: string, key: string, action: string}>, cancelled: bool}
	 */
	public function resolve( array $conflicts, string $mode ): array {
		if ( $mode === self::MODE_CANCEL ) {
			return array(
				'resolved'  => array(),
				'cancelled' => true,
			);
		}
		$action   = self::mode_to_action( $mode );
		$resolved = array();
		foreach ( $conflicts as $c ) {
			$key        = isset( $c['key'] ) ? (string) $c['key'] : '';
			$cat        = isset( $c['category'] ) ? (string) $c['category'] : '';
			$resolved[] = array(
				'category' => $cat,
				'key'      => $key,
				'action'   => $this->action_for_category( $cat, $action ),
			);
		}
		return array(
			'resolved'  => $resolved,
			'cancelled' => false,
		);
	}

	/**
	 * Returns whether the mode is valid.
	 *
	 * @param string $mode
	 * @return bool
	 */
	public static function is_valid_mode( string $mode ): bool {
		return in_array( $mode, array( self::MODE_OVERWRITE, self::MODE_KEEP_CURRENT, self::MODE_DUPLICATE, self::MODE_CANCEL ), true );
	}

	private static function mode_to_action( string $mode ): string {
		switch ( $mode ) {
			case self::MODE_OVERWRITE:
				return self::ACTION_OVERWRITE;
			case self::MODE_KEEP_CURRENT:
				return self::ACTION_KEEP_CURRENT;
			case self::MODE_DUPLICATE:
				return self::ACTION_DUPLICATE;
			default:
				return self::ACTION_SKIP;
		}
	}

	/**
	 * Duplicate not allowed for settings/profile (single blob); treat as skip. Others allow duplicate.
	 *
	 * @param string $category
	 * @param string $action
	 * @return string
	 */
	private function action_for_category( string $category, string $action ): string {
		if ( $action === self::ACTION_DUPLICATE && in_array( $category, array( 'settings', 'profiles', 'uninstall_restore_metadata' ), true ) ) {
			return self::ACTION_SKIP;
		}
		return $action;
	}
}
