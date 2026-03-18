<?php
/**
 * Unit tests for Industry_Override_Conflict_Detector (Prompt 464).
 * Verifies advisory-only behavior, missing-target detection for build_plan_item, and bounded output.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Overrides\Industry_Build_Plan_Item_Override_Service;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Read_Model_Builder;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Schema;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Override_Conflict_Detector;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository_Interface;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Override_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Build_Plan_Item_Override_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Override_Read_Model_Builder.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Override_Conflict_Detector.php';

final class Industry_Override_Conflict_Detector_Test extends TestCase {

	/** No overrides yields empty conflicts. */
	public function test_detect_empty_when_no_overrides(): void {
		$read_model = new Industry_Override_Read_Model_Builder();
		$stub_repo  = new class() implements Build_Plan_Repository_Interface {
			public function get_by_key( string $key ): ?array {
				return null; }
			public function get_plan_definition( int $post_id ): array {
				return array(); }
			public function save_plan_definition( int $post_id, array $definition ): bool {
				return false; }
		};
		$detector   = new Industry_Override_Conflict_Detector( $read_model, $stub_repo );
		$results    = $detector->detect();
		$this->assertIsArray( $results );
		$this->assertCount( 0, $results );
	}

	/** Build plan item override for non-existent plan yields missing_target conflict. */
	public function test_detect_missing_plan_yields_conflict(): void {
		$plan_service = new Industry_Build_Plan_Item_Override_Service();
		$plan_service->record_override( 'non-existent-plan-id', 'item_1', Industry_Override_Schema::STATE_ACCEPTED, 'Test' );
		$read_model = new Industry_Override_Read_Model_Builder( null, null, $plan_service );
		$stub_repo  = new class() implements Build_Plan_Repository_Interface {
			public function get_by_key( string $key ): ?array {
				return null; }
			public function get_plan_definition( int $post_id ): array {
				return array(); }
			public function save_plan_definition( int $post_id, array $definition ): bool {
				return false; }
		};
		$detector   = new Industry_Override_Conflict_Detector( $read_model, $stub_repo );
		$results    = $detector->detect();
		$this->assertCount( 1, $results );
		$this->assertSame( Industry_Override_Conflict_Detector::CONFLICT_TYPE_MISSING_TARGET, $results[0]['conflict_type'] );
		$this->assertSame( Industry_Override_Conflict_Detector::SEVERITY_WARNING, $results[0]['severity'] );
		$this->assertSame( 'non-existent-plan-id', $results[0]['plan_id'] );
		$this->assertSame( 'item_1', $results[0]['target_key'] );
		$this->assertArrayHasKey( 'suggested_review_action', $results[0] );
	}

	/** Without plan repository, build_plan_item overrides are not checked (no conflict for missing plan). */
	public function test_detect_without_plan_repository_skips_build_plan_items(): void {
		$plan_service = new Industry_Build_Plan_Item_Override_Service();
		$plan_service->record_override( 'any-plan', 'item_1', Industry_Override_Schema::STATE_ACCEPTED, 'Test' );
		$read_model = new Industry_Override_Read_Model_Builder( null, null, $plan_service );
		$detector   = new Industry_Override_Conflict_Detector( $read_model, null );
		$results    = $detector->detect();
		$this->assertCount( 0, $results );
	}
}
