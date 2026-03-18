<?php
/**
 * Unit tests for Industry_Section_Library_Read_Model_Builder and Industry_Section_Library_Item_View:
 * recommended-only and full-library modes, missing industry fallback, explanation metadata (Prompt 340).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Library_Item_View;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Library_Read_Model_Builder;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Section_Recommendation_Resolver.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Section_Recommendation_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Section_Library_Item_View.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Section_Library_Read_Model_Builder.php';

final class Industry_Section_Library_Read_Model_Builder_Test extends TestCase {

	private function section( string $key ): array {
		return array(
			Section_Schema::FIELD_INTERNAL_KEY => $key,
			Section_Schema::FIELD_NAME         => $key,
		);
	}

	public function test_recommended_only_view_returns_only_recommended(): void {
		$profile  = array(
			'primary_industry_key'    => 'legal',
			'secondary_industry_keys' => array(),
		);
		$pack     = array(
			Industry_Pack_Schema::FIELD_PREFERRED_SECTION_KEYS   => array( 'hero_legal' ),
			Industry_Pack_Schema::FIELD_DISCOURAGED_SECTION_KEYS => array(),
		);
		$sections = array( $this->section( 'hero_legal' ), $this->section( 'cta_generic' ), $this->section( 'faq_generic' ) );
		$builder  = new Industry_Section_Library_Read_Model_Builder();
		$views    = $builder->build( $profile, $pack, $sections, Industry_Section_Library_Read_Model_Builder::VIEW_RECOMMENDED_ONLY );
		$this->assertNotEmpty( $views );
		foreach ( $views as $view ) {
			$this->assertInstanceOf( Industry_Section_Library_Item_View::class, $view );
			$this->assertSame( 'recommended', $view->get_recommendation_status() );
		}
		$keys = array_map(
			function ( Industry_Section_Library_Item_View $v ) {
				return $v->get_section_key();
			},
			$views
		);
		$this->assertContains( 'hero_legal', $keys );
	}

	public function test_full_library_view_returns_all_sections(): void {
		$profile  = array(
			'primary_industry_key'    => 'legal',
			'secondary_industry_keys' => array(),
		);
		$sections = array( $this->section( 'a' ), $this->section( 'b' ), $this->section( 'c' ) );
		$builder  = new Industry_Section_Library_Read_Model_Builder();
		$views    = $builder->build( $profile, null, $sections, Industry_Section_Library_Read_Model_Builder::VIEW_FULL_LIBRARY );
		$this->assertCount( 3, $views );
	}

	public function test_missing_invalid_industry_context_fallback_full_library(): void {
		$sections = array( $this->section( 'one' ), $this->section( 'two' ) );
		$builder  = new Industry_Section_Library_Read_Model_Builder();
		$views    = $builder->build( array(), null, $sections, Industry_Section_Library_Read_Model_Builder::VIEW_FULL_LIBRARY );
		$this->assertCount( 2, $views );
		foreach ( $views as $view ) {
			$this->assertSame( 'neutral', $view->get_recommendation_status() );
			$this->assertSame( 0, $view->get_score() );
		}
	}

	public function test_explanation_metadata_present_and_stable(): void {
		$profile  = array(
			'primary_industry_key'    => 'legal',
			'secondary_industry_keys' => array(),
		);
		$pack     = array(
			Industry_Pack_Schema::FIELD_PREFERRED_SECTION_KEYS   => array( 'hero_legal' ),
			Industry_Pack_Schema::FIELD_DISCOURAGED_SECTION_KEYS => array(),
		);
		$sections = array( $this->section( 'hero_legal' ) );
		$builder  = new Industry_Section_Library_Read_Model_Builder();
		$views    = $builder->build( $profile, $pack, $sections, Industry_Section_Library_Read_Model_Builder::VIEW_FULL_LIBRARY );
		$this->assertCount( 1, $views );
		$view = $views[0];
		$this->assertIsArray( $view->get_explanation_reasons() );
		$this->assertIsArray( $view->get_industry_source_refs() );
		$this->assertIsString( $view->get_explanation_snippet() );
		$arr = $view->to_array();
		$this->assertArrayHasKey( 'explanation_reasons', $arr );
		$this->assertArrayHasKey( 'explanation_snippet', $arr );
	}
}
