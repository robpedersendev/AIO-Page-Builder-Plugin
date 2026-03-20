<?php
/**
 * Unit tests for Assign_Page_Hierarchy_Handler (v2-scope-backlog.md §1).
 *
 * WP functions (get_post, wp_update_post, is_wp_error) are overridden in this namespace
 * to avoid requiring a full WordPress bootstrap for handler unit tests.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Domain\Execution\Handlers;

use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 6 );
require_once $plugin_root . '/src/Domain/Execution/Executor/Execution_Handler_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Handlers/Assign_Page_Hierarchy_Handler.php';

// ---------------------------------------------------------------------------
// WP function stubs — scoped to handler namespace so real WP is not required.
// ---------------------------------------------------------------------------

/** @var array<int,\stdClass|null> Global stub registry used by the test namespace stubs. */
$GLOBALS['_aph_test_posts'] = array();
/** @var bool|\WP_Error */
$GLOBALS['_aph_test_update_result'] = 0;

function get_post( int $post_id ): ?\stdClass {
	return $GLOBALS['_aph_test_posts'][ $post_id ] ?? null;
}

function wp_update_post( array $args, bool $wp_error = false ) {
	return $GLOBALS['_aph_test_update_result'];
}

function is_wp_error( $thing ): bool {
	return $thing instanceof \WP_Error;
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
		private string $message;
		public function __construct( string $code = '', string $message = '' ) {
			$this->message = $message;
		}
		public function get_error_message(): string {
			return $this->message;
		}
	}
}

// ---------------------------------------------------------------------------
// Minimal WP_Post stub.
// ---------------------------------------------------------------------------
if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post extends \stdClass {}
}

// ---------------------------------------------------------------------------

/**
 * Tests for Assign_Page_Hierarchy_Handler.
 */
final class Assign_Page_Hierarchy_Handler_Test extends TestCase {

	private Assign_Page_Hierarchy_Handler $handler;

	protected function setUp(): void {
		$this->handler                        = new Assign_Page_Hierarchy_Handler();
		$GLOBALS['_aph_test_posts']           = array();
		$GLOBALS['_aph_test_update_result']   = 0;
	}

	private function envelope( array $target_reference ): array {
		return array( 'target_reference' => $target_reference );
	}

	// --- Rejection tests ---

