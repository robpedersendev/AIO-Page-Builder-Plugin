<?php
/**
 * Unit tests for crawl-to-template-family matching (Prompt 209): hierarchy class hints,
 * section-family summary, rebuild signals, low-confidence and unsupported handling.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Classification\Classification_Result;
use AIOPageBuilder\Domain\Crawler\Classification\Crawl_Template_Family_Matcher;
use AIOPageBuilder\Domain\Crawler\Classification\Crawl_Template_Match_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Classification/Classification_Result.php';
require_once $plugin_root . '/src/Domain/Crawler/Classification/Crawl_Template_Match_Result.php';
require_once $plugin_root . '/src/Domain/Crawler/Classification/Crawl_Template_Family_Matcher.php';

final class Crawl_Template_Family_Matcher_Test extends TestCase {

	private function matcher(): Crawl_Template_Family_Matcher {
		return new Crawl_Template_Family_Matcher();
	}

	private function page_record( array $overrides = array() ): array {
		$base = array(
			'url'                    => 'https://example.com/',
			'title_snapshot'         => 'Home',
			'page_classification'    => Classification_Result::CLASSIFICATION_MEANINGFUL,
			'summary_data'           => json_encode( array(
				'page_summary'    => array( 'word_count' => 300, 'h1' => 'Home', 'title' => 'Home', 'meta_description' => '', 'content_excerpt' => '', 'internal_link_count' => 10 ),
				'heading_outline' => array( array( 'level' => 1, 'text' => 'Home' ), array( 'level' => 2, 'text' => 'Section' ) ),
			) ),
			'hierarchy_clues'        => null,
			'navigation_participation' => 1,
		);
		return array_merge( $base, $overrides );
	}

	/** Top-level: shallow path + top_level slug (about). */
	public function test_suggests_top_level_for_about_page(): void {
		$record = $this->page_record( array(
			'url'            => 'https://example.com/about',
			'title_snapshot' => 'About Us',
		) );
		$result = $this->matcher()->match( $record );
		$hint = $result->get_crawl_template_family_hint();
		$this->assertSame( Crawl_Template_Family_Matcher::PAGE_CLASS_TOP_LEVEL, $hint['suggested_page_class'] );
		$this->assertArrayHasKey( 'suggested_families', $hint );
		$this->assertArrayHasKey( 'confidence', $hint );
	}

	/** Hub: /services with in_nav. */
	public function test_suggests_hub_for_services_page(): void {
		$record = $this->page_record( array(
			'url'            => 'https://example.com/services',
			'title_snapshot' => 'Our Services',
			'navigation_participation' => 1,
		) );
		$result = $this->matcher()->match( $record );
		$hint = $result->get_crawl_template_family_hint();
		$this->assertContains( $hint['suggested_page_class'], array( Crawl_Template_Family_Matcher::PAGE_CLASS_HUB, Crawl_Template_Family_Matcher::PAGE_CLASS_TOP_LEVEL ) );
	}

	/** Nested hub: path depth 2, hub-like segment. */
	public function test_suggests_nested_hub_or_child_for_two_segment_path(): void {
		$record = $this->page_record( array(
			'url'            => 'https://example.com/products/category',
			'title_snapshot' => 'Product Category',
			'navigation_participation' => 0,
		) );
		$result = $this->matcher()->match( $record );
		$hint = $result->get_crawl_template_family_hint();
		$this->assertContains( $hint['suggested_page_class'], array(
			Crawl_Template_Family_Matcher::PAGE_CLASS_NESTED_HUB,
			Crawl_Template_Family_Matcher::PAGE_CLASS_CHILD_DETAIL,
			Crawl_Template_Family_Matcher::PAGE_CLASS_HUB,
		) );
	}

	/** Child/detail: deep path (3+ segments) or product-like slug. */
	public function test_suggests_child_detail_for_deep_path(): void {
		$record = $this->page_record( array(
			'url'            => 'https://example.com/services/cleaning/deep-clean',
			'title_snapshot' => 'Deep Clean',
		) );
		$result = $this->matcher()->match( $record );
		$hint = $result->get_crawl_template_family_hint();
		$this->assertSame( Crawl_Template_Family_Matcher::PAGE_CLASS_CHILD_DETAIL, $hint['suggested_page_class'] );
	}

	/** Unsupported: duplicate classification. */
	public function test_returns_unsupported_for_duplicate_classification(): void {
		$record = $this->page_record( array(
			'page_classification' => Classification_Result::CLASSIFICATION_DUPLICATE,
		) );
		$result = $this->matcher()->match( $record );
		$hint = $result->get_crawl_template_family_hint();
		$this->assertSame( Crawl_Template_Match_Result::CONFIDENCE_UNSUPPORTED, $hint['confidence'] );
		$this->assertTrue( $result->is_low_confidence() );
	}

	/** Unsupported: unsupported classification. */
	public function test_returns_unsupported_for_unsupported_classification(): void {
		$record = $this->page_record( array(
			'page_classification' => Classification_Result::CLASSIFICATION_UNSUPPORTED,
		) );
		$result = $this->matcher()->match( $record );
		$hint = $result->get_crawl_template_family_hint();
		$this->assertSame( Crawl_Template_Match_Result::CONFIDENCE_UNSUPPORTED, $hint['confidence'] );
	}

	/** Low confidence: weak signals (no URL would be unsupported; use few words + no h1). */
	public function test_low_confidence_when_weak_signals(): void {
		$record = $this->page_record( array(
			'url'         => 'https://example.com/other',
			'summary_data' => json_encode( array(
				'page_summary'    => array( 'word_count' => 50, 'h1' => '', 'title' => 'Other', 'meta_description' => '', 'content_excerpt' => '', 'internal_link_count' => 0 ),
				'heading_outline' => array(),
			) ),
			'navigation_participation' => 0,
		) );
		$result = $this->matcher()->match( $record );
		$hint = $result->get_crawl_template_family_hint();
		$this->assertContains( $hint['confidence'], array( Crawl_Template_Match_Result::CONFIDENCE_LOW, Crawl_Template_Match_Result::CONFIDENCE_MEDIUM ) );
		$rebuild = $result->get_page_rebuild_signal_summary();
		$this->assertArrayHasKey( 'weak_structure_signals', $rebuild );
		$this->assertNotEmpty( $rebuild['weak_structure_signals'] );
	}

	/** Section-family match summary is present. */
	public function test_section_family_match_summary_present(): void {
		$record = $this->page_record();
		$result = $this->matcher()->match( $record );
		$section = $result->get_section_family_match_summary();
		$this->assertArrayHasKey( 'matched_section_families', $section );
		$this->assertArrayHasKey( 'confidence', $section );
		$this->assertIsArray( $section['matched_section_families'] );
	}

	/** Page rebuild signal summary is present. */
	public function test_page_rebuild_signal_summary_present(): void {
		$record = $this->page_record();
		$result = $this->matcher()->match( $record );
		$rebuild = $result->get_page_rebuild_signal_summary();
		$this->assertArrayHasKey( 'likely_rebuild', $rebuild );
		$this->assertArrayHasKey( 'mismatch_reasons', $rebuild );
		$this->assertArrayHasKey( 'weak_structure_signals', $rebuild );
	}

	/** Result to_payload and from_hierarchy_clues_json round-trip. */
	public function test_match_result_payload_roundtrip(): void {
		$record = $this->page_record( array( 'url' => 'https://example.com/contact' ) );
		$result = $this->matcher()->match( $record );
		$payload = $result->to_payload();
		$this->assertArrayHasKey( 'crawl_template_family_hint', $payload );
		$this->assertArrayHasKey( 'section_family_match_summary', $payload );
		$this->assertArrayHasKey( 'page_rebuild_signal_summary', $payload );

		$json = $result->to_json();
		$this->assertIsString( $json );
		$this->assertNotEmpty( $json );
		$decoded_check = json_decode( $json, true );
		$this->assertIsArray( $decoded_check );
		$decoded = Crawl_Template_Match_Result::from_hierarchy_clues_json( $json );
		$this->assertNotNull( $decoded );
		$this->assertSame( $payload['crawl_template_family_hint']['suggested_page_class'], $decoded['crawl_template_family_hint']['suggested_page_class'] );
	}

	/** from_hierarchy_clues_json returns null for invalid or empty input. */
	public function test_from_hierarchy_clues_json_returns_null_for_invalid(): void {
		$this->assertNull( Crawl_Template_Match_Result::from_hierarchy_clues_json( null ) );
		$this->assertNull( Crawl_Template_Match_Result::from_hierarchy_clues_json( '' ) );
		$this->assertNull( Crawl_Template_Match_Result::from_hierarchy_clues_json( '{}' ) );
		$this->assertNull( Crawl_Template_Match_Result::from_hierarchy_clues_json( '{"other": true}' ) );
	}
}
