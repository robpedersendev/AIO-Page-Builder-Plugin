<?php
/**
 * Persists and retrieves industry section overrides (industry-override-contract.md, Prompt 367).
 * Option-backed; admin-only mutations via Save_Industry_Section_Override_Action.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Overrides;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;

/**
 * Section override persistence: record, get, list. Safe when option missing or invalid.
 */
final class Industry_Section_Override_Service {

	/**
	 * Records an override for a section. Overwrites existing. Validates via Industry_Override_Schema.
	 *
	 * @param string $section_key Section template internal_key.
	 * @param string $state       Industry_Override_Schema::STATE_ACCEPTED or STATE_REJECTED.
	 * @param string $reason      Optional reason (sanitized by caller or use Industry_Override_Schema::sanitize_reason).
	 * @return bool True when saved; false when validation failed or save failed.
	 */
	public function record_override( string $section_key, string $state, string $reason = '' ): bool {
		$section_key = trim( $section_key );
		if ( $section_key === '' || strlen( $section_key ) > Industry_Override_Schema::TARGET_KEY_MAX_LENGTH ) {
			return false;
		}
		$reason = Industry_Override_Schema::sanitize_reason( $reason );
		$override = array(
			Industry_Override_Schema::FIELD_TARGET_TYPE => Industry_Override_Schema::TARGET_TYPE_SECTION,
			Industry_Override_Schema::FIELD_TARGET_KEY  => $section_key,
			Industry_Override_Schema::FIELD_STATE     => $state,
			Industry_Override_Schema::FIELD_REASON    => $reason,
			Industry_Override_Schema::FIELD_CREATED_AT => time(),
			Industry_Override_Schema::FIELD_UPDATED_AT => time(),
		);
		if ( Industry_Override_Schema::validate( $override ) !== array() ) {
			return false;
		}
		$all = $this->get_all();
		$all[ $section_key ] = $override;
		return \update_option( Option_Names::INDUSTRY_SECTION_OVERRIDES, $all, true ) !== false;
	}

	/**
	 * Returns override record for a section, or null.
	 *
	 * @param string $section_key
	 * @return array<string, mixed>|null
	 */
	public function get_override( string $section_key ): ?array {
		$section_key = trim( $section_key );
		if ( $section_key === '' ) {
			return null;
		}
		$all = $this->get_all();
		return isset( $all[ $section_key ] ) && is_array( $all[ $section_key ] ) ? $all[ $section_key ] : null;
	}

	/**
	 * Returns all section overrides (section_key => override record).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function list_overrides(): array {
		return $this->get_all();
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function get_all(): array {
		$raw = \get_option( Option_Names::INDUSTRY_SECTION_OVERRIDES, array() );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $key => $record ) {
			if ( ! is_string( $key ) || $key === '' || ! is_array( $record ) ) {
				continue;
			}
			$out[ $key ] = $record;
		}
		return $out;
	}
}
