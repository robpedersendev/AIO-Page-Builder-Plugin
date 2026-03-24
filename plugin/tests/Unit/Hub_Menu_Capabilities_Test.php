<?php
/**
 * Unit tests for Hub_Menu_Capabilities::map_meta_cap (template library tab access for site admins).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Config\Hub_Menu_Capabilities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Infrastructure/Config/Hub_Menu_Capabilities.php';

/**
 * map_meta_cap grants for virtual hub and registry MANAGE_* fallbacks.
 */
final class Hub_Menu_Capabilities_Test extends TestCase {

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_get_userdata_allcaps'], $GLOBALS['_aio_is_super_admin'], $GLOBALS['_aio_is_multisite'] );
		parent::tearDown();
	}

	public function test_manage_section_templates_maps_to_read_for_manage_options(): void {
		$GLOBALS['_aio_get_userdata_allcaps'] = array( 'manage_options' => true );
		$initial                              = array( Capabilities::MANAGE_SECTION_TEMPLATES );
		$out                                  = Hub_Menu_Capabilities::map_meta_cap(
			$initial,
			Capabilities::MANAGE_SECTION_TEMPLATES,
			1,
			array()
		);
		$this->assertSame( array( 'read' ), $out );
	}

	public function test_manage_page_templates_maps_to_read_for_manage_options(): void {
		$GLOBALS['_aio_get_userdata_allcaps'] = array( 'manage_options' => true );
		$initial                              = array( Capabilities::MANAGE_PAGE_TEMPLATES );
		$out                                  = Hub_Menu_Capabilities::map_meta_cap(
			$initial,
			Capabilities::MANAGE_PAGE_TEMPLATES,
			1,
			array()
		);
		$this->assertSame( array( 'read' ), $out );
	}

	public function test_manage_compositions_maps_to_read_for_manage_options(): void {
		$GLOBALS['_aio_get_userdata_allcaps'] = array( 'manage_options' => true );
		$initial                              = array( Capabilities::MANAGE_COMPOSITIONS );
		$out                                  = Hub_Menu_Capabilities::map_meta_cap(
			$initial,
			Capabilities::MANAGE_COMPOSITIONS,
			1,
			array()
		);
		$this->assertSame( array( 'read' ), $out );
	}

	public function test_manage_section_templates_leaves_caps_when_not_site_admin(): void {
		$GLOBALS['_aio_get_userdata_allcaps'] = array();
		$initial                              = array( Capabilities::MANAGE_SECTION_TEMPLATES );
		$out                                  = Hub_Menu_Capabilities::map_meta_cap(
			$initial,
			Capabilities::MANAGE_SECTION_TEMPLATES,
			2,
			array()
		);
		$this->assertSame( $initial, $out );
	}

	public function test_super_admin_maps_manage_section_to_read_when_multisite(): void {
		$GLOBALS['_aio_get_userdata_allcaps'] = array();
		$GLOBALS['_aio_is_multisite']         = true;
		$GLOBALS['_aio_is_super_admin']       = true;
		$initial                              = array( Capabilities::MANAGE_SECTION_TEMPLATES );
		$out                                  = Hub_Menu_Capabilities::map_meta_cap(
			$initial,
			Capabilities::MANAGE_SECTION_TEMPLATES,
			1,
			array()
		);
		$this->assertSame( array( 'read' ), $out );
	}
}
