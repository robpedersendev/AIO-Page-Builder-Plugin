<?php
/**
 * Data access for Documentation objects (spec §10.7). Backing: CPT aio_documentation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

/**
 * Repository → storage: Object_Type_Keys::DOCUMENTATION (CPT).
 * Internal key: documentation_id (e.g. UUID or slug). Status: draft | active | archived.
 */
final class Documentation_Repository extends Abstract_CPT_Repository {

	/** @inheritdoc */
	protected function get_post_type(): string {
		return Object_Type_Keys::DOCUMENTATION;
	}
}
