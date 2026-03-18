<?php
/**
 * Unit tests for Classification_Result: to_array, is_meaningful, is_duplicate, is_retain (spec §24.12).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Classification\Classification_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Classification/Classification_Result.php';

final class Classification_Result_Test extends TestCase {

	/** Classification matrix: meaningful page (content_weight). */
	public function test_meaningful_result_to_array_and_predicates(): void {
		$flags = array(
			'has_h1'     => true,
			'word_count' => 200,
			'in_nav'     => false,
			'link_count' => 1,
		);
		$r     = new Classification_Result(
			Classification_Result::CLASSIFICATION_MEANINGFUL,
			array( Classification_Result::REASON_CONTENT_WEIGHT ),
			null,
			$flags,
			Classification_Result::RETENTION_RETAIN,
			'abc123'
		);
		$this->assertTrue( $r->is_meaningful() );
		$this->assertFalse( $r->is_duplicate() );
		$this->assertTrue( $r->is_retain() );
		$arr = $r->to_array();
		$this->assertSame( Classification_Result::CLASSIFICATION_MEANINGFUL, $arr['classification'] );
		$this->assertSame( array( Classification_Result::REASON_CONTENT_WEIGHT ), $arr['reason_codes'] );
		$this->assertNull( $arr['duplicate_of'] );
		$this->assertSame( Classification_Result::RETENTION_RETAIN, $arr['retention_decision'] );
		$this->assertSame( 'abc123', $arr['content_hash'] );
	}

	/** Classification matrix: duplicate page. */
	public function test_duplicate_result_is_duplicate_and_exclude(): void {
		$flags = array(
			'has_h1'     => true,
			'word_count' => 100,
			'in_nav'     => false,
			'link_count' => 0,
		);
		$r     = new Classification_Result(
			Classification_Result::CLASSIFICATION_DUPLICATE,
			array( Classification_Result::REASON_DUPLICATE_CANONICAL ),
			'https://example.com/canonical',
			$flags,
			Classification_Result::RETENTION_EXCLUDE,
			'hashdup'
		);
		$this->assertFalse( $r->is_meaningful() );
		$this->assertTrue( $r->is_duplicate() );
		$this->assertFalse( $r->is_retain() );
		$arr = $r->to_array();
		$this->assertSame( 'https://example.com/canonical', $arr['duplicate_of'] );
	}

	/** Classification matrix: low_value (thin) page. */
	public function test_low_value_result_exclude(): void {
		$flags = array(
			'has_h1'     => false,
			'word_count' => 50,
			'in_nav'     => false,
			'link_count' => 0,
		);
		$r     = new Classification_Result(
			Classification_Result::CLASSIFICATION_LOW_VALUE,
			array( Classification_Result::REASON_THIN_CONTENT ),
			null,
			$flags,
			Classification_Result::RETENTION_EXCLUDE,
			null
		);
		$this->assertFalse( $r->is_meaningful() );
		$this->assertFalse( $r->is_duplicate() );
		$this->assertFalse( $r->is_retain() );
		$this->assertSame( Classification_Result::CLASSIFICATION_LOW_VALUE, $r->classification );
	}
}
