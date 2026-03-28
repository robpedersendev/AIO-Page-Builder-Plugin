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

		$allowed = array( 'openai', 'anthropic' );

		$global_fallback_id          = isset( $config_array['fallback_provider_id'] ) && is_string( $config_array['fallback_provider_id'] )
			? trim( $config_array['fallback_provider_id'] )
			: '';
		$fallback_model_raw          = isset( $config_array['fallback_model'] ) && is_string( $config_array['fallback_model'] )
			? trim( $config_array['fallback_model'] )
			: '';
		$global_fallback_model       = $fallback_model_raw !== '' ? $fallback_model_raw : null;
		$global_fallback_ok          = in_array( $global_fallback_id, $allowed, true ) ? $global_fallback_id : null;
		$global_fallback_model_clean = $global_fallback_ok !== null ? $global_fallback_model : null;

		$preferred = isset( $context['preferred_provider_id'] ) ? trim( (string) $context['preferred_provider_id'] ) : '';

		$provider_id            = '';
		$model_override         = null;
		$task_fallback_disabled = false;
		$task_fallback_id       = '';
		$task_fallback_model    = null;

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
			$task_fallback_disabled = ! empty( $slice['fallback_disabled'] );
			if ( isset( $slice['fallback_provider_id'] ) && is_string( $slice['fallback_provider_id'] ) ) {
				$task_fallback_id = trim( $slice['fallback_provider_id'] );
			}
			if ( isset( $slice['fallback_model'] ) && is_string( $slice['fallback_model'] ) ) {
				$tfm = trim( $slice['fallback_model'] );
				if ( $tfm !== '' ) {
					$task_fallback_model = $tfm;
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

		if ( ! in_array( $provider_id, $allowed, true ) ) {
			if ( $task_fallback_disabled || $global_fallback_ok === null ) {
				return AI_Provider_Route_Result::invalid();
			}
			$provider_id    = $global_fallback_ok;
			$model_override = $global_fallback_model_clean ?? $model_override;
		}

		$fallback_for_result  = null;
		$fallback_model_clean = null;
		if ( ! $task_fallback_disabled ) {
			if ( $task_fallback_id !== '' && in_array( $task_fallback_id, $allowed, true ) ) {
				$eff_fb = $task_fallback_id;
				$eff_fm = $task_fallback_model;
			} elseif ( $task_fallback_id !== '' ) {
				$eff_fb = $global_fallback_ok;
				$eff_fm = $global_fallback_model_clean;
			} else {
				$eff_fb = $global_fallback_ok;
				$eff_fm = $global_fallback_model_clean;
			}
			if ( $eff_fb !== null && $eff_fb !== $provider_id ) {
				$fallback_for_result  = $eff_fb;
				$fallback_model_clean = $eff_fm;
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