	public function test_rejects_missing_page_id(): void {
		$result = $this->handler->execute( $this->envelope( array( 'parent_page_id' => 0 ) ) );
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'page_id_required', $result['errors'] );
	}

	public function test_rejects_zero_page_id(): void {
		$result = $this->handler->execute( $this->envelope( array( 'page_id' => 0, 'parent_page_id' => 0 ) ) );
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'page_id_required', $result['errors'] );
	}

	public function test_rejects_missing_parent_page_id(): void {
		$GLOBALS['_aph_test_posts'][5] = new \WP_Post();
		$result = $this->handler->execute( $this->envelope( array( 'page_id' => 5 ) ) );
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'parent_page_id_required', $result['errors'] );
	}

	public function test_rejects_negative_parent_page_id(): void {
		$result = $this->handler->execute( $this->envelope( array( 'page_id' => 5, 'parent_page_id' => -1 ) ) );
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'parent_page_id_required', $result['errors'] );
	}

	public function test_rejects_nonexistent_page(): void {
		$GLOBALS['_aph_test_posts'] = array();
		$result = $this->handler->execute( $this->envelope( array( 'page_id' => 99, 'parent_page_id' => 0 ) ) );
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'page_not_found', $result['errors'] );
	}

	public function test_rejects_nonexistent_parent(): void {
		$page              = new \WP_Post();
		$page->post_parent = 0;
		$GLOBALS['_aph_test_posts'][5]  = $page;
		$GLOBALS['_aph_test_posts'][99] = null;
		$result = $this->handler->execute( $this->envelope( array( 'page_id' => 5, 'parent_page_id' => 99 ) ) );
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'parent_not_found', $result['errors'] );
	}

	// --- No-op test ---

	public function test_returns_no_op_when_parent_already_matches(): void {
		$page              = new \WP_Post();
		$page->post_parent = 10;
		$GLOBALS['_aph_test_posts'][5]  = $page;
		$GLOBALS['_aph_test_posts'][10] = new \WP_Post();
		$result = $this->handler->execute( $this->envelope( array( 'page_id' => 5, 'parent_page_id' => 10 ) ) );
		$this->assertTrue( $result['success'] );
		$this->assertTrue( $result['artifacts']['no_op'] ?? false );
		$this->assertSame( 5, $result['artifacts']['page_id'] );
		$this->assertSame( 10, $result['artifacts']['old_parent'] );
		$this->assertSame( 10, $result['artifacts']['new_parent'] );
	}

	// --- Success tests ---

	public function test_calls_wp_update_post_and_returns_success(): void {
		$page              = new \WP_Post();
		$page->post_parent = 0;
		$GLOBALS['_aph_test_posts'][5]  = $page;
		$GLOBALS['_aph_test_posts'][10] = new \WP_Post();
		$GLOBALS['_aph_test_update_result'] = 5;

		$result = $this->handler->execute( $this->envelope( array( 'page_id' => 5, 'parent_page_id' => 10 ) ) );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 5, $result['artifacts']['page_id'] );
		$this->assertSame( 0, $result['artifacts']['old_parent'] );
		$this->assertSame( 10, $result['artifacts']['new_parent'] );
	}

	public function test_artifacts_contain_old_and_new_parent(): void {
		$page              = new \WP_Post();
		$page->post_parent = 7;
		$GLOBALS['_aph_test_posts'][3]  = $page;
		$GLOBALS['_aph_test_posts'][12] = new \WP_Post();
		$GLOBALS['_aph_test_update_result'] = 3;

		$result = $this->handler->execute( $this->envelope( array( 'page_id' => 3, 'parent_page_id' => 12 ) ) );

		$this->assertSame( 7, $result['artifacts']['old_parent'], 'old_parent must reflect the original post_parent.' );
		$this->assertSame( 12, $result['artifacts']['new_parent'], 'new_parent must reflect the requested parent_page_id.' );
	}

	public function test_top_level_assignment_succeeds_with_parent_zero(): void {
		$page              = new \WP_Post();
		$page->post_parent = 7;
		$GLOBALS['_aph_test_posts'][3] = $page;
		$GLOBALS['_aph_test_update_result'] = 3;

		$result = $this->handler->execute( $this->envelope( array( 'page_id' => 3, 'parent_page_id' => 0 ) ) );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 0, $result['artifacts']['new_parent'] );
	}

	// --- Failure tests ---

	public function test_returns_failure_on_wp_error(): void {
		$page              = new \WP_Post();
		$page->post_parent = 0;
		$GLOBALS['_aph_test_posts'][5] = $page;
		$GLOBALS['_aph_test_update_result'] = new \WP_Error( 'db_error', 'Database write failed.' );

		$result = $this->handler->execute( $this->envelope( array( 'page_id' => 5, 'parent_page_id' => 0 ) ) );

		// Parent 0 = top-level; old_parent = 0 → triggers no-op since 0 === 0. Adjust: use different parent.
		$page->post_parent = 3;
		$GLOBALS['_aph_test_posts'][5] = $page;
		$GLOBALS['_aph_test_update_result'] = new \WP_Error( 'db_error', 'Database write failed.' );
		$result = $this->handler->execute( $this->envelope( array( 'page_id' => 5, 'parent_page_id' => 0 ) ) );

		$this->assertFalse( $result['success'] );
		$this->assertContains( 'wp_update_post_error', $result['errors'] );
	}

	public function test_returns_failure_on_zero_result(): void {
		$page              = new \WP_Post();
		$page->post_parent = 3;
		$GLOBALS['_aph_test_posts'][5]      = $page;
		$GLOBALS['_aph_test_update_result'] = 0;

		$result = $this->handler->execute( $this->envelope( array( 'page_id' => 5, 'parent_page_id' => 0 ) ) );
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'wp_update_post_zero', $result['errors'] );
	}
}
