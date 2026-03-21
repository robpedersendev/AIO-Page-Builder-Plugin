<?php
/**
 * Unit tests for Assign_Page_Hierarchy_Handler (v2-scope-backlog.md §1).
 *
 * WP stubs are provided by the PHPUnit bootstrap (tests/bootstrap.php).
 * Per-ID post lookup uses $GLOBALS['_aio_get_post_by_id'][id] (enhanced bootstrap stub).
 * wp_update_post return is controlled via $GLOBALS['_aio_wp_update_post_return'].
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit\Domain\Execution\Handlers;

use AIOPageBuilder\Domain\Execution\Handlers\Assign_Page_Hierarchy_Handler;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AIOPageBuilder\Domain\Execution\Handlers\Assign_Page_Hierarchy_Handler
 */
final class Assign_Page_Hierarchy_Handler_Test extends TestCase {

	private Assign_Page_Hierarchy_Handler $handler;

	protected function setUp(): void {
		$this->handler                         = new Assign_Page_Hierarchy_Handler();
		$GLOBALS['_aio_get_post_by_id']        = array();
		$GLOBALS['_aio_get_post_return']       = null;
		$GLOBALS['_aio_wp_update_post_return'] = null;
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_get_post_by_id'] );
		unset( $GLOBALS['_aio_get_post_return'] );
		unset( $GLOBALS['_aio_wp_update_post_return'] );
	}

	private function envelope( array $target_reference ): array {
		return array( 'target_reference' => $target_reference );
	}

	private function make_page( int $post_parent = 0 ): \WP_Post {
		$p              = new \WP_Post();
		$p->post_parent = $post_parent;
		return $p;
	}

	// --- Rejection tests: invalid input ---

