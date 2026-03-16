<?php
/**
 * Validates Industry Profile and optional question-pack answers (industry-profile-validation-contract.md).
 * Safe: no throw; returns errors and warnings. Used by readiness scoring and downstream consumers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Profile;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;

/**
 * Validates industry profile shape and content. Optionally uses registries for primary_industry_key and question-pack checks.
 */
final class Industry_Profile_Validator {

	/** @var array<int, string> */
	private array $last_errors = array();

	/** @var array<int, string> */
	private array $last_warnings = array();

	/**
	 * Validates profile. Populates last_errors and last_warnings; safe (no throw).
	 *
	 * @param array<string, mixed>                    $profile          Normalized or raw profile (will be normalized for validation).
	 * @param Industry_Pack_Registry|null            $pack_registry    Optional; used to check primary_industry_key.
	 * @param Industry_Question_Pack_Registry|null   $qp_registry      Optional; used to validate question_pack_answers for primary.
	 * @param Industry_Subtype_Registry|null        $subtype_registry Optional; when set, validates industry_subtype_key matches primary.
	 * @return bool True if no validation errors (warnings allowed).
	 */
	public function validate(
		array $profile,
		?Industry_Pack_Registry $pack_registry = null,
		?Industry_Question_Pack_Registry $qp_registry = null,
		?Industry_Subtype_Registry $subtype_registry = null
	): bool {
		$this->last_errors   = array();
		$this->last_warnings = array();
		$normalized = Industry_Profile_Schema::normalize( $profile );

		if ( ! Industry_Profile_Schema::is_supported_version( (string) ( $normalized[ Industry_Profile_Schema::FIELD_SCHEMA_VERSION ] ?? '' ) ) ) {
			$this->last_errors[] = 'industry_profile_unsupported_schema_version';
			return false;
		}

		$primary = isset( $normalized[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $normalized[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $normalized[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';

		if ( $primary !== '' && $pack_registry !== null ) {
			if ( $pack_registry->get( $primary ) === null ) {
				$this->last_warnings[] = 'primary_industry_key_unknown: ' . $primary;
			}
		}

		$subtype_key = isset( $normalized[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ) && is_string( $normalized[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
			? trim( $normalized[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
			: '';
		if ( $subtype_key !== '' && $subtype_registry !== null ) {
			$def = $subtype_registry->get( $subtype_key );
			if ( $def === null ) {
				$this->last_warnings[] = 'industry_subtype_key_unknown: ' . $subtype_key;
			} else {
				$parent = isset( $def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ) && is_string( $def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] )
					? trim( $def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] )
					: '';
				if ( $parent !== $primary ) {
					$this->last_warnings[] = 'industry_subtype_key_parent_mismatch: ' . $subtype_key . ' (parent ' . $parent . ' != primary ' . $primary . ')';
				}
			}
		}

		$qp_answers = isset( $normalized[ Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS ] ) && is_array( $normalized[ Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS ] )
			? $normalized[ Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS ]
			: array();
		$this->validate_question_pack_answers( $qp_answers, $primary, $qp_registry );

		return $this->last_errors === array();
	}

	/**
	 * @param array<string, array<string, mixed>>     $qp_answers By industry_key => field_key => value.
	 * @param string                                 $primary_industry_key
	 * @param Industry_Question_Pack_Registry|null  $qp_registry
	 */
	private function validate_question_pack_answers( array $qp_answers, string $primary_industry_key, ?Industry_Question_Pack_Registry $qp_registry ): void {
		foreach ( $qp_answers as $industry_key => $by_field ) {
			if ( ! is_string( $industry_key ) || ! is_array( $by_field ) ) {
				continue;
			}
			foreach ( $by_field as $field_key => $value ) {
				if ( ! is_scalar( $value ) && $value !== null ) {
					$this->last_errors[] = 'question_pack_answer_non_scalar: ' . $industry_key . '.' . $field_key;
				}
			}
		}

		if ( $primary_industry_key !== '' && $qp_registry !== null ) {
			$pack = $qp_registry->get( $primary_industry_key );
			if ( $pack !== null && isset( $pack[ Industry_Question_Pack_Registry::FIELD_FIELDS ] ) && is_array( $pack[ Industry_Question_Pack_Registry::FIELD_FIELDS ] ) ) {
				$primary_answers = $qp_answers[ $primary_industry_key ] ?? array();
				$filled = 0;
				foreach ( $pack[ Industry_Question_Pack_Registry::FIELD_FIELDS ] as $field_def ) {
					$key = isset( $field_def['key'] ) && is_string( $field_def['key'] ) ? $field_def['key'] : '';
					if ( $key !== '' && isset( $primary_answers[ $key ] ) && $primary_answers[ $key ] !== '' ) {
						$filled++;
					}
				}
				$total = count( $pack[ Industry_Question_Pack_Registry::FIELD_FIELDS ] );
				if ( $total > 0 && $filled === 0 ) {
					$this->last_warnings[] = 'question_pack_no_answers_for_primary: ' . $primary_industry_key;
				}
			}
		}
	}

	/**
	 * Returns readiness result for the given profile. Uses validate() then computes state and score.
	 *
	 * @param array<string, mixed>                    $profile   Industry profile (normalized or raw).
	 * @param Industry_Pack_Registry|null            $pack_registry Optional.
	 * @param Industry_Question_Pack_Registry|null   $qp_registry  Optional.
	 * @return Industry_Profile_Readiness_Result
	 */
	public function get_readiness(
		array $profile,
		?Industry_Pack_Registry $pack_registry = null,
		?Industry_Question_Pack_Registry $qp_registry = null,
		?Industry_Subtype_Registry $subtype_registry = null
	): Industry_Profile_Readiness_Result {
		$valid = $this->validate( $profile, $pack_registry, $qp_registry, $subtype_registry );
		$normalized = Industry_Profile_Schema::normalize( $profile );
		$primary = isset( $normalized[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $normalized[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $normalized[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';

		if ( ! $valid || $this->last_errors !== array() ) {
			return new Industry_Profile_Readiness_Result(
				Industry_Profile_Readiness_Result::STATE_NONE,
				Industry_Profile_Readiness_Result::SCORE_NONE,
				$this->last_errors,
				$this->last_warnings,
				array( 'primary_set' => false, 'validation_passed' => false )
			);
		}

		if ( $primary === '' ) {
			return new Industry_Profile_Readiness_Result(
				Industry_Profile_Readiness_Result::STATE_MINIMAL,
				Industry_Profile_Readiness_Result::SCORE_MINIMAL,
				array(),
				$this->last_warnings,
				array( 'primary_set' => false, 'question_pack_complete' => false )
			);
		}

		$qp_reg = $qp_registry;
		$qp_answers = isset( $normalized[ Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS ] ) && is_array( $normalized[ Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS ] )
			? $normalized[ Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS ]
			: array();
		$primary_answers = $qp_answers[ $primary ] ?? array();
		$pack = $qp_reg !== null ? $qp_reg->get( $primary ) : null;
		$question_pack_complete = true;
		if ( $pack !== null && isset( $pack[ Industry_Question_Pack_Registry::FIELD_FIELDS ] ) && is_array( $pack[ Industry_Question_Pack_Registry::FIELD_FIELDS ] ) ) {
			$total = count( $pack[ Industry_Question_Pack_Registry::FIELD_FIELDS ] );
			$filled = 0;
			foreach ( $pack[ Industry_Question_Pack_Registry::FIELD_FIELDS ] as $field_def ) {
				$key = isset( $field_def['key'] ) && is_string( $field_def['key'] ) ? $field_def['key'] : '';
				if ( $key !== '' && isset( $primary_answers[ $key ] ) && $primary_answers[ $key ] !== '' ) {
					$filled++;
				}
			}
			$question_pack_complete = $total === 0 || $filled > 0;
		}

		if ( $question_pack_complete ) {
			return new Industry_Profile_Readiness_Result(
				Industry_Profile_Readiness_Result::STATE_READY,
				Industry_Profile_Readiness_Result::SCORE_READY,
				array(),
				$this->last_warnings,
				array( 'primary_set' => true, 'question_pack_complete' => true )
			);
		}

		return new Industry_Profile_Readiness_Result(
			Industry_Profile_Readiness_Result::STATE_PARTIAL,
			Industry_Profile_Readiness_Result::SCORE_PARTIAL,
			array(),
			$this->last_warnings,
			array( 'primary_set' => true, 'question_pack_complete' => false )
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function get_last_validation_errors(): array {
		return $this->last_errors;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_last_validation_warnings(): array {
		return $this->last_warnings;
	}
}
