<?php
/**
 * LPagery planning advisor: translates industry LPagery rules into planning guidance and warnings (industry-lpagery-planning-contract.md).
 * Read-only; no execution or mutation of LPagery binding. Fails safely when rules are absent or incomplete.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\LPagery;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;

/**
 * Produces structured LPagery guidance for Build Plan and UI. No auto-generation of pages.
 */
final class Industry_LPagery_Planning_Advisor {

	/** Max length for concatenated hierarchy_guidance. */
	private const HIERARCHY_GUIDANCE_MAX_LEN = 1024;

	/** @var Industry_LPagery_Rule_Registry|null */
	private ?Industry_LPagery_Rule_Registry $rule_registry;

	public function __construct( ?Industry_LPagery_Rule_Registry $rule_registry = null ) {
		$this->rule_registry = $rule_registry;
	}

	/**
	 * Produces planning result for the given industry key. Safe when registry is null or no active rules.
	 *
	 * @param string $industry_key Primary industry pack key.
	 * @return Industry_LPagery_Planning_Result
	 */
	public function advise( string $industry_key ): Industry_LPagery_Planning_Result {
		$key = trim( $industry_key );
		if ( $key === '' ) {
			return $this->empty_result( array( 'no_industry_key' ) );
		}
		if ( $this->rule_registry === null ) {
			return $this->empty_result( array( 'no_lpagery_rules' ) );
		}
		$rules  = $this->rule_registry->list_by_industry( $key );
		$active = array();
		foreach ( $rules as $rule ) {
			if ( isset( $rule[ Industry_LPagery_Rule_Registry::FIELD_STATUS ] ) && $rule[ Industry_LPagery_Rule_Registry::FIELD_STATUS ] === Industry_LPagery_Rule_Registry::STATUS_ACTIVE ) {
				$active[] = $rule;
			}
		}
		if ( empty( $active ) ) {
			return $this->empty_result( array( 'no_active_lpagery_rules' ) );
		}
		return $this->aggregate_result( $active );
	}

	/**
	 * Produces planning result from industry profile (primary_industry_key). Safe when profile missing or empty.
	 *
	 * @param array<string, mixed> $industry_profile Profile with primary_industry_key.
	 * @return Industry_LPagery_Planning_Result
	 */
	public function advise_from_profile( array $industry_profile ): Industry_LPagery_Planning_Result {
		$primary = isset( $industry_profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $industry_profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $industry_profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		return $this->advise( $primary );
	}

	/**
	 * @param list<array<string, mixed>> $active_rules Valid active rules for the industry.
	 * @return Industry_LPagery_Planning_Result
	 */
	private function aggregate_result( array $active_rules ): Industry_LPagery_Planning_Result {
		$posture            = Industry_LPagery_Rule_Registry::POSTURE_OPTIONAL;
		$required           = array();
		$optional           = array();
		$hierarchy_parts    = array();
		$weak_page_warnings = array();
		$warning_flags      = array();

		foreach ( $active_rules as $rule ) {
			$p = $rule[ Industry_LPagery_Rule_Registry::FIELD_LPAGERY_POSTURE ] ?? '';
			if ( $p === Industry_LPagery_Rule_Registry::POSTURE_CENTRAL ) {
				$posture = Industry_LPagery_Rule_Registry::POSTURE_CENTRAL;
			} elseif ( $p === Industry_LPagery_Rule_Registry::POSTURE_DISCOURAGED && $posture !== Industry_LPagery_Rule_Registry::POSTURE_CENTRAL ) {
				$posture = Industry_LPagery_Rule_Registry::POSTURE_DISCOURAGED;
			} elseif ( $p === Industry_LPagery_Rule_Registry::POSTURE_OPTIONAL && $posture === Industry_LPagery_Rule_Registry::POSTURE_OPTIONAL ) {
				// Already optional; no change.
				(void) $p;
			}

			$req = $rule[ Industry_LPagery_Rule_Registry::FIELD_REQUIRED_TOKEN_REFS ] ?? array();
			if ( is_array( $req ) ) {
				foreach ( $req as $t ) {
					if ( is_string( $t ) && trim( $t ) !== '' ) {
						$required[] = trim( $t );
					}
				}
			}
			$opt = $rule[ Industry_LPagery_Rule_Registry::FIELD_OPTIONAL_TOKEN_REFS ] ?? array();
			if ( is_array( $opt ) ) {
				foreach ( $opt as $t ) {
					if ( is_string( $t ) && trim( $t ) !== '' ) {
						$optional[] = trim( $t );
					}
				}
			}
			$hg = $rule[ Industry_LPagery_Rule_Registry::FIELD_HIERARCHY_GUIDANCE ] ?? '';
			if ( is_string( $hg ) && trim( $hg ) !== '' ) {
				$hierarchy_parts[] = trim( $hg );
			}
			$wpw = $rule[ Industry_LPagery_Rule_Registry::FIELD_WEAK_PAGE_WARNINGS ] ?? null;
			if ( is_array( $wpw ) ) {
				foreach ( $wpw as $w ) {
					if ( is_string( $w ) && trim( $w ) !== '' ) {
						$weak_page_warnings[] = trim( $w );
					}
				}
			} elseif ( is_string( $wpw ) && trim( $wpw ) !== '' ) {
				$weak_page_warnings[] = trim( $wpw );
			}
		}

		$required           = array_values( array_unique( $required ) );
		$optional           = array_values( array_unique( $optional ) );
		$weak_page_warnings = array_values( array_unique( $weak_page_warnings ) );
		$hierarchy_guidance = implode( ' ', $hierarchy_parts );
		if ( strlen( $hierarchy_guidance ) > self::HIERARCHY_GUIDANCE_MAX_LEN ) {
			$hierarchy_guidance = substr( $hierarchy_guidance, 0, self::HIERARCHY_GUIDANCE_MAX_LEN - 3 ) . '...';
		}
		$suggested_page_families = array();
		if ( $hierarchy_guidance !== '' ) {
			$suggested_page_families[] = 'service_area_hub';
		}

		if ( $posture === Industry_LPagery_Rule_Registry::POSTURE_CENTRAL && ! empty( $required ) ) {
			$warning_flags[] = 'required_tokens_for_central_lpagery';
		}
		if ( ! empty( $weak_page_warnings ) ) {
			$warning_flags[] = 'weak_fit_local_page';
		}

		return new Industry_LPagery_Planning_Result(
			$posture,
			$required,
			$optional,
			$suggested_page_families,
			$warning_flags,
			$hierarchy_guidance,
			$weak_page_warnings
		);
	}

	/**
	 * @param list<string> $warning_flags
	 * @return Industry_LPagery_Planning_Result
	 */
	private function empty_result( array $warning_flags = array() ): Industry_LPagery_Planning_Result {
		return new Industry_LPagery_Planning_Result(
			Industry_LPagery_Rule_Registry::POSTURE_OPTIONAL,
			array(),
			array(),
			array(),
			$warning_flags,
			'',
			array()
		);
	}
}
