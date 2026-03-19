<?php
/**
 * Result of section-scoped ACF group registration (acf-conditional-registration-contract, Prompt 284).
 * Summarizes how many groups were registered and which keys were skipped (invalid/missing).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of register_sections(): count registered and list of skipped section keys.
 */
final class Section_Scoped_Group_Registration_Result {

	/** @var int */
	private int $registered_count;

	/** @var array<int, string> Section keys that were not registered (missing/invalid blueprint). */
	private array $skipped_keys;

	/**
	 * @param int                $registered_count Number of groups successfully registered.
	 * @param array<int, string> $skipped_keys     Section keys skipped (no blueprint or invalid).
	 */
	public function __construct( int $registered_count = 0, array $skipped_keys = array() ) {
		$this->registered_count = $registered_count;
		$this->skipped_keys     = array_values( $skipped_keys );
	}

	public function get_registered_count(): int {
		return $this->registered_count;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_skipped_keys(): array {
		return $this->skipped_keys;
	}

	public function has_skipped(): bool {
		return count( $this->skipped_keys ) > 0;
	}
}
