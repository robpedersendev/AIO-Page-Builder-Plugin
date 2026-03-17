<?php
/**
 * Persists and retrieves industry page template overrides (industry-override-contract.md, Prompt 368).
 * Option-backed; admin-only mutations via Save_Industry_Page_Template_Override_Action.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Overrides;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;

/**
 * Page template override persistence: record, get, list. Safe when option missing or invalid.
 */
final class Industry_Page_Template_Override_Service {

	/**
	 * Records an override for a page template.
	 *
	 * @param string $template_key Page template internal_key.
	 * @param string $state        Industry_Override_Schema::STATE_ACCEPTED or STATE_REJECTED.
	 * @param string $reason       Optional reason (sanitized by caller or use Industry_Override_Schema::sanitize_reason).
	 * @return bool True when saved; false when validation failed or save failed.
	 */
	public function record_override( string $template_key, string $state, string $reason = '' ): bool {
		$template_key = trim( $template_key );
		if ( $template_key === '' || strlen( $template_key ) > Industry_Override_Schema::TARGET_KEY_MAX_LENGTH ) {
			return false;
		}
		$reason = Industry_Override_Schema::sanitize_reason( $reason );
		$override = array(
			Industry_Override_Schema::FIELD_TARGET_TYPE => Industry_Override_Schema::TARGET_TYPE_PAGE_TEMPLATE,
			Industry_Override_Schema::FIELD_TARGET_KEY  => $template_key,
			Industry_Override_Schema::FIELD_STATE       => $state,
			Industry_Override_Schema::FIELD_REASON      => $reason,
			Industry_Override_Schema::FIELD_CREATED_AT  => time(),
			Industry_Override_Schema::FIELD_UPDATED_AT  => time(),
		);
		if ( Industry_Override_Schema::validate( $override ) !== array() ) {
			return false;
		}
		$all = $this->get_all();
		$all[ $template_key ] = $override;
		return \update_option( Option_Names::INDUSTRY_PAGE_TEMPLATE_OVERRIDES, $all, true ) !== false;
	}

	/**
	 * Returns override record for a template, or null.
	 *
	 * @param string $template_key
	 * @return array<string, mixed>|null
	 */
	public function get_override( string $template_key ): ?array {
		$template_key = trim( $template_key );
		if ( $template_key === '' ) {
			return null;
		}
		$all = $this->get_all();
		return isset( $all[ $template_key ] ) && is_array( $all[ $template_key ] ) ? $all[ $template_key ] : null;
	}

	/**
	 * Returns all page template overrides (template_key => override record).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function list_overrides(): array {
		return $this->get_all();
	}

	/**
	 * Removes the override for a page template. Bounded single-key removal for audit safety.
	 *
	 * @param string $template_key Page template internal_key.
	 * @return bool True when removed or key was absent; false when save failed.
	 */
	public function remove_override( string $template_key ): bool {
		$template_key = trim( $template_key );
		if ( $template_key === '' ) {
			return true;
		}
		$all = $this->get_all();
		if ( ! isset( $all[ $template_key ] ) ) {
			return true;
		}
		unset( $all[ $template_key ] );
		return \update_option( Option_Names::INDUSTRY_PAGE_TEMPLATE_OVERRIDES, $all, true ) !== false;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function get_all(): array {
		$raw = \get_option( Option_Names::INDUSTRY_PAGE_TEMPLATE_OVERRIDES, array() );
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
