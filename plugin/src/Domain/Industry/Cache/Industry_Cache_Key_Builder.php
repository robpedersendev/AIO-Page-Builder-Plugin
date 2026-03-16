<?php
/**
 * Builds deterministic, bounded cache base keys for industry read models (industry-cache-contract.md; Prompt 434).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Produces base cache keys from scope and normalized inputs. Keys are safe for transient storage (alphanumeric, underscore).
 * Caller must apply Industry_Site_Scope_Helper::scope_cache_key() before get/set.
 */
final class Industry_Cache_Key_Builder {

	public const SCOPE_SECTION_RECOMMENDATION   = 'section_recommendation';
	public const SCOPE_PAGE_TEMPLATE_RECOMMENDATION = 'page_template_recommendation';
	public const SCOPE_HELPER_DOC               = 'helper_doc';
	public const SCOPE_PAGE_ONEPAGER           = 'page_onepager';
	public const SCOPE_STARTER_BUNDLE_LIST     = 'starter_bundle_list';

	private const KEY_MAX_LEN = 172;
	private const HASH_LEN     = 10;

	/**
	 * Base key for section recommendation cache. Inputs: profile (primary, secondary, subtype), section keys hash.
	 *
	 * @param array<string, mixed>       $industry_profile Profile with primary_industry_key, optional secondary_industry_keys, industry_subtype_key.
	 * @param array<int, array<string, mixed>> $sections   Section definitions (used to extract keys for hash).
	 * @param array<string, mixed>      $options          Resolver options (e.g. subtype_key).
	 * @return string Base key (no site scope).
	 */
	public function for_section_recommendation( array $industry_profile, array $sections, array $options = array() ): string {
		$primary   = $this->normalize_string( (string) ( $industry_profile['primary_industry_key'] ?? '' ) );
		$secondary = $this->normalize_string_list( $industry_profile['secondary_industry_keys'] ?? array() );
		$subtype   = $this->normalize_string( (string) ( $industry_profile['industry_subtype_key'] ?? $options['subtype_key'] ?? '' ) );
		$section_hash = $this->hash_section_keys( $sections );
		$parts = array( self::SCOPE_SECTION_RECOMMENDATION, $primary, $secondary, $subtype, $section_hash );
		return $this->join_and_truncate( $parts );
	}

	/**
	 * Base key for page template recommendation cache.
	 *
	 * @param array<string, mixed>       $industry_profile Profile.
	 * @param array<int, array<string, mixed>> $page_templates Page template definitions.
	 * @param array<string, mixed>      $options         Resolver options (e.g. subtype_key).
	 * @return string Base key.
	 */
	public function for_page_template_recommendation( array $industry_profile, array $page_templates, array $options = array() ): string {
		$primary   = $this->normalize_string( (string) ( $industry_profile['primary_industry_key'] ?? '' ) );
		$secondary = $this->normalize_string_list( $industry_profile['secondary_industry_keys'] ?? array() );
		$subtype   = $this->normalize_string( (string) ( $industry_profile['industry_subtype_key'] ?? $options['subtype_key'] ?? '' ) );
		$template_hash = $this->hash_page_template_keys( $page_templates );
		$parts = array( self::SCOPE_PAGE_TEMPLATE_RECOMMENDATION, $primary, $secondary, $subtype, $template_hash );
		return $this->join_and_truncate( $parts );
	}

	/**
	 * Base key for composed helper doc (section_key + industry + subtype).
	 *
	 * @param string $section_key  Section internal_key.
	 * @param string $industry_key Industry pack key.
	 * @param string $subtype_key  Subtype key or empty.
	 * @return string Base key.
	 */
	public function for_helper_doc( string $section_key, string $industry_key, string $subtype_key = '' ): string {
		$parts = array(
			self::SCOPE_HELPER_DOC,
			$this->normalize_string( $section_key ),
			$this->normalize_string( $industry_key ),
			$this->normalize_string( $subtype_key ),
		);
		return $this->join_and_truncate( $parts );
	}

