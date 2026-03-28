<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

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
