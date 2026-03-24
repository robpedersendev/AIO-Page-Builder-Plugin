<?php
/**
 * HTTP status mapping for ticket validation (controller policy).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Frontend;

use AIOPageBuilder\Frontend\Template_Live_Preview_Controller;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class Template_Live_Preview_Controller_Test extends TestCase {

	public function test_http_status_for_ticket_code(): void {
		$c        = new Template_Live_Preview_Controller( null );
		$ref      = new ReflectionMethod( Template_Live_Preview_Controller::class, 'http_status_for_ticket_code' );
		$ref->setAccessible( true );
		$this->assertSame( 410, $ref->invoke( $c, 'expired' ) );
		$this->assertSame( 410, $ref->invoke( $c, 'exhausted' ) );
		$this->assertSame( 429, $ref->invoke( $c, 'rate_limited_document' ) );
		$this->assertSame( 403, $ref->invoke( $c, 'wrong_session' ) );
	}
}
