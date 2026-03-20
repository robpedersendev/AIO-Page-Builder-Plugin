<?php
/**
 * Integration tests — AI run history cost display (v2-scope-backlog.md §4).
 *
 * Verifies that:
 * - AI_Run_Detail_Screen surfaces cost_usd when pricing is known.
 * - AI_Run_Detail_Screen shows "Not available" when cost_usd is null.
 * - AI_Run_Detail_Screen shows token counts alongside the cost row.
 * - AI_Runs_Screen renders a month-to-date spend summary widget.
 * - Aggregate spend shows truthful "exceeded" / "approaching" / "no cap" labels.
 * - Unknown cost is never surfaced as "$0.000000".
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Integration\Admin\AI;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

use AIOPageBuilder\Admin\Screens\AI\AI_Run_Detail_Screen;
use AIOPageBuilder\Admin\Screens\AI\AI_Runs_Screen;
use AIOPageBuilder\Domain\AI\Budget\Provider_Monthly_Spend_Service;
use AIOPageBuilder\Domain\AI\Budget\Provider_Spend_Cap_Settings;
use AIOPageBuilder\Domain\AI\Pricing\Provider_Pricing_Registry;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use PHPUnit\Framework\TestCase;

// ---------------------------------------------------------------------------
// Inline stubs — run service, artifact service, run repository
// ---------------------------------------------------------------------------

/**
 * Spy run service that returns a single configurable run.
 */
final class Stub_AI_Run_Service_Cost {
	/** @var array<string, mixed>|null */
	public ?array $run_to_return;

	/** @param array<string, mixed>|null $run */
	public function __construct( ?array $run ) {
		$this->run_to_return = $run;
	}

	/** @return array<string, mixed>|null */
	public function get_run_by_id( string $run_id ): ?array {
		return $this->run_to_return;
	}
}

/**
 * Spy artifact service returning configurable summary + raw usage data.
 */
final class Stub_AI_Artifact_Service_Cost {
	/** @var array<string, array<string, mixed>> */
	public array $summary_to_return;
	/** @var array<string, mixed>|null */
	public ?array $usage_data;

	/**
	 * @param array<string, array<string, mixed>> $summary
	 * @param array<string, mixed>|null           $usage_data
	 */
	public function __construct( array $summary, ?array $usage_data ) {
		$this->summary_to_return = $summary;
		$this->usage_data        = $usage_data;
	}

	/** @return array<string, array<string, mixed>> */
	public function get_artifact_summary_for_review( int $run_post_id, bool $include_raw = false ): array {
		return $this->summary_to_return;
	}

	/** @return mixed */
	public function get( int $run_post_id, string $category ) {
		return $category === 'usage_metadata' ? $this->usage_data : null;
	}

	/**
	 * Passthrough static redaction helper — not called for usage_metadata.
	 *
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	public static function redact_sensitive_values( array $data ): array {
		return $data;
	}
}

/**
 * Stub run repository returning a configurable list of runs.
 */
final class Stub_AI_Run_Repository_Cost {
	/** @var array<int, array<string, mixed>> */
	public array $runs;

	/** @param array<int, array<string, mixed>> $runs */
	public function __construct( array $runs ) {
		$this->runs = $runs;
	}

