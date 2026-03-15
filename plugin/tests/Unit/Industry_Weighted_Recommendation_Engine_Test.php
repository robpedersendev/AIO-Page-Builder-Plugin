<?php
/**
 * Unit tests for Industry_Weighted_Recommendation_Engine (Prompt 371). Single-industry no conflict; multi-industry conflict.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Weighted_Recommendation_Engine;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Weighted_Recommendation_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Conflict_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Weighted_Recommendation_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Weighted_Recommendation_Engine.php';

final class Industry_Weighted_Recommendation_Engine_Test extends TestCase {

	private function profile_primary_only( string $primary ): array {
		return array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY   => $primary,
			Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => array(),
		);
	}

	private function profile_with_secondary( string $primary, array $secondary ): array {
		return array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY   => $primary,
			Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => $secondary,
		);
	}

	public function test_for_section_item_single_industry_no_conflict(): void {
		$engine = new Industry_Weighted_Recommendation_Engine();
		$profile = $this->profile_primary_only( 'legal' );
		$item = array(
			'section_key'           => 'hero_01',
			'score'                 => 50,
			'explanation_reasons'   => array( 'in_pack_preferred' ),
			'industry_source_refs'  => array( 'legal' ),
			'warning_flags'         => array(),
		);
		$result = $engine->for_section_item( $profile, $item );
		$this->assertSame( 50, $result[ Industry_Weighted_Recommendation_Result::KEY_FINAL_SCORE ] );
		$this->assertSame( array( 'legal' ), $result[ Industry_Weighted_Recommendation_Result::KEY_CONTRIBUTING_INDUSTRIES ] );
		$this->assertEmpty( $result[ Industry_Weighted_Recommendation_Result::KEY_CONFLICT_RESULTS ] );
		$this->assertFalse( $result[ Industry_Weighted_Recommendation_Result::KEY_HAS_WARNING ] );
	}

	public function test_for_section_item_multi_industry_conflict_adds_warning(): void {
		$engine = new Industry_Weighted_Recommendation_Engine();
		$profile = $this->profile_with_secondary( 'legal', array( 'healthcare' ) );
		$item = array(
			'section_key'           => 'hero_01',
			'score'                 => 35,
			'explanation_reasons'   => array( 'in_pack_preferred', 'section_discouraged_secondary' ),
			'industry_source_refs'  => array( 'legal', 'healthcare' ),
			'warning_flags'         => array(),
		);
		$result = $engine->for_section_item( $profile, $item );
		$this->assertSame( 35, $result[ Industry_Weighted_Recommendation_Result::KEY_FINAL_SCORE ] );
		$this->assertCount( 1, $result[ Industry_Weighted_Recommendation_Result::KEY_CONFLICT_RESULTS ] );
		$this->assertTrue( $result[ Industry_Weighted_Recommendation_Result::KEY_HAS_WARNING ] );
	}
}
