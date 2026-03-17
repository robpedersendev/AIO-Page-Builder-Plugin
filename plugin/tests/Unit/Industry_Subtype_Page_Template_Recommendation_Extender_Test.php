<?php
/**
 * Unit tests for Industry_Subtype_Page_Template_Recommendation_Extender (Prompt 423).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Result;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Page_Template_Recommendation_Extender;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || exit;

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Page_Template_Recommendation_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Page_Template_Recommendation_Resolver.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Subtype_Page_Template_Recommendation_Extender.php';

/**
 * @covers \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Page_Template_Recommendation_Extender
 */
final class Industry_Subtype_Page_Template_Recommendation_Extender_Test extends TestCase {

	public function test_apply_subtype_influence_null_subtype_returns_result_with_subtype_fields_false(): void {
		$base = new Industry_Page_Template_Recommendation_Result( array(
			array( 'page_template_key' => 'service_01', 'score' => 10, 'fit_classification' => Industry_Page_Template_Recommendation_Resolver::FIT_NEUTRAL, 'explanation_reasons' => array(), 'industry_source_refs' => array(), 'hierarchy_fit' => '', 'lpagery_fit' => '', 'warning_flags' => array() ),
		) );
		$extender = new Industry_Subtype_Page_Template_Recommendation_Extender();
		$out = $extender->apply_subtype_influence( $base, null, array() );
		$items = $out->get_items();
		$this->assertCount( 1, $items );
		$this->assertFalse( $items[0]['subtype_influence_applied'] );
		$this->assertSame( '', $items[0]['subtype_reason_summary'] );
		$this->assertSame( 10, $items[0]['score'] );
	}

	public function test_apply_subtype_influence_page_family_emphasis_boosts_matching_templates(): void {
		$base = new Industry_Page_Template_Recommendation_Result( array(
			array( 'page_template_key' => 'service_01', 'score' => 15, 'fit_classification' => Industry_Page_Template_Recommendation_Resolver::FIT_ALLOWED_WEAK, 'explanation_reasons' => array(), 'industry_source_refs' => array(), 'hierarchy_fit' => '', 'lpagery_fit' => '', 'warning_flags' => array() ),
			array( 'page_template_key' => 'landing_01', 'score' => 10, 'fit_classification' => Industry_Page_Template_Recommendation_Resolver::FIT_NEUTRAL, 'explanation_reasons' => array(), 'industry_source_refs' => array(), 'hierarchy_fit' => '', 'lpagery_fit' => '', 'warning_flags' => array() ),
		) );
		$templates = array(
			array( Page_Template_Schema::FIELD_INTERNAL_KEY => 'service_01', 'template_family' => 'service' ),
			array( Page_Template_Schema::FIELD_INTERNAL_KEY => 'landing_01', 'template_family' => 'landing' ),
		);
		$subtype = array( 'page_family_emphasis' => array( 'service' ) );
		$extender = new Industry_Subtype_Page_Template_Recommendation_Extender();
		$out = $extender->apply_subtype_influence( $base, $subtype, $templates );
		$items = $out->get_items();
		$by_key = array();
		foreach ( $items as $item ) {
			$by_key[ $item['page_template_key'] ] = $item;
		}
		$this->assertTrue( $by_key['service_01']['subtype_influence_applied'] );
		$this->assertSame( 20, $by_key['service_01']['score'] );
		$this->assertSame( Industry_Page_Template_Recommendation_Resolver::FIT_RECOMMENDED, $by_key['service_01']['fit_classification'] );
		$this->assertFalse( $by_key['landing_01']['subtype_influence_applied'] );
		$this->assertSame( 10, $by_key['landing_01']['score'] );
	}

	public function test_resolver_without_subtype_options_unchanged_behavior(): void {
		$resolver = new Industry_Page_Template_Recommendation_Resolver();
		$profile = array( 'primary_industry_key' => 'realtor' );
		$templates = array( array( Page_Template_Schema::FIELD_INTERNAL_KEY => 'service_01' ) );
		$result = $resolver->resolve( $profile, null, $templates, array() );
		$items = $result->get_items();
		$this->assertCount( 1, $items );
		$this->assertArrayNotHasKey( 'subtype_influence_applied', $items[0] );
		$this->assertArrayNotHasKey( 'subtype_reason_summary', $items[0] );
	}

	public function test_resolver_with_subtype_extender_adds_subtype_fields(): void {
		$resolver = new Industry_Page_Template_Recommendation_Resolver();
		$extender = new Industry_Subtype_Page_Template_Recommendation_Extender();
		$profile = array( 'primary_industry_key' => 'realtor' );
		$templates = array( array( Page_Template_Schema::FIELD_INTERNAL_KEY => 'service_01', 'template_family' => 'service' ) );
		$subtype = array( 'page_family_emphasis' => array( 'service' ) );
		$result = $resolver->resolve( $profile, null, $templates, array( 'subtype_definition' => $subtype, 'subtype_extender' => $extender ) );
		$items = $result->get_items();
		$this->assertCount( 1, $items );
		$this->assertArrayHasKey( 'subtype_influence_applied', $items[0] );
		$this->assertArrayHasKey( 'subtype_reason_summary', $items[0] );
	}
}
