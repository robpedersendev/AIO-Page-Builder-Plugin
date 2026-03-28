<?php
/**
 * Validates and merges AI provider routing fields into provider_config (no secrets).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Rejects unknown provider ids and invalid primary/fallback pairing. On failure, callers should keep the prior option.
 */
final class AI_Provider_Routing_Settings_Sanitizer {

	public const ERROR_INVALID_GLOBAL_PRIMARY = 'invalid_global_primary';

	public const ERROR_INVALID_GLOBAL_FALLBACK = 'invalid_global_fallback';

	public const ERROR_GLOBAL_FALLBACK_CHAIN = 'invalid_global_fallback_chain';

	public const ERROR_INVALID_TASK_PRIMARY = 'invalid_task_primary';

	public const ERROR_INVALID_TASK_FALLBACK = 'invalid_task_fallback';

	public const ERROR_TASK_FALLBACK_CHAIN = 'invalid_task_fallback_chain';

	/**
	 * @param array<string, mixed> $existing        Full provider_config array.
	 * @param array<string, mixed> $routing_payload Secrets-free routing fields from a trusted extractor.
	 * @param array<int, string>   $allowed_providers Registered driver ids (e.g. openai, anthropic).
	 * @return array{ok: true, merged: array<string, mixed>}|array{ok: false, error_code: string}
	 */
	public static function merge_into_config( array $existing, array $routing_payload, array $allowed_providers ): array {
		$allow = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( string $p ): string {
							return \sanitize_key( $p );
						},
						$allowed_providers
					)
				)
			)
		);
		if ( $allow === array() ) {
			return array(
				'ok'         => false,
				'error_code' => self::ERROR_INVALID_GLOBAL_PRIMARY,
			);
		}

		$primary_global = self::read_provider_key( $routing_payload, 'primary_provider_id' );
		if ( $primary_global === '' ) {
			$primary_global = 'openai';
		}
		if ( ! in_array( $primary_global, $allow, true ) ) {
			return array(
				'ok'         => false,
				'error_code' => self::ERROR_INVALID_GLOBAL_PRIMARY,
			);
		}

		$fallback_global_raw = self::read_provider_key( $routing_payload, 'fallback_provider_id' );
		$fallback_global     = $fallback_global_raw;
		if ( $fallback_global !== '' && ! in_array( $fallback_global, $allow, true ) ) {
			return array(
				'ok'         => false,
				'error_code' => self::ERROR_INVALID_GLOBAL_FALLBACK,
			);
		}
		if ( $fallback_global !== '' && $fallback_global === $primary_global ) {
			return array(
				'ok'         => false,
				'error_code' => self::ERROR_GLOBAL_FALLBACK_CHAIN,
			);
		}

		$fallback_model_global = self::sanitize_model( isset( $routing_payload['fallback_model'] ) ? (string) $routing_payload['fallback_model'] : '' );

		$task_in  = isset( $routing_payload['task_routing'] ) && is_array( $routing_payload['task_routing'] )
			? $routing_payload['task_routing']
			: array();
		$task_out = array();

		foreach ( AI_Routing_Task::all() as $task_id ) {
			if ( ! isset( $task_in[ $task_id ] ) || ! is_array( $task_in[ $task_id ] ) ) {
				continue;
			}
			$slice = $task_in[ $task_id ];
			$row   = array();

			$tp = self::read_provider_key( $slice, 'provider_id' );
			if ( $tp !== '' && ! in_array( $tp, $allow, true ) ) {
				return array(
					'ok'         => false,
					'error_code' => self::ERROR_INVALID_TASK_PRIMARY,
				);
			}
			if ( $tp !== '' ) {
				$row['provider_id'] = $tp;
			}
			$tm = self::sanitize_model( isset( $slice['model'] ) ? (string) $slice['model'] : '' );
			if ( $tm !== '' ) {
				$row['model'] = $tm;
			}

			$fb_disabled = ! empty( $slice['fallback_disabled'] );
			$tf          = '';
			if ( $fb_disabled ) {
				$row['fallback_disabled'] = true;
			} else {
				$tf = self::read_provider_key( $slice, 'fallback_provider_id' );
				if ( $tf !== '' && ! in_array( $tf, $allow, true ) ) {
					return array(
						'ok'         => false,
						'error_code' => self::ERROR_INVALID_TASK_FALLBACK,
					);
				}
				if ( $tf !== '' ) {
					$row['fallback_provider_id'] = $tf;
				}
				$tfm = self::sanitize_model( isset( $slice['fallback_model'] ) ? (string) $slice['fallback_model'] : '' );
				if ( $tfm !== '' ) {
					$row['fallback_model'] = $tfm;
				}
			}

			$eff_primary = $tp !== '' ? $tp : $primary_global;
			if ( ! $fb_disabled ) {
				$eff_fb = $tf !== '' ? $tf : $fallback_global;
				if ( $eff_fb !== '' && $eff_fb === $eff_primary ) {
					return array(
						'ok'         => false,
						'error_code' => self::ERROR_TASK_FALLBACK_CHAIN,
					);
				}
			}

			if ( $row !== array() ) {
				$task_out[ $task_id ] = $row;
			}
		}

		$merged                         = $existing;
		$merged['primary_provider_id']  = $primary_global;
		$merged['fallback_provider_id'] = $fallback_global;
		$merged['fallback_model']       = $fallback_model_global !== '' ? $fallback_model_global : '';
		$merged['task_routing']         = $task_out;

		return array(
			'ok'     => true,
			'merged' => $merged,
		);
	}

	private static function read_provider_key( array $slice, string $key ): string {
		if ( ! isset( $slice[ $key ] ) || ! is_string( $slice[ $key ] ) ) {
			return '';
		}
		return \sanitize_key( $slice[ $key ] );
	}

	private static function sanitize_model( string $raw ): string {
		$m = trim( $raw );
		if ( $m === '' ) {
			return '';
		}
		if ( strlen( $m ) > 128 ) {
			return '';
		}
		if ( ! preg_match( '/^[a-zA-Z0-9._\-]+$/', $m ) ) {
			return '';
		}
		return $m;
	}
}
