<?php
/**
 * Regression guards for industry recommendation quality (Prompt 393).
 * Critical pack/ref integrity, representative benchmark structure, fallback and substitute invariants.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Recommendation_Benchmark_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository_Interface;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Page_Template_Recommendation_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Page_Template_Recommendation_Resolver.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Section_Recommendation_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Section_Recommendation_Resolver.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Recommendation_Benchmark_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository_Interface.php';

final class Industry_Recommendation_Regression_Guard_Test extends TestCase {

	private const LAUNCH_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	private function page_repo_stub( int $count = 5 ): Page_Template_Repository_Interface {
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

	/**
	 * Regression: all launch industries must resolve to a pack when built-in definitions are loaded.
	 * Guard §3.1: required schema fields; refs valid or empty; resolution does not fatal.
	 */
	public function test_launch_industries_pack_integrity_when_builtin_loaded(): void {
		$registry = new Industry_Pack_Registry( new Industry_Pack_Validator() );
		$registry->load( Industry_Pack_Registry::get_builtin_pack_definitions() );
		foreach ( self::LAUNCH_INDUSTRIES as $industry_key ) {
			$pack = $registry->get( $industry_key );
			$this->assertNotNull( $pack, "Launch industry {$industry_key} must resolve to a pack when built-in packs are loaded." );
			$this->assertArrayHasKey( Industry_Pack_Schema::FIELD_INDUSTRY_KEY, $pack );
			$this->assertArrayHasKey( Industry_Pack_Schema::FIELD_NAME, $pack );
			$this->assertArrayHasKey( Industry_Pack_Schema::FIELD_SUMMARY, $pack );
			$this->assertArrayHasKey( Industry_Pack_Schema::FIELD_STATUS, $pack );
			$this->assertArrayHasKey( Industry_Pack_Schema::FIELD_VERSION_MARKER, $pack );
			$this->assertSame( Industry_Pack_Schema::SUPPORTED_SCHEMA_VERSION, $pack[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] ?? '', "Pack {$industry_key} must have supported version_marker." );
			// Refs (token_preset_ref, preferred_section_keys) are either empty or valid format; access must not fatal.
			$token_ref = $pack[ Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF ] ?? '';
			$this->assertTrue( $token_ref === '' || is_string( $token_ref ), "Pack {$industry_key} token_preset_ref must be empty or string." );
			$preferred = $pack[ Industry_Pack_Schema::FIELD_PREFERRED_SECTION_KEYS ] ?? null;
			$this->assertTrue( $preferred === null || is_array( $preferred ), "Pack {$industry_key} preferred_section_keys must be array or absent." );
		}
	}

	/**
	 * Regression: benchmark run with built-in packs and page repo produces valid structure and at least one scenario with evaluations.
	 */
	public function test_benchmark_representative_structure_with_builtin_packs(): void {
		$registry = new Industry_Pack_Registry( new Industry_Pack_Validator() );
		$registry->load( Industry_Pack_Registry::get_builtin_pack_definitions() );
		$page_repo = $this->page_repo_stub( 5 );
		$service   = new Industry_Recommendation_Benchmark_Service(
			$registry,
			new Industry_Page_Template_Recommendation_Resolver(),
			null,
			$page_repo,
			null,
			null
		);
		$report    = $service->run( 10, 5 );
		$this->assertCount( 4, $report['scenarios'] );
		$at_least_one_evaluated  = false;
		$at_least_one_pack_found = false;
		foreach ( $report['scenarios'] as $scenario ) {
			$this->assertArrayHasKey( 'industry_key', $scenario );
			$this->assertArrayHasKey( 'pack_found', $scenario );
			$this->assertArrayHasKey( 'page_recommendations', $scenario );
			$this->assertArrayHasKey( 'section_recommendations', $scenario );
			$this->assertArrayHasKey( 'starter_bundle_keys', $scenario );
			$this->assertArrayHasKey( 'metadata_gaps', $scenario );
			$pr = $scenario['page_recommendations'];
			$this->assertArrayHasKey( 'total_evaluated', $pr );
			$this->assertArrayHasKey( 'fit_distribution', $pr );
			$this->assertArrayHasKey( 'top_template_keys', $pr );
			if ( ( $pr['total_evaluated'] ?? 0 ) > 0 ) {
				$at_least_one_evaluated = true;
			}
			if ( ! empty( $scenario['pack_found'] ) ) {
				$at_least_one_pack_found = true;
			}
		}
		$this->assertTrue( $at_least_one_evaluated, 'At least one scenario must have page_recommendations.total_evaluated > 0 when page repo is provided.' );
		$this->assertTrue( $at_least_one_pack_found, 'At least one launch industry scenario must have pack_found true when built-in packs are loaded.' );
		// Guard §3.2: fit_distribution not all zero when pack present.
		$at_least_one_non_zero_fit = false;
		foreach ( $report['scenarios'] as $scenario ) {
			if ( empty( $scenario['pack_found'] ) ) {
				continue;
			}
			$fd = $scenario['page_recommendations']['fit_distribution'] ?? array();
			$sum = ( $fd['recommended'] ?? 0 ) + ( $fd['neutral'] ?? 0 ) + ( $fd['discouraged'] ?? 0 ) + ( $fd['allowed_weak_fit'] ?? 0 );
			if ( $sum > 0 ) {
				$at_least_one_non_zero_fit = true;
				break;
			}
		}
		$this->assertTrue( $at_least_one_non_zero_fit, 'At least one scenario with pack_found must have non-zero fit_distribution when templates are evaluated.' );
	}

	/**
	 * Regression: unknown industry_key with null pack yields neutral (no throw). Resolver contract.
	 */
	/**
	 * Regression: unknown industry_key with null pack yields neutral (no throw). Guard §3.3 page resolver.
	 */
	public function test_unknown_industry_key_yields_neutral_no_throw(): void {
		$resolver  = new Industry_Page_Template_Recommendation_Resolver();
		$profile   = array(
			'primary_industry_key'    => 'nonexistent_industry_xyz',
			'secondary_industry_keys' => array(),
		);
		$templates = array(
			array(
				'internal_key'    => 't1',
				'name'            => 'T1',
				'template_family' => 'landing',
			),
		);
		$result    = $resolver->resolve( $profile, null, $templates, array() );
		$this->assertCount( 1, $result->get_items() );
		$this->assertSame( Industry_Page_Template_Recommendation_Resolver::FIT_NEUTRAL, $result->get_items()[0]['fit_classification'] );
		$this->assertSame( 0, $result->get_items()[0]['score'] );
	}

	/**
	 * Regression: benchmark run with null dependencies does not throw; scenarios complete. Guard §3.3.
	 */
	public function test_benchmark_run_with_null_dependencies_does_not_throw(): void {
		$service = new Industry_Recommendation_Benchmark_Service( null, null, null, null, null, null );
		$report  = $service->run( 0, 0 );
		$this->assertArrayHasKey( 'scenarios', $report );
		$this->assertCount( 4, $report['scenarios'] );
		foreach ( $report['scenarios'] as $scenario ) {
			$this->assertArrayHasKey( 'page_recommendations', $scenario );
			$this->assertArrayHasKey( 'fit_distribution', $scenario['page_recommendations'] );
		}
	}

	/**
	 * Regression: section resolver with unknown industry_key and null pack yields neutral; no exception. Guard §3.3.
	 */
	public function test_section_resolver_unknown_industry_key_yields_neutral_no_throw(): void {
		$resolver = new Industry_Section_Recommendation_Resolver();
		$profile  = array(
			'primary_industry_key'    => 'nonexistent_industry_xyz',
			'secondary_industry_keys' => array(),
		);
		$sections = array(
			array(
				Section_Schema::FIELD_INTERNAL_KEY => 's1',
				Section_Schema::FIELD_NAME         => 'S1',
			),
		);
		$result   = $resolver->resolve( $profile, null, $sections, array() );
		$this->assertCount( 1, $result->get_items() );
		$this->assertSame( Industry_Section_Recommendation_Resolver::FIT_NEUTRAL, $result->get_items()[0]['fit_classification'] );
		$this->assertSame( 0, $result->get_items()[0]['score'] );
	}
}
