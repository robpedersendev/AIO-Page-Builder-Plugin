<?php
/**
 * Unit tests for Industry_Page_Template_Recommendation_Resolver and Industry_Page_Template_Recommendation_Result:
 * page-template scoring by industry, hierarchy/LPagery fit, invalid profile, deterministic ordering (Prompt 334).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Result;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Page_Template_Recommendation_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Page_Template_Recommendation_Resolver.php';

final class Industry_Page_Template_Recommendation_Resolver_Test extends TestCase {

	private function template( string $key, array $extra = array() ): array {
		return array_merge( array(
			Page_Template_Schema::FIELD_INTERNAL_KEY => $key,
			Page_Template_Schema::FIELD_NAME          => $key,
		), $extra );
	}

	public function test_page_template_scoring_by_industry_affinity(): void {
		$resolver = new Industry_Page_Template_Recommendation_Resolver();
		$profile  = array( 'primary_industry_key' => 'legal', 'secondary_industry_keys' => array() );
		$pack     = array( Industry_Pack_Schema::FIELD_SUPPORTED_PAGE_FAMILIES => array( 'landing_legal' ) );
		$templates = array(
			$this->template( 'landing_legal', array(
				Page_Template_Schema::FIELD_INDUSTRY_AFFINITY => array( 'legal' ),
				'template_family' => 'landing_legal',
			) ),
			$this->template( 'hub_generic' ),
		);
		$result = $resolver->resolve( $profile, $pack, $templates, array() );
		$this->assertInstanceOf( Industry_Page_Template_Recommendation_Result::class, $result );
		$items = $result->get_items();
		$this->assertCount( 2, $items );
		$by_key = array_column( $items, null, 'page_template_key' );
		$this->assertSame( Industry_Page_Template_Recommendation_Resolver::FIT_RECOMMENDED, $by_key['landing_legal']['fit_classification'] );
		$this->assertGreaterThanOrEqual( 20, $by_key['landing_legal']['score'] );
		$this->assertContains( 'template_affinity_primary', $by_key['landing_legal']['explanation_reasons'] );
		$this->assertContains( 'pack_family_fit', $by_key['landing_legal']['explanation_reasons'] );
		$this->assertSame( Industry_Page_Template_Recommendation_Resolver::FIT_NEUTRAL, $by_key['hub_generic']['fit_classification'] );
	}

	public function test_hierarchy_and_lpagery_fit_signals(): void {
		$resolver = new Industry_Page_Template_Recommendation_Resolver();
		$profile  = array( 'primary_industry_key' => 'realtor', 'secondary_industry_keys' => array() );
		$templates = array(
			$this->template( 'hub_realtor', array(
				Page_Template_Schema::FIELD_INDUSTRY_HIERARCHY_FIT => array( 'realtor' => 'hub' ),
				Page_Template_Schema::FIELD_INDUSTRY_LPAGERY_FIT   => array( 'realtor' => 'listing_friendly' ),
			) ),
		);
		$result = $resolver->resolve( $profile, null, $templates, array() );
		$items  = $result->get_items();
		$this->assertCount( 1, $items );
		$this->assertSame( 'hub', $items[0]['hierarchy_fit'] );
		$this->assertSame( 'listing_friendly', $items[0]['lpagery_fit'] );
		$this->assertContains( 'hierarchy_fit', $items[0]['explanation_reasons'] );
		$this->assertContains( 'lpagery_fit', $items[0]['explanation_reasons'] );
	}

	public function test_discouraged_page_template_scores_discouraged(): void {
		$resolver = new Industry_Page_Template_Recommendation_Resolver();
		$profile  = array( 'primary_industry_key' => 'legal', 'secondary_industry_keys' => array() );
		$templates = array(
			$this->template( 'casino_landing', array( Page_Template_Schema::FIELD_INDUSTRY_DISCOURAGED => array( 'legal' ) ) ),
		);
		$result = $resolver->resolve( $profile, null, $templates, array() );
		$items  = $result->get_items();
		$this->assertCount( 1, $items );
		$this->assertSame( Industry_Page_Template_Recommendation_Resolver::FIT_DISCOURAGED, $items[0]['fit_classification'] );
		$this->assertLessThanOrEqual( -10, $items[0]['score'] );
		$this->assertContains( 'industry_discouraged_primary', $items[0]['explanation_reasons'] );
	}

	public function test_invalid_or_incomplete_profile_yields_neutral(): void {
		$resolver  = new Industry_Page_Template_Recommendation_Resolver();
		$pack      = array( Industry_Pack_Schema::FIELD_SUPPORTED_PAGE_FAMILIES => array( 'legal_landing' ) );
		$templates = array(
			$this->template( 'legal_landing', array( 'template_family' => 'legal_landing' ) ),
		);
		$result_empty = $resolver->resolve( array(), $pack, $templates, array() );
		$result_no_primary = $resolver->resolve( array( 'secondary_industry_keys' => array( 'legal' ) ), $pack, $templates, array() );
		foreach ( array( $result_empty, $result_no_primary ) as $result ) {
			$items = $result->get_items();
			foreach ( $items as $item ) {
				$this->assertSame( Industry_Page_Template_Recommendation_Resolver::FIT_NEUTRAL, $item['fit_classification'] );
				$this->assertSame( 0, $item['score'] );
			}
		}
	}

	public function test_deterministic_recommendation_ordering(): void {
		$resolver = new Industry_Page_Template_Recommendation_Resolver();
		$profile  = array( 'primary_industry_key' => 'legal', 'secondary_industry_keys' => array() );
		$templates = array(
			$this->template( 'z_neutral' ),
			$this->template( 'a_affinity', array( Page_Template_Schema::FIELD_INDUSTRY_AFFINITY => array( 'legal' ) ) ),
			$this->template( 'b_affinity', array( Page_Template_Schema::FIELD_INDUSTRY_AFFINITY => array( 'legal' ) ) ),
		);
		$result = $resolver->resolve( $profile, null, $templates, array() );
		$ranked = $result->get_ranked_keys();
		$this->assertCount( 3, $ranked );
		$scores = array_column( $result->get_items(), 'score', 'page_template_key' );
		$this->assertSame( $scores['a_affinity'], $scores['b_affinity'] );
		$this->assertGreaterThan( $scores['z_neutral'], $scores['a_affinity'] );
		$this->assertSame( 'a_affinity', $ranked[0] );
		$this->assertSame( 'b_affinity', $ranked[1] );
		$this->assertSame( 'z_neutral', $ranked[2] );
	}

	public function test_result_get_ranked_keys_and_to_array(): void {
		$resolver  = new Industry_Page_Template_Recommendation_Resolver();
		$profile   = array( 'primary_industry_key' => 'legal', 'secondary_industry_keys' => array() );
		$templates = array( $this->template( 'one' ), $this->template( 'two' ) );
		$result    = $resolver->resolve( $profile, null, $templates, array() );
		$this->assertIsArray( $result->get_ranked_keys() );
		$this->assertCount( 2, $result->get_ranked_keys() );
		$arr = $result->to_array();
		$this->assertArrayHasKey( 'items', $arr );
		$this->assertCount( 2, $arr['items'] );
	}
}
