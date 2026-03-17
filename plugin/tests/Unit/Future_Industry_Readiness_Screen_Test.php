<?php
/**
 * Unit tests for Future_Industry_Readiness_Screen (Prompt 566). Slug, capability, view model structure, missing-data fallback.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Industry\Future_Industry_Readiness_Screen;
use AIOPageBuilder\Admin\ViewModels\Industry\Future_Industry_Readiness_View_Model;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Admin/ViewModels/Industry/Future_Industry_Readiness_View_Model.php';
require_once $plugin_root . '/src/Admin/Screens/Industry/Future_Industry_Readiness_Screen.php';

final class Future_Industry_Readiness_Screen_Test extends TestCase {

	public function test_screen_has_expected_slug_and_capability(): void {
		$screen = new Future_Industry_Readiness_Screen( null );
		$this->assertSame( 'aio-page-builder-industry-future-readiness', Future_Industry_Readiness_Screen::SLUG );
		$this->assertSame( 'aio_view_logs', $screen->get_capability() );
	}

	public function test_view_model_with_defaults_returns_bounded_structure(): void {
		$vm = new Future_Industry_Readiness_View_Model( 0, 0, '', '', array(), array(), array( 'author_dashboard' => '#', 'pack_family_comparison' => '#', 'scaffold_promotion' => '#' ) );
		$this->assertSame( 0, $vm->get_expansion_blocker_count() );
		$this->assertSame( 0, $vm->get_scaffold_incomplete_count() );
		$this->assertIsString( $vm->get_candidate_readiness_label() );
		$this->assertIsString( $vm->get_maturity_floor_label() );
		$this->assertIsArray( $vm->get_promotion_readiness_summary() );
		$this->assertIsArray( $vm->get_scaffold_summary() );
		$this->assertArrayHasKey( 'author_dashboard', $vm->get_links() );
		$this->assertArrayHasKey( 'pack_family_comparison', $vm->get_links() );
		$this->assertArrayHasKey( 'scaffold_promotion', $vm->get_links() );
	}

	public function test_view_model_to_array_has_required_keys(): void {
		$vm = new Future_Industry_Readiness_View_Model( 0, 0, '', '', array(), array(), array() );
		$arr = $vm->to_array();
		$this->assertArrayHasKey( Future_Industry_Readiness_View_Model::KEY_EXPANSION_BLOCKER_COUNT, $arr );
		$this->assertArrayHasKey( Future_Industry_Readiness_View_Model::KEY_SCAFFOLD_SUMMARY, $arr );
		$this->assertArrayHasKey( Future_Industry_Readiness_View_Model::KEY_LINKS, $arr );
	}
}
