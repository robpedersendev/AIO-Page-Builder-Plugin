<?php
/**
 * Data access for Section Template objects (spec §10.1). Backing: CPT aio_section_template.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

/**
 * Repository → storage: Object_Type_Keys::SECTION_TEMPLATE (CPT).
 * Internal key: stable slug (e.g. hero_section_v1). Status: draft | active | inactive | deprecated.
 */
final class Section_Template_Repository extends Abstract_CPT_Repository {

	/** @inheritdoc */
	protected function get_post_type(): string {
		return Object_Type_Keys::SECTION_TEMPLATE;
	}
}
