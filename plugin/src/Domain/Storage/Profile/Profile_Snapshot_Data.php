<?php
/**
 * Profile snapshot payload shape (spec §22.11, SPR-010). No persistence in v1; see profile-snapshot-schema.md.
 *
 * Schema/type definition for v2 implementation. Used for type-hint and documentation in v1.
 * TODO: v2 — implement full persistence: storage table, Profile_Snapshot_Repository, capture on onboarding
 *   completion and profile save, history UI, export/restore inclusion.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable snapshot payload: snapshot_id, scope_type, scope_id, created_at, profile_schema_version, brand_profile, business_profile.
 * All fields and nested shape are defined in docs/schemas/profile-snapshot-schema.md. This class exists for type-hint and documentation only; no persistence or UI execution.
 */
final class Profile_Snapshot_Data {

	public string $snapshot_id;
	public string $scope_type;
	public string $scope_id;
	public string $created_at;
	public string $profile_schema_version;
	/** @var array<string, mixed> Shape per profile-schema.md §3 */
	public array $brand_profile;
	/** @var array<string, mixed> Shape per profile-schema.md §4–9 */
	public array $business_profile;

	public function __construct(
		string $snapshot_id,
		string $scope_type,
		string $scope_id,
		string $created_at,
		string $profile_schema_version,
		array $brand_profile,
		array $business_profile
	) {
		$this->snapshot_id            = $snapshot_id;
		$this->scope_type             = $scope_type;
		$this->scope_id               = $scope_id;
		$this->created_at             = $created_at;
		$this->profile_schema_version = $profile_schema_version;
		$this->brand_profile          = $brand_profile;
		$this->business_profile       = $business_profile;
	}
}
