<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Domain;

use AIOPageBuilder\Domain\AI\Providers\Drivers\Provider_Connection_Test_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../wordpress/' );

$plugin_root = dirname( __DIR__, 3 );
require_once $plugin_root . '/src/Domain/AI/Providers/Drivers/Provider_Connection_Test_Service.php';

final class ProviderConnectionTestServiceTest extends TestCase {

	public function test_service_class_exists(): void {
		$this->assertTrue( class_exists( Provider_Connection_Test_Service::class ) );
	}
}
