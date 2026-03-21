<?php
/**
 * Integration tests — CREATE_MENU registration in executor (v2-scope-backlog.md §2).
 *
 * Verifies that:
 * - CREATE_MENU is present in Execution_Action_Types::ALL (v2 contract).
 * - Create_Menu_Handler is registered and discoverable through the dispatcher.
 * - is_valid() accepts CREATE_MENU.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Integration\Domain\Execution;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Execution\Executor\Execution_Dispatcher;
use AIOPageBuilder\Domain\Execution\Handlers\Create_Menu_Handler;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 4 );
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Types.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Execution_Handler_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Execution_Dispatcher.php';
require_once $plugin_root . '/src/Domain/Execution/Handlers/Create_Menu_Handler.php';

// ---------------------------------------------------------------------------
// Minimal WP stubs required by Create_Menu_Handler at load time.
// ---------------------------------------------------------------------------
namespace AIOPageBuilder\Domain\Execution\Handlers;

if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\wp_create_nav_menu' ) ) {
	function wp_create_nav_menu( string $name ) {
		return 99; }
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\get_registered_nav_menus' ) ) {
	function get_registered_nav_menus(): array {
		return array(); }
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\get_theme_mod' ) ) {
	function get_theme_mod( string $name ): array {
		return array(); }
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\set_theme_mod' ) ) {
	function set_theme_mod( string $name, array $value ): void {}
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\wp_update_nav_menu_item' ) ) {
	function wp_update_nav_menu_item( int $id, int $item_id, array $data ): int {
		return 1; }
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\is_wp_error' ) ) {
	function is_wp_error( $thing ): bool {
		return false; }
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\sanitize_text_field' ) ) {
	function sanitize_text_field( string $s ): string {
		return $s; }
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\esc_url_raw' ) ) {
	function esc_url_raw( string $url ): string {
		return $url; }
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text; }
}

namespace AIOPageBuilder\Tests\Integration\Domain\Execution;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Execution\Executor\Execution_Dispatcher;
use AIOPageBuilder\Domain\Execution\Handlers\Create_Menu_Handler;
use PHPUnit\Framework\TestCase;

/**
 * Verifies CREATE_MENU is fully wired into the execution engine.
 */
final class Single_Action_Executor_Create_Menu_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_wp_create_nav_menu_return'] = 99;
	}

	protected function tearDown(): void {
		parent::tearDown();
		unset( $GLOBALS['_aio_wp_create_nav_menu_return'] );
	}

	public function test_create_menu_is_in_all(): void {
		$this->assertContains(
			Execution_Action_Types::CREATE_MENU,
			Execution_Action_Types::ALL,
			'CREATE_MENU must be in Execution_Action_Types::ALL for v2.'
		);
	}

	public function test_create_menu_is_valid(): void {
		$this->assertTrue(
			Execution_Action_Types::is_valid( Execution_Action_Types::CREATE_MENU ),
			'is_valid() must return true for CREATE_MENU in v2.'
		);
	}

	public function test_update_page_metadata_is_not_in_all(): void {
		$this->assertNotContains(
			Execution_Action_Types::UPDATE_PAGE_METADATA,
			Execution_Action_Types::ALL,
			'UPDATE_PAGE_METADATA remains recommendation-only and must not be in ALL.'
		);
	}

	public function test_handler_is_discoverable_through_dispatcher(): void {
		$dispatcher = new Execution_Dispatcher();
		$dispatcher->register_handler(
			Execution_Action_Types::CREATE_MENU,
			new Create_Menu_Handler()
		);
		$this->assertTrue(
			$dispatcher->has_handler( Execution_Action_Types::CREATE_MENU ),
			'Dispatcher must have a handler registered for CREATE_MENU.'
		);
	}

	public function test_handler_class_is_create_menu_handler(): void {
		$dispatcher = new Execution_Dispatcher();
		$dispatcher->register_handler(
			Execution_Action_Types::CREATE_MENU,
			new Create_Menu_Handler()
		);
		$handler = $dispatcher->get_handler( Execution_Action_Types::CREATE_MENU );
		$this->assertInstanceOf(
			Create_Menu_Handler::class,
			$handler,
			'The handler returned by the dispatcher must be Create_Menu_Handler, not a stub.'
		);
	}

	public function test_successful_execution_returns_menu_id_artifact(): void {
		$dispatcher = new Execution_Dispatcher();
		$dispatcher->register_handler(
			Execution_Action_Types::CREATE_MENU,
			new Create_Menu_Handler()
		);
		$handler = $dispatcher->get_handler( Execution_Action_Types::CREATE_MENU );
		$result  = $handler->execute(
			array(
				'target_reference' => array( 'menu_name' => 'Test Nav' ),
			)
		);
		$this->assertTrue( $result['success'] );
		$this->assertSame( 99, $result['artifacts']['menu_id'] );
		$this->assertSame( 'Test Nav', $result['artifacts']['menu_name'] );
	}

	public function test_failed_execution_returns_failure_cleanly(): void {
		$dispatcher = new Execution_Dispatcher();
		$dispatcher->register_handler(
			Execution_Action_Types::CREATE_MENU,
			new Create_Menu_Handler()
		);
		$handler = $dispatcher->get_handler( Execution_Action_Types::CREATE_MENU );
		// Empty envelope produces a clean failure (no menu_name).
		$result = $handler->execute( array() );
		$this->assertFalse( $result['success'] );
		$this->assertArrayHasKey( 'message', $result );
	}

	public function test_create_menu_is_distinct_from_update_menu(): void {
		$this->assertNotSame(
			Execution_Action_Types::CREATE_MENU,
			Execution_Action_Types::UPDATE_MENU,
			'CREATE_MENU and UPDATE_MENU must be distinct action type constants.'
		);
	}
}