	public function test_rejects_missing_page_id(): void {
		$result = $this->handler->execute( $this->envelope( array( 'parent_page_id' => 0 ) ) );
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'page_id_required', $result['errors'] );
	}

	public function test_rejects_zero_page_id(): void {
		$result = $this->handler->execute(
			$this->envelope(
				array(
					'page_id'        => 0,
					'parent_page_id' => 0,
				)
			)
		);
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'page_id_required', $result['errors'] );
	}

	public function test_rejects_missing_parent_page_id(): void {
		$GLOBALS['_aio_get_post_by_id'][5] = $this->make_page();
		$result                            = $this->handler->execute( $this->envelope( array( 'page_id' => 5 ) ) );
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'parent_page_id_required', $result['errors'] );
	}

	public function test_rejects_negative_parent_page_id(): void {
		$result = $this->handler->execute(
			$this->envelope(
				array(
					'page_id'        => 5,
					'parent_page_id' => -1,
				)
			)
		);
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'parent_page_id_required', $result['errors'] );
	}

	// --- Rejection tests: unresolvable references ---

	public function test_rejects_nonexistent_page(): void {
		// post 99 not registered → get_post returns null → page_not_found.
		$result = $this->handler->execute(
			$this->envelope(
				array(
					'page_id'        => 99,
					'parent_page_id' => 0,
				)
			)
		);
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'page_not_found', $result['errors'] );
	}

	public function test_rejects_nonexistent_parent(): void {
		$GLOBALS['_aio_get_post_by_id'][5] = $this->make_page( 0 );
		// post 99 not in registry → get_post(99) returns null → parent_not_found.
		$result = $this->handler->execute(
			$this->envelope(
				array(
					'page_id'        => 5,
					'parent_page_id' => 99,
				)
			)
		);
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'parent_not_found', $result['errors'] );
	}

	// --- Rejection tests: circular / self-parent ---

	public function test_rejects_self_parent_assignment(): void {
		$GLOBALS['_aio_get_post_by_id'][5] = $this->make_page( 0 );
		$result                            = $this->handler->execute(
			$this->envelope(
				array(
					'page_id'        => 5,
					'parent_page_id' => 5,
				)
			)
		);
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'self_parent_circular', $result['errors'] );
	}

	public function test_rejects_circular_chain_direct(): void {
		// page 5 has parent=10. Walking ancestors of proposed parent=5:
		// get_post(5)->post_parent = 10 → 10 ≡ page_id=10 → circular.
		$GLOBALS['_aio_get_post_by_id'][5]  = $this->make_page( 10 ); // page 5 parent=10
		$GLOBALS['_aio_get_post_by_id'][10] = $this->make_page( 5 );  // page 10 parent=5
		// Attempt: assign page 10 a parent of 5.
		// self-parent check: 10 ≠ 5 → OK.
		// parent exists check: get_post(5) = page → OK.
		// circular check: walk ancestors of 5; page5->parent=10 ≡ page_id(10) → circular!
		$result = $this->handler->execute(
			$this->envelope(
				array(
					'page_id'        => 10,
					'parent_page_id' => 5,
				)
			)
		);
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'circular_hierarchy', $result['errors'] );
	}

	public function test_rejects_circular_chain_indirect(): void {
		// Chain: 10→20→5; assigning page 5 a parent of 10 would create 5→10→20→5 cycle.
		$GLOBALS['_aio_get_post_by_id'][5]  = $this->make_page( 0 );
		$GLOBALS['_aio_get_post_by_id'][10] = $this->make_page( 20 );
		$GLOBALS['_aio_get_post_by_id'][20] = $this->make_page( 5 );  // 20's parent is 5
		$result                             = $this->handler->execute(
			$this->envelope(
				array(
					'page_id'        => 5,
					'parent_page_id' => 10,
				)
			)
		);
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'circular_hierarchy', $result['errors'] );
	}

	// --- No-op test ---

	public function test_returns_no_op_when_parent_already_matches(): void {
		$GLOBALS['_aio_get_post_by_id'][5]  = $this->make_page( 10 ); // already child of 10
		$GLOBALS['_aio_get_post_by_id'][10] = $this->make_page( 0 );
		$result                             = $this->handler->execute(
			$this->envelope(
				array(
					'page_id'        => 5,
					'parent_page_id' => 10,
				)
			)
		);
		$this->assertTrue( $result['success'] );
		$this->assertTrue( $result['artifacts']['no_op'] ?? false );
		$this->assertSame( 5, $result['artifacts']['page_id'] );
		$this->assertSame( 10, $result['artifacts']['old_parent'] );
		$this->assertSame( 10, $result['artifacts']['new_parent'] );
	}

	// --- Success tests ---

	public function test_calls_wp_update_post_and_returns_success(): void {
		$GLOBALS['_aio_get_post_by_id'][5]  = $this->make_page( 0 );
		$GLOBALS['_aio_get_post_by_id'][10] = $this->make_page( 0 );
		// Bootstrap wp_update_post returns postarr['ID'] when _aio_wp_update_post_return is null.
		$result = $this->handler->execute(
			$this->envelope(
				array(
					'page_id'        => 5,
					'parent_page_id' => 10,
				)
			)
		);
		$this->assertTrue( $result['success'] );
		$this->assertSame( 5, $result['artifacts']['page_id'] );
		$this->assertSame( 0, $result['artifacts']['old_parent'] );
		$this->assertSame( 10, $result['artifacts']['new_parent'] );
	}

	public function test_artifacts_contain_old_and_new_parent(): void {
		$GLOBALS['_aio_get_post_by_id'][3]  = $this->make_page( 7 ); // old parent = 7
		$GLOBALS['_aio_get_post_by_id'][12] = $this->make_page( 0 );
		$result                             = $this->handler->execute(
			$this->envelope(
				array(
					'page_id'        => 3,
					'parent_page_id' => 12,
				)
			)
		);
		$this->assertSame( 7, $result['artifacts']['old_parent'], 'old_parent must reflect the original post_parent.' );
		$this->assertSame( 12, $result['artifacts']['new_parent'], 'new_parent must reflect the requested parent_page_id.' );
	}

	public function test_top_level_assignment_succeeds_with_parent_zero(): void {
		$GLOBALS['_aio_get_post_by_id'][3] = $this->make_page( 7 ); // currently child of 7
		$result                            = $this->handler->execute(
			$this->envelope(
				array(
					'page_id'        => 3,
					'parent_page_id' => 0,
				)
			)
		);
		$this->assertTrue( $result['success'] );
		$this->assertSame( 0, $result['artifacts']['new_parent'] );
	}

	// --- Failure tests ---

	public function test_returns_failure_on_wp_error(): void {
		$GLOBALS['_aio_get_post_by_id'][5]     = $this->make_page( 3 ); // old parent = 3 → new=0, different from old
		$GLOBALS['_aio_wp_update_post_return'] = new \WP_Error( 'db_error', 'Database write failed.' );
		$result                                = $this->handler->execute(
			$this->envelope(
				array(
					'page_id'        => 5,
					'parent_page_id' => 0,
				)
			)
		);
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'wp_update_post_error', $result['errors'] );
	}

	public function test_returns_failure_on_zero_result(): void {
		$GLOBALS['_aio_get_post_by_id'][5] = $this->make_page( 3 );
		// * Use raw-return override to bypass bootstrap's 0→WP_Error conversion when wp_error=true.
		$GLOBALS['_aio_wp_update_post_raw_return'] = 0;
		$result                                    = $this->handler->execute(
			$this->envelope(
				array(
					'page_id'        => 5,
					'parent_page_id' => 0,
				)
			)
		);
		unset( $GLOBALS['_aio_wp_update_post_raw_return'] );
		$this->assertFalse( $result['success'] );
		$this->assertContains( 'wp_update_post_zero', $result['errors'] );
	}
}
