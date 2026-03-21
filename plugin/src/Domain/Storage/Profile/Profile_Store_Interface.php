<?php
/**
 * Read contract for current profile store (spec §22). Allows test spies without requiring the concrete store.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Minimum readable profile-store contract required by snapshot capture services.
 */
interface Profile_Store_Interface {

	/**
	 * Returns the normalized brand profile array.
	 *
	 * @return array<string, mixed>
	 */
	public function get_brand_profile(): array;

	/**
	 * Returns the normalized business profile array.
	 *
	 * @return array<string, mixed>
	 */
	public function get_business_profile(): array;

	/**
	 * Returns the full profile payload: { brand_profile: array, business_profile: array }.
	 *
	 * @return array{brand_profile: array<string, mixed>, business_profile: array<string, mixed>}
	 */
	public function get_full_profile(): array;
}
