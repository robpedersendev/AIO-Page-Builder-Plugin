<?php
/**
 * Maps routing tasks to structured-output schema refs (diagnostics only; no runtime coupling).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Routing;

use AIOPageBuilder\Domain\AI\Validation\AI_Template_Lab_Draft_Schema_Refs;

defined( 'ABSPATH' ) || exit;

final class AI_Routing_Task_Schema_Map {

	/**
	 * @return string|null Schema ref or null when no single structured schema applies.
	 */
	public static function structured_schema_ref_for_task( string $task_id ): ?string {
		switch ( $task_id ) {
			case AI_Routing_Task::TEMPLATE_LAB_COMPOSITION_DRAFT:
				return AI_Template_Lab_Draft_Schema_Refs::COMPOSITION_DRAFT;
			case AI_Routing_Task::TEMPLATE_LAB_PAGE_TEMPLATE_DRAFT:
				return AI_Template_Lab_Draft_Schema_Refs::PAGE_TEMPLATE_DRAFT;
			case AI_Routing_Task::TEMPLATE_LAB_SECTION_TEMPLATE_DRAFT:
				return AI_Template_Lab_Draft_Schema_Refs::SECTION_TEMPLATE_DRAFT;
			case AI_Routing_Task::TEMPLATE_LAB_REPAIR:
				return AI_Template_Lab_Draft_Schema_Refs::REPAIR_RESULT;
			default:
				return null;
		}
	}
}
