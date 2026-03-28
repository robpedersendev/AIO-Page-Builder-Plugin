<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Rest\AI_Chat_REST_Controller;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class AI_Chat_REST_Controller_Permission_Test extends TestCase {

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_current_user_can_caps'], $GLOBALS['_aio_current_user_can_return'], $GLOBALS['_aio_is_multisite'], $GLOBALS['_aio_is_super_admin'] );
		parent::tearDown();
	}

	public function test_permission_denied_without_cap(): void {
		$c = new Service_Container();
		$c->register(
			'ai_chat_session_repository',
			static function () {
				return new \AIOPageBuilder\Domain\Storage\Repositories\AI_Chat_Session_Repository();
			}
		);
		$ctrl                                  = new AI_Chat_REST_Controller( $c );
		$GLOBALS['_aio_current_user_can_caps'] = array(
			'manage_options'                  => false,
			Capabilities::MANAGE_COMPOSITIONS => false,
		);
		$this->assertFalse( $ctrl->can_manage_template_lab() );
	}

	public function test_permission_granted_with_compositions_cap(): void {
		$c = new Service_Container();
		$c->register(
			'ai_chat_session_repository',
			static function () {
				return new \AIOPageBuilder\Domain\Storage\Repositories\AI_Chat_Session_Repository();
			}
		);
		$ctrl                                  = new AI_Chat_REST_Controller( $c );
		$GLOBALS['_aio_current_user_can_caps'] = array(
			'manage_options'                  => false,
			Capabilities::MANAGE_COMPOSITIONS => true,
		);
		$this->assertTrue( $ctrl->can_manage_template_lab() );
	}
}
