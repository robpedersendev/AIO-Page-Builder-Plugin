<?php
/**
 * Unit tests for Page_Section_Key_Cache_Service (Prompt 290): hit, miss, invalidation, safe fallback.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Registration\Page_Section_Key_Cache_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ACF/Registration/Page_Section_Key_Cache_Service.php';

final class Page_Section_Key_Cache_Service_Test extends TestCase {

	private Page_Section_Key_Cache_Service $cache;

	protected function setUp(): void {
		parent::setUp();
		$this->cache = new Page_Section_Key_Cache_Service( 60 );
	}

	public function test_get_for_page_miss_returns_null(): void {
		$this->assertNull( $this->cache->get_for_page( 1 ) );
	}

	public function test_set_for_page_and_get_for_page_roundtrip(): void {
		$this->cache->set_for_page( 42, array( 'st_hero', 'st_cta' ) );
		$this->assertSame( array( 'st_hero', 'st_cta' ), $this->cache->get_for_page( 42 ) );
	}

	public function test_invalidate_for_page_clears_entry(): void {
		$this->cache->set_for_page( 10, array( 'st_hero' ) );
		$this->cache->invalidate_for_page( 10 );
		$this->assertNull( $this->cache->get_for_page( 10 ) );
	}

	public function test_get_for_page_zero_returns_null(): void {
		$this->assertNull( $this->cache->get_for_page( 0 ) );
	}

	public function test_set_for_page_zero_does_not_store(): void {
		$this->cache->set_for_page( 0, array( 'st_hero' ) );
		$this->assertNull( $this->cache->get_for_page( 0 ) );
	}

	public function test_get_for_template_miss_returns_null(): void {
		$this->assertNull( $this->cache->get_for_template( 'pt_home' ) );
	}

	public function test_set_for_template_and_get_for_template_roundtrip(): void {
		$this->cache->set_for_template( 'pt_home', array( 'st_hero', 'st_faq' ) );
		$this->assertSame( array( 'st_hero', 'st_faq' ), $this->cache->get_for_template( 'pt_home' ) );
	}

	public function test_invalidate_for_template_clears_entry(): void {
		$this->cache->set_for_template( 'pt_about', array( 'st_hero' ) );
		$this->cache->invalidate_for_template( 'pt_about' );
		$this->assertNull( $this->cache->get_for_template( 'pt_about' ) );
	}

	public function test_get_for_composition_miss_returns_null(): void {
		$this->assertNull( $this->cache->get_for_composition( 'comp_1' ) );
	}

	public function test_set_for_composition_and_get_for_composition_roundtrip(): void {
		$this->cache->set_for_composition( 'comp_1', array( 'st_cta' ) );
		$this->assertSame( array( 'st_cta' ), $this->cache->get_for_composition( 'comp_1' ) );
	}

	public function test_invalidate_for_composition_clears_entry(): void {
		$this->cache->set_for_composition( 'comp_2', array( 'st_hero' ) );
		$this->cache->invalidate_for_composition( 'comp_2' );
		$this->assertNull( $this->cache->get_for_composition( 'comp_2' ) );
	}

	/** Prompt 300: assignment change hook invalidates page cache. */
	public function test_assignment_changed_hook_invalidates_page_cache(): void {
		$this->cache->set_for_page( 99, array( 'st_hero' ) );
		$this->cache->listen_for_assignment_changes();
		do_action( 'aio_acf_assignment_changed', 99 );
		$this->assertNull( $this->cache->get_for_page( 99 ) );
	}

	/** Prompt 300: template definition saved hook invalidates template cache. */
	public function test_template_definition_saved_hook_invalidates_template_cache(): void {
		$this->cache->set_for_template( 'pt_test', array( 'st_hero' ) );
		$this->cache->listen_for_definition_changes();
		do_action( 'aio_page_template_definition_saved', 'pt_test' );
		$this->assertNull( $this->cache->get_for_template( 'pt_test' ) );
	}

	/** Prompt 300: composition definition saved hook invalidates composition cache. */
	public function test_composition_definition_saved_hook_invalidates_composition_cache(): void {
		$this->cache->set_for_composition( 'comp_test', array( 'st_cta' ) );
		$this->cache->listen_for_definition_changes();
		do_action( 'aio_composition_definition_saved', 'comp_test' );
		$this->assertNull( $this->cache->get_for_composition( 'comp_test' ) );
	}
}
