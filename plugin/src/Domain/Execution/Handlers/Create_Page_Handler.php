<?php
/**
 * Execution handler for create_page actions (spec §40.1, §40.2; Prompt 081).
 *
 * Delegates to Create_Page_Job_Service. Used by Single_Action_Executor via Execution_Dispatcher.
 * New-page creation only; no replacement, menu, or token logic.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Handlers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Executor\Execution_Handler_Interface;
use AIOPageBuilder\Domain\Execution\Jobs\Create_Page_Job_Service_Interface;
use AIOPageBuilder\Domain\Execution\Jobs\Create_Page_Result;

/**
 * Handler for create_page action type. Runs pipeline via Create_Page_Job_Service.
 */
final class Create_Page_Handler implements Execution_Handler_Interface {

	/** @var Create_Page_Job_Service_Interface */
	private $job_service;

	public function __construct( Create_Page_Job_Service_Interface $job_service ) {
		$this->job_service = $job_service;
	}

	/**
	 * Executes new-page creation. Envelope already validated by Single_Action_Executor.
	 *
	 * @param array<string, mixed> $envelope Governed action envelope.
	 * @return array<string, mixed> success, message, artifacts; optional errors.
	 */
	public function execute( array $envelope ): array {
		$result = $this->job_service->run( $envelope );
		return $result->to_handler_result();
	}
}
