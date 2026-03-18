<?php
/**
 * Unit tests for Meaningful_Page_Classifier and classification matrix (spec §24.5, §24.12).
 *
 * Classification matrix (see data provider and cases):
 * - Meaningful: H1 + ≥150 words, or in_navigation, or likely_role, or link_count ≥ 3.
 * - Thin (low_value): no H1 or <150 words and none of the above.
 * - Duplicate: candidate matches known page by canonical or content_hash.
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Classification\Classification_Result;
use AIOPageBuilder\Domain\Crawler\Classification\Duplicate_Detector;
use AIOPageBuilder\Domain\Crawler\Classification\Meaningful_Page_Classifier;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Classification/Classification_Result.php';
require_once $plugin_root . '/src/Domain/Crawler/Classification/Duplicate_Detector.php';
require_once $plugin_root . '/src/Domain/Crawler/Classification/Meaningful_Page_Classifier.php';

final class Meaningful_Page_Classifier_Test extends TestCase {

	private function classifier(): Meaningful_Page_Classifier {
		return new Meaningful_Page_Classifier( new Duplicate_Detector() );
	}

	/** Classification matrix: meaningful page — H1 + ≥150 words. */
	public function test_classify_meaningful_when_h1_and_sufficient_words(): void {
		$html   = '<!DOCTYPE html><html><head><title>About Us</title></head><body><h1>About Us</h1><p>' . str_repeat( 'word ', 160 ) . '</p></body></html>';
		$cl     = $this->classifier();
		$result = $cl->classify( 'https://example.com/about', $html, array(), array() );
		$this->assertSame( Classification_Result::CLASSIFICATION_MEANINGFUL, $result->classification );
		$this->assertTrue( $result->is_meaningful() );
		$this->assertTrue( $result->is_retain() );
		$this->assertContains( Classification_Result::REASON_CONTENT_WEIGHT, $result->reason_codes );
	}

	/** Classification matrix: meaningful — in_navigation. */
	public function test_classify_meaningful_when_in_navigation(): void {
		$html   = '<!DOCTYPE html><html><head><title>Contact</title></head><body><h1>Contact</h1><p>Short text.</p></body></html>';
		$cl     = $this->classifier();
		$result = $cl->classify( 'https://example.com/contact', $html, array( 'in_navigation' => true ), array() );
		$this->assertSame( Classification_Result::CLASSIFICATION_MEANINGFUL, $result->classification );
		$this->assertContains( Classification_Result::REASON_IN_NAVIGATION, $result->reason_codes );
	}

	/** Classification matrix: meaningful — likely_role (URL segment). */
	public function test_classify_meaningful_when_likely_role_in_url(): void {
		$html   = '<!DOCTYPE html><html><head><title>Services</title></head><body><h1>Services</h1><p>Few words.</p></body></html>';
		$cl     = $this->classifier();
		$result = $cl->classify( 'https://example.com/services', $html, array(), array() );
		$this->assertSame( Classification_Result::CLASSIFICATION_MEANINGFUL, $result->classification );
		$this->assertContains( Classification_Result::REASON_LIKELY_ROLE, $result->reason_codes );
	}

	/** Classification matrix: thin (low_value) — no H1, few words. */
	public function test_classify_thin_when_no_h1_and_few_words(): void {
		$html   = '<!DOCTYPE html><html><head><title>Thin</title></head><body><p>Just a few words here.</p></body></html>';
		$cl     = $this->classifier();
		$result = $cl->classify(
			'https://example.com/thin',
			$html,
			array(
				'in_navigation' => false,
				'link_count'    => 0,
			),
			array()
		);
		$this->assertSame( Classification_Result::CLASSIFICATION_LOW_VALUE, $result->classification );
		$this->assertContains( Classification_Result::REASON_THIN_CONTENT, $result->reason_codes );
		$this->assertFalse( $result->is_retain() );
	}

	/** Classification matrix: duplicate — same canonical as known page. */
	public function test_classify_duplicate_when_canonical_matches_known(): void {
		$canon  = 'https://example.com/main';
		$html   = '<!DOCTYPE html><html><head><title>Main</title></head><body><h1>Main</h1><p>' . str_repeat( 'x ', 200 ) . '</p></body></html>';
		$known  = array(
			array(
				'normalized_url' => $canon,
				'canonical_url'  => $canon,
			),
		);
		$cl     = $this->classifier();
		$result = $cl->classify( 'https://example.com/alias', $html, array( 'canonical_url' => $canon ), $known );
		$this->assertSame( Classification_Result::CLASSIFICATION_DUPLICATE, $result->classification );
		$this->assertSame( $canon, $result->duplicate_of );
		$this->assertContains( Classification_Result::REASON_DUPLICATE_CANONICAL, $result->reason_codes );
	}

	/** Empty HTML → unsupported, exclude. */
	public function test_classify_unsupported_when_empty_html(): void {
		$cl     = $this->classifier();
		$result = $cl->classify( 'https://example.com/empty', '', array(), array() );
		$this->assertSame( Classification_Result::CLASSIFICATION_UNSUPPORTED, $result->classification );
		$this->assertContains( Classification_Result::REASON_FETCH_FAILED, $result->reason_codes );
		$this->assertNull( $result->content_hash );
	}

	/** Meaningful via link_count ≥ 3. */
	public function test_classify_meaningful_when_link_count_three_or_more(): void {
		$html   = '<!DOCTYPE html><html><head><title>Page</title></head><body><p>Short.</p></body></html>';
		$cl     = $this->classifier();
		$result = $cl->classify( 'https://example.com/page', $html, array( 'link_count' => 5 ), array() );
		$this->assertSame( Classification_Result::CLASSIFICATION_MEANINGFUL, $result->classification );
		$this->assertContains( Classification_Result::REASON_LINK_WEIGHT, $result->reason_codes );
	}
}
