<?php
/**
 * Dispatches execution to action-specific handlers (spec §40.1, §40.2; Prompt 079).
 *
 * Single_Action_Executor uses the dispatcher after validation. Handlers are registered
 * by action type; missing handlers use a stub that returns "not implemented".
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Executor;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;

/**
 * Typed dispatch: action_type → Execution_Handler_Interface.
 */
final class Execution_Dispatcher {

	/** @var array<string, Execution_Handler_Interface> */
	private $handlers = array();

	/** @var Stub_Execution_Handler|null */
	private $stub_handler;

	/**
	 * Registers a handler for an action type.
	 *
	 * @param string                      $action_type One of Execution_Action_Types::*.
	 * @param Execution_Handler_Interface $handler
	 * @return void
	 */
	public function register_handler( string $action_type, Execution_Handler_Interface $handler ): void {
		$this->handlers[ $action_type ] = $handler;
	}

	/**
	 * Returns the handler for the action type, or a stub if none registered.
	 *
	 * @param string $action_type
	 * @return Execution_Handler_Interface
	 */
	public function get_handler( string $action_type ): Execution_Handler_Interface {
		if ( isset( $this->handlers[ $action_type ] ) ) {
			return $this->handlers[ $action_type ];
		}
		if ( $this->stub_handler === null ) {
			$this->stub_handler = new Stub_Execution_Handler();
		}
		return new Stub_Execution_Handler( $action_type );
	}

	/**
	 * Dispatches the envelope to the handler for its action_type. No validation here; executor validates first.
	 *
	 * @param array<string, mixed> $envelope Validated action envelope.
	 * @return array<string, mixed> Handler result (success, message, artifacts, etc.).
	 */
	public function dispatch( array $envelope ): array {
		$action_type = isset( $envelope['action_type'] ) && is_string( $envelope['action_type'] ) ? $envelope['action_type'] : '';
		$handler     = $this->get_handler( $action_type );
		return $handler->execute( $envelope );
	}

	/**
	 * Returns whether a concrete (non-stub) handler is registered for the action type.
	 *
	 * @param string $action_type
	 * @return bool
	 */
	public function has_handler( string $action_type ): bool {
		return isset( $this->handlers[ $action_type ] );
	}
}
