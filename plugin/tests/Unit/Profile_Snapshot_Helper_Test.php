<?php
/**
 * Unit tests for Profile_Snapshot_Helper: get_current_for_snapshot returns copy, no persistence (spec §22.11).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Profile\Profile_Normalizer;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Schema;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Helper;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Normalizer.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Store.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Helper.php';

final class Profile_Snapshot_Helper_Test extends TestCase {

	private Settings_Service $settings;
	private Profile_Store $store;
	private Profile_Snapshot_Helper $helper;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_test_options'] = array();
		$this->settings               = new Settings_Service();
		$this->store                  = new Profile_Store( $this->settings, new Profile_Normalizer() );
		$this->helper                 = new Profile_Snapshot_Helper();
	}

	protected function tearDown(): void {
		$GLOBALS['_aio_test_options'] = array();
		parent::tearDown();
	}

	public function test_get_current_for_snapshot_returns_brand_profile_and_business_profile_keys(): void {
		$payload = $this->helper->get_current_for_snapshot( $this->store );
		$this->assertArrayHasKey( Profile_Schema::ROOT_BRAND, $payload );
		$this->assertArrayHasKey( Profile_Schema::ROOT_BUSINESS, $payload );
		$this->assertIsArray( $payload[ Profile_Schema::ROOT_BRAND ] );
		$this->assertIsArray( $payload[ Profile_Schema::ROOT_BUSINESS ] );
	}

	public function test_get_current_for_snapshot_reflects_current_store_state(): void {
		$this->store->set_brand_profile( array( 'brand_voice_summary' => 'Snapshot me' ) );
		$this->store->set_business_profile( array( 'business_name' => 'Acme Snapshot' ) );
		$payload = $this->helper->get_current_for_snapshot( $this->store );
		$this->assertSame( 'Snapshot me', $payload[ Profile_Schema::ROOT_BRAND ]['brand_voice_summary'] );
		$this->assertSame( 'Acme Snapshot', $payload[ Profile_Schema::ROOT_BUSINESS ]['business_name'] );
	}

	public function test_get_current_for_snapshot_does_not_persist(): void {
		$before = $GLOBALS['_aio_test_options'] ?? array();
		$this->helper->get_current_for_snapshot( $this->store );
		$after = $GLOBALS['_aio_test_options'] ?? array();
		$this->assertSame( $before, $after, 'Helper must not write options' );
	}
}
