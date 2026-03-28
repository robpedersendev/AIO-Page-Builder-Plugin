<?php
/**
 * Central schema-reference strings for AI-generated draft payloads (template-lab / repair).
 * These are not identical to canonical registry storage schemas: drafts may carry AI metadata and
 * are normalized separately before persistence to Composition / Page / Section repositories.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Validation;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin-owned refs passed as structured_output_schema_ref and resolved by AI_Output_Validator.
 */
final class AI_Template_Lab_Draft_Schema_Refs {

	public const COMPOSITION_DRAFT      = 'aio/ai-draft/composition-v1';
	public const PAGE_TEMPLATE_DRAFT    = 'aio/ai-draft/page-template-v1';
	public const SECTION_TEMPLATE_DRAFT = 'aio/ai-draft/section-template-v1';
	public const REPAIR_RESULT          = 'aio/ai-draft/repair-result-v1';

	/** Draft payload: monotonic draft format version (distinct from registry schema_version). */
	public const KEY_AI_DRAFT_VERSION = 'ai_draft_version';

	public const DRAFT_VERSION_VALUE = '1';

	/**
	 * @return array<int, string>
	 */
	public static function all(): array {
		return array(
			self::COMPOSITION_DRAFT,
			self::PAGE_TEMPLATE_DRAFT,
			self::SECTION_TEMPLATE_DRAFT,
			self::REPAIR_RESULT,
		);
	}

	public static function is_registered( string $schema_ref ): bool {
		return in_array( $schema_ref, self::all(), true );
	}
}
