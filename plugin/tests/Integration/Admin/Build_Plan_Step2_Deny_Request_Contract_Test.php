<?php
/**
 * Step 2 row deny: nonce action shape and verification (admin handler contract; no browser).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Integration\Admin;

use AIOPageBuilder\Infrastructure\Config\Capabilities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../../../wordpress/' );

$plugin_root = dirname( __DIR__, 3 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';

final class Build_Plan_Step2_Deny_Request_Contract_Test extends TestCase {

	public function test_row_nonce_action_matches_wp_verify_nonce_contract(): void {
		$item_id = 'plan_npc_0';
		$action  = 'aio_pb_build_plan_row_action_' . $item_id;
		$nonce   = \wp_create_nonce( $action );
		$this->assertSame( 1, \wp_verify_nonce( $nonce, $action ) );
		$this->assertFalse( (bool) \wp_verify_nonce( 'invalid-nonce', $action ) );
	}

	public function test_approve_build_plans_capability_string_is_stable(): void {
		$this->assertSame( 'aio_approve_build_plans', Capabilities::APPROVE_BUILD_PLANS );
	}
}
