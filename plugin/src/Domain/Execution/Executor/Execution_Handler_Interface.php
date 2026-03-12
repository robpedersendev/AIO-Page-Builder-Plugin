<?php
/**
 * Action-specific execution handler interface (spec §40.2; Prompt 079).
 *
 * Handlers perform the actual mutation for one action type. The Single_Action_Executor
 * validates envelope, permissions, dependencies, and snapshot; then dispatches to the
 * handler. Handlers must not skip the central executor flow.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Executor;

defined( 'ABSPATH' ) || exit;

/**
 * One handler per action type (create_page, replace_page, update_menu, etc.).
 * Receives validated envelope; returns result shape for the executor to wrap.
 */
interface Execution_Handler_Interface {

	/**
	 * Executes the action. Envelope has already passed validation, approval, permission, and dependency checks.
	 * Snapshot preflight has been satisfied if required. Caller holds lock for the scope.
	 *
	 * @param array<string, mixed> $envelope Governed action envelope (execution-action-contract.md).
	 * @return array<string, mixed> Handler result: at least 'success' (bool); optional 'message', 'artifacts', 'partial_succeeded', 'partial_failed'.
	 *         On failure the handler may throw or return success=false with error details; executor will convert to Execution_Result::failed().
	 */
	public function execute( array $envelope ): array;
}
