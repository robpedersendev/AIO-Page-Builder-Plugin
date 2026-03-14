<?php
/**
 * Execution handler for update_menu actions (spec §34, §40.1, §40.2, §59.10; Prompt 083, 207).
 *
 * Delegates to Template_Menu_Apply_Service when envelope has template/hierarchy context (page_class
 * or template_aware_menu); otherwise to Menu_Change_Job_Service. Governed menu create/rename/replace/
 * update_existing and location assignment.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Handlers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Executor\Execution_Handler_Interface;
use AIOPageBuilder\Domain\Execution\Jobs\Menu_Change_Job_Service_Interface;
use AIOPageBuilder\Domain\Execution\Jobs\Menu_Change_Result;
use AIOPageBuilder\Domain\Execution\Menus\Template_Menu_Apply_Service_Interface;

/**
 * Handler for update_menu action type.
 */
final class Apply_Menu_Change_Handler implements Execution_Handler_Interface {

	/** @var Menu_Change_Job_Service_Interface */
	private $job_service;

	/** @var Template_Menu_Apply_Service_Interface|null */
	private $template_menu_apply_service;

	public function __construct(
		Menu_Change_Job_Service_Interface $job_service,
		?Template_Menu_Apply_Service_Interface $template_menu_apply_service = null
	) {
		$this->job_service = $job_service;
		$this->template_menu_apply_service = $template_menu_apply_service;
	}

	/**
	 * Executes menu change. Uses template-aware apply when envelope has hierarchy/template context.
	 *
	 * @param array<string, mixed> $envelope Governed action envelope.
	 * @return array<string, mixed> success, message, artifacts (menu_id, menu_apply_execution_result, etc.).
	 */
	public function execute( array $envelope ): array {
		if ( $this->template_menu_apply_service !== null && $this->has_template_hierarchy_context( $envelope ) ) {
			$result = $this->template_menu_apply_service->apply( $envelope );
			return $result->to_handler_result();
		}
		$result = $this->job_service->run( $envelope );
		return $result->to_handler_result();
	}

	/**
	 * Whether envelope has template/hierarchy context (page_class in items or template_aware_menu flag).
	 *
	 * @param array<string, mixed> $envelope
	 * @return bool
	 */
	private function has_template_hierarchy_context( array $envelope ): bool {
		$target = isset( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] ) && is_array( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ]
			: array();
		if ( ! empty( $target['template_aware_menu'] ) ) {
			return true;
		}
		$items = isset( $target['items'] ) && is_array( $target['items'] ) ? $target['items'] : array();
		foreach ( $items as $item ) {
			if ( is_array( $item ) && isset( $item['page_class'] ) && is_string( $item['page_class'] ) && trim( $item['page_class'] ) !== '' ) {
				return true;
			}
		}
		return false;
	}
}
