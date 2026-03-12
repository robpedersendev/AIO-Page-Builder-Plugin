<?php
/**
 * Builds UI state for the AI Providers screen (spec §49.9). No secrets; credential status only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Providers\AI_Provider_Interface;
use AIOPageBuilder\Domain\AI\Providers\Drivers\Provider_Connection_Test_Result;
use AIOPageBuilder\Domain\AI\Providers\Drivers\Provider_Connection_Test_Service;
use AIOPageBuilder\Domain\AI\Providers\Provider_Capability_Resolver;
use AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface;
use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

/**
 * Assembles provider list, credential status, model defaults, connection-test summary, last successful use, disclosure.
 * All payloads are safe for admin display; no raw keys.
 */
final class AI_Providers_UI_State_Builder {

	/** Default provider IDs when config has none (drivers available in container). */
	private const DEFAULT_PROVIDER_IDS = array( 'openai' );

	/** @var Provider_Connection_Test_Service */
	private Provider_Connection_Test_Service $connection_test_service;

	/** @var Provider_Secret_Store_Interface */
	private Provider_Secret_Store_Interface $secret_store;

	/** @var Provider_Capability_Resolver */
	private Provider_Capability_Resolver $capability_resolver;

	/** @var Settings_Service */
	private Settings_Service $settings;

	/** @var Service_Container */
	private Service_Container $container;

	public function __construct(
		Provider_Connection_Test_Service $connection_test_service,
		Provider_Secret_Store_Interface $secret_store,
		Provider_Capability_Resolver $capability_resolver,
		Settings_Service $settings,
		Service_Container $container
	) {
		$this->connection_test_service = $connection_test_service;
		$this->secret_store           = $secret_store;
		$this->capability_resolver    = $capability_resolver;
		$this->settings               = $settings;
		$this->container              = $container;
	}

	/**
	 * Builds full UI state for the AI Providers screen.
	 *
	 * @return array{provider_rows: list<array>, disclosure_blocks: list<array>, ai_runs_url: string}
	 */
	public function build(): array {
		$provider_ids = $this->get_known_provider_ids();
		$provider_rows = array();
		foreach ( $provider_ids as $provider_id ) {
			$provider_rows[] = $this->build_provider_row( $provider_id );
		}
		$disclosure_blocks = $this->build_disclosure_blocks();
		$ai_runs_url = \add_query_arg( array( 'page' => 'aio-page-builder-ai-runs' ), \admin_url( 'admin.php' ) );
		return array(
			'provider_rows'    => $provider_rows,
			'disclosure_blocks' => $disclosure_blocks,
			'ai_runs_url'      => $ai_runs_url,
		);
	}

	/**
	 * @return list<string>
	 */
	private function get_known_provider_ids(): array {
		$config = $this->settings->get( Option_Names::PROVIDER_CONFIG_REF );
		$from_config = array();
		if ( isset( $config['providers'] ) && is_array( $config['providers'] ) ) {
			foreach ( $config['providers'] as $p ) {
				if ( is_array( $p ) && isset( $p['provider_id'] ) && is_string( $p['provider_id'] ) ) {
					$from_config[] = \sanitize_text_field( $p['provider_id'] );
				}
			}
		}
		$merged = array_merge( self::DEFAULT_PROVIDER_IDS, $from_config );
		$unique = array_unique( $merged );
		return array_values( $unique );
	}

	/**
	 * Builds one provider_row: provider_id, label, credential_status, model_default_state, connection_test_summary, last_successful_use.
	 *
	 * @param string $provider_id
	 * @return array{provider_id: string, label: string, credential_status: array, model_default_state: array, connection_test_summary: array|null, last_successful_use: string|null}
	 */
	private function build_provider_row( string $provider_id ): array {
		$credential_status = $this->build_credential_status( $provider_id );
		$model_default_state = $this->build_model_default_state( $provider_id );
		$connection_test_summary = $this->build_connection_test_summary( $provider_id );
		$last_successful_use = $this->connection_test_service->get_last_successful_use( $provider_id );
		$label = $this->get_provider_label( $provider_id );
		return array(
			'provider_id'             => $provider_id,
			'label'                   => $label,
			'credential_status'       => $credential_status,
			'model_default_state'     => $model_default_state,
			'connection_test_summary' => $connection_test_summary,
			'last_successful_use'      => $last_successful_use,
		);
	}

