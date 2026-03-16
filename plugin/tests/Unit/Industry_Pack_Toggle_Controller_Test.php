<?php
/**
 * Unit tests for Industry_Pack_Toggle_Controller: get_disabled_pack_keys, is_pack_active, set_pack_disabled (Prompt 389).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Industry\Industry_Pack_Toggle_Controller;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Admin/Screens/Industry/Industry_Pack_Toggle_Controller.php';

final class Industry_Pack_Toggle_Controller_Test extends TestCase {

	/** @var Settings_Service */
	private Settings_Service $settings;

	/** @var Industry_Pack_Toggle_Controller */
	private Industry_Pack_Toggle_Controller $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->settings   = new Settings_Service();
		$this->controller = new Industry_Pack_Toggle_Controller( $this->settings );
	}

	protected function tearDown(): void {
		\delete_option( Option_Names::DISABLED_INDUSTRY_PACKS );
		parent::tearDown();
	}

	public function test_get_disabled_pack_keys_returns_empty_when_none_set(): void {
		$keys = $this->controller->get_disabled_pack_keys();
		$this->assertIsArray( $keys );
		$this->assertCount( 0, $keys );
	}

	public function test_is_pack_active_returns_true_when_none_disabled(): void {
		$this->assertTrue( $this->controller->is_pack_active( 'legal' ) );
		$this->assertTrue( $this->controller->is_pack_active( 'realtor' ) );
	}

	public function test_set_pack_disabled_adds_to_disabled_list(): void {
		$this->controller->set_pack_disabled( 'legal', true );
		$disabled = $this->controller->get_disabled_pack_keys();
		$this->assertCount( 1, $disabled );
		$this->assertContains( 'legal', $disabled );
		$this->assertFalse( $this->controller->is_pack_active( 'legal' ) );
		$this->assertTrue( $this->controller->is_pack_active( 'realtor' ) );
	}

	public function test_set_pack_disabled_false_removes_from_list(): void {
		$this->controller->set_pack_disabled( 'legal', true );
		$this->controller->set_pack_disabled( 'legal', false );
		$disabled = $this->controller->get_disabled_pack_keys();
		$this->assertCount( 0, $disabled );
		$this->assertTrue( $this->controller->is_pack_active( 'legal' ) );
	}

	public function test_multiple_packs_can_be_disabled(): void {
		$this->controller->set_pack_disabled( 'legal', true );
		$this->controller->set_pack_disabled( 'realtor', true );
		$disabled = $this->controller->get_disabled_pack_keys();
		$this->assertCount( 2, $disabled );
		$this->assertContains( 'legal', $disabled );
		$this->assertContains( 'realtor', $disabled );
		$this->assertFalse( $this->controller->is_pack_active( 'legal' ) );
		$this->assertFalse( $this->controller->is_pack_active( 'realtor' ) );
	}

	public function test_empty_industry_key_is_treated_as_active(): void {
		$this->controller->set_pack_disabled( 'legal', true );
		$this->assertTrue( $this->controller->is_pack_active( '' ) );
	}

	public function test_set_pack_disabled_ignores_empty_key(): void {
		$this->controller->set_pack_disabled( '', true );
		$disabled = $this->controller->get_disabled_pack_keys();
		$this->assertCount( 0, $disabled );
	}
}
