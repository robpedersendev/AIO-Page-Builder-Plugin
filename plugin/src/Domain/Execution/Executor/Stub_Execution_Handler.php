<?php
/**
 * Stub execution handler for action types without a registered handler (Prompt 079).
 *
 * Used by Execution_Dispatcher::get_handler() when no handler is registered for an action type.
 * Single_Action_Executor gates unregistered types via has_handler() and returns refused before
 * dispatch, so this stub is only reached if dispatch is invoked without that check (e.g. tests or recovery).
 * Returns a structured unavailable result so callers get a consistent, user-safe message.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Executor;

defined( 'ABSPATH' ) || exit;

/**
 * Handler fallback that reports success=false with a clear unavailability message.
 */
final class Stub_Execution_Handler implements Execution_Handler_Interface {

	/** @var string Action type this stub is for (for messages). */
	private $action_type;

	public function __construct( string $action_type = '' ) {
		$this->action_type = $action_type;
	}

	/**
	 * Returns action-not-available result shape.
	 *
	 * @param array<string, mixed> $envelope
	 * @return array<string, mixed>
	 */
	public function execute( array $envelope ): array {
		return array(
			'success'  => false,
			'message'  => __( 'This action type is not available in this version.', 'aio-page-builder' ),
			'artifacts' => array(),
		);
	}
}
