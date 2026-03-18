<?php
/**
 * Unit tests for Industry_Page_Template_Override_Service (Prompt 368). record_override, get_override, list_overrides.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Schema;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Page_Template_Override_Service;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Override_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Page_Template_Override_Service.php';

final class Industry_Page_Template_Override_Service_Test extends TestCase {

	private function clear_option(): void {
		if ( isset( $GLOBALS['_aio_test_options'][ Option_Names::INDUSTRY_PAGE_TEMPLATE_OVERRIDES ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ Option_Names::INDUSTRY_PAGE_TEMPLATE_OVERRIDES ] );
		}
	}

	protected function setUp(): void {
		parent::setUp();
		$this->clear_option();
	}

	protected function tearDown(): void {
		$this->clear_option();
		parent::tearDown();
	}

	public function test_record_override_saves_and_get_override_returns_it(): void {
		$service = new Industry_Page_Template_Override_Service();
		$ok      = $service->record_override( 'landing_01', Industry_Override_Schema::STATE_ACCEPTED, 'Weak fit accepted.' );
		$this->assertTrue( $ok );
		$override = $service->get_override( 'landing_01' );
		$this->assertIsArray( $override );
		$this->assertSame( Industry_Override_Schema::TARGET_TYPE_PAGE_TEMPLATE, $override[ Industry_Override_Schema::FIELD_TARGET_TYPE ] ?? '' );
		$this->assertSame( 'landing_01', $override[ Industry_Override_Schema::FIELD_TARGET_KEY ] ?? '' );
		$this->assertSame( 'Weak fit accepted.', $override[ Industry_Override_Schema::FIELD_REASON ] ?? '' );
	}

	public function test_get_override_returns_null_for_unknown_key(): void {
		$service = new Industry_Page_Template_Override_Service();
		$this->assertNull( $service->get_override( 'nonexistent' ) );
	}

	public function test_list_overrides_returns_all_recorded(): void {
		$service = new Industry_Page_Template_Override_Service();
		$service->record_override( 't1', Industry_Override_Schema::STATE_ACCEPTED, '' );
		$service->record_override( 't2', Industry_Override_Schema::STATE_REJECTED, 'No.' );
		$list = $service->list_overrides();
		$this->assertCount( 2, $list );
		$this->assertArrayHasKey( 't1', $list );
		$this->assertArrayHasKey( 't2', $list );
	}

	public function test_record_override_returns_false_for_empty_template_key(): void {
		$service = new Industry_Page_Template_Override_Service();
		$this->assertFalse( $service->record_override( '', Industry_Override_Schema::STATE_ACCEPTED, '' ) );
	}

	public function test_remove_override_removes_and_returns_true(): void {
		$service = new Industry_Page_Template_Override_Service();
		$service->record_override( 'to_remove', Industry_Override_Schema::STATE_ACCEPTED, '' );
		$this->assertNotNull( $service->get_override( 'to_remove' ) );
		$ok = $service->remove_override( 'to_remove' );
		$this->assertTrue( $ok );
		$this->assertNull( $service->get_override( 'to_remove' ) );
	}

	public function test_remove_override_returns_true_when_key_absent(): void {
		$service = new Industry_Page_Template_Override_Service();
		$this->assertTrue( $service->remove_override( 'nonexistent' ) );
	}
}
