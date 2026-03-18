<?php
/**
 * Unit tests for Build_Plan_Template_Explanation_Builder: build_explanation shape, explanation_lines, deprecation in explanation (Prompt 190, spec §31, §59.9).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Recommendations\Build_Plan_Template_Explanation_Builder;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Recommendations/Build_Plan_Template_Explanation_Builder.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';

final class Build_Plan_Template_Explanation_Builder_Test extends TestCase {

	private Build_Plan_Template_Explanation_Builder $builder;

	protected function setUp(): void {
		parent::setUp();
		$this->builder = new Build_Plan_Template_Explanation_Builder( new Page_Template_Repository(), null );
	}

	public function test_build_explanation_empty_key_returns_empty_shape(): void {
		$explanation = $this->builder->build_explanation( '', array() );
		$this->assertArrayHasKey( 'template_key', $explanation );
		$this->assertArrayHasKey( 'explanation_lines', $explanation );
		$this->assertSame( array(), $explanation['explanation_lines'] );
	}

	public function test_build_explanation_unknown_key_returns_shape_with_key_and_empty_lines(): void {
		$explanation = $this->builder->build_explanation( 'pt_unknown_key_xyz', array() );
		$this->assertSame( 'pt_unknown_key_xyz', $explanation['template_key'] );
		$this->assertArrayHasKey( 'explanation_lines', $explanation );
		$this->assertIsArray( $explanation['explanation_lines'] );
		$this->assertArrayHasKey( 'deprecation_status', $explanation );
		$this->assertSame( 'unknown', $explanation['deprecation_status'] );
	}

	public function test_build_explanation_has_build_plan_explanation_keys(): void {
		$explanation = $this->builder->build_explanation( 'pt_any', array( 'purpose' => 'Test page' ) );
		$expected    = array( 'template_key', 'name', 'purpose_summary', 'template_category_class', 'template_family', 'hierarchy_hint', 'cta_direction_summary', 'section_count', 'version', 'deprecation_status', 'replacement_keys', 'one_pager_available', 'explanation_lines' );
		foreach ( $expected as $key ) {
			$this->assertArrayHasKey( $key, $explanation );
		}
	}
}
