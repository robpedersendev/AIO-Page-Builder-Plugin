<?php
/**
 * Profile snapshot immutable value object (spec §22.11, v2-scope-backlog.md §3).
 *
 * Fields: snapshot_id, scope_type, scope_id, created_at, profile_schema_version,
 * brand_profile, business_profile, source. Persisted via Profile_Snapshot_Repository.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable snapshot payload. All fields are set at construction time and are read-only after.
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
	/**
	 * @var string Human-readable capture source: brand_profile_merge, business_profile_merge,
	 *             onboarding_completion, restore_event, manual, or other.
	 */
	public string $source;

	public function __construct(
		string $snapshot_id,
		string $scope_type,
		string $scope_id,
		string $created_at,
		string $profile_schema_version,
		array $brand_profile,
		array $business_profile,
		string $source = 'manual'
	) {
		$this->snapshot_id            = $snapshot_id;
		$this->scope_type             = $scope_type;
		$this->scope_id               = $scope_id;
		$this->created_at             = $created_at;
		$this->profile_schema_version = $profile_schema_version;
		$this->brand_profile          = $brand_profile;
		$this->business_profile       = $business_profile;
		$this->source                 = $source;
	}
}
