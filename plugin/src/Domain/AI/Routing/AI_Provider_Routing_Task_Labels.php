<?php
/**
 * Human-readable labels for {@see AI_Routing_Task} (admin routing UI and diagnostics).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Routing;

defined( 'ABSPATH' ) || exit;

final class AI_Provider_Routing_Task_Labels {

	/**
	 * @return array<string, string> task_id => short admin label
	 */
	public static function labels(): array {
		return array(
			AI_Routing_Task::ONBOARDING_PLANNING            => __( 'Onboarding planning', 'aio-page-builder' ),
			AI_Routing_Task::BUILD_PLAN_GENERATION          => __( 'Build plan generation', 'aio-page-builder' ),
			AI_Routing_Task::TEMPLATE_LAB_COMPOSITION_DRAFT => __( 'Template lab — composition draft', 'aio-page-builder' ),
			AI_Routing_Task::TEMPLATE_LAB_PAGE_TEMPLATE_DRAFT => __( 'Template lab — page template draft', 'aio-page-builder' ),
			AI_Routing_Task::TEMPLATE_LAB_SECTION_TEMPLATE_DRAFT => __( 'Template lab — section template draft', 'aio-page-builder' ),
			AI_Routing_Task::TEMPLATE_LAB_REPAIR            => __( 'Template lab — repair / validation retry', 'aio-page-builder' ),
			AI_Routing_Task::TEMPLATE_LAB_CHAT              => __( 'Template lab — chat / threaded interaction', 'aio-page-builder' ),
		);
	}

	public static function label_for( string $task_id ): string {
		$map = self::labels();
		return $map[ $task_id ] ?? $task_id;
	}
}
