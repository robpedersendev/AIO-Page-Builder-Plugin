<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Infrastructure;

use AIOPageBuilder\Infrastructure\Config\Version_State_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../wordpress/' );

$plugin_root = dirname( __DIR__, 3 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Infrastructure/Config/Version_State_Service.php';

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $key, $value, bool $autoload = false ): bool {
		$GLOBALS['__aio_opts'][ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $key, $default = null ) {
		return $GLOBALS['__aio_opts'][ $key ] ?? $default;
	}
}

final class MigrationManagerTest extends TestCase {

	public function test_version_state_service_persists_state(): void {
		$GLOBALS['__aio_opts'] = array();
		$svc                  = new Version_State_Service();
		$svc->persist_current_state();
		$stored = \get_option( \AIOPageBuilder\Infrastructure\Config\Option_Names::PB_VERSION_STATE, array() );
		$this->assertIsArray( $stored );
		$this->assertArrayHasKey( 'plugin_version', $stored );
		$this->assertArrayHasKey( 'last_migrated_at', $stored );
	}
}

