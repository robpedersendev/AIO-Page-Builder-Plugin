<?php
/**
 * Execution handler for replace_page actions (spec §32, §40.1, §40.2; Prompt 082).
 *
 * Delegates to Replace_Page_Job_Service. Existing-page rebuild or replacement only;
 * no rollback, finalization, menu, or token logic.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Handlers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Executor\Execution_Handler_Interface;
use AIOPageBuilder\Domain\Execution\Jobs\Replace_Page_Job_Service_Interface;
use AIOPageBuilder\Domain\Execution\Jobs\Replace_Page_Result;

/**
 * Handler for replace_page action type. Runs rebuild/replace via Replace_Page_Job_Service.
 */
final class Replace_Page_Handler implements Execution_Handler_Interface {

	/** @var Replace_Page_Job_Service_Interface */
	private $job_service;

	public function __construct( Replace_Page_Job_Service_Interface $job_service ) {
		$this->job_service = $job_service;
	}

	/**
	 * Executes existing-page update/replacement. Envelope validated by Single_Action_Executor.
	 *
	 * @param array<string, mixed> $envelope Governed action envelope (target_reference, snapshot_ref when required).
	 * @return array<string, mixed> success, message, artifacts (snapshot_ref, superseded_page_ref when applicable).
	 */
	public function execute( array $envelope ): array {
		$result = $this->job_service->run( $envelope );
		return $result->to_handler_result();
	}
}
