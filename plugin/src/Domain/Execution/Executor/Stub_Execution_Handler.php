<?php
/**
 * Stub execution handler for action types not yet implemented (Prompt 079).
 *
 * Returns a structured "not implemented" result so the executor can complete the flow
 * without concrete mutation logic.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Executor;

defined( 'ABSPATH' ) || exit;

/**
 * No-op handler that reports success=false and message "Not implemented".
 */
final class Stub_Execution_Handler implements Execution_Handler_Interface {

	/** @var string Action type this stub is for (for messages). */
	private $action_type;

	public function __construct( string $action_type = '' ) {
		$this->action_type = $action_type;
	}

	/**
	 * Returns not-implemented result shape.
	 *
	 * @param array<string, mixed> $envelope
	 * @return array<string, mixed>
	 */
	public function execute( array $envelope ): array {
		return array(
			'success'  => false,
			'message'  => $this->action_type !== '' ? sprintf( __( 'Action type "%s" is not yet implemented.', 'aio-page-builder' ), $this->action_type ) : __( 'Action not yet implemented.', 'aio-page-builder' ),
			'artifacts' => array(),
		);
	}
}
