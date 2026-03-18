<?php
/**
 * Unit tests for Industry_Recommendation_Benchmark_Service: repeatable scenarios, report structure (Prompt 392).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Recommendation_Benchmark_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository_Interface;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Page_Template_Recommendation_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Page_Template_Recommendation_Resolver.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Recommendation_Benchmark_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository_Interface.php';

final class Industry_Recommendation_Benchmark_Service_Test extends TestCase {

	private function page_repo_stub( int $count = 10 ): Page_Template_Repository_Interface {
		$templates = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$templates[] = array(
				'internal_key'    => 'template_' . $i,
				'name'            => 'Template ' . $i,
				'template_family' => 'landing',
			);
		}
		return new class( $templates ) implements Page_Template_Repository_Interface {
			private array $templates;
			public function __construct( array $templates ) {
				$this->templates = $templates;
			}
			public function list_all_definitions( int $limit = 0, int $offset = 0 ): array {
				return array_slice( $this->templates, $offset, $limit ? $limit : count( $this->templates ) );
			}
		};
	}

	private function minimal_pack_registry(): Industry_Pack_Registry {
		$registry = new Industry_Pack_Registry( new Industry_Pack_Validator() );
		$registry->load(
			array(
				array(
					Industry_Pack_Schema::FIELD_INDUSTRY_KEY => 'realtor',
					Industry_Pack_Schema::FIELD_NAME    => 'Realtor',
					Industry_Pack_Schema::FIELD_SUMMARY => 'Realtor pack',
					Industry_Pack_Schema::FIELD_STATUS  => Industry_Pack_Schema::STATUS_ACTIVE,
					Industry_Pack_Schema::FIELD_VERSION_MARKER => Industry_Pack_Schema::SUPPORTED_SCHEMA_VERSION,
				),
			)
		);
		return $registry;
	}

	public function test_run_returns_report_with_scenarios_and_run_at(): void {
		$pack_registry = $this->minimal_pack_registry();
		$page_repo     = $this->page_repo_stub( 5 );
		$service       = new Industry_Recommendation_Benchmark_Service(
			$pack_registry,
			new Industry_Page_Template_Recommendation_Resolver(),
			null,
			$page_repo,
			null,
			null
		);
		$report        = $service->run( 10, 5 );
		$this->assertArrayHasKey( 'scenarios', $report );
		$this->assertArrayHasKey( 'run_at', $report );
		$this->assertArrayHasKey( 'launch_industries', $report );
		$this->assertIsArray( $report['scenarios'] );
		$this->assertCount( 4, $report['scenarios'] );
		$this->assertSame( array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' ), $report['launch_industries'] );
	}

	public function test_each_scenario_has_industry_key_and_page_recommendations(): void {
		$pack_registry = $this->minimal_pack_registry();
		$page_repo     = $this->page_repo_stub( 3 );
		$service       = new Industry_Recommendation_Benchmark_Service(
			$pack_registry,
			new Industry_Page_Template_Recommendation_Resolver(),
			null,
			$page_repo,
			null,
			null
		);
		$report        = $service->run( 10, 5 );
		foreach ( $report['scenarios'] as $scenario ) {
			$this->assertArrayHasKey( 'industry_key', $scenario );
			$this->assertArrayHasKey( 'pack_found', $scenario );
			$this->assertArrayHasKey( 'page_recommendations', $scenario );
			$pr = $scenario['page_recommendations'];
			$this->assertArrayHasKey( 'top_template_keys', $pr );
			$this->assertArrayHasKey( 'fit_distribution', $pr );
			$this->assertArrayHasKey( 'total_evaluated', $pr );
			$this->assertIsArray( $pr['fit_distribution'] );
		}
	}

	public function test_run_with_zero_cap_uses_defaults(): void {
		$service = new Industry_Recommendation_Benchmark_Service( null, null, null, null, null, null );
		$report  = $service->run( 0, 0 );
		$this->assertCount( 4, $report['scenarios'] );
		foreach ( $report['scenarios'] as $scenario ) {
			$this->assertArrayHasKey( 'page_recommendations', $scenario );
			$this->assertArrayHasKey( 'section_recommendations', $scenario );
			$this->assertArrayHasKey( 'starter_bundle_keys', $scenario );
			$this->assertArrayHasKey( 'metadata_gaps', $scenario );
		}
	}
}
