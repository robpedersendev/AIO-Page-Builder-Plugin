<?php
/**
 * Cross-cutting live preview integration checks (headers + ticket flow).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Frontend;

use AIOPageBuilder\Frontend\Template_Live_Preview_Response_Service;
use AIOPageBuilder\Frontend\Template_Live_Preview_Ticket_Service;
use PHPUnit\Framework\TestCase;

final class Template_Live_Preview_Integration_Test extends TestCase {

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_transients'], $GLOBALS['_aio_http_headers'], $GLOBALS['_aio_is_logged_in'], $GLOBALS['_aio_current_uid'], $GLOBALS['_aio_session_token'], $GLOBALS['_aio_current_blog_id'] );
		parent::tearDown();
	}

	public function test_successful_ticket_has_cache_and_csp_headers_on_response_service(): void {
		$lines = Template_Live_Preview_Response_Service::preview_response_direct_header_lines();
		$flat  = implode( "\n", $lines );
		$this->assertStringContainsString( 'no-store', $flat );
		$this->assertStringContainsString( 'Content-Security-Policy', $flat );
	}

	public function test_issue_failure_on_rate_limit_burst(): void {
		$GLOBALS['_aio_is_logged_in']    = true;
		$GLOBALS['_aio_current_uid']     = 7;
		$GLOBALS['_aio_session_token']   = 's';
		$GLOBALS['_aio_current_blog_id'] = 1;

		for ( $i = 0; $i < 5; $i++ ) {
			$out = Template_Live_Preview_Ticket_Service::issue(
				Template_Live_Preview_Ticket_Service::TYPE_PAGE,
				'same_key',
				7,
				600,
				array()
			);
			$this->assertSame( '', $out['error'] );
		}
		$sixth = Template_Live_Preview_Ticket_Service::issue(
			Template_Live_Preview_Ticket_Service::TYPE_PAGE,
			'same_key',
			7,
			600,
			array()
		);
		$this->assertSame( 'rate_limited_issue', $sixth['error'] );
	}
}
