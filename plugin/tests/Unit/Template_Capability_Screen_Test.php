<?php
/**
 * Unit tests for template-related screen capability enforcement (Prompt 200, spec §44.5, §49.6, §49.7, §62.2).
 * Asserts each template screen returns the correct capability; capability matrix completeness for template workflows.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Analytics\Template_Analytics_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Compositions_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Page_Template_Detail_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Page_Templates_Directory_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Section_Template_Detail_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Section_Templates_Directory_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Template_Compare_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Admin/Screens/Templates/Section_Templates_Directory_Screen.php';
require_once $plugin_root . '/src/Admin/Screens/Templates/Section_Template_Detail_Screen.php';
require_once $plugin_root . '/src/Admin/Screens/Templates/Page_Templates_Directory_Screen.php';
require_once $plugin_root . '/src/Admin/Screens/Templates/Page_Template_Detail_Screen.php';
require_once $plugin_root . '/src/Admin/Screens/Templates/Template_Compare_Screen.php';
require_once $plugin_root . '/src/Admin/Screens/Templates/Compositions_Screen.php';
require_once $plugin_root . '/src/Admin/Screens/Analytics/Template_Analytics_Screen.php';

final class Template_Capability_Screen_Test extends TestCase {

	public function test_section_templates_directory_requires_manage_section_templates(): void {
		$screen = new Section_Templates_Directory_Screen( null );
		$this->assertSame( Capabilities::MANAGE_SECTION_TEMPLATES, $screen->get_capability() );
	}

	public function test_section_template_detail_requires_manage_section_templates(): void {
		$screen = new Section_Template_Detail_Screen( null );
		$this->assertSame( Capabilities::MANAGE_SECTION_TEMPLATES, $screen->get_capability() );
	}

	public function test_page_templates_directory_requires_manage_page_templates(): void {
		$screen = new Page_Templates_Directory_Screen( null );
		$this->assertSame( Capabilities::MANAGE_PAGE_TEMPLATES, $screen->get_capability() );
	}

	public function test_page_template_detail_requires_manage_page_templates(): void {
		$screen = new Page_Template_Detail_Screen( null );
		$this->assertSame( Capabilities::MANAGE_PAGE_TEMPLATES, $screen->get_capability() );
	}

	public function test_template_compare_requires_manage_page_templates(): void {
		$screen = new Template_Compare_Screen( null );
		$this->assertSame( Capabilities::MANAGE_PAGE_TEMPLATES, $screen->get_capability() );
	}

	public function test_compositions_requires_manage_compositions(): void {
		$screen = new Compositions_Screen( null );
		$this->assertSame( Capabilities::MANAGE_COMPOSITIONS, $screen->get_capability() );
	}

	public function test_template_analytics_requires_view_logs(): void {
		$screen = new Template_Analytics_Screen( null );
		$this->assertSame( Capabilities::VIEW_LOGS, $screen->get_capability() );
	}

	/**
	 * Capability matrix completeness: template workflow caps are defined and in getAll().
	 */
	public function test_template_workflow_capabilities_exist_in_plugin_set(): void {
		$all = Capabilities::get_all();
		$this->assertContains( Capabilities::MANAGE_SECTION_TEMPLATES, $all );
		$this->assertContains( Capabilities::MANAGE_PAGE_TEMPLATES, $all );
		$this->assertContains( Capabilities::MANAGE_COMPOSITIONS, $all );
		$this->assertContains( Capabilities::VIEW_LOGS, $all );
		$this->assertTrue( Capabilities::is_plugin_capability( Capabilities::MANAGE_SECTION_TEMPLATES ) );
		$this->assertTrue( Capabilities::is_plugin_capability( Capabilities::MANAGE_PAGE_TEMPLATES ) );
		$this->assertTrue( Capabilities::is_plugin_capability( Capabilities::MANAGE_COMPOSITIONS ) );
		$this->assertTrue( Capabilities::is_plugin_capability( Capabilities::VIEW_LOGS ) );
	}
}
