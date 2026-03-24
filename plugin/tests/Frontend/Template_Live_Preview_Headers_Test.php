<?php
/**
 * Tests for preview response and header policy wiring.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Frontend;

use AIOPageBuilder\Frontend\Template_Live_Preview_Response_Service;
use AIOPageBuilder\Infrastructure\Http\Header_Policy_Service;
use PHPUnit\Framework\TestCase;

final class Template_Live_Preview_Headers_Test extends TestCase {

	public function test_header_policy_lines(): void {
		$lines = Header_Policy_Service::template_live_preview_security_header_lines();
		$flat  = implode( "\n", $lines );
		$this->assertStringContainsString( 'Referrer-Policy: no-referrer', $flat );
		$this->assertStringContainsString( 'X-Frame-Options: SAMEORIGIN', $flat );
		$this->assertStringContainsString( 'frame-ancestors', $flat );
		$this->assertStringContainsString( 'base-uri', $flat );
	}

	public function test_preview_response_merged_lines(): void {
		$lines = Template_Live_Preview_Response_Service::preview_response_direct_header_lines();
		$flat  = implode( "\n", $lines );
		$this->assertStringContainsString( 'Cache-Control', $flat );
		$this->assertStringContainsString( 'Referrer-Policy', $flat );
	}
}
