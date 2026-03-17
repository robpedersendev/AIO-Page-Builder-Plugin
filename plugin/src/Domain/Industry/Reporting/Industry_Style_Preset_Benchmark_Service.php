<?php
/**
 * Benchmark and review harness for industry style presets (Prompt 478).
 * Evaluates distinctiveness, token usage, component refs, compatibility, and accessibility notes. Internal only; read-only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry;

/**
 * Produces a structured benchmark report for industry style presets. No mutation of styling state.
 */
final class Industry_Style_Preset_Benchmark_Service {

	/** @var Industry_Style_Preset_Registry */
	private $preset_registry;

	public function __construct( Industry_Style_Preset_Registry $preset_registry ) {
		$this->preset_registry = $preset_registry;
	}

	/**
	 * Runs the benchmark on active industry presets and returns a structured report.
	 *
	 * @return array{
	 *   generated_at: string,
	 *   presets_evaluated: list<string>,
	 *   per_preset: array<string, array{label: string, industry_key: string, token_count: int, component_ref_count: int, primary_color: string|null, distinctiveness_note: string, compatibility: string, accessibility_notes: string}>,
	 *   pairwise_distinctiveness: list<array{preset_a: string, preset_b: string, note: string}>,
	 *   summary: array{total_presets: int, all_compatible: bool, findings: list<string>}
	 * }
	 */
	public function run_benchmark(): array {
		$all = $this->preset_registry->get_all();
		$active = array_values( array_filter( $all, function ( $p ) {
			$status = isset( $p[ Industry_Style_Preset_Registry::FIELD_STATUS ] ) ? $p[ Industry_Style_Preset_Registry::FIELD_STATUS ] : '';
			return $status === Industry_Style_Preset_Registry::STATUS_ACTIVE;
		} ) );
		$preset_keys = array_map( function ( $p ) {
			return (string) ( $p[ Industry_Style_Preset_Registry::FIELD_STYLE_PRESET_KEY ] ?? '' );
		}, $active );
		$preset_keys = array_values( array_filter( $preset_keys ) );

		$per_preset = array();
		foreach ( $active as $preset ) {
			$key = (string) ( $preset[ Industry_Style_Preset_Registry::FIELD_STYLE_PRESET_KEY ] ?? '' );
			if ( $key === '' ) {
				continue;
			}
			$token_values = isset( $preset[ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] ) && is_array( $preset[ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] )
				? $preset[ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ]
				: array();
			$comp_refs = isset( $preset[ Industry_Style_Preset_Registry::FIELD_COMPONENT_OVERRIDE_REFS ] ) && is_array( $preset[ Industry_Style_Preset_Registry::FIELD_COMPONENT_OVERRIDE_REFS ] )
				? $preset[ Industry_Style_Preset_Registry::FIELD_COMPONENT_OVERRIDE_REFS ]
				: array();
			$primary = $token_values['--aio-color-primary'] ?? $token_values['--aio-color-accent'] ?? null;
			if ( is_string( $primary ) ) {
				$primary = trim( $primary );
			} else {
				$primary = null;
			}
			$per_preset[ $key ] = array(
				'label'                 => (string) ( $preset[ Industry_Style_Preset_Registry::FIELD_LABEL ] ?? $key ),
				'industry_key'          => (string) ( $preset[ Industry_Style_Preset_Registry::FIELD_INDUSTRY_KEY ] ?? '' ),
				'token_count'            => count( $token_values ),
				'component_ref_count'    => count( $comp_refs ),
				'primary_color'          => $primary,
				'distinctiveness_note'   => $this->distinctiveness_note( $key, $primary, $active ),
				'compatibility'          => $this->compatibility_note( $preset ),
				'accessibility_notes'    => 'Review contrast for --aio-color-text and --aio-color-text-muted; ensure reduced-motion respected where applicable.',
			);
		}

		$pairwise = $this->pairwise_distinctiveness( $active );
		$findings = array();
		$all_compatible = true;
		foreach ( $per_preset as $k => $p ) {
			if ( ( $p['compatibility'] ?? '' ) !== 'pass' ) {
				$all_compatible = false;
				$findings[] = "Preset {$k}: " . ( $p['compatibility'] ?? 'unknown' );
			}
		}
		if ( count( $preset_keys ) === 0 ) {
			$findings[] = 'No active presets to evaluate.';
		}

		return array(
			'generated_at'               => gmdate( 'c' ),
			'presets_evaluated'          => $preset_keys,
			'per_preset'                 => $per_preset,
			'pairwise_distinctiveness'   => $pairwise,
			'summary'                    => array(
				'total_presets'  => count( $preset_keys ),
				'all_compatible' => $all_compatible,
				'findings'       => $findings,
			),
		);
	}

