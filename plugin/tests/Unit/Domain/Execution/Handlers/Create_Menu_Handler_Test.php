<?php
/**
 * Unit tests for Create_Menu_Handler (v2-scope-backlog.md §2).
 *
 * WP nav-menu API functions and helpers are overridden in this namespace
 * so a full WordPress bootstrap is not required.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Domain\Execution\Handlers;

use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 6 );
require_once $plugin_root . '/src/Domain/Execution/Executor/Execution_Handler_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Handlers/Create_Menu_Handler.php';

// ---------------------------------------------------------------------------
// WP function stubs — scoped to handler namespace.
// ---------------------------------------------------------------------------

/** @var int|\WP_Error */
$GLOBALS['_cmh_test_create_result'] = 42;
/** @var array<string,mixed> */
$GLOBALS['_cmh_test_registered_locations'] = array( 'primary' => 'Primary Menu' );
/** @var array<string,int> */
$GLOBALS['_cmh_test_theme_mod_locations'] = array();
/** @var bool */
$GLOBALS['_cmh_test_item_result'] = 1;

function wp_create_nav_menu( string $menu_name ) {
	return $GLOBALS['_cmh_test_create_result'];
}

function get_registered_nav_menus(): array {
	return $GLOBALS['_cmh_test_registered_locations'];
}

function get_theme_mod( string $name ): array {
	return $GLOBALS['_cmh_test_theme_mod_locations'];
}

function set_theme_mod( string $name, array $value ): void {
	$GLOBALS['_cmh_test_theme_mod_locations'] = $value;
}

function wp_update_nav_menu_item( int $menu_id, int $item_db_id, array $item_data ): int {
	return $GLOBALS['_cmh_test_item_result'];
}

function is_wp_error( $thing ): bool {
	return $thing instanceof \WP_Error;
}

function sanitize_text_field( string $str ): string {
	return $str;
}

function esc_url_raw( string $url ): string {
	return $url;
}

function __( string $text, string $domain = 'default' ): string {
	return $text;
}

function sprintf( string $format, ...$args ): string {
	return \vsprintf( $format, $args );
}

// ---------------------------------------------------------------------------
// Minimal WP_Error stub.
// ---------------------------------------------------------------------------
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private string $code;
		private string $message;
		public function __construct( string $code = '', string $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}
		public function get_error_message(): string {
			return $this->message;
		}
	}
}

// ---------------------------------------------------------------------------
// Test case.
// ---------------------------------------------------------------------------

/**
 * @covers \AIOPageBuilder\Domain\Execution\Handlers\Create_Menu_Handler
 */
final class Create_Menu_Handler_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_cmh_test_create_result']       = 42;
		$GLOBALS['_cmh_test_registered_locations'] = array( 'primary' => 'Primary Menu', 'footer' => 'Footer Menu' );
		$GLOBALS['_cmh_test_theme_mod_locations']  = array();
		$GLOBALS['_cmh_test_item_result']          = 1;
	}

	private function handler(): Create_Menu_Handler {
		return new Create_Menu_Handler();
	}

	private function envelope( array $target_reference = array() ): array {
		return array( 'target_reference' => $target_reference );
	}

	public function test_missing_menu_name_returns_failure(): void {
		$result = $this->handler()->execute( $this->envelope( array() ) );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'menu_name', $result['message'] );
	}

	public function test_empty_menu_name_returns_failure(): void {
		$result = $this->handler()->execute( $this->envelope( array( 'menu_name' => '   ' ) ) );
		$this->assertFalse( $result['success'] );
	}

	public function test_wp_create_nav_menu_error_returns_failure(): void {
		$GLOBALS['_cmh_test_create_result'] = new \WP_Error( 'nav_menu_exists', 'That menu name already exists.' );
		$result = $this->handler()->execute( $this->envelope( array( 'menu_name' => 'My Menu' ) ) );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'That menu name already exists.', $result['message'] );
	}

	public function test_wp_create_nav_menu_returning_zero_returns_failure(): void {
		$GLOBALS['_cmh_test_create_result'] = 0;
		$result = $this->handler()->execute( $this->envelope( array( 'menu_name' => 'My Menu' ) ) );
		$this->assertFalse( $result['success'] );
	}

	public function test_successful_creation_returns_menu_id_and_name(): void {
		$result = $this->handler()->execute( $this->envelope( array( 'menu_name' => 'Header Nav' ) ) );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 42, $result['artifacts']['menu_id'] );
		$this->assertSame( 'Header Nav', $result['artifacts']['menu_name'] );
		$this->assertFalse( $result['artifacts']['location_assigned'] );
		$this->assertSame( 0, $result['artifacts']['items_applied'] );
	}

	public function test_theme_location_registered_assigns_menu_and_reports_true(): void {
		$result = $this->handler()->execute(
			$this->envelope(
				array(
					'menu_name'      => 'Header Nav',
					'theme_location' => 'primary',
				)
			)
		);
		$this->assertTrue( $result['success'] );
		$this->assertTrue( $result['artifacts']['location_assigned'] );
		$this->assertSame( 'primary', $result['artifacts']['theme_location'] );
		$this->assertSame( array( 'primary' => 42 ), $GLOBALS['_cmh_test_theme_mod_locations'] );
	}

	public function test_theme_location_not_registered_skips_silently_and_records_reason(): void {
		$result = $this->handler()->execute(
			$this->envelope(
				array(
					'menu_name'      => 'My Menu',
					'theme_location' => 'nonexistent_slot',
				)
			)
		);
		$this->assertTrue( $result['success'] );
		$this->assertFalse( $result['artifacts']['location_assigned'] );
		$this->assertSame( 'not_registered', $result['artifacts']['location_skipped_reason'] );
		$this->assertEmpty( $GLOBALS['_cmh_test_theme_mod_locations'] );
	}

	public function test_items_applied_count_matches_successful_items(): void {
		$result = $this->handler()->execute(
			$this->envelope(
				array(
					'menu_name' => 'Footer Nav',
					'items'     => array(
						array( 'title' => 'Home', 'url' => 'https://example.com/', 'type' => 'custom' ),
						array( 'title' => 'About', 'url' => 'https://example.com/about/', 'type' => 'custom' ),
					),
				)
			)
		);
		$this->assertTrue( $result['success'] );
		$this->assertSame( 2, $result['artifacts']['items_applied'] );
	}

	public function test_item_with_no_content_is_skipped(): void {
		$result = $this->handler()->execute(
			$this->envelope(
				array(
					'menu_name' => 'Sparse Nav',
					'items'     => array(
						array(),
						array( 'title' => 'Home', 'url' => 'https://example.com/' ),
					),
				)
			)
		);
		$this->assertTrue( $result['success'] );
		$this->assertSame( 1, $result['artifacts']['items_applied'] );
	}

	public function test_artifact_has_no_location_skipped_reason_when_no_location_requested(): void {
		$result = $this->handler()->execute( $this->envelope( array( 'menu_name' => 'Simple' ) ) );
		$this->assertTrue( $result['success'] );
		$this->assertArrayNotHasKey( 'location_skipped_reason', $result['artifacts'] );
		$this->assertArrayNotHasKey( 'theme_location', $result['artifacts'] );
	}

	public function test_message_contains_menu_name_and_id(): void {
		$result = $this->handler()->execute( $this->envelope( array( 'menu_name' => 'Global Nav' ) ) );
		$this->assertTrue( $result['success'] );
		$this->assertStringContainsString( 'Global Nav', $result['message'] );
		$this->assertStringContainsString( '42', $result['message'] );
	}
}
