<?php
/**
 * Resolves effective industry context (parent + optional subtype) from profile (industry-subtype-extension-contract.md; Prompt 414).
 * Falls back to parent industry only when subtype is missing or invalid.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Profile;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;

/**
 * Resolves parent industry and optional subtype from profile. Safe fallback when subtype ref is invalid.
 */
final class Industry_Subtype_Resolver {

	/** @var Industry_Profile_Repository */
	private Industry_Profile_Repository $profile_repository;

	/** @var Industry_Subtype_Registry|null */
	private ?Industry_Subtype_Registry $subtype_registry;

	public function __construct( Industry_Profile_Repository $profile_repository, ?Industry_Subtype_Registry $subtype_registry = null ) {
		$this->profile_repository = $profile_repository;
		$this->subtype_registry   = $subtype_registry;
	}

	/**
	 * Resolves effective industry context from current stored profile.
	 * When subtype is valid (exists, parent match, active), context includes subtype; otherwise parent only.
	 *
	 * @return array{primary_industry_key: string, industry_subtype_key: string, resolved_subtype: array<string, mixed>|null, has_valid_subtype: bool}
	 */
	public function resolve(): array {
		return $this->resolve_from_profile( $this->profile_repository->get_profile() );
	}

	/**
	 * Resolves effective industry context from a given profile (normalized or raw; will be normalized).
	 *
	 * @param array<string, mixed> $profile Industry profile.
	 * @return array{primary_industry_key: string, industry_subtype_key: string, resolved_subtype: array<string, mixed>|null, has_valid_subtype: bool}
	 */
	public function resolve_from_profile( array $profile ): array {
		$normalized = Industry_Profile_Schema::normalize( $profile );
		$primary    = isset( $normalized[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $normalized[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $normalized[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		$subtype_key = isset( $normalized[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ) && is_string( $normalized[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
			? trim( $normalized[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
			: '';

		$resolved_subtype   = null;
		$has_valid_subtype  = false;
		$effective_subtype_key = '';

		if ( $subtype_key !== '' && $this->subtype_registry !== null ) {
			$def = $this->subtype_registry->get( $subtype_key );
			if ( $def !== null ) {
				$parent = isset( $def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ) && is_string( $def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] )
					? trim( $def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] )
					: '';
				$status = isset( $def[ Industry_Subtype_Registry::FIELD_STATUS ] ) && is_string( $def[ Industry_Subtype_Registry::FIELD_STATUS ] )
					? $def[ Industry_Subtype_Registry::FIELD_STATUS ]
					: '';
				if ( $parent === $primary && $status === Industry_Subtype_Registry::STATUS_ACTIVE ) {
					$resolved_subtype   = $def;
					$has_valid_subtype  = true;
					$effective_subtype_key = $subtype_key;
				}
			}
		}

		return array(
			'primary_industry_key'   => $primary,
			'industry_subtype_key'  => $effective_subtype_key,
			'resolved_subtype'      => $resolved_subtype,
			'has_valid_subtype'     => $has_valid_subtype,
		);
	}
}
