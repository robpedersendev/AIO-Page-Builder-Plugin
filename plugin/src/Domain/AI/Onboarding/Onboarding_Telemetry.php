<?php
/**
 * Privacy-safe aggregate counters for onboarding UX (event id + step key + time only).
 *
 * **Event ids** (stable snake_case; see class constants):
 * - `draft_save` — Save draft action.
 * - `advance_blocked` — Next blocked by validation or review gate.
 * - `step_advanced` — Next succeeded; step key is the **destination** step.
 * - `submit_attempted` — Request AI plan submitted (before orchestrator).
 *
 * Payload has no free-text profile fields, no API keys, no user id.
 *
 * Schema (option value, v1):
 * array{
 *   v: 1,
 *   c: array<string, int>,           // event_id => count
 *   by_step: array<string, array<string, int>>, // step_key => event_id => count
 *   recent: list<array{e: string, s: string, t: int}>  // max 40, newest last
 * }
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Onboarding;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Records coarse onboarding events for operator dashboards. No free text, no user ids in payload.
 */
final class Onboarding_Telemetry {

	public const OPTION_SHAPE_VERSION = 1;

	public const EVENT_DRAFT_SAVE       = 'draft_save';
	public const EVENT_ADVANCE_BLOCKED  = 'advance_blocked';
	public const EVENT_STEP_ADVANCED    = 'step_advanced';
	public const EVENT_SUBMIT_ATTEMPTED = 'submit_attempted';

	private const RECENT_MAX = 40;

	/** @var Settings_Service */
	private Settings_Service $settings;

	public function __construct( Settings_Service $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @param string $event_id One of self::EVENT_* or a stable snake_case id.
	 * @param string $step_key Empty or a key from {@see Onboarding_Step_Keys::ordered()}.
	 */
	public function record( string $event_id, string $step_key = '' ): void {
		if ( ! Capabilities::current_user_can_for_route( Capabilities::RUN_ONBOARDING ) ) {
			return;
		}
		$event_id = \sanitize_key( $event_id );
		if ( $event_id === '' ) {
			return;
		}
		$step_key = \sanitize_key( $step_key );
		if ( $step_key !== '' && ! \in_array( $step_key, Onboarding_Step_Keys::ordered(), true ) ) {
			$step_key = '';
		}

		$raw = $this->settings->get( Option_Names::ONBOARDING_TELEMETRY_AGGREGATE );
		$agg = $this->normalize_aggregate( $raw );

		$agg['c'][ $event_id ] = isset( $agg['c'][ $event_id ] ) ? (int) $agg['c'][ $event_id ] + 1 : 1;
		if ( $step_key !== '' ) {
			if ( ! isset( $agg['by_step'][ $step_key ] ) || ! \is_array( $agg['by_step'][ $step_key ] ) ) {
				$agg['by_step'][ $step_key ] = array();
			}
			$agg['by_step'][ $step_key ][ $event_id ] = isset( $agg['by_step'][ $step_key ][ $event_id ] )
				? (int) $agg['by_step'][ $step_key ][ $event_id ] + 1
				: 1;
		}
		$agg['recent'][] = array(
			'e' => $event_id,
			's' => $step_key,
			't' => \time(),
		);
		if ( \count( $agg['recent'] ) > self::RECENT_MAX ) {
			$agg['recent'] = \array_slice( $agg['recent'], -self::RECENT_MAX );
		}

		$this->settings->set( Option_Names::ONBOARDING_TELEMETRY_AGGREGATE, $agg );
		Named_Debug_Log::event(
			Named_Debug_Log_Event::ONBOARDING_TELEMETRY_RECORD,
			'e=' . $event_id . ' s=' . $step_key
		);
	}

	/**
	 * Read-only snapshot for dashboards (no mutation).
	 *
	 * @return array{v: int, c: array<string, int>, by_step: array<string, array<string, int>>, recent: list<array{e: string, s: string, t: int}>}
	 */
	public function get_aggregate(): array {
		$raw = $this->settings->get( Option_Names::ONBOARDING_TELEMETRY_AGGREGATE );
		return $this->normalize_aggregate( $raw );
	}

	/**
	 * @param mixed $raw
	 * @return array{v: int, c: array<string, int>, by_step: array<string, array<string, int>>, recent: list<array{e: string, s: string, t: int}>}
	 */
	private function normalize_aggregate( $raw ): array {
		$out = array(
			'v'       => self::OPTION_SHAPE_VERSION,
			'c'       => array(),
			'by_step' => array(),
			'recent'  => array(),
		);
		if ( ! \is_array( $raw ) ) {
			return $out;
		}
		if ( isset( $raw['v'] ) ) {
			$out['v'] = (int) $raw['v'];
		}
		if ( isset( $raw['c'] ) && \is_array( $raw['c'] ) ) {
			foreach ( $raw['c'] as $k => $n ) {
				if ( \is_string( $k ) && $k !== '' && \is_numeric( $n ) ) {
					$out['c'][ \sanitize_key( $k ) ] = (int) $n;
				}
			}
		}
		if ( isset( $raw['by_step'] ) && \is_array( $raw['by_step'] ) ) {
			foreach ( $raw['by_step'] as $sk => $evs ) {
				$sk = \sanitize_key( (string) $sk );
				if ( $sk === '' || ! \is_array( $evs ) ) {
					continue;
				}
				$out['by_step'][ $sk ] = array();
				foreach ( $evs as $ek => $n ) {
					if ( \is_string( $ek ) && $ek !== '' && \is_numeric( $n ) ) {
						$out['by_step'][ $sk ][ \sanitize_key( $ek ) ] = (int) $n;
					}
				}
			}
		}
		if ( isset( $raw['recent'] ) && \is_array( $raw['recent'] ) ) {
			foreach ( $raw['recent'] as $row ) {
				if ( ! \is_array( $row ) ) {
					continue;
				}
				$e = isset( $row['e'] ) ? \sanitize_key( (string) $row['e'] ) : '';
				$s = isset( $row['s'] ) ? \sanitize_key( (string) $row['s'] ) : '';
				$t = isset( $row['t'] ) ? (int) $row['t'] : 0;
				if ( $e === '' || $t <= 0 ) {
					continue;
				}
				$out['recent'][] = array(
					'e' => $e,
					's' => $s,
					't' => $t,
				);
			}
			if ( \count( $out['recent'] ) > self::RECENT_MAX ) {
				$out['recent'] = \array_slice( $out['recent'], -self::RECENT_MAX );
			}
		}
		return $out;
	}
}
