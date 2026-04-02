<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Payload_Json_String_Normalizer;
use PHPUnit\Framework\TestCase;

final class Build_Plan_Payload_Json_String_Normalizer_Test extends TestCase {

	public function test_leaves_plain_text_unchanged(): void {
		$this->assertSame(
			'hero, about, contact',
			Build_Plan_Payload_Json_String_Normalizer::normalize_optional_json_fragment( 'hero, about, contact' )
		);
	}

	public function test_reencodes_json_array_string(): void {
		$raw    = '[{"section_type":"hero","content_focus":"x"}]';
		$normal = Build_Plan_Payload_Json_String_Normalizer::normalize_optional_json_fragment( $raw );
		$this->assertNotSame( '', $normal );
		$decoded_raw    = json_decode( $raw, true );
		$decoded_normal = json_decode( $normal, true );
		$this->assertIsArray( $decoded_normal );
		$this->assertSame( $decoded_raw, $decoded_normal );
		$this->assertSame( 'hero', (string) ( $decoded_normal[0]['section_type'] ?? '' ) );
	}

	public function test_invalid_json_returns_original(): void {
		$bad = '[{not json';
		$this->assertSame( $bad, Build_Plan_Payload_Json_String_Normalizer::normalize_optional_json_fragment( $bad ) );
	}
}
