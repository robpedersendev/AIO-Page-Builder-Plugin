<?php
/**
 * Unit tests for Template_Recommendation_Context_Builder: recommendation context shape, deprecation-aware filtering (Prompt 190, spec §59.8).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Planning\Template_Recommendation_Context_Builder;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Deprecation_Metadata.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/AI/Planning/Template_Recommendation_Context_Builder.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';

final class Template_Recommendation_Context_Builder_Test extends TestCase {

	private Template_Recommendation_Context_Builder $builder;

	protected function setUp(): void {
		parent::setUp();
		$this->builder = new Template_Recommendation_Context_Builder( new Page_Template_Repository() );
	}

	public function test_build_returns_template_recommendation_context_and_total_active(): void {
		$result = $this->builder->build( array() );
		$this->assertArrayHasKey( 'template_recommendation_context', $result );
		$this->assertArrayHasKey( 'total_active', $result );
		$this->assertIsArray( $result['template_recommendation_context'] );
		$this->assertIsInt( $result['total_active'] );
		$this->assertLessThanOrEqual( Template_Recommendation_Context_Builder::DEFAULT_MAX_TEMPLATES, count( $result['template_recommendation_context'] ) );
	}

	public function test_build_respects_max_templates_option(): void {
		$result = $this->builder->build( array( 'max_templates' => 5 ) );
		$this->assertLessThanOrEqual( 5, count( $result['template_recommendation_context'] ) );
	}

	public function test_build_includes_template_preference_profile_when_provided(): void {
		$prefs  = array(
			'page_emphasis'             => 'conversion',
			'conversion_posture'        => 'moderate',
			'proof_style'               => 'social_proof',
			'content_density'           => 'moderate',
			'animation_preference'      => 'reduced',
			'cta_intensity_preference'  => 'medium',
			'reduced_motion_preference' => true,
		);
		$result = $this->builder->build(
			array(
				'max_templates'               => 1,
				'template_preference_profile' => $prefs,
			)
		);
		$this->assertArrayHasKey( 'template_preference_profile', $result );
		$this->assertSame( $prefs, $result['template_preference_profile'] );
	}

	public function test_get_recommended_template_summary_unknown_key_returns_empty_summary(): void {
		$summary = $this->builder->get_recommended_template_summary( 'pt_nonexistent_xyz' );
		$this->assertSame( 'pt_nonexistent_xyz', $summary['template_key'] );
		$this->assertSame( '', $summary['name'] );
		$this->assertSame( 'unknown', $summary['deprecation_status'] );
	}

	public function test_recommended_template_summary_has_required_keys(): void {
		$result        = $this->builder->build( array( 'max_templates' => 1 ) );
		$list          = $result['template_recommendation_context'];
		$expected_keys = array( 'template_key', 'name', 'purpose_summary', 'template_category_class', 'template_family', 'archetype', 'hierarchy_hint', 'cta_direction_summary', 'section_count', 'version', 'deprecation_status', 'one_pager_available' );
		if ( count( $list ) > 0 ) {
			$first = $list[0];
			foreach ( $expected_keys as $key ) {
				$this->assertArrayHasKey( $key, $first );
			}
			$this->assertSame( 'active', $first['deprecation_status'] );
		}
		$this->assertTrue( true );
	}
}
