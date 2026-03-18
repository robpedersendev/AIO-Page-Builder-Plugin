<?php
/**
 * Unit tests for crawl profiles: keys, resolution, limits, summary payload (spec §24, §59.7; Prompt 128).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Profiles\Crawl_Profile_Keys;
use AIOPageBuilder\Domain\Crawler\Profiles\Crawl_Profile_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Profiles/Crawl_Profile_Keys.php';
require_once $plugin_root . '/src/Domain/Crawler/Profiles/Crawl_Profile_Service.php';

final class Crawl_Profile_Service_Test extends TestCase {

	public function test_all_approved_keys_are_non_empty(): void {
		$all = Crawl_Profile_Keys::all();
		$this->assertNotEmpty( $all );
		foreach ( $all as $key ) {
			$this->assertIsString( $key );
			$this->assertNotSame( '', trim( $key ) );
		}
	}

	public function test_quick_context_refresh_is_approved(): void {
		$this->assertTrue( Crawl_Profile_Keys::is_approved( Crawl_Profile_Keys::QUICK_CONTEXT_REFRESH ) );
	}

	public function test_full_public_baseline_is_default(): void {
		$this->assertSame( Crawl_Profile_Keys::FULL_PUBLIC_BASELINE, Crawl_Profile_Keys::DEFAULT );
	}

	public function test_resolve_profile_key_returns_approved_unchanged(): void {
		$svc = new Crawl_Profile_Service();
		$this->assertSame( Crawl_Profile_Keys::QUICK_CONTEXT_REFRESH, $svc->resolve_profile_key( Crawl_Profile_Keys::QUICK_CONTEXT_REFRESH ) );
		$this->assertSame( Crawl_Profile_Keys::FULL_PUBLIC_BASELINE, $svc->resolve_profile_key( Crawl_Profile_Keys::FULL_PUBLIC_BASELINE ) );
		$this->assertSame( Crawl_Profile_Keys::SUPPORT_TRIAGE_CRAWL, $svc->resolve_profile_key( Crawl_Profile_Keys::SUPPORT_TRIAGE_CRAWL ) );
	}

	public function test_resolve_profile_key_rejects_unsupported_and_returns_default(): void {
		$svc = new Crawl_Profile_Service();
		$this->assertSame( Crawl_Profile_Keys::DEFAULT, $svc->resolve_profile_key( 'custom_unbounded' ) );
		$this->assertSame( Crawl_Profile_Keys::DEFAULT, $svc->resolve_profile_key( '' ) );
	}

	public function test_get_max_pages_for_profile_quick_context_refresh_tighter_than_contract(): void {
		$svc = new Crawl_Profile_Service();
		$max = $svc->get_max_pages_for_profile( Crawl_Profile_Keys::QUICK_CONTEXT_REFRESH );
		$this->assertLessThanOrEqual( 500, $max );
		$this->assertGreaterThanOrEqual( 1, $max );
		$this->assertSame( 50, $max );
	}

	public function test_get_max_depth_for_profile_quick_context_refresh_tighter_than_contract(): void {
		$svc = new Crawl_Profile_Service();
		$max = $svc->get_max_depth_for_profile( Crawl_Profile_Keys::QUICK_CONTEXT_REFRESH );
		$this->assertLessThanOrEqual( 4, $max );
		$this->assertGreaterThanOrEqual( 1, $max );
		$this->assertSame( 2, $max );
	}

	public function test_full_public_baseline_uses_contract_ceiling(): void {
		$svc = new Crawl_Profile_Service();
		$this->assertSame( 500, $svc->get_max_pages_for_profile( Crawl_Profile_Keys::FULL_PUBLIC_BASELINE ) );
		$this->assertSame( 4, $svc->get_max_depth_for_profile( Crawl_Profile_Keys::FULL_PUBLIC_BASELINE ) );
	}

	public function test_unsupported_key_gets_contract_limits(): void {
		$svc = new Crawl_Profile_Service();
		$this->assertSame( 500, $svc->get_max_pages_for_profile( 'invalid' ) );
		$this->assertSame( 4, $svc->get_max_depth_for_profile( 'invalid' ) );
	}

	public function test_list_profiles_for_selection_returns_key_and_label(): void {
		$svc  = new Crawl_Profile_Service();
		$list = $svc->list_profiles_for_selection();
		$this->assertIsArray( $list );
		$this->assertCount( count( Crawl_Profile_Keys::all() ), $list );
		foreach ( $list as $item ) {
			$this->assertArrayHasKey( 'key', $item );
			$this->assertArrayHasKey( 'label', $item );
			$this->assertTrue( Crawl_Profile_Keys::is_approved( $item['key'] ) );
		}
	}

	public function test_get_profile_payload_structure(): void {
		$svc     = new Crawl_Profile_Service();
		$payload = $svc->get_profile_payload( Crawl_Profile_Keys::QUICK_CONTEXT_REFRESH );
		$this->assertArrayHasKey( 'key', $payload );
		$this->assertArrayHasKey( 'label', $payload );
		$this->assertArrayHasKey( 'description', $payload );
		$this->assertArrayHasKey( 'max_pages', $payload );
		$this->assertArrayHasKey( 'max_depth', $payload );
		$this->assertSame( Crawl_Profile_Keys::QUICK_CONTEXT_REFRESH, $payload['key'] );
		$this->assertSame( 50, $payload['max_pages'] );
		$this->assertSame( 2, $payload['max_depth'] );
	}

	/**
	 * Example crawl_profile_summary payload (spec §24, §59.7; Prompt 128). No pseudocode.
	 */
	public function test_get_profile_summary_payload_example(): void {
		$svc     = new Crawl_Profile_Service();
		$summary = $svc->get_profile_summary();
		$this->assertArrayHasKey( 'profiles', $summary );
		$this->assertArrayHasKey( 'contract_max_pages', $summary );
		$this->assertArrayHasKey( 'contract_max_depth', $summary );
		$this->assertSame( 500, $summary['contract_max_pages'] );
		$this->assertSame( 4, $summary['contract_max_depth'] );
		$this->assertIsArray( $summary['profiles'] );
		$this->assertCount( 3, $summary['profiles'] );

		$example_crawl_profile_summary = array(
			'profiles'           => array(
				array(
					'key'         => 'quick_context_refresh',
					'label'       => 'Quick context refresh',
					'description' => 'Fewer pages and depth for fast site-context updates.',
					'max_pages'   => 50,
					'max_depth'   => 2,
				),
				array(
					'key'         => 'full_public_baseline',
					'label'       => 'Full public-site baseline',
					'description' => 'Spec default: up to 500 pages, depth 4.',
					'max_pages'   => 500,
					'max_depth'   => 4,
				),
				array(
					'key'         => 'support_triage_crawl',
					'label'       => 'Support triage crawl',
					'description' => 'Moderate bounds for support and diagnostics use.',
					'max_pages'   => 100,
					'max_depth'   => 3,
				),
			),
			'contract_max_pages' => 500,
			'contract_max_depth' => 4,
		);
		$this->assertEquals( $example_crawl_profile_summary, $summary );
	}
}
