<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Infrastructure\Diagnostics;

use AIOPageBuilder\Bootstrap\Environment_Validator;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../../../wordpress/' );

$plugin_root = dirname( __DIR__, 4 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Bootstrap/Environment_Validator.php';

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

final class EnvironmentValidatorTest extends TestCase {

	public function test_build_snapshot_persists_expected_shape(): void {
		$validator = new Environment_Validator();
		$snapshot  = $validator->build_snapshot( true );
		$this->assertArrayHasKey( 'generated_at', $snapshot );
		$this->assertArrayHasKey( 'checks', $snapshot );
		$this->assertIsArray( $snapshot['checks'] );
		$persisted = \get_option( \AIOPageBuilder\Infrastructure\Config\Option_Names::PB_ENVIRONMENT_DIAGNOSTICS, array() );
		$this->assertIsArray( $persisted );
		$this->assertArrayHasKey( 'generated_at', $persisted );
	}
}
