<?php
/**
 * Unit tests for Industry_Page_Template_Directory_Read_Model_Builder and Industry_Page_Template_Directory_Item_View:
 * view modes, hierarchy-fit and LPagery-fit metadata, safe fallback for missing industry (Prompt 341).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Directory_Item_View;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Directory_Read_Model_Builder;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Page_Template_Recommendation_Resolver.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Page_Template_Recommendation_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Page_Template_Directory_Item_View.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Page_Template_Directory_Read_Model_Builder.php';

final class Industry_Page_Template_Directory_Read_Model_Builder_Test extends TestCase {

	private function template( string $key, array $extra = array() ): array {
		return array_merge(
			array(
				Page_Template_Schema::FIELD_INTERNAL_KEY => $key,
				Page_Template_Schema::FIELD_NAME         => $key,
			),
			$extra
		);
	}

	public function test_recommended_only_view_returns_only_recommended(): void {
		$profile   = array(
			'primary_industry_key'    => 'legal',
			'secondary_industry_keys' => array(),
		);
		$templates = array(
			$this->template( 'landing_legal', array( Page_Template_Schema::FIELD_INDUSTRY_AFFINITY => array( 'legal' ) ) ),
			$this->template( 'hub_generic' ),
		);
		$builder   = new Industry_Page_Template_Directory_Read_Model_Builder();
		$views     = $builder->build( $profile, null, $templates, Industry_Page_Template_Directory_Read_Model_Builder::VIEW_RECOMMENDED_ONLY );
		foreach ( $views as $view ) {
			$this->assertInstanceOf( Industry_Page_Template_Directory_Item_View::class, $view );
			$this->assertSame( 'recommended', $view->get_recommendation_status() );
		}
	}

	public function test_full_library_view_returns_all_templates(): void {
		$profile   = array(
			'primary_industry_key'    => 'legal',
			'secondary_industry_keys' => array(),
		);
		$templates = array( $this->template( 'a' ), $this->template( 'b' ) );
		$builder   = new Industry_Page_Template_Directory_Read_Model_Builder();
		$views     = $builder->build( $profile, null, $templates, Industry_Page_Template_Directory_Read_Model_Builder::VIEW_FULL_LIBRARY );
		$this->assertCount( 2, $views );
	}

	public function test_hierarchy_fit_and_lpagery_fit_metadata_present(): void {
		$profile   = array(
			'primary_industry_key'    => 'realtor',
			'secondary_industry_keys' => array(),
		);
		$templates = array(
			$this->template(
				'hub_realtor',
				array(
					Page_Template_Schema::FIELD_INDUSTRY_HIERARCHY_FIT => array( 'realtor' => 'hub' ),
					Page_Template_Schema::FIELD_INDUSTRY_LPAGERY_FIT   => array( 'realtor' => 'listing_friendly' ),
				)
			),
		);
		$builder   = new Industry_Page_Template_Directory_Read_Model_Builder();
		$views     = $builder->build( $profile, null, $templates, Industry_Page_Template_Directory_Read_Model_Builder::VIEW_FULL_LIBRARY );
		$this->assertCount( 1, $views );
		$this->assertSame( 'hub', $views[0]->get_hierarchy_fit() );
		$this->assertSame( 'listing_friendly', $views[0]->get_lpagery_fit() );
	}

	public function test_safe_fallback_missing_industry_context(): void {
		$templates = array( $this->template( 'one' ), $this->template( 'two' ) );
		$builder   = new Industry_Page_Template_Directory_Read_Model_Builder();
		$views     = $builder->build( array(), null, $templates, Industry_Page_Template_Directory_Read_Model_Builder::VIEW_FULL_LIBRARY );
		$this->assertCount( 2, $views );
		foreach ( $views as $view ) {
			$this->assertSame( 'neutral', $view->get_recommendation_status() );
		}
	}

	public function test_item_view_to_array_includes_fit_metadata(): void {
		$profile   = array(
			'primary_industry_key'    => 'legal',
			'secondary_industry_keys' => array(),
		);
		$templates = array( $this->template( 'pt_meta' ) );
		$builder   = new Industry_Page_Template_Directory_Read_Model_Builder();
		$views     = $builder->build( $profile, null, $templates, Industry_Page_Template_Directory_Read_Model_Builder::VIEW_FULL_LIBRARY );
		$this->assertCount( 1, $views );
		$arr = $views[0]->to_array();
		$this->assertArrayHasKey( 'hierarchy_fit', $arr );
		$this->assertArrayHasKey( 'lpagery_fit', $arr );
		$this->assertArrayHasKey( 'explanation_snippet', $arr );
	}
}
