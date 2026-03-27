<?php
/**
 * Multisite-related preview ticket expectations.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Frontend;

use AIOPageBuilder\Frontend\Template_Live_Preview_Ticket_Service;
use PHPUnit\Framework\TestCase;

final class Template_Live_Preview_Multisite_Test extends TestCase {

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_transients'], $GLOBALS['_aio_is_logged_in'], $GLOBALS['_aio_current_uid'], $GLOBALS['_aio_session_token'], $GLOBALS['_aio_current_blog_id'], $GLOBALS['_aio_is_multisite'] );
		parent::tearDown();
	}

	public function test_ticket_records_current_blog_id(): void {
		$GLOBALS['_aio_is_multisite']    = true;
		$GLOBALS['_aio_current_blog_id'] = 3;
		$GLOBALS['_aio_is_logged_in']    = true;
		$GLOBALS['_aio_current_uid']     = 2;
		$GLOBALS['_aio_session_token']   = 'x';

		$issued = Template_Live_Preview_Ticket_Service::issue(
			Template_Live_Preview_Ticket_Service::TYPE_PAGE,
			'k',
			2,
			600,
			array()
		);
		$this->assertNotSame( '', $issued['ticket'] );
		$r = Template_Live_Preview_Ticket_Service::validate_and_consume( $issued['ticket'] );
		$this->assertTrue( $r['ok'] );
		$this->assertSame( 3, (int) ( $r['record']['blog_id'] ?? 0 ) );
	}
}
