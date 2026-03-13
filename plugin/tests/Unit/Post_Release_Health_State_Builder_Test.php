<?php
/**
 * Unit tests for Post_Release_Health_State_Builder (spec §45, §49.11, §59.15, §60.8; Prompt 131).
 *
 * Covers aggregation shape, domain_health_scores keys, recommended_investigation_items,
 * permission gating (caller responsibility), and deep-link URLs. Includes example post_release_health_summary payload.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Reporting\UI\Post_Release_Health_State_Builder;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Domain/Reporting/UI/Reporting_Health_Summary_Builder.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Types.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Job_Queue_Status.php';
require_once $plugin_root . '/src/Domain/Execution/Queue/Queue_Health_Summary_Builder.php';
require_once $plugin_root . '/src/Domain/Reporting/Contracts/Reporting_Event_Types.php';
require_once $plugin_root . '/src/Domain/Reporting/UI/Support_Triage_State_Builder.php';
require_once $plugin_root . '/src/Domain/Reporting/UI/Post_Release_Health_State_Builder.php';

final class Post_Release_Health_State_Builder_Test extends TestCase {

	public function test_build_returns_stable_aggregation_shape(): void {
		$builder = new Post_Release_Health_State_Builder( null, null, null, null );
		$state   = $builder->build( null, null );
		$this->assertArrayHasKey( 'post_release_health_summary', $state );
		$this->assertArrayHasKey( 'domain_health_scores', $state );
		$this->assertArrayHasKey( 'recommended_investigation_items', $state );
		$this->assertIsArray( $state['post_release_health_summary'] );
		$this->assertIsArray( $state['domain_health_scores'] );
		$this->assertIsArray( $state['recommended_investigation_items'] );
	}

	public function test_post_release_health_summary_has_required_keys(): void {
		$builder = new Post_Release_Health_State_Builder( null, null, null, null );
		$state   = $builder->build( '2025-02-01', '2025-03-01' );
		$summary = $state['post_release_health_summary'];
		$this->assertArrayHasKey( 'period_start', $summary );
		$this->assertArrayHasKey( 'period_end', $summary );
		$this->assertArrayHasKey( 'overall_status', $summary );
		$this->assertArrayHasKey( 'summary_message', $summary );
		$this->assertSame( '2025-02-01', $summary['period_start'] );
		$this->assertSame( '2025-03-01', $summary['period_end'] );
		$this->assertContains( $summary['overall_status'], array( 'ok', 'attention', 'critical' ) );
	}

	public function test_domain_health_scores_contain_expected_domains(): void {
		$builder = new Post_Release_Health_State_Builder( null, null, null, null );
		$state   = $builder->build( null, null );
		$scores  = $state['domain_health_scores'];
		$expected = array( 'reporting', 'queue', 'build_plan_review', 'ai_run_validity', 'rollback', 'import_export', 'support_package' );
		foreach ( $expected as $domain ) {
			$this->assertArrayHasKey( $domain, $scores, "domain_health_scores must include: $domain" );
			$this->assertArrayHasKey( 'status', $scores[ $domain ] );
			$this->assertArrayHasKey( 'score_label', $scores[ $domain ] );
			$this->assertArrayHasKey( 'message', $scores[ $domain ] );
			$this->assertArrayHasKey( 'link_url', $scores[ $domain ] );
			$this->assertArrayHasKey( 'link_label', $scores[ $domain ] );
		}
	}

	public function test_recommended_investigation_items_have_priority_and_links(): void {
		$builder = new Post_Release_Health_State_Builder( null, null, null, null );
		$state   = $builder->build( null, null );
		$items   = $state['recommended_investigation_items'];
		$this->assertIsArray( $items );
		foreach ( $items as $item ) {
			$this->assertArrayHasKey( 'domain', $item );
			$this->assertArrayHasKey( 'priority', $item );
			$this->assertArrayHasKey( 'title', $item );
			$this->assertArrayHasKey( 'message', $item );
			$this->assertArrayHasKey( 'link_url', $item );
			$this->assertArrayHasKey( 'link_label', $item );
		}
	}

	public function test_deep_link_urls_contain_admin_page(): void {
		$builder = new Post_Release_Health_State_Builder( null, null, null, null );
		$state   = $builder->build( null, null );
		foreach ( $state['domain_health_scores'] as $domain => $score ) {
			$this->assertStringContainsString( 'admin.php', (string) ( $score['link_url'] ?? '' ), "domain $domain link_url must point to admin" );
		}
		foreach ( $state['recommended_investigation_items'] as $item ) {
			$this->assertStringContainsString( 'admin.php', (string) ( $item['link_url'] ?? '' ) );
		}
	}

	public function test_payload_has_no_secret_like_keys(): void {
		$builder = new Post_Release_Health_State_Builder( null, null, null, null );
		$state   = $builder->build( null, null );
		$json    = \wp_json_encode( $state );
		$this->assertNotFalse( $json );
		$this->assertStringNotContainsString( 'api_key', $json );
		$this->assertStringNotContainsString( 'password', $json );
		$this->assertStringNotContainsString( 'secret', $json );
	}

	/**
	 * Example post-release health summary payload (spec §59.15, §60.8; Prompt 131). No pseudocode.
	 */
	public function test_example_post_release_health_summary_payload(): void {
		$builder = new Post_Release_Health_State_Builder( null, null, null, null );
		$state   = $builder->build( '2025-02-15', '2025-03-15' );

		$example_summary = array(
			'period_start'    => '2025-02-15',
			'period_end'     => '2025-03-15',
			'overall_status' => 'ok',
			'summary_message'=> 'Operational health good across domains for the selected period.',
		);

		$this->assertSame( $example_summary['period_start'], $state['post_release_health_summary']['period_start'] );
		$this->assertSame( $example_summary['period_end'], $state['post_release_health_summary']['period_end'] );
		$this->assertContains( $state['post_release_health_summary']['overall_status'], array( 'ok', 'attention', 'critical' ) );
		$this->assertNotEmpty( $state['post_release_health_summary']['summary_message'] );
		$this->assertArrayHasKey( 'reporting', $state['domain_health_scores'] );
		$this->assertArrayHasKey( 'queue', $state['domain_health_scores'] );
		$this->assertArrayHasKey( 'build_plan_review', $state['domain_health_scores'] );
		$this->assertArrayHasKey( 'ai_run_validity', $state['domain_health_scores'] );
		$this->assertArrayHasKey( 'rollback', $state['domain_health_scores'] );
		$this->assertArrayHasKey( 'import_export', $state['domain_health_scores'] );
		$this->assertArrayHasKey( 'support_package', $state['domain_health_scores'] );
	}
}
