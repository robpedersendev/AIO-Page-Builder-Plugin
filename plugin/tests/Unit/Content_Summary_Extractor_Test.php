<?php
/**
 * Unit tests for Content_Summary_Extractor: title, meta, H1, H2 outline, excerpt, notes (spec §24.13).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Extraction\Content_Summary_Extractor;
use AIOPageBuilder\Domain\Crawler\Extraction\Extraction_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Extraction/Extraction_Result.php';
require_once $plugin_root . '/src/Domain/Crawler/Extraction/Content_Summary_Extractor.php';

final class Content_Summary_Extractor_Test extends TestCase {

	public function test_extract_returns_page_summary_and_heading_outline(): void {
		$html  = '<!DOCTYPE html><html><head><title>Test Page</title><meta name="description" content="A test."></head>';
		$html .= '<body><h1>Test Page</h1><h2>Section A</h2><p>Some text here.</p><h2>Section B</h2><p>More text.</p></body></html>';
		$ext   = new Content_Summary_Extractor();
		$out   = $ext->extract( $html, 'example.com' );
		$this->assertSame( 'Test Page', $out['page_summary']['title'] );
		$this->assertSame( 'A test.', $out['page_summary']['meta_description'] );
		$this->assertSame( 'Test Page', $out['page_summary']['h1'] );
		$this->assertSame( array( 'Section A', 'Section B' ), $out['page_summary']['h2_outline'] );
		$this->assertGreaterThanOrEqual( 4, $out['page_summary']['word_count'] );
		$this->assertCount( 3, $out['heading_outline'] );
		$this->assertSame( 1, $out['heading_outline'][0]['level'] );
		$this->assertSame( 'Test Page', $out['heading_outline'][0]['text'] );
	}

	public function test_extract_adds_notes_when_title_and_h1_missing(): void {
		$html = '<!DOCTYPE html><html><body><p>No title or h1.</p></body></html>';
		$ext  = new Content_Summary_Extractor();
		$out  = $ext->extract( $html, null );
		$this->assertContains( Extraction_Result::NOTE_NO_TITLE, $out['extraction_notes'] );
		$this->assertContains( Extraction_Result::NOTE_NO_H1, $out['extraction_notes'] );
		$this->assertContains( Extraction_Result::NOTE_NO_META_DESCRIPTION, $out['extraction_notes'] );
	}

	public function test_extract_meta_description_content_first(): void {
		$html = '<html><head><meta name="description" content="Meta here"></head><body></body></html>';
		$ext  = new Content_Summary_Extractor();
		$out  = $ext->extract( $html, null );
		$this->assertSame( 'Meta here', $out['page_summary']['meta_description'] );
	}

	public function test_extract_caps_h2_outline_at_10(): void {
		$parts = array( '<html><body><h1>Page</h1>' );
		for ( $i = 0; $i < 12; $i++ ) {
			$parts[] = '<h2>H2 ' . $i . '</h2>';
		}
		$parts[] = '</body></html>';
		$html    = implode( '', $parts );
		$ext     = new Content_Summary_Extractor();
		$out     = $ext->extract( $html, null );
		$this->assertCount( 10, $out['page_summary']['h2_outline'] );
	}

	public function test_extract_long_content_adds_excerpt_truncated_note(): void {
		$html = '<html><head><title>Long</title></head><body><h1>Long</h1><p>' . str_repeat( 'word ', 600 ) . '</p></body></html>';
		$ext  = new Content_Summary_Extractor();
		$out  = $ext->extract( $html, null );
		$this->assertContains( Extraction_Result::NOTE_EXCERPT_TRUNCATED, $out['extraction_notes'] );
		$words = str_word_count( $out['page_summary']['content_excerpt'] );
		$this->assertLessThanOrEqual( 500, $words );
	}

	public function test_extract_internal_link_count_with_base_host(): void {
		$html = '<html><body><a href="/">Home</a><a href="https://example.com/page">Page</a><a href="https://other.com">Out</a></body></html>';
		$ext  = new Content_Summary_Extractor();
		$out  = $ext->extract( $html, 'example.com' );
		$this->assertSame( 2, $out['page_summary']['internal_link_count'] );
	}

	public function test_extract_sparse_html_returns_bounded_shape(): void {
		$html = '<p>Only one paragraph.</p>';
		$ext  = new Content_Summary_Extractor();
		$out  = $ext->extract( $html, null );
		$this->assertArrayHasKey( 'title', $out['page_summary'] );
		$this->assertArrayHasKey( 'word_count', $out['page_summary'] );
		$this->assertArrayHasKey( 'content_excerpt', $out['page_summary'] );
		$this->assertSame( array(), $out['heading_outline'] );
	}
}
