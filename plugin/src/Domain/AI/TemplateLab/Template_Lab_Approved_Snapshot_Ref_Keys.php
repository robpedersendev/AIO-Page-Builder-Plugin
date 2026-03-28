<?php
/**
 * Stable keys for approved snapshot references on chat sessions and template-lab apply flow.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

/**
 * Values are stored in chat session JSON (sanitized) and used only after explicit approval.
 */
final class Template_Lab_Approved_Snapshot_Ref_Keys {

	public const RUN_POST_ID = 'run_post_id';

	/** Must match template_lab_trace.artifact_fingerprint on the run. */
	public const ARTIFACT_FINGERPRINT = 'artifact_fingerprint';

	/** composition | page_template | section_template */
	public const TARGET_KIND = 'target_kind';

	/** pending | approved */
	public const APPROVAL_STATE = 'approval_state';

	public const APPROVAL_PENDING  = 'pending';
	public const APPROVAL_APPROVED = 'approved';
	public const TARGET_COMPOSITION  = 'composition';
	public const TARGET_PAGE         = 'page_template';
	public const TARGET_SECTION      = 'section_template';

	/**
	 * @return array<int, string>
	 */
	public static function valid_target_kinds(): array {
		return array(
			self::TARGET_COMPOSITION,
			self::TARGET_PAGE,
			self::TARGET_SECTION,
		);
	}

	public static function is_valid_target_kind( string $kind ): bool {
		return in_array( $kind, self::valid_target_kinds(), true );
	}
}
