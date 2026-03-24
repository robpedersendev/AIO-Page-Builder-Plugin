<?php
/**
 * Tests for preview context / shell selection.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Frontend;

use AIOPageBuilder\Frontend\Preview_Context_Builder;
use AIOPageBuilder\Frontend\Template_Live_Preview_Ticket_Service;
use PHPUnit\Framework\TestCase;

final class Template_Live_Preview_Context_Test extends TestCase {

	public function test_minimal_mode_body_classes(): void {
		$ctx = new Preview_Context_Builder();
		$out = $ctx->build(
			array(
				'shell' => Template_Live_Preview_Ticket_Service::SHELL_MINIMAL,
			)
		);
		$this->assertSame( Template_Live_Preview_Ticket_Service::SHELL_MINIMAL, $out['shell'] );
		$this->assertSame( array( 'aio-template-live-preview' ), $out['body_classes'] );
		$ctx->teardown();
	}
}
