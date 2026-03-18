<?php
/**
 * Unit tests for Industry_Section_Recommendation_Resolver and Industry_Section_Recommendation_Result:
 * high-affinity (recommended), discouraged, neutral, multi-industry weighting, invalid profile (neutral), deterministic ordering (Prompt 333).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Recommendation_Result;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Section_Recommendation_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Section_Recommendation_Resolver.php';

final class Industry_Section_Recommendation_Resolver_Test extends TestCase {

	private function section( string $key, array $extra = array() ): array {
		return array_merge(
			array(
				Section_Schema::FIELD_INTERNAL_KEY => $key,
				Section_Schema::FIELD_NAME         => $key,
			),
			$extra
		);
	}

	public function test_high_affinity_section_scores_recommended(): void {
		$resolver = new Industry_Section_Recommendation_Resolver();
		$profile  = array(
			'primary_industry_key'    => 'legal',
			'secondary_industry_keys' => array(),
		);
		$pack     = array(
			Industry_Pack_Schema::FIELD_PREFERRED_SECTION_KEYS => array( 'hero_legal' ),
			Industry_Pack_Schema::FIELD_DISCOURAGED_SECTION_KEYS => array(),
		);
		$sections = array(
			$this->section( 'hero_legal' ),
			$this->section( 'hero_generic', array( Section_Schema::FIELD_INDUSTRY_AFFINITY => array( 'legal' ) ) ),
			$this->section( 'cta_contact' ),
		);
		$result   = $resolver->resolve( $profile, $pack, $sections, array() );
		$this->assertInstanceOf( Industry_Section_Recommendation_Result::class, $result );
		$items = $result->get_items();
		$this->assertCount( 3, $items );
		$by_key = array_column( $items, null, 'section_key' );
		$this->assertSame( Industry_Section_Recommendation_Resolver::FIT_RECOMMENDED, $by_key['hero_legal']['fit_classification'] );
		$this->assertGreaterThanOrEqual( 20, $by_key['hero_legal']['score'] );
		$this->assertContains( 'in_pack_preferred', $by_key['hero_legal']['explanation_reasons'] );
		$this->assertSame( Industry_Section_Recommendation_Resolver::FIT_RECOMMENDED, $by_key['hero_generic']['fit_classification'] );
		$this->assertContains( 'section_affinity_primary', $by_key['hero_generic']['explanation_reasons'] );
		$this->assertSame( Industry_Section_Recommendation_Resolver::FIT_NEUTRAL, $by_key['cta_contact']['fit_classification'] );
		$this->assertSame( 0, $by_key['cta_contact']['score'] );
	}

	public function test_discouraged_section_scores_discouraged(): void {
		$resolver = new Industry_Section_Recommendation_Resolver();
		$profile  = array(
			'primary_industry_key'    => 'legal',
			'secondary_industry_keys' => array(),
		);
		$pack     = array(
			Industry_Pack_Schema::FIELD_PREFERRED_SECTION_KEYS   => array(),
			Industry_Pack_Schema::FIELD_DISCOURAGED_SECTION_KEYS => array( 'testimonial_casino' ),
		);
		$sections = array(
			$this->section( 'testimonial_casino' ),
			$this->section( 'faq_legal', array( Section_Schema::FIELD_INDUSTRY_DISCOURAGED => array( 'legal' ) ) ),
		);
		$result   = $resolver->resolve( $profile, $pack, $sections, array() );
		$items    = $result->get_items();
		$by_key   = array_column( $items, null, 'section_key' );
		$this->assertSame( Industry_Section_Recommendation_Resolver::FIT_DISCOURAGED, $by_key['testimonial_casino']['fit_classification'] );
		$this->assertLessThanOrEqual( -10, $by_key['testimonial_casino']['score'] );
		$this->assertContains( 'in_pack_discouraged', $by_key['testimonial_casino']['explanation_reasons'] );
		$this->assertSame( Industry_Section_Recommendation_Resolver::FIT_DISCOURAGED, $by_key['faq_legal']['fit_classification'] );
		$this->assertContains( 'section_discouraged_primary', $by_key['faq_legal']['explanation_reasons'] );
	}

	public function test_neutral_section_with_no_metadata(): void {
		$resolver = new Industry_Section_Recommendation_Resolver();
		$profile  = array(
			'primary_industry_key'    => 'realtor',
			'secondary_industry_keys' => array(),
		);
		$pack     = null;
		$sections = array( $this->section( 'hero_generic' ) );
		$result   = $resolver->resolve( $profile, $pack, $sections, array() );
		$items    = $result->get_items();
		$this->assertCount( 1, $items );
		$this->assertSame( 'hero_generic', $items[0]['section_key'] );
		$this->assertSame( 0, $items[0]['score'] );
		$this->assertSame( Industry_Section_Recommendation_Resolver::FIT_NEUTRAL, $items[0]['fit_classification'] );
		$this->assertSame( array(), $items[0]['explanation_reasons'] );
	}

	public function test_multi_industry_weighting_secondary_adds_less(): void {
		$resolver = new Industry_Section_Recommendation_Resolver();
		$profile  = array(
			'primary_industry_key'    => 'legal',
			'secondary_industry_keys' => array( 'healthcare' ),
		);
		$pack     = array(
			Industry_Pack_Schema::FIELD_PREFERRED_SECTION_KEYS   => array(),
			Industry_Pack_Schema::FIELD_DISCOURAGED_SECTION_KEYS => array(),
		);
		$sections = array(
			$this->section( 'hero_legal', array( Section_Schema::FIELD_INDUSTRY_AFFINITY => array( 'legal' ) ) ),
			$this->section( 'hero_healthcare', array( Section_Schema::FIELD_INDUSTRY_AFFINITY => array( 'healthcare' ) ) ),
		);
		$result   = $resolver->resolve( $profile, $pack, $sections, array() );
		$items    = $result->get_items();
		$by_key   = array_column( $items, null, 'section_key' );
		$this->assertGreaterThan( $by_key['hero_healthcare']['score'], $by_key['hero_legal']['score'] );
		$this->assertContains( 'section_affinity_primary', $by_key['hero_legal']['explanation_reasons'] );
		$this->assertContains( 'section_affinity_secondary', $by_key['hero_healthcare']['explanation_reasons'] );
	}

	public function test_invalid_or_missing_profile_yields_neutral_ranking(): void {
		$resolver          = new Industry_Section_Recommendation_Resolver();
		$pack              = array(
			Industry_Pack_Schema::FIELD_PREFERRED_SECTION_KEYS   => array( 'hero_legal' ),
			Industry_Pack_Schema::FIELD_DISCOURAGED_SECTION_KEYS => array(),
		);
		$sections          = array( $this->section( 'hero_legal' ), $this->section( 'cta_contact' ) );
		$result_empty      = $resolver->resolve( array(), $pack, $sections, array() );
		$result_no_primary = $resolver->resolve( array( 'secondary_industry_keys' => array( 'legal' ) ), $pack, $sections, array() );
		foreach ( array( $result_empty, $result_no_primary ) as $result ) {
			$items = $result->get_items();
			foreach ( $items as $item ) {
				$this->assertSame( Industry_Section_Recommendation_Resolver::FIT_NEUTRAL, $item['fit_classification'] );
				$this->assertSame( 0, $item['score'] );
			}
		}
	}

	public function test_deterministic_ordering_by_score_then_section_key(): void {
		$resolver = new Industry_Section_Recommendation_Resolver();
		$profile  = array(
			'primary_industry_key'    => 'legal',
			'secondary_industry_keys' => array(),
		);
		$pack     = array(
			Industry_Pack_Schema::FIELD_PREFERRED_SECTION_KEYS   => array( 'b_section', 'a_section' ),
			Industry_Pack_Schema::FIELD_DISCOURAGED_SECTION_KEYS => array(),
		);
		$sections = array(
			$this->section( 'a_section' ),
			$this->section( 'b_section' ),
			$this->section( 'z_neutral' ),
		);
		$result   = $resolver->resolve( $profile, $pack, $sections, array() );
		$ranked   = $result->get_ranked_keys();
		$this->assertCount( 3, $ranked );
		$scores = array_column( $result->get_items(), 'score', 'section_key' );
		$this->assertSame( $scores['a_section'], $scores['b_section'] );
		$this->assertGreaterThan( $scores['z_neutral'], $scores['a_section'] );
		$this->assertSame( 'a_section', $ranked[0] );
		$this->assertSame( 'b_section', $ranked[1] );
		$this->assertSame( 'z_neutral', $ranked[2] );
	}

	public function test_result_get_ranked_keys_and_to_array(): void {
		$resolver = new Industry_Section_Recommendation_Resolver();
		$profile  = array(
			'primary_industry_key'    => 'legal',
			'secondary_industry_keys' => array(),
		);
		$sections = array( $this->section( 'one' ), $this->section( 'two' ) );
		$result   = $resolver->resolve( $profile, null, $sections, array() );
		$this->assertIsArray( $result->get_ranked_keys() );
		$this->assertCount( 2, $result->get_ranked_keys() );
		$arr = $result->to_array();
		$this->assertArrayHasKey( 'items', $arr );
		$this->assertCount( 2, $arr['items'] );
	}
}
