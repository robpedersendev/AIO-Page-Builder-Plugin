<?php
/**
 * Unit tests for Industry_CTA_Pattern_Registry: valid loading, invalid/duplicate handling,
 * resolve_keys for industry pack refs (industry-cta-pattern-contract; Prompt 325).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_CTA_Pattern_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_CTA_Pattern_Registry.php';

final class Industry_CTA_Pattern_Registry_Test extends TestCase {

	private function valid_pattern( string $key = 'consult', string $name = 'Consult' ): array {
		return array(
			Industry_CTA_Pattern_Registry::FIELD_PATTERN_KEY   => $key,
			Industry_CTA_Pattern_Registry::FIELD_NAME          => $name,
			Industry_CTA_Pattern_Registry::FIELD_DESCRIPTION  => 'Request a consultation.',
		);
	}

	public function test_load_and_get_valid_pattern(): void {
		$registry = new Industry_CTA_Pattern_Registry();
		$registry->load( array( $this->valid_pattern( 'book_now', 'Book now' ) ) );
		$def = $registry->get( 'book_now' );
		$this->assertNotNull( $def );
		$this->assertSame( 'book_now', $def[ Industry_CTA_Pattern_Registry::FIELD_PATTERN_KEY ] );
		$this->assertSame( 'Book now', $def[ Industry_CTA_Pattern_Registry::FIELD_NAME ] );
	}

	public function test_get_returns_null_for_unknown_key(): void {
		$registry = new Industry_CTA_Pattern_Registry();
		$registry->load( array( $this->valid_pattern( 'consult' ) ) );
		$this->assertNull( $registry->get( 'unknown' ) );
		$this->assertNull( $registry->get( '' ) );
	}

	public function test_has_returns_true_for_loaded_false_for_unknown(): void {
		$registry = new Industry_CTA_Pattern_Registry();
		$registry->load( array( $this->valid_pattern( 'call_now' ) ) );
		$this->assertTrue( $registry->has( 'call_now' ) );
		$this->assertFalse( $registry->has( 'unknown' ) );
	}

	public function test_load_skips_invalid_pattern_key(): void {
		$registry = new Industry_CTA_Pattern_Registry();
		$registry->load( array(
			$this->valid_pattern( 'valid_key' ),
			array( Industry_CTA_Pattern_Registry::FIELD_PATTERN_KEY => 'Invalid-Key!', Industry_CTA_Pattern_Registry::FIELD_NAME => 'Bad' ),
		) );
		$this->assertCount( 1, $registry->get_all() );
		$this->assertNotNull( $registry->get( 'valid_key' ) );
		$this->assertNull( $registry->get( 'Invalid-Key!' ) );
	}

	public function test_load_skips_duplicate_key_first_wins(): void {
		$registry = new Industry_CTA_Pattern_Registry();
		$registry->load( array(
			$this->valid_pattern( 'consult', 'First' ),
			$this->valid_pattern( 'consult', 'Second' ),
		) );
		$def = $registry->get( 'consult' );
		$this->assertNotNull( $def );
		$this->assertSame( 'First', $def[ Industry_CTA_Pattern_Registry::FIELD_NAME ] );
		$this->assertCount( 1, $registry->get_all() );
	}

	public function test_resolve_keys_returns_definitions_for_known_keys_skips_unknown(): void {
		$registry = new Industry_CTA_Pattern_Registry();
		$registry->load( array(
			$this->valid_pattern( 'consult' ),
			$this->valid_pattern( 'book_now' ),
		) );
		$resolved = $registry->resolve_keys( array( 'consult', 'unknown', 'book_now' ) );
		$this->assertCount( 2, $resolved );
		$this->assertSame( 'consult', $resolved[0][ Industry_CTA_Pattern_Registry::FIELD_PATTERN_KEY ] );
		$this->assertSame( 'book_now', $resolved[1][ Industry_CTA_Pattern_Registry::FIELD_PATTERN_KEY ] );
	}

	public function test_registry_exposure_is_deterministic(): void {
		$registry = new Industry_CTA_Pattern_Registry();
		$registry->load( array(
			$this->valid_pattern( 'a' ),
			$this->valid_pattern( 'b' ),
			$this->valid_pattern( 'c' ),
		) );
		$all1 = $registry->get_all();
		$all2 = $registry->get_all();
		$this->assertEquals( $all1, $all2 );
		$this->assertSame( $registry->get( 'b' )[ Industry_CTA_Pattern_Registry::FIELD_NAME ], $registry->get( 'b' )[ Industry_CTA_Pattern_Registry::FIELD_NAME ] );
	}

	public function test_load_skips_entry_without_name(): void {
		$registry = new Industry_CTA_Pattern_Registry();
		$registry->load( array(
			array( Industry_CTA_Pattern_Registry::FIELD_PATTERN_KEY => 'no_name', Industry_CTA_Pattern_Registry::FIELD_NAME => '' ),
		) );
		$this->assertNull( $registry->get( 'no_name' ) );
		$this->assertCount( 0, $registry->get_all() );
	}
}
