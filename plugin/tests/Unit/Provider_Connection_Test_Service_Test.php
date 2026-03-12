<?php
/**
 * Unit tests for Provider_Connection_Test_Service: successful test, invalid credential, last-successful-use (spec §49.9).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Providers\Drivers\Provider_Connection_Test_Result;
use AIOPageBuilder\Domain\AI\Providers\Drivers\Provider_Connection_Test_Service;
use AIOPageBuilder\Domain\AI\Providers\Provider_Capability_Resolver;
use AIOPageBuilder\Domain\AI\Providers\Provider_Request_Context_Builder;
use AIOPageBuilder\Domain\AI\Providers\Provider_Response_Normalizer;
use AIOPageBuilder\Domain\AI\Providers\Stub_AI_Provider_Driver;
use AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Response_Normalizer.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Error_Normalizer.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Request_Context_Builder.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Capability_Resolver.php';
require_once $plugin_root . '/src/Domain/AI/Providers/AI_Provider_Interface.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Abstract_AI_Provider_Driver.php';
require_once $plugin_root . '/src/Domain/AI/Secrets/Provider_Secret_Store_Interface.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Stub_AI_Provider_Driver.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Drivers/Provider_Connection_Test_Result.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Drivers/Provider_Connection_Test_Service.php';

final class Provider_Connection_Test_Service_Test extends TestCase {

	private function get_settings(): Settings_Service {
		return new Settings_Service();
	}

	private function stub_store_with_credential(): Provider_Secret_Store_Interface {
		$store = new class() implements Provider_Secret_Store_Interface {
			private array $creds = array( 'stub' => 'key' );
			private array $states = array( 'stub' => Provider_Secret_Store_Interface::STATE_CONFIGURED );

			public function get_credential_for_provider( string $provider_id ): ?string {
				return $this->creds[ $provider_id ] ?? null;
			}
			public function get_credential_state( string $provider_id ): string {
				return $this->states[ $provider_id ] ?? self::STATE_ABSENT;
			}
			public function has_credential( string $provider_id ): bool {
				return isset( $this->creds[ $provider_id ] );
			}
			public function set_credential( string $provider_id, string $value ): bool {
				$this->creds[ $provider_id ]   = $value;
				$this->states[ $provider_id ] = self::STATE_PENDING_VALIDATION;
				return true;
			}
			public function delete_credential( string $provider_id ): bool {
				$had = isset( $this->creds[ $provider_id ] );
				unset( $this->creds[ $provider_id ], $this->states[ $provider_id ] );
				return $had;
			}
		};
		return $store;
	}

	private function stub_store_without_credential(): Provider_Secret_Store_Interface {
		$store = new class() implements Provider_Secret_Store_Interface {
			public function get_credential_for_provider( string $provider_id ): ?string {
				return null;
			}
			public function get_credential_state( string $provider_id ): string {
				return self::STATE_ABSENT;
			}
			public function has_credential( string $provider_id ): bool {
				return false;
			}
			public function set_credential( string $provider_id, string $value ): bool {
				return true;
			}
			public function delete_credential( string $provider_id ): bool {
				return false;
			}
		};
		return $store;
	}

	public function test_successful_connection_test_returns_success_and_persists(): void {
		$store  = $this->stub_store_with_credential();
		$driver = new Stub_AI_Provider_Driver( $store );
		$driver->set_success_result( array( 'structured_payload' => array( 'content' => 'ok' ) ) );

		$settings = $this->get_settings();
		$service  = new Provider_Connection_Test_Service(
			new Provider_Request_Context_Builder(),
			new Provider_Capability_Resolver(),
			$settings
		);

		$result = $service->run_test( $driver );
		$this->assertInstanceOf( Provider_Connection_Test_Result::class, $result );
		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'stub', $result->get_provider_id() );
		$this->assertSame( 'Connection successful.', $result->get_user_message() );

		$last = $service->get_last_result( 'stub' );
		$this->assertNotNull( $last );
		$this->assertTrue( $last->is_success() );
		$this->assertNotNull( $service->get_last_successful_use( 'stub' ) );
	}

	public function test_invalid_credential_failure_returns_normalized_error(): void {
		$store  = $this->stub_store_without_credential();
		$driver = new Stub_AI_Provider_Driver( $store );

		$settings = $this->get_settings();
		$service  = new Provider_Connection_Test_Service(
			new Provider_Request_Context_Builder(),
			new Provider_Capability_Resolver(),
			$settings
		);

		$result = $service->run_test( $driver );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'stub', $result->get_provider_id() );
		$this->assertNotNull( $result->get_normalized_error() );
		$this->assertSame( Provider_Response_Normalizer::ERROR_AUTH_FAILURE, $result->get_normalized_error()['category'] );
	}

	public function test_driver_error_result_maps_to_normalized_error(): void {
		$store  = $this->stub_store_with_credential();
		$driver = new Stub_AI_Provider_Driver( $store );
		$driver->set_error_result( 429, 'rate_limit_exceeded', 'Rate limit exceeded' );

		$settings = $this->get_settings();
		$service  = new Provider_Connection_Test_Service(
			new Provider_Request_Context_Builder(),
			new Provider_Capability_Resolver(),
			$settings
		);

		$result = $service->run_test( $driver );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( Provider_Response_Normalizer::ERROR_RATE_LIMIT, $result->get_normalized_error()['category'] );
	}

	public function test_record_and_get_last_successful_use(): void {
		$settings = $this->get_settings();
		$service  = new Provider_Connection_Test_Service(
			new Provider_Request_Context_Builder(),
			new Provider_Capability_Resolver(),
			$settings
		);

		$this->assertNull( $service->get_last_successful_use( 'openai' ) );
		$ts = '2025-07-15T15:00:00+00:00';
		$service->record_last_successful_use( 'openai', $ts );
		$this->assertSame( $ts, $service->get_last_successful_use( 'openai' ) );
	}
}
