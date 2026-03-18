<?php
/**
 * Unit tests for Industry_Read_Model_Cache_Service and Industry_Cache_Key_Builder (industry-cache-contract; Prompts 434, 435).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Cache\Industry_Cache_Key_Builder;
use AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Industry_Site_Scope_Helper.php';
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Domain/Industry/Cache/Industry_Cache_Key_Builder.php';
require_once $plugin_root . '/src/Domain/Industry/Cache/Industry_Read_Model_Cache_Service.php';

final class Industry_Read_Model_Cache_Service_Test extends TestCase {

	public function test_cache_key_builder_for_section_recommendation_deterministic(): void {
		$builder  = new Industry_Cache_Key_Builder();
		$profile  = array(
			'primary_industry_key'    => 'realtor',
			'secondary_industry_keys' => array(),
			'industry_subtype_key'    => 'realtor_buyer_agent',
		);
		$sections = array( array( 'internal_key' => 'hero_01' ), array( 'internal_key' => 'cta_01' ) );
		$key1     = $builder->for_section_recommendation( $profile, $sections, array() );
		$key2     = $builder->for_section_recommendation( $profile, $sections, array() );
		$this->assertSame( $key1, $key2 );
		$this->assertStringContainsString( Industry_Cache_Key_Builder::SCOPE_SECTION_RECOMMENDATION, $key1 );
	}

	public function test_cache_key_builder_for_helper_doc(): void {
		$builder = new Industry_Cache_Key_Builder();
		$key     = $builder->for_helper_doc( 'hero_01', 'realtor', 'realtor_buyer_agent' );
		$this->assertStringContainsString( Industry_Cache_Key_Builder::SCOPE_HELPER_DOC, $key );
	}

	public function test_cache_key_builder_for_starter_bundle_list(): void {
		$builder = new Industry_Cache_Key_Builder();
		$key     = $builder->for_starter_bundle_list( 'plumber', 'plumber_residential' );
		$this->assertStringContainsString( Industry_Cache_Key_Builder::SCOPE_STARTER_BUNDLE_LIST, $key );
	}

	public function test_cache_service_get_miss_returns_null(): void {
		$service = new Industry_Read_Model_Cache_Service( 60 );
		$key     = 'test_scope_unknown_key_' . uniqid();
		$this->assertNull( $service->get( $key ) );
	}

	public function test_cache_service_set_then_get_returns_value(): void {
		$service = new Industry_Read_Model_Cache_Service( 60 );
		$key     = 'test_scope_setget_' . uniqid();
		$payload = array(
			'items' => array(
				array(
					'section_key' => 'hero_01',
					'score'       => 10,
				),
			),
		);
		$service->set( $key, $payload );
		$got = $service->get( $key );
		$this->assertIsArray( $got );
		$this->assertSame( $payload['items'], $got['items'] ?? null );
		$service->delete( $key );
	}

	public function test_cache_service_invalidate_all_bumps_version(): void {
		$service = new Industry_Read_Model_Cache_Service( 60 );
		$key     = 'test_invalidate_' . uniqid();
		$service->set( $key, array( 'x' => 1 ) );
		$before = $service->get( $key );
		$this->assertNotNull( $before );
		$service->invalidate_all_industry_read_models();
		$after = $service->get( $key );
		$this->assertNull( $after );
	}
}
