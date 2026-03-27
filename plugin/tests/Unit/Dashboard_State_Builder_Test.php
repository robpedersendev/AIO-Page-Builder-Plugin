<?php
/**
 * Unit tests for Dashboard_State_Builder: state shape, readiness strip, welcome state (spec §49.5).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Admin\Dashboard\Dashboard_State_Builder;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Bootstrap/Constants.php';
require_once $plugin_root . '/src/Infrastructure/Config/Dependency_Requirements.php';
require_once $plugin_root . '/src/Bootstrap/Environment_Validator.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Statuses.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Step_Keys.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Draft_Service.php';
require_once $plugin_root . '/src/Domain/Reporting/Contracts/Reporting_Event_Types.php';
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Domain/Storage/Assignments/Assignment_Types.php';
require_once $plugin_root . '/src/Domain/Reporting/UI/Logs_Monitoring_State_Builder.php';
require_once $plugin_root . '/src/Domain/Admin/Dashboard/Dashboard_State_Builder.php';

final class Dashboard_State_Builder_Test extends TestCase {

	public function test_build_returns_expected_keys(): void {
		$state = $this->build_state();
		$this->assertArrayHasKey( 'overview_metrics', $state );
		$this->assertArrayHasKey( 'onboarding_callout', $state );
		$this->assertArrayHasKey( 'readiness_strip', $state );
		$this->assertArrayHasKey( 'activity_pulse', $state );
		$this->assertArrayHasKey( 'queue_warning_summary', $state );
		$this->assertArrayHasKey( 'critical_error_summary', $state );
		$this->assertArrayHasKey( 'explore_links', $state );
		$this->assertArrayHasKey( 'footer_links', $state );
		$this->assertArrayHasKey( 'welcome_state', $state );
		$this->assertArrayHasKey( 'onboarding_metrics', $state );
		$this->assertArrayHasKey( 'visible', $state['onboarding_metrics'] );
	}

	public function test_readiness_strip_has_summary_and_url(): void {
		$state = $this->build_state();
		$r     = $state['readiness_strip'];
		$this->assertArrayHasKey( 'all_ready', $r );
		$this->assertArrayHasKey( 'summary', $r );
		$this->assertArrayHasKey( 'diagnostics_url', $r );
		$this->assertStringContainsString( 'aio-page-builder-diagnostics', $r['diagnostics_url'] );
	}

	public function test_activity_pulse_has_expected_structure(): void {
		$state = $this->build_state();
		$a     = $state['activity_pulse'];
		$this->assertArrayHasKey( 'last_crawl', $a );
		$this->assertArrayHasKey( 'last_ai_run', $a );
		$this->assertArrayHasKey( 'active_build_plans', $a );
		$this->assertIsArray( $a['active_build_plans'] );
		$this->assertArrayHasKey( 'plans_hub_url', $a );
		$this->assertArrayHasKey( 'ai_hub_url', $a );
	}

	public function test_overview_metrics_has_built_pages_and_spend(): void {
		$state = $this->build_state();
		$m     = $state['overview_metrics'];
		$this->assertArrayHasKey( 'built_pages', $m );
		$this->assertArrayHasKey( 'ai_spend_mtd_usd', $m );
		$this->assertArrayHasKey( 'ai_spend_label', $m );
		$this->assertArrayHasKey( 'active_plans', $m );
		$this->assertArrayHasKey( 'provider_ready', $m );
	}

	public function test_queue_warning_summary_has_required_keys(): void {
		$state = $this->build_state();
		$q     = $state['queue_warning_summary'];
		$this->assertArrayHasKey( 'has_warnings', $q );
		$this->assertArrayHasKey( 'message', $q );
		$this->assertArrayHasKey( 'queue_logs_url', $q );
	}

	public function test_critical_error_summary_has_count_and_items(): void {
		$state = $this->build_state();
		$c     = $state['critical_error_summary'];
		$this->assertArrayHasKey( 'count', $c );
		$this->assertArrayHasKey( 'items', $c );
		$this->assertArrayHasKey( 'logs_url', $c );
		$this->assertIsArray( $c['items'] );
	}

	public function test_welcome_state_has_is_first_run_and_onboarding_url(): void {
		$state = $this->build_state();
		$w     = $state['welcome_state'];
		$this->assertArrayHasKey( 'is_first_run', $w );
		$this->assertArrayHasKey( 'is_resume', $w );
		$this->assertArrayHasKey( 'onboarding_url', $w );
		$this->assertStringContainsString( 'aio-page-builder-onboarding', $w['onboarding_url'] );
	}

	public function test_onboarding_callout_shape(): void {
		$state = $this->build_state();
		$o     = $state['onboarding_callout'];
		$this->assertArrayHasKey( 'visible', $o );
		$this->assertArrayHasKey( 'variant', $o );
		$this->assertArrayHasKey( 'url', $o );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function build_state(): array {
		$settings = new Settings_Service();
		$builder  = new Dashboard_State_Builder( $settings, null, null, null, null, null, null, null );
		return $builder->build();
	}
}
