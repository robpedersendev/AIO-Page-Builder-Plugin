<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Support\Logging\Admin_Ux_Trace;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AIOPageBuilder\Support\Logging\Admin_Ux_Trace
 */
final class Admin_Ux_Trace_Test extends TestCase {

	public function test_compose_record_includes_required_fields(): void {
		$r = Admin_Ux_Trace::compose_record(
			array(
				'severity' => 'flow',
				'category' => Admin_Ux_Trace::CATEGORY_ADMIN_UX,
				'facet'    => 'navigation',
				'hub'      => 'aio-page-builder-build-plans',
				'tab'      => 'build_plans',
			)
		);
		$this->assertSame( '1', $r['schema_version'] );
		$this->assertArrayHasKey( 'ts_utc', $r );
		$this->assertArrayHasKey( 'sequence', $r );
		$this->assertSame( 'flow', $r['severity'] );
		$this->assertSame( 'navigation', $r['facet'] );
		$this->assertArrayHasKey( 'query_snapshot', $r );
		$this->assertIsArray( $r['query_snapshot'] );
		$this->assertArrayHasKey( 'actor_user_id', $r );
		$this->assertArrayHasKey( 'request_method', $r );
		$this->assertArrayHasKey( 'url_path', $r );
	}

	public function test_enabled_false_when_wp_debug_not_true(): void {
		$this->assertFalse( Admin_Ux_Trace::enabled() );
	}
}
