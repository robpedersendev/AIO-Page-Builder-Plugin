<?php
/**
 * Unit tests for Navigation_Extractor: nav, header, footer, role=navigation; sparse/malformed HTML (spec §24.12).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Extraction\Navigation_Extractor;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Extraction/Navigation_Extractor.php';

final class Navigation_Extractor_Test extends TestCase {

	public function test_extract_returns_empty_for_empty_html(): void {
		$ext = new Navigation_Extractor();
		$this->assertSame( array(), $ext->extract( '' ) );
	}

	public function test_extract_returns_links_from_nav(): void {
		$html = '<nav><ul><li><a href="/">Home</a></li><li><a href="/about">About</a></li></ul></nav>';
		$ext = new Navigation_Extractor();
		$links = $ext->extract( $html );
		$this->assertCount( 2, $links );
		$this->assertSame( 'nav', $links[0]['context'] );
		$this->assertSame( 'Home', $links[0]['label'] );
		$this->assertSame( '/', $links[0]['url'] );
		$this->assertSame( 'About', $links[1]['label'] );
		$this->assertSame( '/about', $links[1]['url'] );
	}

	public function test_extract_returns_links_from_header_and_footer(): void {
		$html = '<header><a href="/">Site</a></header><main>content</main><footer><a href="/privacy">Privacy</a></footer>';
		$ext = new Navigation_Extractor();
		$links = $ext->extract( $html );
		$this->assertCount( 2, $links );
		$contexts = array_column( $links, 'context' );
		$this->assertContains( 'header', $contexts );
		$this->assertContains( 'footer', $contexts );
	}

	public function test_extract_deduplicates_same_href_and_label(): void {
		$html = '<nav><a href="/">Home</a></nav><footer><a href="/">Home</a></footer>';
		$ext = new Navigation_Extractor();
		$links = $ext->extract( $html );
		$this->assertCount( 1, $links );
	}

	public function test_extract_handles_malformed_fragment(): void {
		$html = '<nav><a href="">Empty</a><a href="/ok">OK</a></nav>';
		$ext = new Navigation_Extractor();
		$links = $ext->extract( $html );
		$this->assertCount( 1, $links );
		$this->assertSame( '/ok', $links[0]['url'] );
	}

	public function test_extract_role_navigation(): void {
		$html = '<div role="navigation"><a href="/menu">Menu</a></div>';
		$ext = new Navigation_Extractor();
		$links = $ext->extract( $html );
		$this->assertCount( 1, $links );
		$this->assertSame( 'nav', $links[0]['context'] );
		$this->assertSame( 'Menu', $links[0]['label'] );
	}
}
