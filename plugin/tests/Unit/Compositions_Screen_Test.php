<?php
/**
 * Unit tests for Compositions_Screen (Prompt 177).
 * Permission gating (capability), title, slug.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Templates\Compositions_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Large_Library_Pagination.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Large_Library_Filter_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Composition_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Large_Library_Query_Service.php';
require_once $plugin_root . '/src/Domain/Registries/Compositions/UI/Composition_Filter_State.php';
require_once $plugin_root . '/src/Domain/Registries/Compositions/UI/Composition_Builder_State_Builder.php';
require_once $plugin_root . '/src/Admin/Screens/Templates/Compositions_Screen.php';

final class Compositions_Screen_Test extends TestCase {

	public function test_get_capability_returns_manage_compositions(): void {
		$screen = new Compositions_Screen( null );
		$this->assertSame( Capabilities::MANAGE_COMPOSITIONS, $screen->get_capability() );
	}

	public function test_get_title_returns_translated_string(): void {
		$screen = new Compositions_Screen( null );
		$title  = $screen->get_title();
		$this->assertSame( 'Compositions', $title );
	}

	public function test_slug_constant(): void {
		$this->assertSame( 'aio-page-builder-compositions', Compositions_Screen::SLUG );
	}
}
