<?php
/**
 * Named states for template-lab AI runs (distinct from build-plan orchestration).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

/**
 * Explicit lifecycle; persisted under run metadata key template_lab.state.
 */
final class Template_Lab_Run_States {

	public const QUEUED              = 'queued';
	public const REQUESTING_PROVIDER = 'requesting_provider';
	public const VALIDATING          = 'validating';
	public const REPAIRING           = 'repairing';
	public const DRAFT_SAVED         = 'draft_saved';
	public const COMPLETED           = 'completed';
	public const FAILED              = 'failed';
	public const TIMED_OUT           = 'timed_out';
}
