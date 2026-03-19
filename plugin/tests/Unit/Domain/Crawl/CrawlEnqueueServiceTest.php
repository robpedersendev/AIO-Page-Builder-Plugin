<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Domain\Crawl;

use AIOPageBuilder\Domain\Crawler\Queue\Crawl_Enqueue_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../../wordpress/' );

$plugin_root = dirname( __DIR__, 4 );
require_once $plugin_root . '/src/Domain/Crawler/Queue/Crawl_Enqueue_Service.php';

final class CrawlEnqueueServiceTest extends TestCase {

	public function test_service_class_exists(): void {
		$this->assertTrue( class_exists( Crawl_Enqueue_Service::class ) );
	}
}

