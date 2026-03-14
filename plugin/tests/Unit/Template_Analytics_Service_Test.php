<?php
/**
 * Unit tests for Template_Analytics_Service: aggregation by family/class, date filtering, rejection-reason grouping, rollback-frequency summary (spec §49.11, Prompt 199).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Analytics\Build_Plan_List_Provider_Interface;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Registries\Analytics\Template_Analytics_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

require_once dirname( __DIR__ ) . '/bootstrap-sanitize.php';

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/BuildPlan/Analytics/Build_Plan_List_Provider_Interface.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/Registries/Analytics/Template_Analytics_Service.php';

/**
 * Stub plan list provider returning fixed plans for tests.
 */
final class Stub_Plan_List_Provider implements Build_Plan_List_Provider_Interface {

	/** @var list<array<string, mixed>> */
	public $plans = array();

	public function list_recent( int $limit = 50, int $offset = 0 ): array {
		return array_slice( $this->plans, $offset, $limit > 0 ? $limit : 50 );
	}
}

final class Template_Analytics_Service_Test extends TestCase {

	public function test_aggregation_by_family_and_class(): void {
		$provider = new Stub_Plan_List_Provider();
		$provider->plans = array(
			array(
				'post_date' => '2025-06-15 10:00:00',
				Build_Plan_Schema::KEY_STEPS => array(
					array(
						Build_Plan_Item_Schema::KEY_ITEMS => array(
							array(
								Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
								Build_Plan_Item_Schema::KEY_STATUS     => Build_Plan_Item_Statuses::APPROVED,
								Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
									'template_key' => 'pt_landing_01',
									'proposed_template_summary' => array(
										'template_family'           => 'services',
										'template_category_class'  => 'top_level',
									),
								),
							),
							array(
								Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
								Build_Plan_Item_Schema::KEY_STATUS     => Build_Plan_Item_Statuses::REJECTED,
								Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
									'template_key' => 'pt_hub_01',
									'proposed_template_summary' => array(
										'template_family'           => 'hub',
										'template_category_class'  => 'hub',
									),
									'rejection_reason' => 'User preferred different template',
								),
							),
						),
					),
				),
			),
		);
		$service = new Template_Analytics_Service( $provider, null, null );
		$summary = $service->get_analytics_summary( null, null );
		$usage = $summary['template_usage_trends'];
		$this->assertSame( 2, $usage['total_items'] );
		$this->assertArrayHasKey( 'services', $usage['by_family'] );
		$this->assertSame( 1, $usage['by_family']['services'] );
		$this->assertArrayHasKey( 'hub', $usage['by_family'] );
		$this->assertSame( 1, $usage['by_family']['hub'] );
		$this->assertArrayHasKey( 'top_level', $usage['by_class'] );
		$this->assertSame( 1, $usage['by_class']['top_level'] );
		$this->assertArrayHasKey( 'hub', $usage['by_class'] );
		$this->assertSame( 1, $usage['by_class']['hub'] );
	}

	public function test_recommendation_acceptance_by_family(): void {
		$provider = new Stub_Plan_List_Provider();
		$provider->plans = array(
			array(
				'post_date' => '2025-06-15 10:00:00',
				Build_Plan_Schema::KEY_STEPS => array(
					array(
						Build_Plan_Item_Schema::KEY_ITEMS => array(
							array(
								Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
								Build_Plan_Item_Schema::KEY_STATUS     => Build_Plan_Item_Statuses::APPROVED,
								Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
									'template_key' => 'pt_01',
									'proposed_template_summary' => array( 'template_family' => 'landing', 'template_category_class' => 'top_level' ),
								),
							),
							array(
								Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
								Build_Plan_Item_Schema::KEY_STATUS     => Build_Plan_Item_Statuses::REJECTED,
								Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
									'template_key' => 'pt_02',
									'proposed_template_summary' => array( 'template_family' => 'landing', 'template_category_class' => 'top_level' ),
									'rejection_reason' => 'not_needed',
								),
							),
						),
					),
				),
			),
		);
		$service = new Template_Analytics_Service( $provider, null, null );
		$summary = $service->get_analytics_summary( null, null );
		$acceptance = $summary['recommendation_acceptance'];
		$this->assertArrayHasKey( 'landing', $acceptance['by_family'] );
		$this->assertSame( 2, $acceptance['by_family']['landing']['proposed'] );
		$this->assertSame( 1, $acceptance['by_family']['landing']['approved'] );
		$this->assertSame( 1, $acceptance['by_family']['landing']['rejected'] );
	}

	public function test_rejection_reasons_grouped(): void {
		$provider = new Stub_Plan_List_Provider();
		$provider->plans = array(
			array(
				'post_date' => '2025-06-15 10:00:00',
				Build_Plan_Schema::KEY_STEPS => array(
					array(
						Build_Plan_Item_Schema::KEY_ITEMS => array(
							array(
								Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
								Build_Plan_Item_Schema::KEY_STATUS     => Build_Plan_Item_Statuses::REJECTED,
								Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
									'template_key' => 'pt_a',
									'proposed_template_summary' => array( 'template_family' => 'x', 'template_category_class' => 'y' ),
									'rejection_reason' => 'Duplicate content',
								),
							),
							array(
								Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
								Build_Plan_Item_Schema::KEY_STATUS     => Build_Plan_Item_Statuses::REJECTED,
								Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
									'template_key' => 'pt_b',
									'proposed_template_summary' => array( 'template_family' => 'x', 'template_category_class' => 'y' ),
									'rejection_reason' => 'Duplicate content',
								),
							),
							array(
								Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
								Build_Plan_Item_Schema::KEY_STATUS     => Build_Plan_Item_Statuses::REJECTED,
								Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
									'template_key' => 'pt_c',
									'proposed_template_summary' => array( 'template_family' => 'x', 'template_category_class' => 'y' ),
									'rejection_reason' => 'Other reason',
								),
							),
						),
					),
				),
			),
		);
		$service = new Template_Analytics_Service( $provider, null, null );
		$summary = $service->get_analytics_summary( null, null );
		$reasons = $summary['rejection_reasons'];
		$this->assertSame( 3, $reasons['total'] );
		$this->assertCount( 2, $reasons['reasons'] );
		$first = $reasons['reasons'][0];
		$this->assertSame( 'Duplicate content', $first['reason'] );
		$this->assertSame( 2, $first['count'] );
	}

	public function test_date_filtering_excludes_plans_outside_range(): void {
		$provider = new Stub_Plan_List_Provider();
		$provider->plans = array(
			array(
				'post_date' => '2025-06-01 10:00:00',
				Build_Plan_Schema::KEY_STEPS => array(
					array(
						Build_Plan_Item_Schema::KEY_ITEMS => array(
							array(
								Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
								Build_Plan_Item_Schema::KEY_STATUS     => Build_Plan_Item_Statuses::APPROVED,
								Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
									'template_key' => 'pt_out',
									'proposed_template_summary' => array( 'template_family' => 'out', 'template_category_class' => 'top_level' ),
								),
							),
						),
					),
				),
			),
			array(
				'post_date' => '2025-06-15 10:00:00',
				Build_Plan_Schema::KEY_STEPS => array(
					array(
						Build_Plan_Item_Schema::KEY_ITEMS => array(
							array(
								Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
								Build_Plan_Item_Schema::KEY_STATUS     => Build_Plan_Item_Statuses::APPROVED,
								Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
									'template_key' => 'pt_in',
									'proposed_template_summary' => array( 'template_family' => 'in_range', 'template_category_class' => 'top_level' ),
								),
							),
						),
					),
				),
			),
		);
		$service = new Template_Analytics_Service( $provider, null, null );
		$summary = $service->get_analytics_summary( '2025-06-10', '2025-06-20' );
		$usage = $summary['template_usage_trends'];
		$this->assertSame( 1, $usage['total_items'] );
		$this->assertArrayHasKey( 'in_range', $usage['by_family'] );
		$this->assertArrayNotHasKey( 'out', $usage['by_family'] );
	}

	public function test_rollback_frequency_summary_structure(): void {
		$provider = new Stub_Plan_List_Provider();
		$provider->plans = array();
		$service = new Template_Analytics_Service( $provider, null, null );
		$summary = $service->get_analytics_summary( null, null );
		$this->assertArrayHasKey( 'rollback_frequency', $summary );
		$this->assertArrayHasKey( 'total_rollbacks', $summary['rollback_frequency'] );
		$this->assertArrayHasKey( 'by_month', $summary['rollback_frequency'] );
		$this->assertIsArray( $summary['rollback_frequency']['by_month'] );
	}

	public function test_composition_usage_structure_when_repo_null(): void {
		$provider = new Stub_Plan_List_Provider();
		$service = new Template_Analytics_Service( $provider, null, null );
		$summary = $service->get_analytics_summary( null, null );
		$this->assertArrayHasKey( 'composition_usage', $summary );
		$this->assertSame( array(), $summary['composition_usage']['by_status'] );
		$this->assertSame( 0, $summary['composition_usage']['total'] );
	}
}
