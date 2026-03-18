<?php
/**
 * Unit tests for ACF_Fixture_Builder: scenario coverage, structure validation (spec §56.2, Prompt 040).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Diagnostics\ACF_Fixture_Builder;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ACF/Diagnostics/ACF_Fixture_Builder.php';

final class ACF_Fixture_Builder_Test extends TestCase {

	public function test_all_scenarios_returns_four_keys(): void {
		$scenarios = ACF_Fixture_Builder::all_scenarios();
		$this->assertCount( 4, $scenarios );
		$this->assertContains( ACF_Fixture_Builder::SCENARIO_VALID, $scenarios );
		$this->assertContains( ACF_Fixture_Builder::SCENARIO_STALE, $scenarios );
		$this->assertContains( ACF_Fixture_Builder::SCENARIO_DEPRECATED, $scenarios );
		$this->assertContains( ACF_Fixture_Builder::SCENARIO_INVALID, $scenarios );
	}

	public function test_build_scenario_valid_returns_expected_structure(): void {
		$result = ACF_Fixture_Builder::build_scenario( ACF_Fixture_Builder::SCENARIO_VALID );
		$this->assertArrayHasKey( 'description', $result );
		$this->assertArrayHasKey( 'section_definitions', $result );
		$this->assertArrayHasKey( 'template_definitions', $result );
		$this->assertArrayHasKey( 'assignment_rows', $result );
		$this->assertNotEmpty( $result['section_definitions'] );
		$this->assertNotEmpty( $result['template_definitions'] );
		$this->assertNotEmpty( $result['assignment_rows'] );
		$this->assertStringContainsString( 'Valid', $result['description'] );
	}

	public function test_build_scenario_valid_section_has_blueprint(): void {
		$result  = ACF_Fixture_Builder::build_scenario( ACF_Fixture_Builder::SCENARIO_VALID );
		$section = $result['section_definitions'][0];
		$this->assertArrayHasKey( 'field_blueprint', $section );
		$this->assertArrayHasKey( 'internal_key', $section );
		$this->assertSame( 'st_fixture_hero', $section['internal_key'] );
	}

	public function test_build_scenario_stale_returns_stale_description(): void {
		$result = ACF_Fixture_Builder::build_scenario( ACF_Fixture_Builder::SCENARIO_STALE );
		$this->assertStringContainsString( 'Stale', $result['description'] );
		$this->assertNotEmpty( $result['assignment_rows'] );
	}

	public function test_build_scenario_deprecated_has_deprecated_section(): void {
		$result = ACF_Fixture_Builder::build_scenario( ACF_Fixture_Builder::SCENARIO_DEPRECATED );
		$this->assertNotEmpty( $result['section_definitions'] );
		$section = $result['section_definitions'][0];
		$this->assertSame( 'deprecated', $section['status'] );
		$this->assertArrayHasKey( 'deprecation', $section );
	}

	public function test_build_scenario_invalid_has_sections_without_valid_blueprint(): void {
		$result = ACF_Fixture_Builder::build_scenario( ACF_Fixture_Builder::SCENARIO_INVALID );
		$this->assertCount( 2, $result['section_definitions'] );
		$this->assertEmpty( $result['assignment_rows'] );
		$this->assertStringContainsString( 'Invalid', $result['description'] );
	}

	public function test_build_scenario_unknown_returns_safe_default(): void {
		$result = ACF_Fixture_Builder::build_scenario( 'unknown_scenario' );
		$this->assertArrayHasKey( 'description', $result );
		$this->assertArrayHasKey( 'section_definitions', $result );
		$this->assertArrayHasKey( 'assignment_rows', $result );
		$this->assertSame( 'Unknown scenario.', $result['description'] );
		$this->assertEmpty( $result['section_definitions'] );
	}

	public function test_assignment_rows_have_map_type_and_refs(): void {
		$result = ACF_Fixture_Builder::build_scenario( ACF_Fixture_Builder::SCENARIO_VALID );
		foreach ( $result['assignment_rows'] as $row ) {
			$this->assertArrayHasKey( 'map_type', $row );
			$this->assertArrayHasKey( 'source_ref', $row );
			$this->assertArrayHasKey( 'target_ref', $row );
		}
	}
}
