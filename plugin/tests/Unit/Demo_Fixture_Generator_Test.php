<?php
/**
 * Unit tests for demo fixture generator: deterministic seeding, schema-valid output, synthetic markers, no external-call leakage (spec §56.4, §60.7; Prompt 130).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Statuses;
use AIOPageBuilder\Domain\Fixtures\Demo_Fixture_Generator;
use AIOPageBuilder\Domain\Fixtures\Demo_Fixture_Result;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Schema.php';
require_once $plugin_root . '/src/Domain/AI/Runs/Artifact_Category_Keys.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Contracts/Export_Mode_Keys.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Documentation/Documentation_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Snapshots/Version_Snapshot_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Fixtures/Registry_Fixture_Builder.php';
require_once $plugin_root . '/src/Domain/Fixtures/Demo_Fixture_Result.php';
require_once $plugin_root . '/src/Domain/Fixtures/Demo_Fixture_Generator.php';

/**
 * Tests for Demo_Fixture_Generator and Demo_Fixture_Result.
 */
final class Demo_Fixture_Generator_Test extends TestCase {

	public function test_generate_returns_success_result(): void {
		$gen   = new Demo_Fixture_Generator();
		$result = $gen->generate();
		$this->assertInstanceOf( Demo_Fixture_Result::class, $result );
		$this->assertTrue( $result->is_success() );
		$this->assertTrue( $result->is_synthetic() );
		$this->assertNotEmpty( $result->get_message() );
	}

	public function test_deterministic_seeding_same_output_twice(): void {
		$gen = new Demo_Fixture_Generator();
		$a   = $gen->generate();
		$b   = $gen->generate();
		$this->assertSame( $a->get_counts(), $b->get_counts() );
		$sum_a = $a->get_summary();
		$sum_b = $b->get_summary();
		$this->assertSame( array_keys( $sum_a ), array_keys( $sum_b ) );
		$this->assertSame( $sum_a['seed_result']['generator'] ?? '', $sum_b['seed_result']['generator'] ?? '' );
		$this->assertSame( $sum_a['seed_result'][ Demo_Fixture_Generator::SYNTHETIC_MARKER ] ?? false, $sum_b['seed_result'][ Demo_Fixture_Generator::SYNTHETIC_MARKER ] ?? false );
	}

	public function test_build_plan_fixture_schema_valid(): void {
		$gen   = new Demo_Fixture_Generator();
		$plans = $gen->get_build_plan_fixture();
		$this->assertNotEmpty( $plans );
		$plan = $plans[0];
		foreach ( Build_Plan_Schema::REQUIRED_ROOT_KEYS as $key ) {
			$this->assertArrayHasKey( $key, $plan, "Build plan fixture must have required key: $key" );
		}
		$this->assertTrue( Build_Plan_Schema::is_valid_status( $plan[ Build_Plan_Schema::KEY_STATUS ] ) );
		$steps = $plan[ Build_Plan_Schema::KEY_STEPS ] ?? array();
		$this->assertIsArray( $steps );
		foreach ( $steps as $step ) {
			$this->assertIsArray( $step );
			$this->assertTrue( Build_Plan_Schema::is_valid_step_type( $step[ Build_Plan_Item_Schema::KEY_STEP_TYPE ] ?? '' ) );
			$items = $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ?? array();
			foreach ( $items as $item ) {
				$this->assertArrayHasKey( Build_Plan_Item_Schema::KEY_ITEM_ID, $item );
				$this->assertArrayHasKey( Build_Plan_Item_Schema::KEY_ITEM_TYPE, $item );
				$this->assertArrayHasKey( Build_Plan_Item_Schema::KEY_PAYLOAD, $item );
			}
		}
		$this->assertSame( Build_Plan_Statuses::ROOT_APPROVED, $plan[ Build_Plan_Schema::KEY_STATUS ] );
	}

	public function test_profile_fixture_has_schema_keys(): void {
		$gen    = new Demo_Fixture_Generator();
		$profile = $gen->get_profile_fixture();
		$this->assertArrayHasKey( Profile_Schema::ROOT_BRAND, $profile );
		$this->assertArrayHasKey( Profile_Schema::ROOT_BUSINESS, $profile );
		$this->assertIsArray( $profile[ Profile_Schema::ROOT_BRAND ] );
		$this->assertIsArray( $profile[ Profile_Schema::ROOT_BUSINESS ] );
	}

