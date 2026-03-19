<?php
/**
 * Site-level industry profile persistence (industry-profile-schema.md).
 * Read/write industry profile via Settings_Service; additive to Profile_Store.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Profile;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Reporting\Industry_Profile_Audit_Trail_Service;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

/**
 * Repository for site-level industry profile. Mutations are admin/authorized-only by callers.
 * Optional audit trail service records profile changes (Prompt 465).
 */
class Industry_Profile_Repository {

	private const OPTION_KEY = Option_Names::INDUSTRY_PROFILE;

	/** @var Settings_Service */
	private Settings_Service $settings;

	/** @var Industry_Profile_Audit_Trail_Service|null */
	private ?Industry_Profile_Audit_Trail_Service $audit_trail;

	public function __construct( Settings_Service $settings, ?Industry_Profile_Audit_Trail_Service $audit_trail = null ) {
		$this->settings    = $settings;
		$this->audit_trail = $audit_trail;
	}

	/**
	 * Returns current industry profile (normalized). Empty state when not set or corrupt.
	 *
	 * @return array<string, mixed>
	 */
	public function get_profile(): array {
		$raw = $this->settings->get( self::OPTION_KEY );
		return Industry_Profile_Schema::normalize( $raw );
	}

	/**
	 * Returns default empty profile shape (no read from storage).
	 *
	 * @return array<string, mixed>
	 */
	public function get_empty_profile(): array {
		return Industry_Profile_Schema::get_empty_profile();
	}

	/**
	 * Replaces industry profile with normalized payload. Callers must enforce capability and nonce.
	 * When audit trail service is set, records profile change (Prompt 465).
	 *
	 * @param array<string, mixed> $profile Profile shape (partial or full); will be normalized.
	 * @return void
	 */
	public function set_profile( array $profile ): void {
		$old        = $this->get_profile();
		$normalized = Industry_Profile_Schema::normalize( $profile );
		$this->settings->set( self::OPTION_KEY, $normalized );
		if ( $this->audit_trail !== null ) {
			$this->audit_trail->record_profile_change( $old, $normalized );
		}
	}

	/**
	 * Merges partial profile into current. Only provided keys are updated; then normalized for storage.
	 *
	 * @param array<string, mixed> $partial Keys to update (primary_industry_key, secondary_industry_keys, subtype, service_model, geo_model, etc.).
	 * @return void
	 */
	public function merge_profile( array $partial ): void {
		$current = $this->get_profile();
		if ( array_key_exists( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY, $partial ) ) {
			$current[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] = is_string( $partial[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
				? trim( $partial[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
				: '';
		}
		if ( array_key_exists( Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS, $partial ) && is_array( $partial[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] ) ) {
			$current[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] = array_values(
				array_unique(
					array_filter(
						array_map(
							function ( $v ) {
								return is_string( $v ) ? trim( $v ) : '';
							},
							$partial[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ]
						)
					)
				)
			);
		}
		if ( array_key_exists( Industry_Profile_Schema::FIELD_SUBTYPE, $partial ) ) {
			$current[ Industry_Profile_Schema::FIELD_SUBTYPE ] = is_string( $partial[ Industry_Profile_Schema::FIELD_SUBTYPE ] )
				? trim( $partial[ Industry_Profile_Schema::FIELD_SUBTYPE ] )
				: '';
		}
		if ( array_key_exists( Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY, $partial ) ) {
			$current[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] = is_string( $partial[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
				? trim( $partial[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
				: '';
			if ( strlen( $current[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ) > 64 ) {
				$current[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] = '';
			}
		}
		if ( array_key_exists( Industry_Profile_Schema::FIELD_SERVICE_MODEL, $partial ) ) {
			$current[ Industry_Profile_Schema::FIELD_SERVICE_MODEL ] = is_string( $partial[ Industry_Profile_Schema::FIELD_SERVICE_MODEL ] )
				? trim( $partial[ Industry_Profile_Schema::FIELD_SERVICE_MODEL ] )
				: '';
		}
		if ( array_key_exists( Industry_Profile_Schema::FIELD_GEO_MODEL, $partial ) ) {
			$current[ Industry_Profile_Schema::FIELD_GEO_MODEL ] = is_string( $partial[ Industry_Profile_Schema::FIELD_GEO_MODEL ] )
				? trim( $partial[ Industry_Profile_Schema::FIELD_GEO_MODEL ] )
				: '';
		}
		if ( array_key_exists( Industry_Profile_Schema::FIELD_DERIVED_FLAGS, $partial ) && is_array( $partial[ Industry_Profile_Schema::FIELD_DERIVED_FLAGS ] ) ) {
			$current[ Industry_Profile_Schema::FIELD_DERIVED_FLAGS ] = $partial[ Industry_Profile_Schema::FIELD_DERIVED_FLAGS ];
		}
		if ( array_key_exists( Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS, $partial ) && is_array( $partial[ Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS ] ) ) {
			$incoming = Industry_Profile_Schema::normalize_question_pack_answers( $partial[ Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS ] );
			$existing = isset( $current[ Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS ] ) && is_array( $current[ Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS ] )
				? $current[ Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS ]
				: array();
			foreach ( $incoming as $industry_key => $by_field ) {
				$existing[ $industry_key ] = isset( $existing[ $industry_key ] ) && is_array( $existing[ $industry_key ] )
					? array_merge( $existing[ $industry_key ], $by_field )
					: $by_field;
			}
			$current[ Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS ] = $existing;
		}
		if ( array_key_exists( Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY, $partial ) ) {
			$current[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] = is_string( $partial[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
				? trim( $partial[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
				: '';
			if ( strlen( $current[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] ) > 64 ) {
				$current[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] = '';
			}
		}
		if ( array_key_exists( Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY, $partial ) ) {
			$current[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] = is_string( $partial[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] )
				? trim( $partial[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] )
				: '';
			if ( strlen( $current[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] ) > 64 ) {
				$current[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] = '';
			}
		}
		if ( array_key_exists( Industry_Profile_Schema::FIELD_SECONDARY_CONVERSION_GOAL_KEY, $partial ) ) {
			$current[ Industry_Profile_Schema::FIELD_SECONDARY_CONVERSION_GOAL_KEY ] = is_string( $partial[ Industry_Profile_Schema::FIELD_SECONDARY_CONVERSION_GOAL_KEY ] )
				? trim( $partial[ Industry_Profile_Schema::FIELD_SECONDARY_CONVERSION_GOAL_KEY ] )
				: '';
			if ( strlen( $current[ Industry_Profile_Schema::FIELD_SECONDARY_CONVERSION_GOAL_KEY ] ) > 64 ) {
				$current[ Industry_Profile_Schema::FIELD_SECONDARY_CONVERSION_GOAL_KEY ] = '';
			}
		}
		$this->set_profile( $current );
	}

	/**
	 * Returns readiness/completeness for the current profile (industry-profile-validation-contract).
	 * Uses Industry_Profile_Validator; optional registries improve validation and scoring.
	 *
	 * @param \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry|null            $pack_registry Optional; validates primary_industry_key.
	 * @param \AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry|null $qp_registry  Optional; validates question-pack answers.
	 * @return Industry_Profile_Readiness_Result
	 */
	public function get_readiness(
		?\AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry $pack_registry = null,
		?\AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry $qp_registry = null
	): Industry_Profile_Readiness_Result {
		$validator = new Industry_Profile_Validator();
		return $validator->get_readiness( $this->get_profile(), $pack_registry, $qp_registry );
	}
}
