<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

/**
 * Documents why two JSON serializations of the same logical plan can differ in byte length
 * (e.g. \\uXXXX ASCII escapes vs UTF-8 with JSON_UNESCAPED_UNICODE), matching {@see Build_Plan_Repository::save_plan_definition()}.
 */
final class Build_Plan_Definition_Meta_Encoding_Parity_Test extends TestCase {

	public function test_json_unescaped_unicode_produces_fewer_bytes_than_default_escapes_for_non_ascii(): void {
		$utf8_label = str_repeat( 'é', 200 );
		$payload    = array(
			'plan_id' => 'encoding-test',
			'label'   => $utf8_label,
		);
		$escaped    = json_encode( $payload );
		$this->assertIsString( $escaped );
		$opts = JSON_UNESCAPED_UNICODE;
		if ( defined( 'JSON_INVALID_UTF8_SUBSTITUTE' ) ) {
			$opts |= constant( 'JSON_INVALID_UTF8_SUBSTITUTE' );
		}
		$unescaped = json_encode( $payload, $opts );
		$this->assertIsString( $unescaped );
		$this->assertLessThan( strlen( $escaped ), strlen( $unescaped ), 'Default json_encode escapes non-ASCII; UNESCAPED_UNICODE should shrink UTF-8 payload.' );
		$this->assertEquals( json_decode( $escaped, true ), json_decode( $unescaped, true ) );
	}

	public function test_byte_gap_matches_unicode_escape_overhead_for_bmp_characters(): void {
		// * Each 'é' is 2 bytes in UTF-8 but 6 bytes as \u00e9 in default json_encode.
		$n         = 161;
		$payload   = array( 's' => str_repeat( 'é', $n ) );
		$escaped   = json_encode( $payload );
		$unescaped = json_encode( $payload, JSON_UNESCAPED_UNICODE );
		$this->assertIsString( $escaped );
		$this->assertIsString( $unescaped );
		$delta = strlen( $escaped ) - strlen( $unescaped );
		$this->assertSame( 4 * $n, $delta );
		$this->assertSame( 644, $delta );
	}
}
