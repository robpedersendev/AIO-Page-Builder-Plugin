<?php
/**
 * Contract for template-aware menu apply (spec §59.10; Prompt 207).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Menus;

defined( 'ABSPATH' ) || exit;

/**
 * Template-aware menu apply. Implemented by Template_Menu_Apply_Service; tests may stub.
 */
interface Template_Menu_Apply_Service_Interface {

	/**
	 * Applies template-aware menu changes (validate target, order by hierarchy, apply with parent/child).
	 *
	 * @param array<string, mixed> $envelope Validated action envelope.
	 * @return Template_Menu_Apply_Result
	 */
	public function apply( array $envelope ): Template_Menu_Apply_Result;
}
