<?php
/**
 * Unit tests for Future_Subtype_Readiness_Screen (Prompt 567). Slug, capability, view model structure, missing-data fallback.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Industry\Future_Subtype_Readiness_Screen;
use AIOPageBuilder\Admin\ViewModels\Industry\Future_Subtype_Readiness_View_Model;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Admin/ViewModels/Industry/Future_Subtype_Readiness_View_Model.php';
require_once $plugin_root . '/src/Admin/Screens/Industry/Future_Subtype_Readiness_Screen.php';

final class Future_Subtype_Readiness_Screen_Test extends TestCase {

	public function test_screen_has_expected_slug_and_capability(): void {
		$screen = new Future_Subtype_Readiness_Screen( null );
		$this->assertSame( 'aio-page-builder-industry-future-subtype-readiness', Future_Subtype_Readiness_Screen::SLUG );
		$this->assertSame( 'aio_view_logs', $screen->get_capability() );
	}

	public function test_view_model_with_defaults_returns_bounded_structure(): void {
		$vm = new Future_Subtype_Readiness_View_Model(
			0,
			0,
			array(),
			0,
			array(
				'author_dashboard'   => '#',
				'subtype_comparison' => '#',
				'scaffold_promotion' => '#',
			)
		);
		$this->assertSame( 0, $vm->get_subtype_scaffold_count() );
		$this->assertSame( 0, $vm->get_subtype_missing_count() );
		$this->assertSame( 0, $vm->get_blocker_count() );
		$this->assertIsArray( $vm->get_promotion_readiness_subtype_summary() );
		$this->assertArrayHasKey( 'author_dashboard', $vm->get_links() );
		$this->assertArrayHasKey( 'subtype_comparison', $vm->get_links() );
		$this->assertArrayHasKey( 'scaffold_promotion', $vm->get_links() );
	}

	public function test_view_model_to_array_has_required_keys(): void {
		$vm  = new Future_Subtype_Readiness_View_Model( 0, 0, array(), 0, array() );
		$arr = $vm->to_array();
		$this->assertArrayHasKey( Future_Subtype_Readiness_View_Model::KEY_SUBTYPE_SCAFFOLD_COUNT, $arr );
		$this->assertArrayHasKey( Future_Subtype_Readiness_View_Model::KEY_PROMO_SUBTYPE_SUMMARY, $arr );
		$this->assertArrayHasKey( Future_Subtype_Readiness_View_Model::KEY_LINKS, $arr );
	}
}
