<?php
/**
 * Execution handler for apply_token_set actions (spec §35, §40.1, §40.2; Prompt 083).
 *
 * Delegates to Token_Set_Job_Service. Governed token value application only;
 * does not change selector names or structural markup.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Handlers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Executor\Execution_Handler_Interface;
use AIOPageBuilder\Domain\Execution\Jobs\Token_Set_Job_Service_Interface;
use AIOPageBuilder\Domain\Execution\Jobs\Token_Set_Result;

/**
 * Handler for apply_token_set action type.
 */
final class Apply_Token_Set_Handler implements Execution_Handler_Interface {

	/** @var Token_Set_Job_Service_Interface */
	private $job_service;

	public function __construct( Token_Set_Job_Service_Interface $job_service ) {
		$this->job_service = $job_service;
	}

	/**
	 * Executes token apply. Envelope validated by Single_Action_Executor.
	 *
	 * @param array<string, mixed> $envelope Governed action envelope.
	 * @return array<string, mixed> success, message, artifacts (token_group, token_name, previous_value_ref, etc.).
	 */
	public function execute( array $envelope ): array {
		$result = $this->job_service->run( $envelope );
		return $result->to_handler_result();
	}
}
