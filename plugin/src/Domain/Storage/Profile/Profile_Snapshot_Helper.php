<?php
/**
 * Provides current profile payload for later snapshotting (spec §22.11). No snapshot persistence.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Returns a copy of current profile in snapshot payload shape (brand_profile + business_profile).
 * Callers must not mutate the returned array; snapshot_id, scope_type, scope_id, created_at, profile_schema_version
 * are added when creating an actual snapshot record elsewhere.
 */
final class Profile_Snapshot_Helper {

	/**
	 * Returns current profile as a copy suitable for snapshot payload. Keys: brand_profile, business_profile.
	 * Immutable-by-convention: do not modify the returned array.
	 *
	 * @param Profile_Store_Interface $store Current profile store.
	 * @return array{brand_profile: array<string, mixed>, business_profile: array<string, mixed>}
	 */
	public function get_current_for_snapshot( Profile_Store_Interface $store ): array {
		$full = $store->get_full_profile();
		return array(
			Profile_Schema::ROOT_BRAND    => $full[ Profile_Schema::ROOT_BRAND ],
			Profile_Schema::ROOT_BUSINESS => $full[ Profile_Schema::ROOT_BUSINESS ],
		);
	}
}
