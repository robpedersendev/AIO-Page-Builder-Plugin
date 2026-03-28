<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Approved_Snapshot_Ref_Keys;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Config\Template_Lab_Access;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Template_Lab_Access_Test extends TestCase {

	protected function tearDown(): void {
		unset(
			$GLOBALS['_aio_current_user_can_caps'],
			$GLOBALS['_aio_current_user_can_return'],
			$GLOBALS['_aio_current_uid'],
			$GLOBALS['_aio_is_logged_in']
		);
		parent::tearDown();
	}

	public function test_actor_may_use_chat_session_owner_or_privileged(): void {
		$session = array( 'owner_user_id' => 2 );
		$this->assertTrue( Template_Lab_Access::actor_may_use_chat_session( 2, $session ) );
		$GLOBALS['_aio_is_logged_in']          = true;
		$GLOBALS['_aio_current_uid']           = 3;
		$GLOBALS['_aio_current_user_can_caps'] = array( 'manage_options' => false );
		$this->assertFalse( Template_Lab_Access::actor_may_use_chat_session( 3, $session ) );
		$GLOBALS['_aio_current_user_can_caps'] = array( 'manage_options' => true );
		$this->assertTrue( Template_Lab_Access::actor_may_use_chat_session( 3, $session ) );
	}

	public function test_capability_for_target_maps_registry_caps(): void {
		$this->assertSame(
			Capabilities::MANAGE_COMPOSITIONS,
			Template_Lab_Access::capability_for_approved_target_kind( Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION )
		);
		$this->assertSame(
			Capabilities::MANAGE_PAGE_TEMPLATES,
			Template_Lab_Access::capability_for_approved_target_kind( Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_PAGE )
		);
	}

	public function test_matches_compositions_cap_gate(): void {
		$GLOBALS['_aio_is_logged_in']          = true;
		$GLOBALS['_aio_current_uid']           = 1;
		$GLOBALS['_aio_current_user_can_caps'] = array(
			'manage_options'                  => false,
			Capabilities::MANAGE_COMPOSITIONS => true,
		);
		$this->assertTrue( Template_Lab_Access::can_manage_template_lab() );
		$GLOBALS['_aio_current_user_can_caps'] = array(
			'manage_options'                  => false,
			Capabilities::MANAGE_COMPOSITIONS => false,
		);
		$this->assertFalse( Template_Lab_Access::can_manage_template_lab() );
	}
}
