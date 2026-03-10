<?php
/**
 * Data access for AI Run metadata/identity (spec §10.5). Backing: CPT aio_ai_run; raw artifacts in table.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

/**
 * Repository → storage: Object_Type_Keys::AI_RUN (CPT for metadata/identity).
 * Internal key: run_id (e.g. UUID). Status: pending_generation | completed | failed_validation | failed.
 */
final class AI_Run_Repository extends Abstract_CPT_Repository {

	/** @inheritdoc */
	protected function get_post_type(): string {
		return Object_Type_Keys::AI_RUN;
	}
}
