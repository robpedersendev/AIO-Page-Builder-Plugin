<?php
/**
 * Contract for menu/navigation change job (spec §34, §40.2; Prompt 083).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Menu change job. Implemented by Menu_Change_Job_Service; tests may stub.
 */
interface Menu_Change_Job_Service_Interface {

	/**
	 * Runs the menu change flow.
	 *
	 * @param array<string, mixed> $envelope Validated action envelope.
	 * @return Menu_Change_Result
	 */
	public function run( array $envelope ): Menu_Change_Result;
}
