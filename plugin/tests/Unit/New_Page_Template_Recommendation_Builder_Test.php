<?php
/**
 * Unit tests for New_Page_Template_Recommendation_Builder: grouped hierarchy display, family-aware summaries,
 * template selection reason, dependency warnings, deprecation-aware filtering (Prompt 192, spec §33).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\UI\New_Page_Template_Recommendation_Builder;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Recommendations/Template_Explanation_Builder_Interface.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/New_Page_Template_Recommendation_Builder.php';

/**
 * Stub explanation builder that returns a fixed shape for build_explanation (avoids Page_Template_Repository in unit test).
 */
class New_Page_Template_Recommendation_Builder_Test_Stub_Explanation implements \AIOPageBuilder\Domain\BuildPlan\Recommendations\Template_Explanation_Builder_Interface {
	public function build_explanation( string $template_key, array $item_payload = array() ): array {
		if ( $template_key === '' ) {
			return array(
				'template_key'       => '',
				'explanation_lines'  => array(),
				'deprecation_status' => 'unknown',
			);
		}
		$deprecated = $template_key === 'pt_deprecated_test';
		return array(
			'template_key'            => $template_key,
			'name'                    => 'Test Template',
			'template_category_class' => 'top_level',
			'template_family'         => 'home',
			'hierarchy_hint'          => 'top_level',
			'cta_direction_summary'   => 'Consultation, booking',
			'section_count'           => 10,
			'deprecation_status'      => $deprecated ? 'deprecated' : 'active',
			'replacement_keys'        => $deprecated ? array( 'pt_replacement' ) : array(),
			'explanation_lines'       => array( 'Purpose: Test page.' ),
		);
	}
}

final class New_Page_Template_Recommendation_Builder_Test extends TestCase {

	private New_Page_Template_Recommendation_Builder $builder;

	protected function setUp(): void {
		parent::setUp();
		$this->builder = new New_Page_Template_Recommendation_Builder( new New_Page_Template_Recommendation_Builder_Test_Stub_Explanation() );
	}

