<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Plans_Hub_Tab_Resolver;
use PHPUnit\Framework\TestCase;

final class Plans_Hub_Tab_Resolver_Test extends TestCase {

	public function test_forces_build_plans_when_plan_id_and_cap(): void {
		$this->assertSame(
			'build_plans',
			Plans_Hub_Tab_Resolver::apply_deep_link_to_tab( 'bp_analytics', 'aio-plan-x', '', true )
		);
	}

	public function test_forces_build_plans_when_numeric_id_and_cap(): void {
		$this->assertSame(
			'build_plans',
			Plans_Hub_Tab_Resolver::apply_deep_link_to_tab( 'template_analytics', '', '42', true )
		);
	}

	public function test_does_not_override_when_missing_cap(): void {
		$this->assertSame(
			'bp_analytics',
			Plans_Hub_Tab_Resolver::apply_deep_link_to_tab( 'bp_analytics', 'aio-plan-x', '', false )
		);
	}

	public function test_no_deep_link_leaves_tab(): void {
		$this->assertSame(
			'bp_analytics',
			Plans_Hub_Tab_Resolver::apply_deep_link_to_tab( 'bp_analytics', '', '', true )
		);
	}
}