	/**
	 * Base key for composed page one-pager.
	 *
	 * @param string $page_template_key Page template internal_key.
	 * @param string $industry_key      Industry pack key.
	 * @param string $subtype_key       Subtype key or empty.
	 * @return string Base key.
	 */
	public function for_page_onepager( string $page_template_key, string $industry_key, string $subtype_key = '' ): string {
		$parts = array(
			self::SCOPE_PAGE_ONEPAGER,
			$this->normalize_string( $page_template_key ),
			$this->normalize_string( $industry_key ),
			$this->normalize_string( $subtype_key ),
		);
		return $this->join_and_truncate( $parts );
	}

	/**
	 * Base key for starter bundle list (get_for_industry result).
	 *
	 * @param string $industry_key Industry pack key.
	 * @param string $subtype_key  Subtype key or empty.
	 * @return string Base key.
	 */
	public function for_starter_bundle_list( string $industry_key, string $subtype_key = '' ): string {
		$parts = array(
			self::SCOPE_STARTER_BUNDLE_LIST,
			$this->normalize_string( $industry_key ),
			$this->normalize_string( $subtype_key ),
		);
		return $this->join_and_truncate( $parts );
	}

	/**
	 * Returns a base key prefix for a scope (for invalidation by scope). E.g. "section_recommendation" invalidates all section recommendation keys.
	 *
	 * @param string $scope One of SCOPE_* constants.
	 * @return string Prefix (scope only).
	 */
	public function scope_prefix( string $scope ): string {
		$s = preg_replace( '/[^a-z0-9_]/i', '_', $scope );
		return $s !== '' ? $s : 'industry';
	}

	private function normalize_string( string $s ): string {
		return trim( $s );
	}

	/**
	 * @param array<int|string, mixed> $list
	 * @return string Comma-sorted, trimmed, unique.
	 */
	private function normalize_string_list( array $list ): string {
		$out = array();
		foreach ( $list as $v ) {
			if ( is_string( $v ) ) {
				$t = trim( $v );
				if ( $t !== '' ) {
					$out[] = $t;
				}
			}
		}
		$out = array_unique( $out );
		sort( $out );
		return implode( ',', $out );
	}

	/**
	 * @param array<int, array<string, mixed>> $sections
	 * @return string Short hash of section internal_key list.
	 */
	private function hash_section_keys( array $sections ): string {
		$keys = array();
		foreach ( $sections as $s ) {
			if ( is_array( $s ) && isset( $s['internal_key'] ) && is_string( $s['internal_key'] ) ) {
				$keys[] = trim( $s['internal_key'] );
			}
		}
		sort( $keys );
		return substr( md5( implode( ',', $keys ) ), 0, self::HASH_LEN );
	}

	/**
	 * @param array<int, array<string, mixed>> $templates
	 * @return string Short hash of page template internal_key list.
	 */
	private function hash_page_template_keys( array $templates ): string {
		$keys = array();
		foreach ( $templates as $t ) {
			if ( is_array( $t ) && isset( $t['internal_key'] ) && is_string( $t['internal_key'] ) ) {
				$keys[] = trim( $t['internal_key'] );
			}
		}
		sort( $keys );
		return substr( md5( implode( ',', $keys ) ), 0, self::HASH_LEN );
	}

	/**
	 * @param array<int, string> $parts
	 * @return string Joined with _, sanitized, truncated.
	 */
	private function join_and_truncate( array $parts ): string {
		$joined = implode( '_', array_map( function ( $p ) {
			return preg_replace( '/[^a-z0-9_-]/i', '_', (string) $p );
		}, $parts ) );
		if ( strlen( $joined ) > self::KEY_MAX_LEN ) {
			return substr( $joined, 0, self::KEY_MAX_LEN - self::HASH_LEN ) . '_' . substr( md5( $joined ), 0, self::HASH_LEN );
		}
		return $joined;
	}
}
