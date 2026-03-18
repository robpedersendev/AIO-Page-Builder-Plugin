<?php
/**
 * Unit tests for Duplicate_Detector: canonical match, content_hash match, redirect, no match, deterministic hash (spec §24.13).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Classification\Classification_Result;
use AIOPageBuilder\Domain\Crawler\Classification\Duplicate_Detector;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Classification/Classification_Result.php';
require_once $plugin_root . '/src/Domain/Crawler/Classification/Duplicate_Detector.php';

final class Duplicate_Detector_Test extends TestCase {

	public function test_find_duplicate_returns_null_when_empty_known_pages(): void {
		$detector  = new Duplicate_Detector();
		$candidate = array(
			'normalized_url' => 'https://example.com/page',
			'canonical_url'  => 'https://example.com/page',
		);
		$this->assertNull( $detector->find_duplicate( $candidate, array() ) );
	}

	public function test_find_duplicate_returns_null_when_no_match(): void {
		$detector  = new Duplicate_Detector();
		$candidate = array(
			'normalized_url' => 'https://example.com/other',
			'canonical_url'  => 'https://example.com/other',
			'content_hash'   => 'aaa',
		);
		$known     = array(
			array(
				'normalized_url' => 'https://example.com/one',
				'canonical_url'  => 'https://example.com/one',
				'content_hash'   => 'bbb',
			),
		);
		$this->assertNull( $detector->find_duplicate( $candidate, $known ) );
	}

	/** Duplicate by same canonical URL. */
	public function test_find_duplicate_returns_canonical_match(): void {
		$detector  = new Duplicate_Detector();
		$canon     = 'https://example.com/canonical-page';
		$candidate = array(
			'normalized_url' => 'https://example.com/dupe-variant',
			'canonical_url'  => $canon,
		);
		$known     = array(
			array(
				'normalized_url' => 'https://example.com/canonical-page',
				'canonical_url'  => $canon,
			),
		);
		$result    = $detector->find_duplicate( $candidate, $known );
		$this->assertNotNull( $result );
		$this->assertSame( 'https://example.com/canonical-page', $result['duplicate_of'] );
		$this->assertSame( Classification_Result::REASON_DUPLICATE_CANONICAL, $result['reason'] );
	}

	/** Duplicate by content_hash (and title/h1 match). */
	public function test_find_duplicate_returns_content_hash_match(): void {
		$hash      = Duplicate_Detector::content_hash( 'Same Title', 'Same H1', 'Same body excerpt here.' );
		$detector  = new Duplicate_Detector();
		$candidate = array(
			'normalized_url' => 'https://example.com/copy',
			'canonical_url'  => 'https://example.com/copy',
			'title'          => 'Same Title',
			'h1'             => 'Same H1',
			'content_hash'   => $hash,
		);
		$known     = array(
			array(
				'normalized_url' => 'https://example.com/original',
				'canonical_url'  => 'https://example.com/original',
				'title'          => 'Same Title',
				'h1'             => 'Same H1',
				'content_hash'   => $hash,
			),
		);
		$result    = $detector->find_duplicate( $candidate, $known );
		$this->assertNotNull( $result );
		$this->assertSame( 'https://example.com/original', $result['duplicate_of'] );
		$this->assertSame( Classification_Result::REASON_DUPLICATE_CONTENT_HASH, $result['reason'] );
	}

	/** Redirect: candidate final_url equals known normalized_url. */
	public function test_find_duplicate_returns_redirect_match(): void {
		$detector  = new Duplicate_Detector();
		$target    = 'https://example.com/final';
		$candidate = array(
			'normalized_url' => 'https://example.com/redirecting',
			'final_url'      => $target,
		);
		$known     = array( array( 'normalized_url' => $target ) );
		$result    = $detector->find_duplicate( $candidate, $known );
		$this->assertNotNull( $result );
		$this->assertSame( $target, $result['duplicate_of'] );
		$this->assertSame( Classification_Result::REASON_DUPLICATE_REDIRECT, $result['reason'] );
	}

	public function test_content_hash_is_deterministic(): void {
		$h1 = Duplicate_Detector::content_hash( 'Title', 'H1', 'Body text.', 2000 );
		$h2 = Duplicate_Detector::content_hash( 'Title', 'H1', 'Body text.', 2000 );
		$this->assertSame( $h1, $h2 );
		$h3 = Duplicate_Detector::content_hash( 'Title', 'H1', 'Different body', 2000 );
		$this->assertNotSame( $h1, $h3 );
	}
}
