<?php
/**
 * Contract for token-set apply job (spec §35, §40.2; Prompt 083).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Token-set apply job. Implemented by Token_Set_Job_Service; tests may stub.
 */
interface Token_Set_Job_Service_Interface {

	/**
	 * Runs the token apply flow.
	 *
	 * @param array<string, mixed> $envelope Validated action envelope.
	 * @return Token_Set_Result
	 */
	public function run( array $envelope ): Token_Set_Result;
}
