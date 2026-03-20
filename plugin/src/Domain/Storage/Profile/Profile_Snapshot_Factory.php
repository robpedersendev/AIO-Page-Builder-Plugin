<?php
/**
 * Builds Profile_Snapshot_Data from current profile store state (v2-scope-backlog.md §3).
 *
 * Callers provide source metadata; the factory assigns a unique snapshot_id and timestamp.
 * Does not persist — call Profile_Snapshot_Repository::save() after building.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Profile;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Versions;

/**
 * Creates versioned Profile_Snapshot_Data from current profile store state.
 */
final class Profile_Snapshot_Factory {

	/** @var Profile_Snapshot_Helper */
	private Profile_Snapshot_Helper $helper;

	public function __construct( Profile_Snapshot_Helper $helper ) {
		$this->helper = $helper;
	}

	/**
	 * Builds a new snapshot from the current profile store state.
	 *
	 * @param Profile_Store $store       Current profile store (read-only access).
	 * @param string        $source      Capture context: brand_profile_merge, business_profile_merge,
	 *                                   onboarding_completion, restore_event, manual.
	 * @param string        $scope_type  Snapshot scope type per Profile_Schema::SNAPSHOT_SCOPE_TYPES.
	 * @param string        $scope_id    Optional scope reference (e.g. AI run ID or plan ID).
	 * @return Profile_Snapshot_Data     Unsaved snapshot value object.
	 */
	public function build(
		Profile_Store $store,
		string $source = 'manual',
		string $scope_type = 'other',
		string $scope_id = ''
	): Profile_Snapshot_Data {
		$payload = $this->helper->get_current_for_snapshot( $store );

		return new Profile_Snapshot_Data(
			$this->generate_id(),
			$scope_type,
			$scope_id,
			\gmdate( 'Y-m-d H:i:s' ),
			Versions::PROFILE_SCHEMA_VERSION,
			is_array( $payload[ Profile_Schema::ROOT_BRAND ] ?? null )
				? $payload[ Profile_Schema::ROOT_BRAND ]
				: array(),
			is_array( $payload[ Profile_Schema::ROOT_BUSINESS ] ?? null )
				? $payload[ Profile_Schema::ROOT_BUSINESS ]
				: array(),
			$source
		);
	}

	/**
	 * Generates a unique snapshot ID in the form `snap_{timestamp}_{rand}`.
	 *
	 * @return string
	 */
	private function generate_id(): string {
		return 'snap_' . \gmdate( 'YmdHis' ) . '_' . \bin2hex( \random_bytes( 4 ) );
	}
}
