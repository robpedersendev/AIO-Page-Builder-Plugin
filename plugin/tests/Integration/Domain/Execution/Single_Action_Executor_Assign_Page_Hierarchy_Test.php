<?php
/**
 * Integration tests — ASSIGN_PAGE_HIERARCHY registration in executor (v2-scope-backlog.md §1).
 *
 * Verifies that:
 * - ASSIGN_PAGE_HIERARCHY is present in Execution_Action_Types::ALL (v2 contract).
 * - Assign_Page_Hierarchy_Handler is registered and discoverable through the dispatcher.
 * - Single_Action_Executor dispatches the action to the handler (not a stub handler).
 * - is_valid() accepts ASSIGN_PAGE_HIERARCHY.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Integration\Domain\Execution;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Execution\Executor\Execution_Dispatcher;
use AIOPageBuilder\Domain\Execution\Handlers\Assign_Page_Hierarchy_Handler;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 4 );
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Types.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Execution_Handler_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Execution_Dispatcher.php';
require_once $plugin_root . '/src/Domain/Execution/Handlers/Assign_Page_Hierarchy_Handler.php';

// ---------------------------------------------------------------------------
// Minimal WP stubs required by Assign_Page_Hierarchy_Handler at load time.
// ---------------------------------------------------------------------------
namespace AIOPageBuilder\Domain\Execution\Handlers;

if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\get_post' ) ) {
	function get_post( int $id ) { return null; }
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\wp_update_post' ) ) {
	function wp_update_post( array $args, bool $return_error = false ) { return 0; }
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\is_wp_error' ) ) {
	function is_wp_error( $thing ): bool { return false; }
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\__' ) ) {
	function __( string $text, string $domain = 'default' ): string { return $text; }
}

namespace AIOPageBuilder\Tests\Integration\Domain\Execution;

/**
 * Verifies ASSIGN_PAGE_HIERARCHY is fully wired into the execution engine.
 */
final class Single_Action_Executor_Assign_Page_Hierarchy_Test extends TestCase {

	public function test_assign_page_hierarchy_is_in_all(): void {
		$this->assertContains(
			Execution_Action_Types::ASSIGN_PAGE_HIERARCHY,
			Execution_Action_Types::ALL,
			'ASSIGN_PAGE_HIERARCHY must be in Execution_Action_Types::ALL for v2.'
		);
	}

	public function test_assign_page_hierarchy_is_valid(): void {
		$this->assertTrue(
			Execution_Action_Types::is_valid( Execution_Action_Types::ASSIGN_PAGE_HIERARCHY ),
			'is_valid() must return true for ASSIGN_PAGE_HIERARCHY in v2.'
		);
	}

	public function test_handler_is_discoverable_through_dispatcher(): void {
		$dispatcher = new Execution_Dispatcher();
		$dispatcher->register_handler(
			Execution_Action_Types::ASSIGN_PAGE_HIERARCHY,
			new Assign_Page_Hierarchy_Handler()
		);
		$this->assertTrue(
			$dispatcher->has_handler( Execution_Action_Types::ASSIGN_PAGE_HIERARCHY ),
			'Dispatcher must have a handler registered for ASSIGN_PAGE_HIERARCHY.'
		);
	}

	public function test_handler_class_is_assign_page_hierarchy_handler(): void {
		$dispatcher = new Execution_Dispatcher();
		$dispatcher->register_handler(
			Execution_Action_Types::ASSIGN_PAGE_HIERARCHY,
			new Assign_Page_Hierarchy_Handler()
		);
		$handler = $dispatcher->get_handler( Execution_Action_Types::ASSIGN_PAGE_HIERARCHY );
		$this->assertInstanceOf(
			Assign_Page_Hierarchy_Handler::class,
			$handler,
			'The handler returned by the dispatcher must be Assign_Page_Hierarchy_Handler, not a stub.'
		);
	}

	public function test_all_contains_create_menu(): void {
		// * CREATE_MENU is now executable in v2 via Create_Menu_Handler.
		$this->assertContains(
			Execution_Action_Types::CREATE_MENU,
			Execution_Action_Types::ALL
		);
	}

	public function test_all_does_not_contain_update_page_metadata(): void {
		// * UPDATE_PAGE_METADATA remains recommendation-only — not executable.
		$this->assertNotContains(
			Execution_Action_Types::UPDATE_PAGE_METADATA,
			Execution_Action_Types::ALL
		);
	}
}
