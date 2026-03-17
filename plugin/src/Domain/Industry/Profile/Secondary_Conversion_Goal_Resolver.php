<?php
/**
 * Resolves primary and optional secondary conversion goal from industry profile (Prompt 529).
 * secondary-conversion-goal-contract.md. Precedence-aware; safe fallback when unset or invalid.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Returns resolved primary_goal_key and secondary_goal_key for consumers.
 * Secondary is empty when not set, invalid, or same as primary.
 */
final class Secondary_Conversion_Goal_Resolver {

	/** Launch goal set (conversion-goal-profile-contract; same as primary). */
	private const ALLOWED_GOAL_KEYS = array( 'calls', 'bookings', 'estimates', 'consultations', 'valuations', 'lead_capture' );

	/** @var Industry_Profile_Repository|null */
	private $profile_repo;

	public function __construct( ?Industry_Profile_Repository $profile_repo = null ) {
		$this->profile_repo = $profile_repo;
	}

	/**
	 * Resolves primary and secondary conversion goal from profile. Safe fallback.
	 *
	 * @param array<string, mixed>|null $profile Normalized industry profile (or null to read from repo).
	 * @return array{primary_goal_key: string, secondary_goal_key: string}
	 */
	public function resolve( ?array $profile = null ): array {
		if ( $profile === null && $this->profile_repo !== null ) {
			$profile = $this->profile_repo->get_profile();
		}
		if ( ! is_array( $profile ) ) {
			return array( 'primary_goal_key' => '', 'secondary_goal_key' => '' );
		}
		$normalized = Industry_Profile_Schema::normalize( $profile );
		$primary = isset( $normalized[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] ) && is_string( $normalized[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] )
			? trim( $normalized[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] )
			: '';
		$secondary_raw = isset( $normalized[ Industry_Profile_Schema::FIELD_SECONDARY_CONVERSION_GOAL_KEY ] ) && is_string( $normalized[ Industry_Profile_Schema::FIELD_SECONDARY_CONVERSION_GOAL_KEY ] )
			? trim( $normalized[ Industry_Profile_Schema::FIELD_SECONDARY_CONVERSION_GOAL_KEY ] )
			: '';

		if ( $primary !== '' && ! $this->is_allowed_key( $primary ) ) {
			$primary = '';
		}
		$secondary = '';
		if ( $primary !== '' && $secondary_raw !== '' && $this->is_allowed_key( $secondary_raw ) && $secondary_raw !== $primary ) {
			$secondary = $secondary_raw;
		}
		return array( 'primary_goal_key' => $primary, 'secondary_goal_key' => $secondary );
	}

	private function is_allowed_key( string $key ): bool {
		return in_array( $key, self::ALLOWED_GOAL_KEYS, true );
	}
}
