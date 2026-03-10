<?php
/**
 * Data access for Build Plan objects (spec §10.4). Backing: CPT aio_build_plan.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

/**
 * Repository → storage: Object_Type_Keys::BUILD_PLAN (CPT).
 * Internal key: plan_id (e.g. UUID). Status: pending_review | approved | rejected | in_progress | completed | superseded.
 */
final class Build_Plan_Repository extends Abstract_CPT_Repository {

	/** @inheritdoc */
	protected function get_post_type(): string {
		return Object_Type_Keys::BUILD_PLAN;
	}
}
