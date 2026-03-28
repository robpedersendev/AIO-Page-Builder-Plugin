<?php
/**
 * Stable prompt-pack identifiers aligned with routing tasks (auditable, log-safe; not prompt body storage).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Planning;

use AIOPageBuilder\Domain\AI\Routing\AI_Routing_Task;

defined( 'ABSPATH' ) || exit;

final class AI_Prompt_Pack_Keys {

	public const ONBOARDING_PLANNING = 'aio.prompt_pack.onboarding_planning';

	public const BUILD_PLAN_GENERATION = 'aio.prompt_pack.build_plan_generation';

	public const TEMPLATE_LAB_COMPOSITION_DRAFT = 'aio.prompt_pack.template_lab.composition_draft';

	public const TEMPLATE_LAB_PAGE_TEMPLATE_DRAFT = 'aio.prompt_pack.template_lab.page_template_draft';

	public const TEMPLATE_LAB_SECTION_TEMPLATE_DRAFT = 'aio.prompt_pack.template_lab.section_template_draft';

	public const TEMPLATE_LAB_REPAIR = 'aio.prompt_pack.template_lab.repair_loop';

	public const TEMPLATE_LAB_CHAT = 'aio.prompt_pack.template_lab.chat_shell';

	/** Reserved for future structured diff / explain tasks (no prompt text stored here). */
	public const TEMPLATE_LAB_STRUCTURED_EXPLAIN_DIFF = 'aio.prompt_pack.template_lab.structured_explain_diff';

	public static function for_routing_task( string $routing_task ): string {
		return match ( $routing_task ) {
			AI_Routing_Task::ONBOARDING_PLANNING => self::ONBOARDING_PLANNING,
			AI_Routing_Task::BUILD_PLAN_GENERATION => self::BUILD_PLAN_GENERATION,
			AI_Routing_Task::TEMPLATE_LAB_COMPOSITION_DRAFT => self::TEMPLATE_LAB_COMPOSITION_DRAFT,
			AI_Routing_Task::TEMPLATE_LAB_PAGE_TEMPLATE_DRAFT => self::TEMPLATE_LAB_PAGE_TEMPLATE_DRAFT,
			AI_Routing_Task::TEMPLATE_LAB_SECTION_TEMPLATE_DRAFT => self::TEMPLATE_LAB_SECTION_TEMPLATE_DRAFT,
			AI_Routing_Task::TEMPLATE_LAB_REPAIR => self::TEMPLATE_LAB_REPAIR,
			AI_Routing_Task::TEMPLATE_LAB_CHAT => self::TEMPLATE_LAB_CHAT,
			default => 'aio.prompt_pack.unmapped.' . substr( \sanitize_key( $routing_task ), 0, 80 ),
		};
	}
}
