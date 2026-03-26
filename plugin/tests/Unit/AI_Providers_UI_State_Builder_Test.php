<?php
/**
 * Unit tests for AI_Providers_UI_State_Builder: state shape, no secrets, disclosure (spec §49.9).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Providers\Drivers\Provider_Connection_Test_Service;
use AIOPageBuilder\Domain\AI\Providers\Provider_Capability_Resolver;
use AIOPageBuilder\Domain\AI\Providers\Provider_Request_Context_Builder;
use AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface;
use AIOPageBuilder\Domain\AI\UI\AI_Providers_UI_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Secrets/Provider_Secret_Store_Interface.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Capability_Resolver.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Request_Context_Builder.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Infrastructure/Container/Service_Container.php';
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Drivers/Provider_Connection_Test_Service.php';
require_once $plugin_root . '/src/Domain/AI/Validation/Build_Plan_Draft_Schema.php';
require_once $plugin_root . '/src/Domain/AI/UI/AI_Providers_UI_State_Builder.php';

final class AI_Providers_UI_State_Builder_Test extends TestCase {

	public function test_build_returns_expected_keys(): void {
		$state = $this->build_state_with_mocks();
		$this->assertArrayHasKey( 'provider_rows', $state );
		$this->assertArrayHasKey( 'disclosure_blocks', $state );
		$this->assertArrayHasKey( 'ai_runs_url', $state );
		$this->assertIsArray( $state['provider_rows'] );
		$this->assertIsArray( $state['disclosure_blocks'] );
		$this->assertIsString( $state['ai_runs_url'] );
	}

	public function test_build_disclosure_blocks_contain_external_transfer_and_cost(): void {
		$state    = $this->build_state_with_mocks();
		$headings = array_column( $state['disclosure_blocks'], 'heading' );
		$this->assertContains( 'External transfer', $headings );
		$this->assertContains( 'Cost', $headings );
	}

	public function test_build_provider_rows_have_credential_status_and_no_raw_secrets(): void {
		$state = $this->build_state_with_mocks();
		$this->assertNotEmpty( $state['provider_rows'], 'Default provider list should include at least openai' );
		foreach ( $state['provider_rows'] as $row ) {
			$this->assertArrayHasKey( 'credential_status', $row );
			$this->assertArrayHasKey( 'state', $row['credential_status'] );
			$this->assertArrayHasKey( 'label', $row['credential_status'] );
			$this->assertArrayHasKey( 'model_default_state', $row );
			$this->assertArrayHasKey( 'connection_test_summary', $row );
			$this->assertArrayHasKey( 'last_successful_use', $row );
			$this->assertArrayNotHasKey( 'api_key', $row );
			$this->assertArrayNotHasKey( 'secret', $row );
		}
	}

	public function test_build_state_contains_no_secret_like_values(): void {
		$state = $this->build_state_with_mocks();
		$this->assertNoSecretKeysInArray( $state );
	}

	public function test_build_credential_trust_banner_none_when_no_stored_credentials(): void {
		$builder = $this->make_builder_with_secret_has_credential( false );
		$banner  = $builder->build_credential_trust_banner();
		$this->assertSame( 'none', $banner['trust_level'] );
		$this->assertSame( 'aio-ai-credential-trust-none', $banner['trust_level_id'] );
		$this->assertArrayHasKey( 'summary', $banner );
		$this->assertArrayHasKey( 'detail', $banner );
		$this->assertNoSecretKeysInArray( $banner );
	}

	public function test_build_credential_trust_banner_stored_when_key_present_without_successful_test(): void {
		$builder = $this->make_builder_with_secret_has_credential( true );
		$banner  = $builder->build_credential_trust_banner();
		$this->assertSame( 'stored', $banner['trust_level'] );
		$this->assertSame( 'aio-ai-credential-trust-stored', $banner['trust_level_id'] );
		$this->assertNoSecretKeysInArray( $banner );
	}

	public function test_build_credential_trust_banner_validated_when_key_and_successful_test(): void {
		$GLOBALS['_aio_test_options'][ Option_Names::PB_AI_PROVIDERS ] = array(
			'openai' => array(
				'last_test_status' => 'success',
			),
		);
		$builder = $this->make_builder_with_secret_has_credential( true );
		$banner  = $builder->build_credential_trust_banner();
		$this->assertSame( 'validated', $banner['trust_level'] );
		$this->assertSame( 'aio-ai-credential-trust-validated', $banner['trust_level_id'] );
		$this->assertNoSecretKeysInArray( $banner );
		unset( $GLOBALS['_aio_test_options'][ Option_Names::PB_AI_PROVIDERS ] );
	}

	/**
	 * Documents and asserts the stable AI Providers screen state payload shape (spec §49.9).
	 * Example payload (no pseudocode):
	 *
	 * Provider_rows: [ { provider_id: "openai", label: "OpenAI", credential_status: { state: "absent", label: "Not configured" },
	 *   model_default_state: { model_id: null, label: "—" }, connection_test_summary: null, last_successful_use: null } ]
	 * disclosure_blocks: [ { heading: "External transfer", content: "When you use AI providers..." }, { heading: "Cost", content: "AI requests consume tokens..." } ]
	 * ai_runs_url: "http://example.org/wp-admin/admin.php?page=aio-page-builder-ai-workspace&aio_tab=ai_runs"
	 */
	public function test_example_state_payload_structure(): void {
		$state = $this->build_state_with_mocks();
		$this->assertCount( 3, array_keys( $state ), 'State must have exactly provider_rows, disclosure_blocks, ai_runs_url' );
		$row = $state['provider_rows'][0] ?? null;
		$this->assertNotNull( $row );
		$this->assertSame( 'openai', $row['provider_id'] );
		$this->assertSame( 'OpenAI', $row['label'] );
		$this->assertSame( 'absent', $row['credential_status']['state'] );
		$this->assertArrayHasKey( 'heading', $state['disclosure_blocks'][0] );
		$this->assertArrayHasKey( 'content', $state['disclosure_blocks'][0] );
		$this->assertStringContainsString( 'aio-page-builder-ai-workspace', $state['ai_runs_url'] );
		$this->assertStringContainsString( 'aio_tab=ai_runs', $state['ai_runs_url'] );
	}

	/**
	 * Asserts that no key in the state is a known secret-bearing key name (exact match).
	 *
	 * @param array<string, mixed> $arr State array to check for forbidden key names.
	 */
	private function assertNoSecretKeysInArray( array $arr ): void {
		$forbidden = array( 'api_key', 'secret', 'token', 'password', 'apikey' );
		foreach ( $arr as $key => $value ) {
			$lower = strtolower( (string) $key );
			$this->assertNotContains( $lower, $forbidden, "State must not contain secret key: {$key}" );
			if ( is_array( $value ) ) {
				$this->assertNoSecretKeysInArray( $value );
			}
		}
	}

	/**
	 * Builds state using real connection test and settings (final classes); only secret store is mocked.
	 *
	 * @return array{provider_rows: list<array>, disclosure_blocks: list<array>, ai_runs_url: string}
	 */
	private function build_state_with_mocks(): array {
		$settings = new Settings_Service();

		$connection_test = new Provider_Connection_Test_Service(
			new Provider_Request_Context_Builder(),
			new Provider_Capability_Resolver(),
			$settings
		);

		$secret_store = $this->createMock( Provider_Secret_Store_Interface::class );
		$secret_store->method( 'get_credential_state' )->willReturn( Provider_Secret_Store_Interface::STATE_ABSENT );

		$container = new Service_Container();

		$builder = new AI_Providers_UI_State_Builder(
			$connection_test,
			$secret_store,
			new Provider_Capability_Resolver(),
			$settings,
			$container
		);
		return $builder->build();
	}

	/**
	 * @param bool $has_cred Whether the secret store reports a stored credential for known providers (e.g. openai).
	 */
	private function make_builder_with_secret_has_credential( bool $has_cred ): AI_Providers_UI_State_Builder {
		$settings = new Settings_Service();

		$connection_test = new Provider_Connection_Test_Service(
			new Provider_Request_Context_Builder(),
			new Provider_Capability_Resolver(),
			$settings
		);

		$secret_store = $this->createMock( Provider_Secret_Store_Interface::class );
		$secret_store->method( 'has_credential' )->willReturn( $has_cred );
		$secret_store->method( 'get_credential_state' )->willReturn(
			$has_cred ? Provider_Secret_Store_Interface::STATE_PENDING_VALIDATION : Provider_Secret_Store_Interface::STATE_ABSENT
		);

		$container = new Service_Container();

		return new AI_Providers_UI_State_Builder(
			$connection_test,
			$secret_store,
			new Provider_Capability_Resolver(),
			$settings,
			$container
		);
	}
}