	/**
	 * @param string $provider_id
	 * @return array{state: string, label: string}
	 */
	private function build_credential_status( string $provider_id ): array {
		$state = $this->secret_store->get_credential_state( $provider_id );
		$label = $this->get_credential_state_label( $state );
		return array(
			'state' => $state,
			'label' => $label,
		);
	}

	/**
	 * @param string $provider_id
	 * @return array{model_id: string|null, label: string}
	 */
	private function build_model_default_state( string $provider_id ): array {
		$driver = $this->get_driver_for_provider( $provider_id );
		if ( $driver === null ) {
			return array( 'model_id' => null, 'label' => __( '—', 'aio-page-builder' ) );
		}
		$schema_ref = Build_Plan_Draft_Schema::SCHEMA_REF;
		$model_id = $this->capability_resolver->resolve_default_model_for_planning( $driver, $schema_ref );
		$label = $model_id !== null && $model_id !== '' ? $model_id : __( 'No default', 'aio-page-builder' );
		return array(
			'model_id' => $model_id,
			'label'   => $label,
		);
	}

	/**
	 * @param string $provider_id
	 * @return array{success: bool, tested_at: string, user_message: string, model_used: string}|null
	 */
	private function build_connection_test_summary( string $provider_id ): ?array {
		$result = $this->connection_test_service->get_last_result( $provider_id );
		if ( $result === null ) {
			return null;
		}
		return array(
			'success'      => $result->is_success(),
			'tested_at'    => $result->get_tested_at(),
			'user_message' => $result->get_user_message(),
			'model_used'   => $result->get_model_used(),
		);
	}

	/**
	 * @param string $provider_id
	 * @return AI_Provider_Interface|null
	 */
	private function get_driver_for_provider( string $provider_id ): ?AI_Provider_Interface {
		if ( $provider_id === 'openai' && $this->container->has( 'openai_provider_driver' ) ) {
			return $this->container->get( 'openai_provider_driver' );
		}
		return null;
	}

	private function get_provider_label( string $provider_id ): string {
		$labels = array(
			'openai' => 'OpenAI',
		);
		return $labels[ $provider_id ] ?? $provider_id;
	}

	private function get_credential_state_label( string $state ): string {
		$labels = array(
			Provider_Secret_Store_Interface::STATE_ABSENT             => __( 'Not configured', 'aio-page-builder' ),
			Provider_Secret_Store_Interface::STATE_CONFIGURED       => __( 'Configured', 'aio-page-builder' ),
			Provider_Secret_Store_Interface::STATE_INVALID           => __( 'Invalid', 'aio-page-builder' ),
			Provider_Secret_Store_Interface::STATE_ROTATED           => __( 'Rotated', 'aio-page-builder' ),
			Provider_Secret_Store_Interface::STATE_PENDING_VALIDATION => __( 'Pending validation', 'aio-page-builder' ),
		);
		return $labels[ $state ] ?? $state;
	}

	/**
	 * Disclosure blocks for external transfer and cost (spec §49.9). Must remain visible.
	 *
	 * @return list<array{heading: string, content: string}>
	 */
	private function build_disclosure_blocks(): array {
		return array(
			array(
				'heading' => __( 'External transfer', 'aio-page-builder' ),
				'content' => __( 'When you use AI providers, your profile and site context are sent to the provider’s API. Responses are returned over the network. Do not include sensitive data in prompts. Keys are stored locally and never displayed in full after save.', 'aio-page-builder' ),
			),
			array(
				'heading' => __( 'Cost', 'aio-page-builder' ),
				'content' => __( 'AI requests consume tokens and may incur cost according to the provider’s pricing. Run connection tests and planning requests only when needed. This plugin does not manage billing or quotas.', 'aio-page-builder' ),
			),
		);
	}
}
