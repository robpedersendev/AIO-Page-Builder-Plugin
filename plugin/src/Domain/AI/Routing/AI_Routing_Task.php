<?php
/**
 * Stable task identifiers for AI provider routing (spec §25.1).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Named tasks used by Default_AI_Provider_Router and settings task_routing slice.
 */
final class AI_Routing_Task {

	public const ONBOARDING_PLANNING = 'onboarding_planning';

	public const TEMPLATE_LAB_COMPOSITION_DRAFT = 'template_lab_composition_draft';

	public const TEMPLATE_LAB_PAGE_TEMPLATE_DRAFT = 'template_lab_page_template_draft';

	public const TEMPLATE_LAB_SECTION_TEMPLATE_DRAFT = 'template_lab_section_template_draft';

	public const TEMPLATE_LAB_REPAIR = 'template_lab_repair';

	/**
	 * @return array<int, string>
	 */
	public static function all(): array {
		return array(
			self::ONBOARDING_PLANNING,
			self::TEMPLATE_LAB_COMPOSITION_DRAFT,
			self::TEMPLATE_LAB_PAGE_TEMPLATE_DRAFT,
			self::TEMPLATE_LAB_SECTION_TEMPLATE_DRAFT,
			self::TEMPLATE_LAB_REPAIR,
		);
	}
}