	public function test_build_for_item_returns_required_keys(): void {
		$item = array(
			Build_Plan_Item_Schema::KEY_ITEM_ID => 'plan_npc_0',
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'proposed_page_title' => 'Home',
				'template_key'        => 'pt_home_01',
				'purpose'             => 'Landing and conversion',
			),
		);
		$out  = $this->builder->build_for_item( $item );
		$this->assertArrayHasKey( New_Page_Template_Recommendation_Builder::KEY_PROPOSED_TEMPLATE_SUMMARY, $out );
		$this->assertArrayHasKey( New_Page_Template_Recommendation_Builder::KEY_HIERARCHY_CONTEXT_SUMMARY, $out );
		$this->assertArrayHasKey( New_Page_Template_Recommendation_Builder::KEY_TEMPLATE_SELECTION_REASON, $out );
		$this->assertArrayHasKey( New_Page_Template_Recommendation_Builder::KEY_GROUP_LABEL, $out );
		$this->assertArrayHasKey( New_Page_Template_Recommendation_Builder::KEY_GROUP_HIERARCHY_ROLE, $out );
		$this->assertArrayHasKey( New_Page_Template_Recommendation_Builder::KEY_GROUP_TEMPLATE_FAMILY, $out );
		$this->assertArrayHasKey( New_Page_Template_Recommendation_Builder::KEY_DEPENDENCY_WARNINGS, $out );
		$this->assertArrayHasKey( New_Page_Template_Recommendation_Builder::KEY_DEPRECATION_AWARE, $out );
		$this->assertArrayHasKey( New_Page_Template_Recommendation_Builder::KEY_CONFIDENCE_NOTE, $out );
	}

	public function test_build_for_item_empty_template_key_produces_empty_summary_and_group(): void {
		$item = array(
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'proposed_page_title' => 'Mystery',
				'template_key'        => '',
			),
		);
		$out  = $this->builder->build_for_item( $item );
		$this->assertSame( array(), $out[ New_Page_Template_Recommendation_Builder::KEY_PROPOSED_TEMPLATE_SUMMARY ] );
		$this->assertSame( '', $out[ New_Page_Template_Recommendation_Builder::KEY_GROUP_LABEL ] );
		$this->assertIsArray( $out[ New_Page_Template_Recommendation_Builder::KEY_HIERARCHY_CONTEXT_SUMMARY ] );
		$this->assertArrayHasKey( 'intended_parent', $out[ New_Page_Template_Recommendation_Builder::KEY_HIERARCHY_CONTEXT_SUMMARY ] );
	}

	public function test_build_for_item_hierarchy_context_summary_includes_parent_and_children(): void {
		$item = array(
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'intended_parent'    => '/about',
				'intended_children'  => array( 'child-a', 'child-b' ),
				'hierarchy_position' => 'hub',
				'hierarchy_role'     => 'hub',
			),
		);
		$out  = $this->builder->build_for_item( $item );
		$ctx  = $out[ New_Page_Template_Recommendation_Builder::KEY_HIERARCHY_CONTEXT_SUMMARY ];
		$this->assertSame( '/about', $ctx['intended_parent'] );
		$this->assertSame( array( 'child-a', 'child-b' ), $ctx['intended_children'] );
		$this->assertSame( 'hub', $ctx['hierarchy_position'] );
		$this->assertSame( 'hub', $ctx['hierarchy_role'] );
	}

	public function test_build_for_item_dependency_blocking_reasons_become_warnings(): void {
		$item = array(
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'dependency_blocking_reasons' => array( 'Parent must exist.', 'Slug conflict.' ),
			),
		);
		$out  = $this->builder->build_for_item( $item );
		$this->assertSame( array( 'Parent must exist.', 'Slug conflict.' ), $out[ New_Page_Template_Recommendation_Builder::KEY_DEPENDENCY_WARNINGS ] );
	}

	public function test_build_for_item_template_selection_reason_uses_purpose(): void {
		$item = array(
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'template_key' => 'pt_any',
				'purpose'      => 'Lead capture and support',
			),
		);
		$out  = $this->builder->build_for_item( $item );
		$this->assertSame( 'Lead capture and support', $out[ New_Page_Template_Recommendation_Builder::KEY_TEMPLATE_SELECTION_REASON ] );
	}

	public function test_build_for_item_confidence_note_when_confidence_in_payload(): void {
		$item = array(
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'confidence' => 'high',
			),
		);
		$out  = $this->builder->build_for_item( $item );
		$this->assertStringContainsString( 'high', $out[ New_Page_Template_Recommendation_Builder::KEY_CONFIDENCE_NOTE ] );
	}

	public function test_build_for_item_proposed_template_summary_has_template_key_and_family_when_known(): void {
		$item    = array(
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'template_key' => 'pt_any',
				'purpose'      => 'Test',
			),
		);
		$out     = $this->builder->build_for_item( $item );
		$summary = $out[ New_Page_Template_Recommendation_Builder::KEY_PROPOSED_TEMPLATE_SUMMARY ];
		$this->assertArrayHasKey( 'template_key', $summary );
		$this->assertSame( 'top_level', $summary['template_category_class'] );
		$this->assertSame( 'home', $summary['template_family'] );
		$this->assertArrayHasKey( 'cta_direction_summary', $summary );
		$this->assertSame( 10, $summary['section_count'] );
		$this->assertArrayHasKey( 'deprecation_status', $summary );
	}

	/** Deprecation-aware flag is true when explanation returns deprecation_status deprecated. */
	public function test_build_for_item_deprecation_aware_when_template_deprecated(): void {
		$item = array(
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'template_key' => 'pt_deprecated_test',
				'purpose'      => 'Legacy',
			),
		);
		$out  = $this->builder->build_for_item( $item );
		$this->assertTrue( $out[ New_Page_Template_Recommendation_Builder::KEY_DEPRECATION_AWARE ] );
		$summary = $out[ New_Page_Template_Recommendation_Builder::KEY_PROPOSED_TEMPLATE_SUMMARY ];
		$this->assertSame( 'deprecated', $summary['deprecation_status'] );
		$this->assertSame( array( 'pt_replacement' ), $summary['replacement_keys'] );
	}
}
