<?php
/**
 * Unit tests for industry dummy data generator (industry-preview-dummy-data-contract, Prompt 385).
 *
 * Covers: get_overrides_for_family by industry and purpose_family; unsupported/empty industry returns empty;
 * four industries return realistic placeholders; no persistence (generator is pure).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Preview\Industry_Dummy_Data_Generator;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Preview/Industry_Dummy_Data_Generator.php';

final class Industry_Dummy_Data_Generator_Test extends TestCase {

	private Industry_Dummy_Data_Generator $generator;

	protected function setUp(): void {
		parent::setUp();
		$this->generator = new Industry_Dummy_Data_Generator();
	}

	public function test_unsupported_industry_returns_empty(): void {
		$this->assertSame( array(), $this->generator->get_overrides_for_family( 'hero', 'unknown_industry' ) );
		$this->assertSame( array(), $this->generator->get_overrides_for_family( 'cta', 'healthcare' ) );
	}

	public function test_empty_industry_key_returns_empty(): void {
		$this->assertSame( array(), $this->generator->get_overrides_for_family( 'hero', '' ) );
	}

	public function test_cosmetology_nail_hero_returns_expected_overrides(): void {
		$out = $this->generator->get_overrides_for_family( 'hero', 'cosmetology_nail' );
		$this->assertIsArray( $out );
		$this->assertArrayHasKey( 'headline', $out );
		$this->assertArrayHasKey( 'subheadline', $out );
		$this->assertArrayHasKey( 'cta_text', $out );
		$this->assertArrayHasKey( 'eyebrow', $out );
		$this->assertSame( 'Book now', $out['cta_text'] );
	}

	public function test_realtor_hero_returns_non_empty(): void {
		$out = $this->generator->get_overrides_for_family( 'hero', 'realtor' );
		$this->assertIsArray( $out );
		$this->assertNotEmpty( $out );
		$this->assertArrayHasKey( 'headline', $out );
	}

	public function test_plumber_hero_returns_non_empty(): void {
		$out = $this->generator->get_overrides_for_family( 'hero', 'plumber' );
		$this->assertIsArray( $out );
		$this->assertNotEmpty( $out );
		$this->assertArrayHasKey( 'cta_text', $out );
	}

	public function test_disaster_recovery_hero_returns_non_empty(): void {
		$out = $this->generator->get_overrides_for_family( 'hero', 'disaster_recovery' );
		$this->assertIsArray( $out );
		$this->assertNotEmpty( $out );
		$this->assertArrayHasKey( 'headline', $out );
	}

	public function test_unknown_purpose_family_returns_empty_for_supported_industry(): void {
		$out = $this->generator->get_overrides_for_family( 'unknown_family', 'cosmetology_nail' );
		$this->assertSame( array(), $out );
	}

	public function test_proof_family_returns_items_shape(): void {
		$out = $this->generator->get_overrides_for_family( 'proof', 'cosmetology_nail' );
		$this->assertIsArray( $out );
		$this->assertArrayHasKey( 'items', $out );
		$this->assertIsArray( $out['items'] );
		$this->assertGreaterThanOrEqual( 1, count( $out['items'] ) );
		$this->assertArrayHasKey( 'name', $out['items'][0] );
		$this->assertArrayHasKey( 'quote', $out['items'][0] );
	}

	public function test_generator_is_pure_no_persistence(): void {
		$one = $this->generator->get_overrides_for_family( 'hero', 'realtor' );
		$two = $this->generator->get_overrides_for_family( 'hero', 'realtor' );
		$this->assertSame( $one, $two );
		$this->assertArrayHasKey( 'headline', $one );
	}
}
