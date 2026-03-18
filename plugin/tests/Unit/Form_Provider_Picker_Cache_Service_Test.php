<?php
/**
 * Unit tests for Form_Provider_Picker_Cache_Service (Prompt 237): get, set, TTL, fallback, invalidate, clear, eviction.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Integrations\FormProviders\Form_Provider_Picker_Cache_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Integrations/FormProviders/Form_Provider_Picker_Cache_Service.php';

final class Form_Provider_Picker_Cache_Service_Test extends TestCase {

	public function test_set_and_get_returns_stored_entry(): void {
		$cache = new Form_Provider_Picker_Cache_Service( 300 );
		$items = array(
			array(
				'provider_key' => 'ndr_forms',
				'item_id'      => 'f1',
				'item_label'   => 'Contact',
			),
		);
		$cache->set( 'ndr_forms', $items, 'success' );
		$entry = $cache->get( 'ndr_forms' );
		$this->assertNotNull( $entry );
		$this->assertSame( $items, $entry['items'] );
		$this->assertSame( 'success', $entry['outcome'] );
		$this->assertIsInt( $entry['fetched_at'] );
	}

	public function test_get_miss_returns_null(): void {
		$cache = new Form_Provider_Picker_Cache_Service( 300 );
		$this->assertNull( $cache->get( 'unknown' ) );
	}

	public function test_get_fallback_returns_expired_entry(): void {
		$cache = new Form_Provider_Picker_Cache_Service( 1 );
		$items = array(
			array(
				'provider_key' => 'ndr_forms',
				'item_id'      => 'f1',
				'item_label'   => 'Contact',
			),
		);
		$cache->set( 'ndr_forms', $items, 'success' );
		sleep( 2 );
		$fallback = $cache->get_fallback( 'ndr_forms' );
		$this->assertNotNull( $fallback );
		$this->assertSame( $items, $fallback['items'] );
		$this->assertNull( $cache->get( 'ndr_forms' ) );
	}

	public function test_invalidate_removes_entry(): void {
		$cache = new Form_Provider_Picker_Cache_Service( 300 );
		$cache->set( 'ndr_forms', array(), 'empty' );
		$cache->invalidate( 'ndr_forms' );
		$this->assertNull( $cache->get( 'ndr_forms' ) );
		$this->assertNull( $cache->get_fallback( 'ndr_forms' ) );
	}

	public function test_clear_removes_all_entries(): void {
		$cache = new Form_Provider_Picker_Cache_Service( 300 );
		$cache->set( 'p1', array(), 'empty' );
		$cache->set( 'p2', array(), 'empty' );
		$cache->clear();
		$this->assertNull( $cache->get( 'p1' ) );
		$this->assertNull( $cache->get( 'p2' ) );
	}

	public function test_sanitizes_provider_key(): void {
		$cache = new Form_Provider_Picker_Cache_Service( 300 );
		$cache->set(
			'NDR-Forms',
			array(
				array(
					'provider_key' => 'ndr_forms',
					'item_id'      => 'x',
					'item_label'   => 'X',
				),
			),
			'success'
		);
		$entry = $cache->get( 'ndrforms' );
		$this->assertNotNull( $entry );
		$this->assertSame( 'success', $entry['outcome'] );
	}

	public function test_set_normalizes_outcome(): void {
		$cache = new Form_Provider_Picker_Cache_Service( 300 );
		$cache->set( 'p1', array(), 'empty' );
		$this->assertSame( 'empty', $cache->get( 'p1' )['outcome'] );
		$cache->set(
			'p2',
			array(
				array(
					'provider_key' => 'p2',
					'item_id'      => 'a',
					'item_label'   => 'A',
				),
			),
			'success'
		);
		$this->assertSame( 'success', $cache->get( 'p2' )['outcome'] );
		$cache->set( 'p3', array(), 'invalid_outcome' );
		$this->assertSame( 'error', $cache->get( 'p3' )['outcome'] );
	}

	public function test_get_ttl_seconds(): void {
		$this->assertSame( 300, ( new Form_Provider_Picker_Cache_Service( 300 ) )->get_ttl_seconds() );
		$this->assertSame( 300, ( new Form_Provider_Picker_Cache_Service( 0 ) )->get_ttl_seconds() );
	}
}
