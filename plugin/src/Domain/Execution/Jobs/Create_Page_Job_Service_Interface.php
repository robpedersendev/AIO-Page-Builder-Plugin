<?php
/**
 * Contract for new-page creation job (spec §33.5; Prompt 081).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * New-page creation job. Implemented by Create_Page_Job_Service; tests may stub.
 */
interface Create_Page_Job_Service_Interface {

	/**
	 * Runs the new-page creation flow.
	 *
	 * @param array<string, mixed> $envelope Validated action envelope.
	 * @return Create_Page_Result
	 */
	public function run( array $envelope ): Create_Page_Result;
}
