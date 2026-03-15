<?php
/**
 * Unit tests for Industry_Build_Plan_Item_Override_Service (Prompt 369). record_override, get_override, list_for_plan.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Overrides\Industry_Build_Plan_Item_Override_Service;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Schema;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Override_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Build_Plan_Item_Override_Service.php';

final class Industry_Build_Plan_Item_Override_Service_Test extends TestCase {

	private function clear_option(): void {
		if ( isset( $GLOBALS['_aio_test_options'][ Option_Names::INDUSTRY_BUILD_PLAN_ITEM_OVERRIDES ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ Option_Names::INDUSTRY_BUILD_PLAN_ITEM_OVERRIDES ] );
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
		$service = new Industry_Build_Plan_Item_Override_Service();
		$ok = $service->record_override( 'plan-1', 'item-a', Industry_Override_Schema::STATE_ACCEPTED, 'Reviewer approved.' );
		$this->assertTrue( $ok );
		$override = $service->get_override( 'plan-1', 'item-a' );
		$this->assertIsArray( $override );
		$this->assertSame( Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM, $override[ Industry_Override_Schema::FIELD_TARGET_TYPE ] ?? '' );
		$this->assertSame( 'item-a', $override[ Industry_Override_Schema::FIELD_TARGET_KEY ] ?? '' );
		$this->assertSame( 'plan-1', $override[ Industry_Override_Schema::FIELD_PLAN_ID ] ?? '' );
		$this->assertSame( 'Reviewer approved.', $override[ Industry_Override_Schema::FIELD_REASON ] ?? '' );
	}

	public function test_get_override_returns_null_for_unknown_plan_or_item(): void {
		$service = new Industry_Build_Plan_Item_Override_Service();
		$service->record_override( 'plan-1', 'item-a', Industry_Override_Schema::STATE_ACCEPTED, '' );
		$this->assertNull( $service->get_override( 'plan-2', 'item-a' ) );
		$this->assertNull( $service->get_override( 'plan-1', 'item-b' ) );
	}

	public function test_list_for_plan_returns_only_that_plan_overrides(): void {
		$service = new Industry_Build_Plan_Item_Override_Service();
		$service->record_override( 'plan-1', 'item-1', Industry_Override_Schema::STATE_ACCEPTED, '' );
		$service->record_override( 'plan-1', 'item-2', Industry_Override_Schema::STATE_REJECTED, 'No' );
		$service->record_override( 'plan-2', 'item-1', Industry_Override_Schema::STATE_ACCEPTED, '' );
		$list = $service->list_for_plan( 'plan-1' );
		$this->assertCount( 2, $list );
		$this->assertArrayHasKey( 'item-1', $list );
		$this->assertArrayHasKey( 'item-2', $list );
		$this->assertCount( 1, $service->list_for_plan( 'plan-2' ) );
	}

	public function test_record_override_returns_false_for_empty_plan_id_or_item_id(): void {
		$service = new Industry_Build_Plan_Item_Override_Service();
		$this->assertFalse( $service->record_override( '', 'item-a', Industry_Override_Schema::STATE_ACCEPTED, '' ) );
		$this->assertFalse( $service->record_override( 'plan-1', '', Industry_Override_Schema::STATE_ACCEPTED, '' ) );
	}

	public function test_record_override_sanitizes_reason(): void {
		$service = new Industry_Build_Plan_Item_Override_Service();
		$service->record_override( 'p1', 'i1', Industry_Override_Schema::STATE_ACCEPTED, '  <b>Note</b>  ' );
		$override = $service->get_override( 'p1', 'i1' );
		$this->assertNotNull( $override );
		$reason = (string) ( $override[ Industry_Override_Schema::FIELD_REASON ] ?? '' );
		$this->assertStringNotContainsString( '<', $reason );
		$this->assertSame( 'Note', $reason );
	}
}
