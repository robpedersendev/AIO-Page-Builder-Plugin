<?php
/**
 * Settings-backed task routing for AI providers (spec §25.1).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Routing;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

/**
 * Reads provider_config.task_routing[task] = { provider_id, model? }; falls back to preferred or openai.
 */
final class Default_AI_Provider_Router implements AI_Provider_Router_Interface {

	private Settings_Service $settings;

	public function __construct( Settings_Service $settings ) {
		$this->settings = $settings;
	}

	/** @inheritdoc */
	public function resolve_route( string $task, array $context = array() ): AI_Provider_Route_Result {
		$config_array = $this->settings->get( Option_Names::PROVIDER_CONFIG_REF );

		$task_routing = isset( $config_array['task_routing'] ) && is_array( $config_array['task_routing'] )
			? $config_array['task_routing']
			: array();

		$fallback_id = isset( $config_array['fallback_provider_id'] ) && is_string( $config_array['fallback_provider_id'] )
			? trim( $config_array['fallback_provider_id'] )
			: '';
		$fallback_model_raw = isset( $config_array['fallback_model'] ) && is_string( $config_array['fallback_model'] )
			? trim( $config_array['fallback_model'] )
			: '';
		$fallback_model       = $fallback_model_raw !== '' ? $fallback_model_raw : null;
		$fallback_for_result  = in_array( $fallback_id, array( 'openai', 'anthropic' ), true ) ? $fallback_id : null;
		$fallback_model_clean = $fallback_for_result !== null ? $fallback_model : null;

		$preferred = isset( $context['preferred_provider_id'] ) ? trim( (string) $context['preferred_provider_id'] ) : '';

		$provider_id    = '';
		$model_override = null;

		if ( isset( $task_routing[ $task ] ) && is_array( $task_routing[ $task ] ) ) {
			$slice = $task_routing[ $task ];
			if ( isset( $slice['provider_id'] ) && is_string( $slice['provider_id'] ) ) {
				$provider_id = trim( $slice['provider_id'] );
			}
			if ( isset( $slice['model'] ) && is_string( $slice['model'] ) ) {
				$m = trim( $slice['model'] );
				if ( $m !== '' ) {
					$model_override = $m;
				}
			}
		}

		if ( $provider_id === '' && $preferred !== '' ) {
			$provider_id = $preferred;
		}

		if ( $provider_id === '' ) {
			$primary     = isset( $config_array['primary_provider_id'] ) && is_string( $config_array['primary_provider_id'] )
				? trim( $config_array['primary_provider_id'] )
				: '';
			$provider_id = $primary !== '' ? $primary : 'openai';
		}

		if ( ! in_array( $provider_id, array( 'openai', 'anthropic' ), true ) ) {
			if ( $fallback_for_result !== null ) {
				$provider_id    = $fallback_for_result;
				$model_override = $fallback_model_clean ?? $model_override;
			} else {
				return AI_Provider_Route_Result::invalid();
			}
		}

		return new AI_Provider_Route_Result(
			$provider_id,
			$model_override,
			true,
			$fallback_for_result,
			$fallback_model_clean
		);
	}
}
