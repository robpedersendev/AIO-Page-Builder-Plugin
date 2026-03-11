<?php
/**
 * Unit tests for Module_Registrar bootstrap wiring.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Bootstrap\Constants;
use AIOPageBuilder\Bootstrap\Module_Registrar;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Bootstrap/Constants.php';
Constants::init();
require_once $plugin_root . '/src/Infrastructure/Container/Service_Provider_Interface.php';
require_once $plugin_root . '/src/Infrastructure/Container/Service_Container.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Infrastructure/Config/Plugin_Config.php';
require_once $plugin_root . '/src/Support/Logging/Log_Severities.php';
require_once $plugin_root . '/src/Support/Logging/Log_Categories.php';
require_once $plugin_root . '/src/Support/Logging/Error_Record.php';
require_once $plugin_root . '/src/Support/Logging/Logger_Interface.php';
require_once $plugin_root . '/src/Support/Logging/Null_Logger.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/Config_Provider.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/Diagnostics_Provider.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/Admin_Router_Provider.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/Capability_Provider.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Post_Type_Registrar.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/Object_Registration_Provider.php';
require_once $plugin_root . '/src/Domain/Storage/Tables/Table_Names.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_Table_Repository.php';
require_once $plugin_root . '/src/Domain/Crawler/Snapshots/Crawl_Snapshot_Payload_Builder.php';
require_once $plugin_root . '/src/Domain/Crawler/Snapshots/Crawl_Snapshot_Repository.php';
require_once $plugin_root . '/src/Domain/Crawler/Snapshots/Crawl_Snapshot_Service.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/Crawler_Provider.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Pack_Registry_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Prompt_Pack_Repository.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/Repositories_Provider.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/ACF_Blueprints_Provider.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/ACF_Registration_Provider.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/ACF_Assignment_Provider.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/ACF_Compatibility_Provider.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/ACF_Diagnostics_Provider.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/Rendering_Provider.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/Registries_Provider.php';
require_once $plugin_root . '/src/Domain/AI/Validation/Build_Plan_Draft_Schema.php';
require_once $plugin_root . '/src/Domain/AI/Validation/Dropped_Record_Report.php';
require_once $plugin_root . '/src/Domain/AI/Validation/Validation_Report.php';
require_once $plugin_root . '/src/Domain/AI/Validation/Normalized_Output_Builder.php';
require_once $plugin_root . '/src/Domain/AI/Validation/AI_Output_Validator.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/AI_Validation_Provider.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Response_Normalizer.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Error_Normalizer.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Request_Context_Builder.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Capability_Resolver.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/AI_Provider_Base_Provider.php';
require_once $plugin_root . '/src/Domain/AI/InputArtifacts/Input_Artifact_Schema.php';
require_once $plugin_root . '/src/Domain/AI/InputArtifacts/Input_Artifact_Builder.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Package_Result.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Normalized_Prompt_Package_Builder.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Pack_Registry_Service.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/AI_Prompt_Pack_Provider.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/Onboarding_Provider.php';
require_once $plugin_root . '/src/Infrastructure/Container/Providers/Storage_Services_Provider.php';
require_once $plugin_root . '/src/Bootstrap/Module_Registrar.php';

/**
 * Tests that bootstrap wiring uses the registrar and registers expected service IDs.
 */
final class Module_Registrar_Test extends TestCase {

	public function test_registrar_registers_bootstrap_services(): void {
		$container = new Service_Container();
		$registrar = new Module_Registrar( $container );
		$registrar->register_bootstrap();
		$this->assertTrue( $container->has( 'config' ) );
		$this->assertTrue( $container->has( 'diagnostics' ) );
		$this->assertTrue( $container->has( 'admin_router' ) );
		$this->assertTrue( $container->has( 'capabilities' ) );
		$this->assertTrue( $container->has( 'post_type_registrar' ) );
		$this->assertTrue( $container->has( 'ai_output_validator' ) );
		$this->assertTrue( $container->has( 'normalized_output_builder' ) );
		$this->assertTrue( $container->has( 'provider_response_normalizer' ) );
		$this->assertTrue( $container->has( 'provider_error_normalizer' ) );
		$this->assertTrue( $container->has( 'provider_request_context_builder' ) );
		$this->assertTrue( $container->has( 'provider_capability_resolver' ) );
		$this->assertTrue( $container->has( 'prompt_pack_registry_service' ) );
		$this->assertTrue( $container->has( 'input_artifact_builder' ) );
		$this->assertTrue( $container->has( 'normalized_prompt_package_builder' ) );
	}

	public function test_config_service_resolves_and_exposes_versions(): void {
		$container = new Service_Container();
		$registrar = new Module_Registrar( $container );
		$registrar->register_bootstrap();
		$config = $container->get( 'config' );
		$this->assertIsObject( $config );
		$this->assertSame( Constants::plugin_version(), $config->plugin_version() );
		$versions = $config->versions();
		$this->assertArrayHasKey( 'plugin', $versions );
		$this->assertArrayHasKey( 'global_schema', $versions );
		$this->assertArrayHasKey( 'registry_schema', $versions );
	}

	public function test_bootstrap_services_are_singletons(): void {
		$container = new Service_Container();
		$registrar = new Module_Registrar( $container );
		$registrar->register_bootstrap();
		$this->assertSame( $container->get( 'config' ), $container->get( 'config' ) );
		$this->assertSame( $container->get( 'diagnostics' ), $container->get( 'diagnostics' ) );
	}
}