	/** @return array<int, array<string, mixed>> */
	public function list_recent( int $limit, int $offset ): array {
		return $this->runs;
	}
}

/**
 * @covers \AIOPageBuilder\Admin\Screens\AI\AI_Run_Detail_Screen
 * @covers \AIOPageBuilder\Admin\Screens\AI\AI_Runs_Screen
 */
final class AI_Run_History_Cost_Display_Test extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_aio_test_options']    = array();
		$GLOBALS['__spend_test_options'] = array();
		// * Ensure current_user_can returns false (no VIEW_SENSITIVE_DIAGNOSTICS).
		$GLOBALS['_aio_current_user_can_return'] = false;
		// * Ensure $_GET has no run_id so AI_Runs_Screen renders the list.
		unset( $_GET['run_id'] );
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['_aio_test_options'],
			$GLOBALS['__spend_test_options'],
			$GLOBALS['_aio_current_user_can_return'],
			$_GET['run_id']
		);
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function build_detail_container(
		?array $run,
		?array $usage_data
	): Service_Container {
		$container    = new Service_Container();
		$run_svc      = new Stub_AI_Run_Service_Cost( $run );
		$artifact_svc = new Stub_AI_Artifact_Service_Cost( array(), $usage_data );

		$container->register( 'ai_run_service', fn() => $run_svc );
		$container->register( 'ai_run_artifact_service', fn() => $artifact_svc );
		return $container;
	}

	/** Captures render() output as a string. */
	private function capture_detail( Service_Container $container, string $run_id ): string {
		$screen = new AI_Run_Detail_Screen( $container );
		ob_start();
		$screen->render( $run_id );
		return (string) ob_get_clean();
	}

	private function make_run( string $run_id, int $wp_id = 42 ): array {
		return array(
			'id'           => $wp_id,
			'internal_key' => $run_id,
			'status'       => 'completed',
			'run_metadata' => array(
				'provider_id'    => 'openai',
				'model_used'     => 'gpt-4o',
				'created_at'     => '2025-07-01 00:00:00',
				'completed_at'   => '2025-07-01 00:01:00',
				'actor'          => 'admin',
				'prompt_pack_ref' => 'pack-v1',
				'retry_count'    => 0,
				'build_plan_ref' => '',
			),
		);
	}

	// -----------------------------------------------------------------------
	// Detail screen — cost display
	// -----------------------------------------------------------------------

	public function test_detail_shows_cost_row_when_cost_usd_is_known(): void {
		$usage_data = array(
			'prompt_tokens'     => 1000,
			'completion_tokens' => 500,
			'total_tokens'      => 1500,
			'cost_usd'          => 0.007500,
		);
		$container = $this->build_detail_container( $this->make_run( 'run-abc' ), $usage_data );
		$html      = $this->capture_detail( $container, 'run-abc' );

		$this->assertStringContainsString( 'Estimated cost', $html );
		$this->assertStringContainsString( '$0.007500', $html );
	}

	public function test_detail_shows_token_counts(): void {
		$usage_data = array(
			'prompt_tokens'     => 800,
			'completion_tokens' => 200,
			'total_tokens'      => 1000,
			'cost_usd'          => 0.003,
		);
		$container = $this->build_detail_container( $this->make_run( 'run-abc' ), $usage_data );
		$html      = $this->capture_detail( $container, 'run-abc' );

		$this->assertStringContainsString( 'Token usage', $html );
		// Prompt + completion + total token counts must all appear.
		$this->assertStringContainsString( '800', $html );
		$this->assertStringContainsString( '200', $html );
		$this->assertStringContainsString( '1000', $html );
	}

	public function test_detail_shows_not_available_when_cost_usd_null(): void {
		$usage_data = array(
			'prompt_tokens'     => 500,
			'completion_tokens' => 300,
			'total_tokens'      => 800,
			'cost_usd'          => null,
		);
		$container = $this->build_detail_container( $this->make_run( 'run-xyz' ), $usage_data );
		$html      = $this->capture_detail( $container, 'run-xyz' );

		$this->assertStringContainsString( 'Not available', $html );
		// * "$0.000000" must NOT appear — zero and null have different semantics.
		$this->assertStringNotContainsString( '$0.000000', $html );
	}

	public function test_detail_omits_cost_section_when_no_usage_data(): void {
		$container = $this->build_detail_container( $this->make_run( 'run-empty' ), null );
		$html      = $this->capture_detail( $container, 'run-empty' );

		// No usage_data at all → neither cost row nor token row appears.
		$this->assertStringNotContainsString( 'Estimated cost', $html );
		$this->assertStringNotContainsString( 'Token usage', $html );
	}

	public function test_detail_shows_not_found_when_run_is_null(): void {
		$container = $this->build_detail_container( null, null );
		$html      = $this->capture_detail( $container, 'run-missing' );

		$this->assertStringContainsString( 'Run not found', $html );
	}

	// -----------------------------------------------------------------------
	// List screen — aggregate spend summary widget
	// -----------------------------------------------------------------------

	private function build_list_container_with_spend(
		Provider_Monthly_Spend_Service $spend_svc,
		Provider_Pricing_Registry $registry
	): Service_Container {
		$container = new Service_Container();
		$repo      = new Stub_AI_Run_Repository_Cost( array() );

		$container->register( 'ai_run_repository', fn() => $repo );
		$container->register( 'provider_monthly_spend_service', fn() => $spend_svc );
		$container->register( 'provider_pricing_registry', fn() => $registry );
		return $container;
	}

	/** Captures AI_Runs_Screen list output. */
	private function capture_list( Service_Container $container ): string {
		$screen = new AI_Runs_Screen( $container );
		ob_start();
		$screen->render();
		return (string) ob_get_clean();
	}

	private function make_spend_service_no_cap(): Provider_Monthly_Spend_Service {
		$GLOBALS['__spend_test_options'] = array();
		$cap_settings = new Provider_Spend_Cap_Settings();
		return new Provider_Monthly_Spend_Service( $cap_settings );
	}

	public function test_list_renders_spend_summary_section(): void {
		$spend_svc = $this->make_spend_service_no_cap();
		$registry  = new Provider_Pricing_Registry();
		$container = $this->build_list_container_with_spend( $spend_svc, $registry );

		$html = $this->capture_list( $container );

		// Section heading must appear when provider_monthly_spend_service is registered.
		$this->assertStringContainsString( 'Month-to-date spend', $html );
	}

	public function test_list_spend_shows_provider_names(): void {
		$spend_svc = $this->make_spend_service_no_cap();
		$registry  = new Provider_Pricing_Registry();
		$container = $this->build_list_container_with_spend( $spend_svc, $registry );

		$html = $this->capture_list( $container );

		// Both providers from the pricing registry must appear in the spend table.
		$this->assertStringContainsString( 'openai', $html );
		$this->assertStringContainsString( 'anthropic', $html );
	}

	public function test_list_spend_shows_no_cap_set_when_cap_is_zero(): void {
		$spend_svc = $this->make_spend_service_no_cap();
		$registry  = new Provider_Pricing_Registry();
		$container = $this->build_list_container_with_spend( $spend_svc, $registry );

		$html = $this->capture_list( $container );

		$this->assertStringContainsString( 'No cap set', $html );
	}

	public function test_list_spend_shows_approaching_when_over_threshold(): void {
		$GLOBALS['__spend_test_options'] = array();
		$cap_settings = new Provider_Spend_Cap_Settings();
		$spend_svc    = new Provider_Monthly_Spend_Service( $cap_settings );
		$cap_settings->save_settings( 'openai', 10.0, false );
		$spend_svc->record_run_cost( 'openai', 9.0 );

		$registry  = new Provider_Pricing_Registry();
		$container = $this->build_list_container_with_spend( $spend_svc, $registry );

		$html = $this->capture_list( $container );

		$this->assertStringContainsString( 'Approaching cap', $html );
	}

	public function test_list_spend_shows_cap_exceeded_when_exceeded(): void {
		$GLOBALS['__spend_test_options'] = array();
		$cap_settings = new Provider_Spend_Cap_Settings();
		$spend_svc    = new Provider_Monthly_Spend_Service( $cap_settings );
		$cap_settings->save_settings( 'openai', 5.0, false );
		$spend_svc->record_run_cost( 'openai', 6.0 );

		$registry  = new Provider_Pricing_Registry();
		$container = $this->build_list_container_with_spend( $spend_svc, $registry );

		$html = $this->capture_list( $container );

		$this->assertStringContainsString( 'Cap exceeded', $html );
	}

	public function test_list_spend_shows_dollar_spent_value(): void {
		$GLOBALS['__spend_test_options'] = array();
		$cap_settings = new Provider_Spend_Cap_Settings();
		$spend_svc    = new Provider_Monthly_Spend_Service( $cap_settings );
		$spend_svc->record_run_cost( 'openai', 2.5 );

		$registry  = new Provider_Pricing_Registry();
		$container = $this->build_list_container_with_spend( $spend_svc, $registry );

		$html = $this->capture_list( $container );

		// Spent value formatted to 4dp should appear.
		$this->assertStringContainsString( '$2.5000', $html );
	}

	public function test_list_omits_spend_widget_when_service_not_registered(): void {
		$container = new Service_Container();
		$repo      = new Stub_AI_Run_Repository_Cost( array() );
		$container->register( 'ai_run_repository', fn() => $repo );
		// * No provider_monthly_spend_service or provider_pricing_registry registered.

		$html = $this->capture_list( $container );

		$this->assertStringNotContainsString( 'Month-to-date spend', $html );
	}
}
