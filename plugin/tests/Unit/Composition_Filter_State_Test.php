<?php
/**
 * Unit tests for Composition_Filter_State (Prompt 177).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Compositions\UI\Composition_Filter_State;
use AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Shared/Large_Library_Pagination.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Large_Library_Filter_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Large_Library_Query_Service.php';
require_once $plugin_root . '/src/Domain/Registries/Compositions/UI/Composition_Filter_State.php';

final class Composition_Filter_State_Test extends TestCase {

	public function test_to_array_returns_all_filter_keys(): void {
		$state = new Composition_Filter_State( 'hero', 'hero_intro', 'primary_cta', 'hero_compact', 'search', 'active', 2, 50 );
		$a     = $state->to_array();
		$this->assertSame( 'hero', $a['purpose_family'] );
		$this->assertSame( 'hero_intro', $a['category'] );
		$this->assertSame( 'primary_cta', $a['cta_classification'] );
		$this->assertSame( 'hero_compact', $a['variation_family_key'] );
		$this->assertSame( 'search', $a['search'] );
		$this->assertSame( 'active', $a['status'] );
		$this->assertSame( 2, $a['paged'] );
		$this->assertSame( 50, $a['per_page'] );
	}

	public function test_to_query_filters_maps_to_large_library_filter_keys(): void {
		$state   = new Composition_Filter_State( 'cta', '', 'contact_cta', '', '', '', 1, 25 );
		$filters = $state->to_query_filters();
		$this->assertSame( 'cta', $filters[ Large_Library_Query_Service::FILTER_SECTION_PURPOSE_FAMILY ] ?? '' );
		$this->assertSame( 'contact_cta', $filters[ Large_Library_Query_Service::FILTER_CTA_CLASSIFICATION ] ?? '' );
	}

	public function test_paged_and_per_page_bounded(): void {
		$state = new Composition_Filter_State( '', '', '', '', '', '', 0, 200 );
		$this->assertSame( 1, $state->get_paged() );
		$this->assertSame( 100, $state->get_per_page() );
	}
}
