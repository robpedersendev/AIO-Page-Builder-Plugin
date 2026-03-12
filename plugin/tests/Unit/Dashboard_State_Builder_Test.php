<?php
/**
 * Unit tests for Dashboard_State_Builder: state shape, readiness cards, welcome state (spec §49.5).
 *
 * Example dashboard state payload (no pseudocode):
 * readiness_cards: { environment: { ready: true, message: "Ready.", blocking_count: 0, warning_count: 0 }, dependency: { ready: true, message: "Dependencies met." }, provider: { ready: false, message: "No AI provider configured." } }
 * last_activity_cards: { last_crawl: null, last_ai_run: null, active_build_plans: [] }
 * queue_warning_summary: { has_warnings: false, pending_count: 0, failed_count: 0, message: "No queue warnings.", queue_logs_url: "..." }
 * critical_error_summary: { count: 0, items: [], logs_url: "..." }
 * quick_actions: [ { label: "Start / Resume Onboarding", url: "...", capability: "manage_options" }, ... ]  (filtered by current_user_can)
 * welcome_state: { is_first_run: true, is_resume: false, onboarding_url: "..." }
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
require_once $plugin_root . '/src/Domain/Reporting/UI/Logs_Monitoring_State_Builder.php';
require_once $plugin_root . '/src/Domain/Admin/Dashboard/Dashboard_State_Builder.php';

final class Dashboard_State_Builder_Test extends TestCase {

	public function test_build_returns_expected_keys(): void {
		$state = $this->build_state();
		$this->assertArrayHasKey( 'readiness_cards', $state );
		$this->assertArrayHasKey( 'last_activity_cards', $state );
		$this->assertArrayHasKey( 'queue_warning_summary', $state );
		$this->assertArrayHasKey( 'critical_error_summary', $state );
		$this->assertArrayHasKey( 'quick_actions', $state );
		$this->assertArrayHasKey( 'welcome_state', $state );
	}

	public function test_readiness_cards_have_environment_dependency_provider(): void {
		$state = $this->build_state();
		$r = $state['readiness_cards'];
		$this->assertArrayHasKey( 'environment', $r );
		$this->assertArrayHasKey( 'dependency', $r );
		$this->assertArrayHasKey( 'provider', $r );
		$this->assertArrayHasKey( 'ready', $r['environment'] );
		$this->assertArrayHasKey( 'message', $r['environment'] );
		$this->assertArrayHasKey( 'ready', $r['dependency'] );
		$this->assertArrayHasKey( 'ready', $r['provider'] );
	}

	public function test_last_activity_cards_have_expected_structure(): void {
		$state = $this->build_state();
		$a = $state['last_activity_cards'];
		$this->assertArrayHasKey( 'last_crawl', $a );
		$this->assertArrayHasKey( 'last_ai_run', $a );
		$this->assertArrayHasKey( 'active_build_plans', $a );
		$this->assertIsArray( $a['active_build_plans'] );
	}

	public function test_queue_warning_summary_has_required_keys(): void {
		$state = $this->build_state();
		$q = $state['queue_warning_summary'];
		$this->assertArrayHasKey( 'has_warnings', $q );
		$this->assertArrayHasKey( 'message', $q );
		$this->assertArrayHasKey( 'queue_logs_url', $q );
	}

	public function test_critical_error_summary_has_count_and_items(): void {
		$state = $this->build_state();
		$c = $state['critical_error_summary'];
		$this->assertArrayHasKey( 'count', $c );
		$this->assertArrayHasKey( 'items', $c );
		$this->assertArrayHasKey( 'logs_url', $c );
		$this->assertIsArray( $c['items'] );
	}

	public function test_welcome_state_has_is_first_run_and_onboarding_url(): void {
		$state = $this->build_state();
		$w = $state['welcome_state'];
		$this->assertArrayHasKey( 'is_first_run', $w );
		$this->assertArrayHasKey( 'is_resume', $w );
		$this->assertArrayHasKey( 'onboarding_url', $w );
		$this->assertStringContainsString( 'aio-page-builder-onboarding', $w['onboarding_url'] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function build_state(): array {
		$settings = new Settings_Service();
		$builder = new Dashboard_State_Builder( $settings, null, null, null, null );
		return $builder->build();
	}
}
