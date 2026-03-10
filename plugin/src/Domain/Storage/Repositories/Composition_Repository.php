<?php
/**
 * Data access for Custom Template Composition objects (spec §10.3). Backing: CPT aio_composition.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

/**
 * Repository → storage: Object_Type_Keys::COMPOSITION (CPT).
 * Internal key: composition_id (e.g. UUID). Status: draft | active | archived.
 */
final class Composition_Repository extends Abstract_CPT_Repository {

	/** @inheritdoc */
	protected function get_post_type(): string {
		return Object_Type_Keys::COMPOSITION;
	}
}
