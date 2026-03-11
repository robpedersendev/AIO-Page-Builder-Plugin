<?php
/**
 * Unit tests for Extraction_Result: to_array, to_summary_data_json (spec §24.13, §24.14).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Extraction\Extraction_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Extraction/Extraction_Result.php';

final class Extraction_Result_Test extends TestCase {

	public function test_to_array_returns_stable_shape(): void {
		$page_summary = array(
			'title'                => 'About Us',
			'meta_description'     => 'Our company story.',
			'h1'                  => 'About Us',
			'h2_outline'          => array( 'Team', 'History' ),
			'word_count'          => 200,
			'content_excerpt'     => 'First 500 words here...',
			'internal_link_count' => 5,
		);
		$heading_outline = array(
			array( 'level' => 1, 'text' => 'About Us' ),
			array( 'level' => 2, 'text' => 'Team' ),
			array( 'level' => 2, 'text' => 'History' ),
		);
		$navigation_summary = array(
			array( 'context' => 'nav', 'label' => 'Home', 'url' => 'https://example.com/' ),
		);
		$notes = array();
		$r = new Extraction_Result( $page_summary, $heading_outline, $navigation_summary, $notes );
		$arr = $r->to_array();
		$this->assertSame( $page_summary, $arr['page_summary'] );
		$this->assertSame( $heading_outline, $arr['heading_outline'] );
		$this->assertSame( $navigation_summary, $arr['navigation_summary'] );
		$this->assertSame( array(), $arr['extraction_notes'] );
	}

	public function test_to_summary_data_json_is_valid_json(): void {
		$page_summary = array(
			'title'                => 'Page',
			'meta_description'     => '',
			'h1'                  => 'Page',
			'h2_outline'          => array(),
			'word_count'          => 10,
			'content_excerpt'     => 'Short.',
			'internal_link_count' => 0,
		);
		$r = new Extraction_Result( $page_summary, array(), array(), array( Extraction_Result::NOTE_NO_META_DESCRIPTION ) );
		$json = $r->to_summary_data_json();
		$this->assertIsString( $json );
		$decoded = json_decode( $json, true );
		$this->assertIsArray( $decoded );
		$this->assertArrayHasKey( 'extraction_notes', $decoded );
		$this->assertContains( Extraction_Result::NOTE_NO_META_DESCRIPTION, $decoded['extraction_notes'] );
	}

	/**
	 * Example extraction result payload (spec §24.13, §24.14): one meaningful page.
	 */
	public function test_example_extraction_payload_structure(): void {
		$page_summary = array(
			'title'                => 'Services - Example Co',
			'meta_description'     => 'We offer design and development services.',
			'h1'                  => 'Our Services',
			'h2_outline'          => array( 'Design', 'Development', 'Support' ),
			'word_count'          => 320,
			'content_excerpt'     => 'We provide design and development. Our team delivers. Design services include...',
			'internal_link_count' => 8,
		);
		$heading_outline = array(
			array( 'level' => 1, 'text' => 'Our Services' ),
			array( 'level' => 2, 'text' => 'Design' ),
			array( 'level' => 2, 'text' => 'Development' ),
			array( 'level' => 2, 'text' => 'Support' ),
		);
		$navigation_summary = array(
			array( 'context' => 'nav', 'label' => 'Home', 'url' => 'https://example.com/' ),
			array( 'context' => 'nav', 'label' => 'Services', 'url' => 'https://example.com/services' ),
			array( 'context' => 'nav', 'label' => 'Contact', 'url' => 'https://example.com/contact' ),
		);
		$extraction_notes = array();
		$result = new Extraction_Result( $page_summary, $heading_outline, $navigation_summary, $extraction_notes );
		$arr = $result->to_array();
		$this->assertSame( 'Services - Example Co', $arr['page_summary']['title'] );
		$this->assertCount( 3, $arr['navigation_summary'] );
		$this->assertCount( 4, $arr['heading_outline'] );
	}
}
