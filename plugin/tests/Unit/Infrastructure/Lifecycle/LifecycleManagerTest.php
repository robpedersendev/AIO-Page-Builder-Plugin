<?php
/**
 * @package AIOPageBuilder
 */

namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/../../../wordpress/' );
	}
}

namespace AIOPageBuilder\Tests\Unit\Infrastructure\Lifecycle {

	use AIOPageBuilder\Bootstrap\Lifecycle_Manager;
	use PHPUnit\Framework\TestCase;

	$plugin_root = dirname( __DIR__, 4 );
	require_once $plugin_root . '/src/Bootstrap/Lifecycle_Manager.php';

	final class LifecycleManagerTest extends TestCase {

		public function test_deactivate_sets_last_deactivation_at(): void {
			$mgr = new Lifecycle_Manager();
			$mgr->deactivate();
			$value = \get_option( \AIOPageBuilder\Infrastructure\Config\Option_Names::PB_LAST_DEACTIVATION_AT, '' );
			$this->assertNotSame( '', (string) $value );
		}
	}
}