	public function test_synthetic_markers_present_in_summary(): void {
		$gen    = new Demo_Fixture_Generator();
		$result = $gen->generate();
		$summary = $result->get_summary();
		$this->assertArrayHasKey( 'seed_result', $summary );
		$this->assertTrue( $summary['seed_result'][ Demo_Fixture_Generator::SYNTHETIC_MARKER ] ?? false );
		foreach ( array( 'registries', 'profile', 'crawl_summary', 'ai_runs', 'build_plans', 'logs', 'export_example' ) as $domain ) {
			if ( isset( $summary[ $domain ] ) ) {
				$payload = $summary[ $domain ];
				if ( is_array( $payload ) && array_key_exists( Demo_Fixture_Generator::SYNTHETIC_MARKER, $payload ) ) {
					$this->assertTrue( $payload[ Demo_Fixture_Generator::SYNTHETIC_MARKER ] );
				}
			}
		}
	}

	public function test_no_secret_like_keys_in_summary(): void {
		$gen    = new Demo_Fixture_Generator();
		$result = $gen->generate();
		$summary = $result->get_summary();
		$this->assertArrayNotHasKey( 'api_key', $summary );
		$this->assertArrayNotHasKey( 'secret', $summary );
		$this->assertArrayNotHasKey( 'password', $summary );
		$json = wp_json_encode( $summary );
		$this->assertNotFalse( $json );
		$this->assertStringNotContainsString( 'sk-', $json );
	}

	public function test_options_exclude_domains(): void {
		$gen = new Demo_Fixture_Generator();
		$result = $gen->generate( array(
			'include_registries'  => false,
			'include_build_plans' => false,
		) );
		$counts = $result->get_counts();
		$this->assertSame( 0, $counts['registries'] );
		$this->assertSame( 0, $counts['build_plans'] );
		$summary = $result->get_summary();
		$this->assertArrayNotHasKey( 'registries', $summary );
		$this->assertArrayNotHasKey( 'build_plans', $summary );
	}

	public function test_result_to_payload_stable_shape(): void {
		$gen    = new Demo_Fixture_Generator();
		$result = $gen->generate();
		$payload = $result->to_payload();
		$this->assertArrayHasKey( 'success', $payload );
		$this->assertArrayHasKey( 'message', $payload );
		$this->assertArrayHasKey( 'synthetic', $payload );
		$this->assertArrayHasKey( 'counts', $payload );
		$this->assertArrayHasKey( 'summary', $payload );
		$this->assertTrue( $payload['synthetic'] );
	}

	/**
	 * Example seed-result payload (spec §60.7; Prompt 130). No pseudocode.
	 */
	public function test_example_seed_result_payload(): void {
		$gen    = new Demo_Fixture_Generator();
		$result = $gen->generate();
		$payload = $result->to_payload();

		$example = array(
			'success'   => true,
			'message'   => 'Demo fixture generation completed. All data is synthetic.',
			'synthetic' => true,
			'counts'    => array(
				'registries'     => 5,
				'profile'        => 1,
				'crawl_summary'  => 2,
				'ai_runs'        => 1,
				'build_plans'    => 1,
				'logs'           => 2,
				'export_example' => 1,
			),
			'summary' => array(
				'seed_result' => array(
					'generator' => 'Demo_Fixture_Generator',
					'purpose'   => 'demo_qa_review',
					'_synthetic' => true,
				),
			),
		);

		$this->assertSame( $example['success'], $payload['success'] );
		$this->assertSame( $example['message'], $payload['message'] );
		$this->assertSame( $example['synthetic'], $payload['synthetic'] );
		$this->assertSame( $example['counts'], $payload['counts'] );
		$this->assertSame( $example['summary']['seed_result']['generator'], $payload['summary']['seed_result']['generator'] );
		$this->assertSame( $example['summary']['seed_result']['purpose'], $payload['summary']['seed_result']['purpose'] );
		$this->assertTrue( $payload['summary']['seed_result']['_synthetic'] );
	}
}
