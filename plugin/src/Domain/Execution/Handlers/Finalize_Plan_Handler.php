<?php
/**
 * Execution handler for finalize_plan actions (spec §37, §40.1, §40.2, §40.10; Prompt 084).
 *
 * Delegates to Finalization_Job_Service. Governed finalization gate only; no rollback or export.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Handlers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Executor\Execution_Handler_Interface;
use AIOPageBuilder\Domain\Execution\Jobs\Finalization_Job_Service;
use AIOPageBuilder\Domain\Execution\Jobs\Finalization_Result;

/**
 * Handler for finalize_plan action type.
 */
final class Finalize_Plan_Handler implements Execution_Handler_Interface {

	/** @var Finalization_Job_Service */
	private $job_service;

	public function __construct( Finalization_Job_Service $job_service ) {
		$this->job_service = $job_service;
	}

	/**
	 * Executes finalization. Envelope validated by Single_Action_Executor.
	 *
	 * @param array<string, mixed> $envelope Plan-level envelope (plan_id, actor context).
	 * @return array<string, mixed> success, message, artifacts (completion_summary, finalized_at, etc.).
	 */
	public function execute( array $envelope ): array {
		$result = $this->job_service->run( $envelope );
		return $result->to_handler_result();
	}
}
