<?php
/**
 * Tests for Template_Live_Preview_Ticket_Service.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Frontend;

use AIOPageBuilder\Frontend\Template_Live_Preview_Ticket_Service;
use PHPUnit\Framework\TestCase;

final class Template_Live_Preview_Ticket_Service_Test extends TestCase {

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_transients'], $GLOBALS['_aio_is_logged_in'], $GLOBALS['_aio_current_uid'], $GLOBALS['_aio_session_token'], $GLOBALS['_aio_current_blog_id'] );
		parent::tearDown();
	}

	public function test_issue_validate_consume_success_twice_then_exhausted(): void {
		$GLOBALS['_aio_is_logged_in']    = true;
		$GLOBALS['_aio_current_uid']     = 5;
		$GLOBALS['_aio_session_token']   = 'session-alpha';
		$GLOBALS['_aio_current_blog_id'] = 1;

		$issued = Template_Live_Preview_Ticket_Service::issue(
			Template_Live_Preview_Ticket_Service::TYPE_PAGE,
			'my_tpl',
			5,
			600,
			array()
		);
		$this->assertSame( '', $issued['error'] );
		$this->assertNotSame( '', $issued['ticket'] );

		$r1 = Template_Live_Preview_Ticket_Service::validate_and_consume( $issued['ticket'] );
		$this->assertTrue( $r1['ok'] );
		$this->assertSame( 'ok', $r1['code'] );

		$r2 = Template_Live_Preview_Ticket_Service::validate_and_consume( $issued['ticket'] );
		$this->assertTrue( $r2['ok'] );

		$r3 = Template_Live_Preview_Ticket_Service::validate_and_consume( $issued['ticket'] );
		$this->assertFalse( $r3['ok'] );
		$this->assertSame( 'exhausted', $r3['code'] );
	}

	public function test_wrong_user_rejected(): void {
		$GLOBALS['_aio_is_logged_in']    = true;
		$GLOBALS['_aio_current_uid']     = 5;
		$GLOBALS['_aio_session_token']   = 'session-alpha';
		$GLOBALS['_aio_current_blog_id'] = 1;

		$issued                      = Template_Live_Preview_Ticket_Service::issue(
			Template_Live_Preview_Ticket_Service::TYPE_SECTION,
			'sec',
			5,
			600,
			array()
		);
		$GLOBALS['_aio_current_uid'] = 99;
		$r                           = Template_Live_Preview_Ticket_Service::validate_and_consume( $issued['ticket'] );
		$this->assertFalse( $r['ok'] );
		$this->assertSame( 'wrong_user', $r['code'] );
	}

	public function test_wrong_session_rejected(): void {
		$GLOBALS['_aio_is_logged_in']    = true;
		$GLOBALS['_aio_current_uid']     = 5;
		$GLOBALS['_aio_session_token']   = 'session-alpha';
		$GLOBALS['_aio_current_blog_id'] = 1;

		$issued                        = Template_Live_Preview_Ticket_Service::issue(
			Template_Live_Preview_Ticket_Service::TYPE_PAGE,
			'k',
			5,
			600,
			array()
		);
		$GLOBALS['_aio_session_token'] = 'session-beta';
		$r                             = Template_Live_Preview_Ticket_Service::validate_and_consume( $issued['ticket'] );
		$this->assertFalse( $r['ok'] );
		$this->assertSame( 'wrong_session', $r['code'] );
	}

	public function test_wrong_blog_rejected(): void {
		$GLOBALS['_aio_is_logged_in']    = true;
		$GLOBALS['_aio_current_uid']     = 5;
		$GLOBALS['_aio_session_token']   = 'session-alpha';
		$GLOBALS['_aio_current_blog_id'] = 1;

		$issued                          = Template_Live_Preview_Ticket_Service::issue(
			Template_Live_Preview_Ticket_Service::TYPE_PAGE,
			'k',
			5,
			600,
			array()
		);
		$GLOBALS['_aio_current_blog_id'] = 2;
		$r                               = Template_Live_Preview_Ticket_Service::validate_and_consume( $issued['ticket'] );
		$this->assertFalse( $r['ok'] );
		$this->assertSame( 'wrong_blog', $r['code'] );
	}

	public function test_reduced_motion_roundtrip_in_record(): void {
		$GLOBALS['_aio_is_logged_in']    = true;
		$GLOBALS['_aio_current_uid']     = 5;
		$GLOBALS['_aio_session_token']   = 'session-alpha';
		$GLOBALS['_aio_current_blog_id'] = 1;

		$issued = Template_Live_Preview_Ticket_Service::issue(
			Template_Live_Preview_Ticket_Service::TYPE_PAGE,
			'k',
			5,
			600,
			array( 'reduced_motion' => true )
		);
		$r      = Template_Live_Preview_Ticket_Service::validate_and_consume( $issued['ticket'] );
		$this->assertTrue( $r['ok'] );
		$this->assertNotNull( $r['record'] );
		$this->assertTrue( ! empty( $r['record']['rm'] ) );
	}
}
