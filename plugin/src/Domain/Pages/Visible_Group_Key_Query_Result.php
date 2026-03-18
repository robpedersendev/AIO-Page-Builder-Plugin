<?php
/**
 * Result of a visible-group-keys query for a page (Prompt 295).
 * Holds the minimal list of group keys from the assignment map read path.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Pages;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of querying visible group keys for a page. Read-path only.
 */
final class Visible_Group_Key_Query_Result {

	/** @var list<string> */
	private array $group_keys;

	public function __construct( array $group_keys ) {
		$this->group_keys = array_values( array_unique( array_filter( $group_keys, 'is_string' ) ) );
	}

	/**
	 * Returns the list of visible group keys (e.g. group_aio_st01_hero).
	 *
	 * @return list<string>
	 */
	public function get_group_keys(): array {
		return $this->group_keys;
	}
}
