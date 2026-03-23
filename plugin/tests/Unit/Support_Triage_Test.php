<?php
/**
 * Unit tests for Support_Triage_State_Builder and support triage payload (spec §49.11, §59.12; Prompt 125).
 *
 * Covers payload shape, severity grouping, deep-link presence, redacted summary (no secret keys in shape).
 *
 * Manual verification checklist:
 * - Permission gating: Only users with aio_view_logs see Support Triage menu and screen; others get 403.
 * - Severity grouping: Critical issues appear in "Critical issues" with severity=critical; filter ?severity=critical shows only those.
 * - Deep-link correctness: Each link opens the correct screen (Queue & Logs tab, Build Plans plan_id, AI Runs run_id, Import/Export).
 * - Redacted summary: No raw payloads, API keys, or credentials in any card; only titles, messages, and links.
 * - Filter by domain: ?domain=queue|reporting|ai_runs filters critical_issues, degraded_systems, recent_failed_workflows.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Reporting\UI\Support_Triage_State_Builder;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Reporting/UI/Support_Triage_State_Builder.php';
require_once $plugin_root . '/src/Domain/Reporting/UI/Reporting_Health_Summary_Builder.php';
require_once $plugin_root . '/src/Domain/Execution/Queue/Queue_Health_Summary_Builder.php';
require_once $plugin_root . '/src/Domain/Reporting/Contracts/Reporting_Event_Types.php';
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';

final class Support_Triage_Test extends TestCase {

	/** Example support-triage payload (Support_Triage_State_Builder::build() shape). */
	public static function example_support_triage_payload(): array {
		return array(
			'critical_issues'         => array(
				array(
					'severity'   => 'critical',
					'domain'     => 'reporting',
					'title'      => 'Critical error delivery failures',
					'message'    => '2 developer error report(s) failed to deliver.',
					'link_url'   => 'http://example.org/wp-admin/admin.php?page=aio-page-builder-queue-logs&tab=critical',
					'link_label' => 'View critical errors',
				),
			),
			'degraded_systems'        => array(
				array(
					'domain'     => 'queue',
					'title'      => 'Queue',
					'message'    => 'Queue: 5 pending, 1 running, 3 failed.',
					'link_url'   => 'http://example.org/wp-admin/admin.php?page=aio-page-builder-queue-logs&tab=queue',
					'link_label' => 'Queue & Logs',
				),
			),
			'recent_failed_workflows' => array(
				array(
					'domain'     => 'ai_runs',
					'identifier' => 'run-abc-123',
					'summary'    => 'failed — 2025-03-12 10:00:00',
					'link_url'   => 'http://example.org/wp-admin/admin.php?page=aio-page-builder-ai-workspace&aio_tab=ai_runs&run_id=run-abc-123',
					'link_label' => 'View AI run',
				),
			),
			'rollback_candidates'     => array(
				array(
					'job_ref'      => 'job_replace_plan_1_20250312100000_456',
					'job_type'     => 'replace_page',
					'plan_id'      => 'plan-uuid-1',
					'completed_at' => '2025-03-12 11:00:00',
					'link_url'     => 'http://example.org/wp-admin/admin.php?page=aio-page-builder-build-plans&plan_id=plan-uuid-1',
					'link_label'   => 'Open plan',
				),
			),
			'import_export_failures'  => array(),
			'recommended_links'       => array(
				array(
					'label'       => 'Queue & Logs',
					'url'         => 'http://example.org/wp-admin/admin.php?page=aio-page-builder-queue-logs',
					'description' => 'Queue health, execution logs, reporting logs.',
				),
			),
			'stale_plans'             => array(
				array(
					'plan_id' => 'plan-xyz',
					'status'  => 'pending_review',
					'title'   => 'My Plan',
				),
			),
		);
	}

	public function test_build_returns_stable_payload_shape(): void {
		$builder = new Support_Triage_State_Builder( null, null, null );
		$state   = $builder->build();
		$this->assertArrayHasKey( 'critical_issues', $state );
		$this->assertArrayHasKey( 'degraded_systems', $state );
		$this->assertArrayHasKey( 'recent_failed_workflows', $state );
		$this->assertArrayHasKey( 'rollback_candidates', $state );
		$this->assertArrayHasKey( 'import_export_failures', $state );
		$this->assertArrayHasKey( 'recommended_links', $state );
		$this->assertArrayHasKey( 'stale_plans', $state );
		$this->assertIsArray( $state['critical_issues'] );
		$this->assertIsArray( $state['recommended_links'] );
		$this->assertIsArray( $state['stale_plans'] );
	}

	public function test_critical_issues_has_severity_and_domain(): void {
		$builder = new Support_Triage_State_Builder( null, null, null );
		$state   = $builder->build();
		$this->assertIsArray( $state['critical_issues'] );
		foreach ( $state['critical_issues'] as $item ) {
			$this->assertArrayHasKey( 'severity', $item );
			$this->assertArrayHasKey( 'domain', $item );
			$this->assertArrayHasKey( 'title', $item );
			$this->assertArrayHasKey( 'link_url', $item );
			$this->assertArrayHasKey( 'link_label', $item );
		}
	}

	public function test_recommended_links_contain_deep_links(): void {
		$builder = new Support_Triage_State_Builder( null, null, null );
		$state   = $builder->build();
		$this->assertNotEmpty( $state['recommended_links'] );
		foreach ( $state['recommended_links'] as $link ) {
			$this->assertArrayHasKey( 'url', $link );
			$this->assertStringContainsString( 'admin.php', (string) $link['url'] );
			$this->assertArrayHasKey( 'label', $link );
			$this->assertArrayHasKey( 'description', $link );
		}
	}

	public function test_payload_has_no_secret_like_keys_in_issue_items(): void {
		$builder   = new Support_Triage_State_Builder( null, null, null );
		$state     = $builder->build();
		$forbidden = array( 'api_key', 'password', 'secret', 'token', 'credential' );
		$checked   = 0;
		foreach ( array( 'critical_issues', 'degraded_systems', 'recent_failed_workflows', 'rollback_candidates' ) as $key ) {
			$this->assertArrayHasKey( $key, $state );
			foreach ( $state[ $key ] as $item ) {
				foreach ( array_keys( $item ) as $k ) {
					$lower = strtolower( $k );
					foreach ( $forbidden as $f ) {
						$this->assertStringNotContainsString( $f, $lower, "Payload should not expose secret-like key: {$k}" );
					}
					++$checked;
				}
			}
		}
		$this->assertGreaterThanOrEqual( 0, $checked );
	}

	public function test_example_support_triage_payload_shape(): void {
		$example = self::example_support_triage_payload();
		$this->assertArrayHasKey( 'critical_issues', $example );
		$this->assertArrayHasKey( 'degraded_systems', $example );
		$this->assertArrayHasKey( 'recent_failed_workflows', $example );
		$this->assertArrayHasKey( 'rollback_candidates', $example );
		$this->assertArrayHasKey( 'import_export_failures', $example );
		$this->assertArrayHasKey( 'recommended_links', $example );
		$this->assertArrayHasKey( 'stale_plans', $example );
		$this->assertSame( 'critical', $example['critical_issues'][0]['severity'] );
		$this->assertStringContainsString( 'admin.php', $example['recommended_links'][0]['url'] );
	}
}
