<?php
/**
 * Unit tests for Industry_Coverage_Gap_Analyzer (Prompt 439). Empty state, missing overlays/bundle.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Coverage_Gap_Analyzer;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/Industry_Section_Helper_Overlay_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/Industry_Page_OnePager_Overlay_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Starter_Bundle_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Coverage_Gap_Analyzer.php';

final class Industry_Coverage_Gap_Analyzer_Test extends TestCase {

	public function test_analyze_returns_structure_with_empty_gaps_when_pack_registry_null(): void {
		$analyzer = new Industry_Coverage_Gap_Analyzer( null, null, null, null, null, null, null, null, null );
		$result   = $analyzer->analyze( true );
		$this->assertArrayHasKey( 'gaps', $result );
		$this->assertArrayHasKey( 'by_scope', $result );
		$this->assertIsArray( $result['gaps'] );
		$this->assertIsArray( $result['by_scope'] );
		$this->assertCount( 0, $result['gaps'] );
		$this->assertCount( 0, $result['by_scope'] );
	}

	public function test_analyze_with_active_pack_and_no_overlays_reports_gaps(): void {
		$pack_registry = new Industry_Pack_Registry( new Industry_Pack_Validator() );
		$pack_registry->load(
			array(
				array(
					Industry_Pack_Schema::FIELD_INDUSTRY_KEY => 'gap_test_industry',
					Industry_Pack_Schema::FIELD_NAME    => 'Gap Test',
					Industry_Pack_Schema::FIELD_SUMMARY => 'For coverage gap test',
					Industry_Pack_Schema::FIELD_STATUS  => Industry_Pack_Schema::STATUS_ACTIVE,
					Industry_Pack_Schema::FIELD_VERSION_MARKER => '1',
				),
			)
		);
		$section_overlay = new \AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry();
		$section_overlay->load( array() );
		$page_overlay = new \AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry();
		$page_overlay->load( array() );
		$bundle_registry = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry();
		$bundle_registry->load( array() );

		$analyzer = new Industry_Coverage_Gap_Analyzer( $pack_registry, $section_overlay, $page_overlay, $bundle_registry, null, null, null, null, null );
		$result   = $analyzer->analyze( false );

		$this->assertGreaterThanOrEqual( 1, count( $result['gaps'] ) );
		$this->assertArrayHasKey( 'gap_test_industry', $result['by_scope'] );

		$classes = array_column( $result['gaps'], 'missing_artifact_class' );
		$this->assertContains( Industry_Coverage_Gap_Analyzer::GAP_SECTION_HELPER_OVERLAYS, $classes );
		$this->assertContains( Industry_Coverage_Gap_Analyzer::GAP_PAGE_ONEPAGER_OVERLAYS, $classes );
		$this->assertContains( Industry_Coverage_Gap_Analyzer::GAP_STARTER_BUNDLE, $classes );
	}

	public function test_analyze_gap_has_scope_priority_and_explanation(): void {
		$pack_registry = new Industry_Pack_Registry( new Industry_Pack_Validator() );
		$pack_registry->load(
			array(
				array(
					Industry_Pack_Schema::FIELD_INDUSTRY_KEY => 'scope_test',
					Industry_Pack_Schema::FIELD_NAME    => 'Scope Test',
					Industry_Pack_Schema::FIELD_SUMMARY => 'Test',
					Industry_Pack_Schema::FIELD_STATUS  => Industry_Pack_Schema::STATUS_ACTIVE,
					Industry_Pack_Schema::FIELD_VERSION_MARKER => '1',
				),
			)
		);
		$bundle_registry = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry();
		$bundle_registry->load( array() );

		$analyzer = new Industry_Coverage_Gap_Analyzer( $pack_registry, null, null, $bundle_registry, null, null, null, null, null );
		$result   = $analyzer->analyze( false );

		$this->assertGreaterThanOrEqual( 1, count( $result['gaps'] ) );
		$first = $result['gaps'][0];
		$this->assertArrayHasKey( 'scope', $first );
		$this->assertArrayHasKey( 'missing_artifact_class', $first );
		$this->assertArrayHasKey( 'priority', $first );
		$this->assertArrayHasKey( 'explanation', $first );
		$this->assertSame( 'scope_test', $first['scope'] );
		$this->assertContains( $first['priority'], array( Industry_Coverage_Gap_Analyzer::PRIORITY_HIGH, Industry_Coverage_Gap_Analyzer::PRIORITY_MEDIUM, Industry_Coverage_Gap_Analyzer::PRIORITY_LOW ) );
	}
}
