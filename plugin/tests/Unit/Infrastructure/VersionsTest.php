<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Infrastructure;

use AIOPageBuilder\Infrastructure\Config\Versions;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../../wordpress/' );

$plugin_root = dirname( __DIR__, 3 );
require_once $plugin_root . '/src/Bootstrap/Constants.php';
\AIOPageBuilder\Bootstrap\Constants::init();
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';

final class VersionsTest extends TestCase {

	public function test_versions_are_not_placeholders(): void {
		$all = Versions::all();
		$this->assertNotEmpty( $all['plugin'] );
		$this->assertSame( Versions::GLOBAL_SCHEMA_VERSION, $all['global_schema'] );
		$this->assertSame( Versions::TABLE_SCHEMA_VERSION, $all['table_schema'] );
		$this->assertSame( Versions::REGISTRY_SCHEMA_VERSION, $all['registry_schema'] );
		$this->assertSame( Versions::EXPORT_SCHEMA_VERSION, $all['export_schema'] );
	}
}

