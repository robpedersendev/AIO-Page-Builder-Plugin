<?php
/**
 * Contract for existing-page replace/rebuild job (spec §32, §40.2; Prompt 082).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Existing-page replace/rebuild job. Implemented by Replace_Page_Job_Service; tests may stub.
 */
interface Replace_Page_Job_Service_Interface {

	/**
	 * Runs the replace/rebuild flow.
	 *
	 * @param array<string, mixed> $envelope Validated action envelope (target_reference, snapshot_ref when required).
	 * @return Replace_Page_Result
	 */
	public function run( array $envelope ): Replace_Page_Result;
}
