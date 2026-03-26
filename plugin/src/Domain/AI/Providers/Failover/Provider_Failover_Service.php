<?php
/**
 * Optional provider failover and retry policy layer (spec §25.1, §25.5, §29.6, §45.1, Prompt 119).
 * Routes a planning request to a secondary configured provider when the primary fails in an approved, bounded way.
 * Failover remains explicit, logged, and policy-bound; same validator pipeline applies to fallback output.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Providers\Failover;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Providers\AI_Provider_Interface;
use AIOPageBuilder\Domain\AI\Providers\Provider_Capability_Resolver;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Resolves failover policy from config; attempts one fallback request when policy allows and capability matches.
 */
final class Provider_Failover_Service {

	/** @var Settings_Service */
	private Settings_Service $settings;

	/** @var Provider_Capability_Resolver */
	private Provider_Capability_Resolver $capability_resolver;

	public function __construct( Settings_Service $settings, Provider_Capability_Resolver $capability_resolver ) {
		$this->settings            = $settings;
		$this->capability_resolver = $capability_resolver;
	}

	/**
	 * Resolves failover policy for the given primary provider (from provider_config failover_policy).
	 *
	 * @param string $primary_provider_id Primary provider id (e.g. from prefill selection).
	 * @return Provider_Failover_Policy
	 */
	public function get_policy_for_primary( string $primary_provider_id ): Provider_Failover_Policy {
		$config = $this->settings->get( Option_Names::PROVIDER_CONFIG_REF );
		$slice  = isset( $config['failover_policy'] ) && is_array( $config['failover_policy'] )
			? $config['failover_policy']
			: array();
		return Provider_Failover_Policy::from_config( $slice, $primary_provider_id );
	}

	/**
	 * Attempts one fallback request when policy allows and fallback driver is compatible.
	 * Caller must apply the same validator pipeline to the returned response.
	 *
	 * @param Provider_Failover_Policy $policy              Resolved policy.
	 * @param string                   $primary_provider_id Primary provider that failed.
	 * @param string                   $primary_model       Model used on primary.
	 * @param array<string, mixed>     $primary_response     Normalized response (success=false, normalized_error set).
	 * @param array<string, mixed>     $normalized_request   Same request to send to fallback.
	 * @param string                   $schema_ref          Schema ref for capability check.
	 * @param Service_Container        $container           Container to resolve fallback driver.
	 * @return array{response: array<string, mixed>, result: Failover_Result}
	 */
	public function try_fallback(
		Provider_Failover_Policy $policy,
		string $primary_provider_id,
		string $primary_model,
		array $primary_response,
		array $normalized_request,
		string $schema_ref,
		Service_Container $container
	): array {
		$category        = isset( $primary_response['normalized_error']['category'] ) && is_string( $primary_response['normalized_error']['category'] )
			? $primary_response['normalized_error']['category']
			: 'provider_error';
		$policy_snapshot = $policy->to_metadata_snapshot();
		$attempts        = array(
			array(
				'provider_id'  => $primary_provider_id,
				'model_used'   => $primary_model,
				'category'     => $category,
				'attempted_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			),
		);

		if ( ! $policy->can_attempt_fallback( $primary_provider_id, 0 ) || ! $policy->is_eligible_category( $category ) ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::FAILOVER_NO_ATTEMPT, 'primary=' . $primary_provider_id . ' category=' . $category );
			return array(
				'response' => $primary_response,
				'result'   => Failover_Result::primary_failure_no_fallback( $primary_provider_id, $primary_model, $category, $policy_snapshot ),
			);
		}

		$fallback_id = $policy->get_fallback_provider_id();
		$driver      = $this->get_driver_for_provider( $fallback_id, $container );
		if ( $driver === null ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::FAILOVER_FALLBACK_DRIVER_MISSING, 'fallback_id=' . $fallback_id );
			return array(
				'response' => $primary_response,
				'result'   => Failover_Result::primary_failure_no_fallback( $primary_provider_id, $primary_model, $category, $policy_snapshot ),
			);
		}

		if ( ! $this->capability_resolver->supports_schema( $driver, $schema_ref ) ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::FAILOVER_FALLBACK_SCHEMA_UNSUPPORTED, 'fallback=' . $fallback_id . ' schema=' . $schema_ref );
			return array(
				'response' => $primary_response,
				'result'   => Failover_Result::primary_failure_no_fallback( $primary_provider_id, $primary_model, $category, $policy_snapshot ),
			);
		}

		$fallback_model = $this->capability_resolver->resolve_default_model_for_planning( $driver, $schema_ref );
		if ( $fallback_model === null || $fallback_model === '' ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::FAILOVER_FALLBACK_NO_MODEL, 'fallback=' . $fallback_id );
			return array(
				'response' => $primary_response,
				'result'   => Failover_Result::primary_failure_no_fallback( $primary_provider_id, $primary_model, $category, $policy_snapshot ),
			);
		}

		// * Single fallback attempt; same request, model override for fallback.
		$request_for_fallback          = $normalized_request;
		$request_for_fallback['model'] = $fallback_model;

		Named_Debug_Log::event(
			Named_Debug_Log_Event::FAILOVER_FALLBACK_REQUEST,
			'primary=' . $primary_provider_id . ' fallback=' . $fallback_id . ' model=' . $fallback_model
		);
		$response = $driver->request( $request_for_fallback );

		$attempts[] = array(
			'provider_id'  => $fallback_id,
			'model_used'   => $fallback_model,
			'category'     => ! empty( $response['success'] ) ? 'success' : ( (string) ( $response['normalized_error']['category'] ?? 'provider_error' ) ),
			'attempted_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
		);

		if ( ! empty( $response['success'] ) ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::FAILOVER_FALLBACK_SUCCESS, 'fallback=' . $fallback_id );
			return array(
				'response' => $response,
				'result'   => Failover_Result::fallback_success( $fallback_id, $fallback_model, $attempts, $policy_snapshot ),
			);
		}

		Named_Debug_Log::event( Named_Debug_Log_Event::FAILOVER_FALLBACK_FAILED, 'fallback=' . $fallback_id );
		return array(
			'response' => $response,
			'result'   => Failover_Result::fallback_failure( $fallback_id, $fallback_model, $attempts, $policy_snapshot ),
		);
	}

	/**
	 * @param string            $provider_id Provider id.
	 * @param Service_Container $container   Container.
	 * @return AI_Provider_Interface|null
	 */
	private function get_driver_for_provider( string $provider_id, Service_Container $container ): ?AI_Provider_Interface {
		if ( $provider_id === 'openai' && $container->has( 'openai_provider_driver' ) ) {
			return $container->get( 'openai_provider_driver' );
		}
		if ( $provider_id === 'anthropic' && $container->has( 'anthropic_provider_driver' ) ) {
			return $container->get( 'anthropic_provider_driver' );
		}
		return null;
	}
}
