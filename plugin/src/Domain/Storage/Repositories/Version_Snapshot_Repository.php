<?php
/**
 * Data access for Version Snapshot objects (spec §10.8). Backing: CPT aio_version_snapshot.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

/**
 * Repository → storage: Object_Type_Keys::VERSION_SNAPSHOT (CPT).
 * Internal key: snapshot_id (e.g. UUID). Status: active | superseded.
 */
final class Version_Snapshot_Repository extends Abstract_CPT_Repository {

	/** @inheritdoc */
	protected function get_post_type(): string {
		return Object_Type_Keys::VERSION_SNAPSHOT;
	}
}
