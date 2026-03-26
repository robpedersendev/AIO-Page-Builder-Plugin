<?php
/**
 * Contract tests for Create_Build_Plan_From_AI_Run_Action (admin_post hook and nonce align).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit\Admin;

use AIOPageBuilder\Admin\Actions\Create_Build_Plan_From_AI_Run_Action;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AIOPageBuilder\Admin\Actions\Create_Build_Plan_From_AI_Run_Action
 */
final class Create_Build_Plan_From_AI_Run_Action_Contract_Test extends TestCase {

	public function test_nonce_action_matches_admin_post_suffix(): void {
		$this->assertSame( 'aio_create_build_plan_from_ai_run', Create_Build_Plan_From_AI_Run_Action::NONCE_ACTION );
	}

	public function test_query_result_arg_is_stable(): void {
		$this->assertSame( 'aio_bp_from_run', Create_Build_Plan_From_AI_Run_Action::QUERY_RESULT );
	}
}
