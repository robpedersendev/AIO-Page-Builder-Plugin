<?php
/**
 * Unit tests for Industry_Override_Read_Model_Builder (Prompt 436). build(), filters.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Overrides\Industry_Build_Plan_Item_Override_Service;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Read_Model_Builder;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Schema;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Page_Template_Override_Service;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Section_Override_Service;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Override_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Section_Override_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Page_Template_Override_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Build_Plan_Item_Override_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Override_Read_Model_Builder.php';

final class Industry_Override_Read_Model_Builder_Test extends TestCase {

	private function clear_options(): void {
		foreach ( array( Option_Names::INDUSTRY_SECTION_OVERRIDES, Option_Names::INDUSTRY_PAGE_TEMPLATE_OVERRIDES, Option_Names::INDUSTRY_BUILD_PLAN_ITEM_OVERRIDES ) as $key ) {
			if ( isset( $GLOBALS['_aio_test_options'][ $key ] ) ) {
				unset( $GLOBALS['_aio_test_options'][ $key ] );
			}
		}
	}

	protected function setUp(): void {
		parent::setUp();
		$this->clear_options();
	}

	protected function tearDown(): void {
		$this->clear_options();
		parent::tearDown();
	}

	public function test_build_returns_empty_when_no_overrides(): void {
		$builder = new Industry_Override_Read_Model_Builder();
		$rows = $builder->build( array() );
		$this->assertIsArray( $rows );
		$this->assertCount( 0, $rows );
	}

	public function test_build_aggregates_section_template_and_plan_item_overrides(): void {
		$section = new Industry_Section_Override_Service();
		$section->record_override( 'sec_1', Industry_Override_Schema::STATE_ACCEPTED, 'Reason A' );
		$template = new Industry_Page_Template_Override_Service();
		$template->record_override( 'tpl_1', Industry_Override_Schema::STATE_REJECTED, 'Reason B' );
		$plan = new Industry_Build_Plan_Item_Override_Service();
		$plan->record_override( 'plan-1', 'item-1', Industry_Override_Schema::STATE_ACCEPTED, '' );

		$builder = new Industry_Override_Read_Model_Builder();
		$rows = $builder->build( array() );
		$this->assertCount( 3, $rows );

		$types = array_column( $rows, 'target_type' );
		$this->assertContains( Industry_Override_Schema::TARGET_TYPE_SECTION, $types );
		$this->assertContains( Industry_Override_Schema::TARGET_TYPE_PAGE_TEMPLATE, $types );
		$this->assertContains( Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM, $types );

		$keys = array_column( $rows, 'target_key' );
		$this->assertContains( 'sec_1', $keys );
		$this->assertContains( 'tpl_1', $keys );
		$this->assertContains( 'item-1', $keys );
	}

	public function test_build_filter_by_target_type_returns_only_that_type(): void {
		$section = new Industry_Section_Override_Service();
		$section->record_override( 'sec_1', Industry_Override_Schema::STATE_ACCEPTED, '' );
		$template = new Industry_Page_Template_Override_Service();
		$template->record_override( 'tpl_1', Industry_Override_Schema::STATE_ACCEPTED, '' );

		$builder = new Industry_Override_Read_Model_Builder();
		$rows = $builder->build( array( Industry_Override_Read_Model_Builder::FILTER_TARGET_TYPE => Industry_Override_Schema::TARGET_TYPE_SECTION ) );
		$this->assertCount( 1, $rows );
		$this->assertSame( Industry_Override_Schema::TARGET_TYPE_SECTION, $rows[0]['target_type'] );
		$this->assertSame( 'sec_1', $rows[0]['target_key'] );
	}

	public function test_build_filter_by_state_returns_only_that_state(): void {
		$section = new Industry_Section_Override_Service();
		$section->record_override( 'accepted_sec', Industry_Override_Schema::STATE_ACCEPTED, '' );
		$section->record_override( 'rejected_sec', Industry_Override_Schema::STATE_REJECTED, 'No' );

		$builder = new Industry_Override_Read_Model_Builder();
		$rows = $builder->build( array( Industry_Override_Read_Model_Builder::FILTER_STATE => Industry_Override_Schema::STATE_REJECTED ) );
		$this->assertCount( 1, $rows );
		$this->assertSame( Industry_Override_Schema::STATE_REJECTED, $rows[0]['state'] );
	}

	public function test_build_filter_reason_present_excludes_empty_reason(): void {
		$section = new Industry_Section_Override_Service();
		$section->record_override( 'with_reason', Industry_Override_Schema::STATE_ACCEPTED, 'Has reason' );
		$section->record_override( 'no_reason', Industry_Override_Schema::STATE_ACCEPTED, '' );

		$builder = new Industry_Override_Read_Model_Builder();
		$rows = $builder->build( array( Industry_Override_Read_Model_Builder::FILTER_REASON_PRESENT => true ) );
		$this->assertCount( 1, $rows );
		$this->assertSame( 'with_reason', $rows[0]['target_key'] );
	}
}