	/**
	 * @param array<string, mixed> $preset
	 */
	private function distinctiveness_note( string $key, ?string $primary_color, array $all_active ): string {
		if ( $primary_color === null ) {
			return 'No primary/accent token captured; review token_values.';
		}
		$same = 0;
		foreach ( $all_active as $p ) {
			$pk = (string) ( $p[ Industry_Style_Preset_Registry::FIELD_STYLE_PRESET_KEY ] ?? '' );
			if ( $pk === $key ) {
				continue;
			}
			$tv = isset( $p[ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] ) && is_array( $p[ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] ) ? $p[ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] : array();
			$other = $tv['--aio-color-primary'] ?? $tv['--aio-color-accent'] ?? null;
			if ( is_string( $other ) && strtolower( trim( $other ) ) === strtolower( $primary_color ) ) {
				$same++;
			}
		}
		return $same > 0 ? 'Primary/accent overlaps with another preset; consider differentiation.' : 'Distinct primary/accent.';
	}

	/**
	 * @param array<string, mixed> $preset
	 */
	private function compatibility_note( array $preset ): string {
		$token_values = isset( $preset[ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] ) && is_array( $preset[ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] )
			? $preset[ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ]
			: array();
		$prohibited = array( 'url(', 'expression(', 'javascript:', 'vbscript:', 'data:', '<', '>', '{', '}' );
		foreach ( $token_values as $name => $value ) {
			if ( ! is_string( $name ) || ! is_string( $value ) ) {
				return 'Invalid token name or value type.';
			}
			if ( ! preg_match( '#^--aio-[a-z0-9_-]+$#', $name ) ) {
				return 'Token name not allowed (--aio-* pattern).';
			}
			$v = strtolower( $value );
			foreach ( $prohibited as $p ) {
				if ( strpos( $v, $p ) !== false ) {
					return 'Prohibited value pattern.';
				}
			}
		}
		return 'pass';
	}

	/**
	 * @param list<array<string, mixed>> $active
	 * @return list<array{preset_a: string, preset_b: string, note: string}>
	 */
	private function pairwise_distinctiveness( array $active ): array {
		$out = array();
		$n = count( $active );
		for ( $i = 0; $i < $n; $i++ ) {
			$key_a = (string) ( $active[ $i ][ Industry_Style_Preset_Registry::FIELD_STYLE_PRESET_KEY ] ?? '' );
			$tv_a = isset( $active[ $i ][ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] ) && is_array( $active[ $i ][ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] )
				? $active[ $i ][ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ]
				: array();
			$primary_a = $tv_a['--aio-color-primary'] ?? $tv_a['--aio-color-accent'] ?? null;
			if ( ! is_string( $primary_a ) ) {
				continue;
			}
			$primary_a = strtolower( trim( $primary_a ) );
			for ( $j = $i + 1; $j < $n; $j++ ) {
				$key_b = (string) ( $active[ $j ][ Industry_Style_Preset_Registry::FIELD_STYLE_PRESET_KEY ] ?? '' );
				$tv_b = isset( $active[ $j ][ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] ) && is_array( $active[ $j ][ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] )
					? $active[ $j ][ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ]
					: array();
				$primary_b = $tv_b['--aio-color-primary'] ?? $tv_b['--aio-color-accent'] ?? null;
				if ( ! is_string( $primary_b ) ) {
					continue;
				}
				$primary_b = strtolower( trim( $primary_b ) );
				$out[] = array(
					'preset_a' => $key_a,
					'preset_b' => $key_b,
					'note'     => $primary_a === $primary_b ? 'Same primary/accent; consider differentiating.' : 'Distinct.',
				);
			}
		}
		return $out;
	}
}
