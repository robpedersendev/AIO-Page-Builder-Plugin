<?php
/**
 * Data access for Page Template objects (spec §10.2). Backing: CPT aio_page_template.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

/**
 * Repository → storage: Object_Type_Keys::PAGE_TEMPLATE (CPT).
 * Internal key: stable slug (e.g. landing_contact). Status: draft | active | inactive | deprecated.
 */
final class Page_Template_Repository extends Abstract_CPT_Repository {

	/** @inheritdoc */
	protected function get_post_type(): string {
		return Object_Type_Keys::PAGE_TEMPLATE;
	}
}
