<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use PHPUnit\Framework\TestCase;

final class Admin_Screen_Hub_Url_Contract_Test extends TestCase {

	public function test_build_plans_screen_hub_workspace_url_includes_tab(): void {
		$url = Build_Plans_Screen::hub_workspace_url(
			array(
				'plan_id' => 'aio-plan-x',
				'step'    => '3',
			)
		);
		$this->assertStringContainsString( 'page=' . Build_Plans_Screen::SLUG, $url );
		$this->assertStringContainsString( 'aio_tab=build_plans', $url );
		$this->assertStringContainsString( 'plan_id=', $url );
		$this->assertStringContainsString( 'step=3', $url );
	}

	public function test_build_plans_screen_hub_plans_list_url_includes_tab(): void {
		$url = Build_Plans_Screen::hub_plans_list_url();
		$this->assertStringContainsString( 'page=' . Build_Plans_Screen::SLUG, $url );
		$this->assertStringContainsString( 'aio_tab=build_plans', $url );
	}

	public function test_tab_url_contains_page_tab_and_extra(): void {
		$url = Admin_Screen_Hub::tab_url(
			Build_Plans_Screen::SLUG,
			'build_plans',
			array( 'plan_id' => 'aio-plan-test-1' )
		);
		$this->assertStringContainsString( 'page=' . Build_Plans_Screen::SLUG, $url );
		$this->assertStringContainsString( 'aio_tab=build_plans', $url );
		$this->assertStringContainsString( 'plan_id=aio-plan-test-1', $url );
	}

	public function test_subtab_url_contains_subtab_key(): void {
		$url = Admin_Screen_Hub::subtab_url(
			'aio-page-builder-industry-profile',
			'reports',
			'health',
			array()
		);
		$this->assertStringContainsString( 'page=aio-page-builder-industry-profile', $url );
		$this->assertStringContainsString( 'aio_tab=reports', $url );
		$this->assertStringContainsString( 'aio_subtab=health', $url );
	}
}
