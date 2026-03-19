<?php
/**
 * Reverse mapping from ACF group keys to section keys (acf-conditional-registration-contract §5).
 * Converts assignment-map group keys (group_aio_*) back to section keys for section-scoped registration.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Key_Generator;

/**
 * Resolves plugin ACF group keys to section internal_key. Rejects malformed or non-plugin keys.
 * Deterministic: consistent with Field_Key_Generator::group_key() (prefix group_aio_ + sanitized section key).
 */
final class Group_Key_Section_Key_Resolver {

	/** Plugin group key prefix (must match Field_Key_Generator). */
	private const GROUP_KEY_PREFIX = 'group_aio_';

	/**
	 * Resolves a single group key to section key. Returns empty string for invalid or foreign keys.
	 *
	 * @param string $group_key ACF group key (e.g. group_aio_st01_hero).
	 * @return string Section internal_key or empty string if not a valid plugin group key.
	 */
	public function group_key_to_section_key( string $group_key ): string {
		$group_key = trim( $group_key );
		if ( $group_key === '' || ! str_starts_with( $group_key, self::GROUP_KEY_PREFIX ) ) {
			return '';
		}
		if ( ! Field_Key_Generator::is_valid_key( $group_key, 'group' ) ) {
			return '';
		}
		$section_key = substr( $group_key, strlen( self::GROUP_KEY_PREFIX ) );
		return $section_key !== '' ? $section_key : '';
	}

	/**
	 * Resolves multiple group keys to section keys. Invalid/foreign keys are omitted; duplicates removed.
	 *
	 * @param array<int, string> $group_keys
	 * @return array<int, string> Section keys corresponding to valid plugin group keys, de-duplicated.
	 */
	public function group_keys_to_section_keys( array $group_keys ): array {
		$section_keys = array();
		foreach ( $group_keys as $gk ) {
			$sk = $this->group_key_to_section_key( (string) $gk );
			if ( $sk !== '' && ! in_array( $sk, $section_keys, true ) ) {
				$section_keys[] = $sk;
			}
		}
		return $section_keys;
	}

	/**
	 * Returns whether the given string is a valid plugin group key (can be resolved to a section key).
	 *
	 * @param string $group_key
	 * @return bool
	 */
	public function is_plugin_group_key( string $group_key ): bool {
		return $this->group_key_to_section_key( $group_key ) !== '';
	}
}
