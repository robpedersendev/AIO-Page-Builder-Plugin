<?php
/**
 * Tests JSON parsing helper for Industry hub navigation advisor.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit\Domain\AI\Onboarding;

use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Industry_Hub_Navigation_Advisor;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Industry_Hub_Navigation_Advisor::parse_json_object
 */
final class Onboarding_Industry_Hub_Navigation_Advisor_Parse_Test extends TestCase {

	public function test_parse_plain_json(): void {
		$out = Onboarding_Industry_Hub_Navigation_Advisor::parse_json_object( '{"tab":"style","subtab":null}' );
		$this->assertIsArray( $out );
		$this->assertSame( 'style', $out['tab'] );
		$this->assertNull( $out['subtab'] );
	}

	public function test_strips_markdown_fence(): void {
		$raw = "```json\n{\"tab\":\"reports\",\"subtab\":\"drift\"}\n```";
		$out = Onboarding_Industry_Hub_Navigation_Advisor::parse_json_object( $raw );
		$this->assertIsArray( $out );
		$this->assertSame( 'reports', $out['tab'] );
		$this->assertSame( 'drift', $out['subtab'] );
	}
}
