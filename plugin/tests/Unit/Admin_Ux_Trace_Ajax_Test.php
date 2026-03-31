<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Admin_Ux_Trace_Ajax;
use AIOPageBuilder\Support\Logging\Admin_Ux_Trace;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * @covers \AIOPageBuilder\Admin\Admin_Ux_Trace_Ajax
 */
final class Admin_Ux_Trace_Ajax_Test extends TestCase {

	public function test_sanitize_client_partial_strips_unknown_and_keeps_core(): void {
		$m = new ReflectionMethod( Admin_Ux_Trace_Ajax::class, 'sanitize_client_partial' );
		$m->setAccessible( true );
		/** @var array<string, mixed>|null $out */
		$out = $m->invoke(
			null,
			array(
				'severity'          => 'flow',
				'facet'             => 'client_interaction',
				'detail'            => 'click:test',
				'tags'              => array( 'section:foo' ),
				'malicious_payload' => array( 'x' => 'y' ),
			)
		);
		$this->assertIsArray( $out );
		$this->assertSame( 'flow', $out['severity'] );
		$this->assertSame( Admin_Ux_Trace::CATEGORY_ADMIN_UX, $out['category'] );
		$this->assertArrayNotHasKey( 'malicious_payload', $out );
	}
}
