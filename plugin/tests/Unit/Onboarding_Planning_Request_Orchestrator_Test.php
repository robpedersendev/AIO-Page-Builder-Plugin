<?php
/**
 * Unit tests for Onboarding_Planning_Request_Orchestrator: blocked states, UI-safe result payloads (spec §49.8, §59.8).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Draft_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Planning_Request_Orchestrator;
use AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Step_Keys;
use AIOPageBuilder\Domain\AI\Onboarding\Planning_Request_Result;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Service;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Service;
use AIOPageBuilder\Domain\AI\Validation\AI_Output_Validator;
use AIOPageBuilder\Domain\AI\InputArtifacts\Input_Artifact_Builder;
use AIOPageBuilder\Domain\AI\PromptPacks\Normalized_Prompt_Package_Builder;
use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Registry_Service;
use AIOPageBuilder\Domain\AI\Providers\Failover\Provider_Failover_Service;
use AIOPageBuilder\Domain\AI\Providers\Provider_Capability_Resolver;
use AIOPageBuilder\Domain\AI\Providers\Provider_Request_Context_Builder;
use AIOPageBuilder\Domain\AI\Providers\Drivers\Provider_Connection_Test_Service;
use AIOPageBuilder\Domain\Storage\Repositories\AI_Run_Repository;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Draft_Service.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Step_Keys.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Statuses.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Planning_Request_Result.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Planning_Request_Orchestrator.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Prefill_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Normalizer.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Store.php';
require_once $plugin_root . '/src/Infrastructure/Container/Service_Container.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Pack_Registry_Service.php';
require_once $plugin_root . '/src/Domain/AI/Runs/AI_Run_Service.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Drivers/Provider_Connection_Test_Service.php';
require_once $plugin_root . '/src/Domain/AI/InputArtifacts/Input_Artifact_Builder.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Normalized_Prompt_Package_Builder.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Request_Context_Builder.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Capability_Resolver.php';
require_once $plugin_root . '/src/Domain/AI/Validation/Build_Plan_Draft_Schema.php';
require_once $plugin_root . '/src/Domain/AI/Validation/Normalized_Output_Builder.php';
require_once $plugin_root . '/src/Domain/AI/Validation/Validation_Report.php';
require_once $plugin_root . '/src/Domain/AI/Validation/Dropped_Record_Report.php';
require_once $plugin_root . '/src/Domain/AI/Validation/AI_Output_Validator.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Pack_Registry_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/AI/Runs/Artifact_Category_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/AI_Run_Repository.php';
require_once $plugin_root . '/src/Domain/AI/Runs/AI_Run_Artifact_Service.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Drivers/Provider_Connection_Test_Result.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Response_Normalizer.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Failover/Provider_Failover_Policy.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Failover/Failover_Result.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Failover/Provider_Failover_Service.php';

/** Stub repository: no packs, so select_for_planning returns null when we get that far. */
final class Stub_Prompt_Pack_Repo_For_Orchestrator implements \AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Registry_Repository_Interface {
	public function get_definition_by_key_and_version( string $internal_key, string $version ): ?array {
		return null;
	}
	public function get_definition_by_key( string $internal_key ): ?array {
		return null;
	}
	public function list_definitions_by_status( string $status, int $limit = 0, int $offset = 0 ): array {
		return array();
	}
}

final class Onboarding_Planning_Request_Orchestrator_Test extends TestCase {

	/** Builds orchestrator with real draft/prefill; provider config is empty so is_provider_ready() is false. */
	private function orchestrator_with_real_services( string $current_step ): Onboarding_Planning_Request_Orchestrator {
		$settings                  = new \AIOPageBuilder\Infrastructure\Settings\Settings_Service();
		$draft_service             = new Onboarding_Draft_Service( $settings );
		$draft                     = $draft_service->get_draft();
		$draft['current_step_key'] = $current_step;
		$draft_service->save_draft( $draft );

		$profile_normalizer = new \AIOPageBuilder\Domain\Storage\Profile\Profile_Normalizer();
		$profile_store      = new \AIOPageBuilder\Domain\Storage\Profile\Profile_Store( $settings, $profile_normalizer );
		$secret_store       = $this->createMock( Provider_Secret_Store_Interface::class );
		$secret_store->method( 'get_credential_state' )->willReturn( Provider_Secret_Store_Interface::STATE_ABSENT );
		$secret_store->method( 'has_credential' )->willReturn( false );
		$prefill            = new \AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Prefill_Service( $profile_store, $settings, null, $secret_store );

		$prompt_pack_registry    = new Prompt_Pack_Registry_Service( new Stub_Prompt_Pack_Repo_For_Orchestrator() );
		$container               = new Service_Container();
		$run_repo                = new AI_Run_Repository();
		$artifact_service        = new AI_Run_Artifact_Service( $run_repo );
		$ai_run_service          = new AI_Run_Service( $run_repo, $artifact_service );
		$request_context_builder = new Provider_Request_Context_Builder();
		$capability_resolver     = new Provider_Capability_Resolver();
		$connection_test_service = new Provider_Connection_Test_Service( $request_context_builder, $capability_resolver, $settings );
		$failover_service        = new Provider_Failover_Service( $settings, $capability_resolver );
		return new Onboarding_Planning_Request_Orchestrator(
			$draft_service,
			$prefill,
			$prompt_pack_registry,
			new Input_Artifact_Builder(),
			new Normalized_Prompt_Package_Builder(),
			new Provider_Request_Context_Builder(),
			new Provider_Capability_Resolver(),
			new AI_Output_Validator(),
			$ai_run_service,
			$connection_test_service,
			$failover_service,
			$container,
			null,
			null,
			null
		);
	}

	public function test_submit_returns_blocked_when_not_on_submission_step(): void {
		$orchestrator = $this->orchestrator_with_real_services( Onboarding_Step_Keys::REVIEW );
		$result       = $orchestrator->submit();
		$this->assertInstanceOf( Planning_Request_Result::class, $result );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( Planning_Request_Result::STATUS_BLOCKED, $result->get_status() );
		$this->assertSame( '', $result->get_run_id() );
		$this->assertSame( 0, $result->get_run_post_id() );
		$this->assertSame( 'not_on_submission_step', $result->get_blocking_reason() );
	}

	public function test_submit_returns_blocked_when_provider_not_ready(): void {
		$orchestrator = $this->orchestrator_with_real_services( Onboarding_Step_Keys::SUBMISSION );
		$result       = $orchestrator->submit();
		$this->assertFalse( $result->is_success() );
		$this->assertSame( Planning_Request_Result::STATUS_BLOCKED, $result->get_status() );
		$this->assertSame( 'provider_not_ready', $result->get_blocking_reason() );
	}

	public function test_result_payload_is_ui_safe(): void {
		$orchestrator = $this->orchestrator_with_real_services( Onboarding_Step_Keys::SUBMISSION );
		$result       = $orchestrator->submit();
		$arr          = $result->to_array();
		$this->assertArrayHasKey( 'success', $arr );
		$this->assertArrayHasKey( 'status', $arr );
		$this->assertArrayHasKey( 'user_message', $arr );
		$this->assertArrayHasKey( 'run_id', $arr );
		$this->assertArrayHasKey( 'run_post_id', $arr );
		$this->assertArrayNotHasKey( 'api_key', $arr );
		$this->assertArrayNotHasKey( 'credential', $arr );
	}
}
