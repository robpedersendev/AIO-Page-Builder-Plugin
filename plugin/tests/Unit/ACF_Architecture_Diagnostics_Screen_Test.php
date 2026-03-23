<?php
/**
 * Unit tests for ACF Architecture Diagnostics Screen (Prompt 223).
 * Covers: capability and repair-capability, empty state shape, screen slug.
 * Manual verification checklist: missing-group detection, stale-version display,
 * assignment mismatch summaries, LPagery support state, repair-link visibility by permission.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Diagnostics\ACF_Architecture_Diagnostics_Screen;
use AIOPageBuilder\Domain\ACF\Diagnostics\ACF_Diagnostics_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Domain/ACF/Diagnostics/ACF_Diagnostics_State_Builder.php';
require_once $plugin_root . '/src/Admin/Screens/Diagnostics/ACF_Architecture_Diagnostics_Screen.php';

/**
 * ACF Architecture Diagnostics Screen: capability, slug, and empty-state shape.
 */
final class ACF_Architecture_Diagnostics_Screen_Test extends TestCase {

	public function test_screen_slug_is_stable(): void {
		$this->assertSame( 'aio-page-builder-acf-diagnostics', ACF_Architecture_Diagnostics_Screen::SLUG );
		$screen = new ACF_Architecture_Diagnostics_Screen( null );
		$this->assertNotEmpty( $screen->get_title() );
	}

	public function test_view_capability_is_view_logs(): void {
		$screen = new ACF_Architecture_Diagnostics_Screen( null );
		$this->assertSame( Capabilities::VIEW_LOGS, $screen->get_capability() );
	}

	public function test_repair_capability_is_manage_section_templates(): void {
		$screen = new ACF_Architecture_Diagnostics_Screen( null );
		$this->assertSame( Capabilities::MANAGE_SECTION_TEMPLATES, $screen->get_repair_capability() );
	}

	public function test_repair_link_visibility_requires_higher_capability_than_view(): void {
		$screen = new ACF_Architecture_Diagnostics_Screen( null );
		$this->assertNotSame( $screen->get_capability(), $screen->get_repair_capability() );
		$this->assertTrue( in_array( $screen->get_repair_capability(), array( Capabilities::MANAGE_SECTION_TEMPLATES, Capabilities::MANAGE_PAGE_TEMPLATES ), true ) );
	}

	/**
	 * Empty state (no container or missing state builder) must expose keys expected by the screen render.
	 */
	public function test_empty_state_has_required_keys_for_render(): void {
		$screen = new ACF_Architecture_Diagnostics_Screen( null );
		$ref    = new \ReflectionClass( $screen );
		$method = $ref->getMethod( 'build_state' );
		$state  = $method->invoke( $screen );
		$this->assertArrayHasKey( 'acf_diagnostics_summary', $state );
		$this->assertArrayHasKey( 'field_architecture_health_card', $state );
		$this->assertArrayHasKey( 'assignment_mismatch_groups', $state );
		$this->assertArrayHasKey( 'lpagery_field_support_summary', $state );
		$this->assertArrayHasKey( 'regeneration_plan_summary', $state );
		$summary = $state['acf_diagnostics_summary'];
		$this->assertArrayHasKey( 'overall_status', $summary );
		$this->assertArrayHasKey( 'repair_readiness', $summary );
		$this->assertArrayHasKey( 'lpagery_status', $summary );
		$this->assertSame( ACF_Diagnostics_State_Builder::OVERALL_BLOCKED, $summary['overall_status'] );
	}
}
