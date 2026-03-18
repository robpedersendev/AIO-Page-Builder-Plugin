<?php
/**
 * Unit tests for Industry_Subtype_Section_Recommendation_Extender (Prompt 422).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Recommendation_Result;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Section_Recommendation_Extender;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || exit;

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Section_Recommendation_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Section_Recommendation_Resolver.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Subtype_Section_Recommendation_Extender.php';

/**
 * @covers \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Section_Recommendation_Extender
 */
final class Industry_Subtype_Section_Recommendation_Extender_Test extends TestCase {

	public function test_apply_subtype_influence_null_subtype_returns_result_with_subtype_fields_false(): void {
		$base     = new Industry_Section_Recommendation_Result(
			array(
				array(
					'section_key'          => 'hero_01',
					'score'                => 10,
					'fit_classification'   => Industry_Section_Recommendation_Resolver::FIT_NEUTRAL,
					'explanation_reasons'  => array(),
					'industry_source_refs' => array(),
					'warning_flags'        => array(),
				),
			)
		);
		$extender = new Industry_Subtype_Section_Recommendation_Extender();
		$out      = $extender->apply_subtype_influence( $base, null, array() );
		$items    = $out->get_items();
		$this->assertCount( 1, $items );
		$this->assertFalse( $items[0]['subtype_influence_applied'] );
		$this->assertSame( '', $items[0]['subtype_reason_summary'] );
		$this->assertSame( 10, $items[0]['score'] );
	}

	public function test_apply_subtype_influence_with_helper_overlay_refs_boosts_matching_sections(): void {
		$base     = new Industry_Section_Recommendation_Result(
			array(
				array(
					'section_key'          => 'hero_01',
					'score'                => 10,
					'fit_classification'   => Industry_Section_Recommendation_Resolver::FIT_NEUTRAL,
					'explanation_reasons'  => array(),
					'industry_source_refs' => array(),
					'warning_flags'        => array(),
				),
				array(
					'section_key'          => 'cta_01',
					'score'                => 15,
					'fit_classification'   => Industry_Section_Recommendation_Resolver::FIT_ALLOWED_WEAK,
					'explanation_reasons'  => array(),
					'industry_source_refs' => array(),
					'warning_flags'        => array(),
				),
			)
		);
		$subtype  = array( 'helper_overlay_refs' => array( 'cta_01' ) );
		$extender = new Industry_Subtype_Section_Recommendation_Extender();
		$out      = $extender->apply_subtype_influence( $base, $subtype, array() );
		$items    = $out->get_items();
		$this->assertCount( 2, $items );
		$by_key = array();
		foreach ( $items as $item ) {
			$by_key[ $item['section_key'] ] = $item;
		}
		$this->assertFalse( $by_key['hero_01']['subtype_influence_applied'] );
		$this->assertSame( '', $by_key['hero_01']['subtype_reason_summary'] );
		$this->assertSame( 10, $by_key['hero_01']['score'] );
		$this->assertTrue( $by_key['cta_01']['subtype_influence_applied'] );
		$this->assertSame( 'subtype_overlay_priority', $by_key['cta_01']['subtype_reason_summary'] );
		$this->assertSame( 20, $by_key['cta_01']['score'] );
		$this->assertSame( Industry_Section_Recommendation_Resolver::FIT_RECOMMENDED, $by_key['cta_01']['fit_classification'] );
	}

	public function test_resolver_without_subtype_options_unchanged_behavior(): void {
		$resolver = new Industry_Section_Recommendation_Resolver();
		$profile  = array( 'primary_industry_key' => 'realtor' );
		$sections = array( array( 'internal_key' => 'hero_01' ) );
		$result   = $resolver->resolve( $profile, null, $sections, array() );
		$items    = $result->get_items();
		$this->assertCount( 1, $items );
		$this->assertArrayNotHasKey( 'subtype_influence_applied', $items[0] );
		$this->assertArrayNotHasKey( 'subtype_reason_summary', $items[0] );
	}

	public function test_resolver_with_subtype_extender_adds_subtype_fields(): void {
		$resolver = new Industry_Section_Recommendation_Resolver();
		$extender = new Industry_Subtype_Section_Recommendation_Extender();
		$profile  = array( 'primary_industry_key' => 'realtor' );
		$sections = array( array( 'internal_key' => 'hero_01' ) );
		$subtype  = array( 'helper_overlay_refs' => array( 'hero_01' ) );
		$result   = $resolver->resolve(
			$profile,
			null,
			$sections,
			array(
				'subtype_definition' => $subtype,
				'subtype_extender'   => $extender,
			)
		);
		$items    = $result->get_items();
		$this->assertCount( 1, $items );
		$this->assertArrayHasKey( 'subtype_influence_applied', $items[0] );
		$this->assertArrayHasKey( 'subtype_reason_summary', $items[0] );
	}
}
