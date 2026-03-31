<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Infrastructure\AdminRouting\Admin_Router;
use AIOPageBuilder\Infrastructure\AdminRouting\Template_Library_Hub_Urls;
use PHPUnit\Framework\TestCase;

final class Admin_Router_Url_Contract_Test extends TestCase {

	public function test_build_plan_workspace_url_includes_plan_id(): void {
		$router = new Admin_Router();
		$url    = $router->url(
			'build_plan_workspace',
			array(
				'plan_id' => 'aio-plan-abc',
				'step'    => 2,
			)
		);
		$this->assertStringContainsString( 'page=aio-page-builder-build-plans', $url );
		$this->assertStringContainsString( 'plan_id=aio-plan-abc', $url );
		$this->assertStringContainsString( 'step=2', $url );
	}

	public function test_template_library_route_sets_tab_default(): void {
		$router = new Admin_Router();
		$url    = $router->url( 'page_templates_directory', array() );
		$this->assertStringContainsString( 'page=' . Template_Library_Hub_Urls::HUB_PAGE_SLUG, $url );
		$this->assertStringContainsString( Template_Library_Hub_Urls::QUERY_TAB . '=', $url );
	}
}
