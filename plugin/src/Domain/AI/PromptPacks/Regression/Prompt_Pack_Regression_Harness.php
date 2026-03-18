<?php
/**
 * Regression-testing harness for prompt packs (spec §26, §28.11–28.13, §56.2, §58.3, Prompt 120).
 * Compares validator output against golden fixtures for schema compliance, dropped-record behavior, and normalized output.
 * Internal QA only; no runtime exposure. Machine-readable results only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\PromptPacks\Regression;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Validation\AI_Output_Validator;
use AIOPageBuilder\Domain\AI\Validation\Validation_Report;

/**
 * Runs prompt-pack regression: load golden fixture, validate input, compare to expected, produce Regression_Result.
 */
final class Prompt_Pack_Regression_Harness {

	/** Golden fixture key: prompt pack reference. */
	public const FIXTURE_PROMPT_PACK_REF = 'prompt_pack_ref';

	/** Golden fixture key: schema ref. */
	public const FIXTURE_SCHEMA_REF = 'schema_ref';

	/** Golden fixture key: raw input (string or array). */
	public const FIXTURE_INPUT = 'input';

	/** Golden fixture key: expected result. */
	public const FIXTURE_EXPECTED = 'expected';

	/** Expected key: final_validation_state. */
	public const EXPECTED_FINAL_STATE = 'final_validation_state';

	/** Expected key: normalized_output (optional). */
	public const EXPECTED_NORMALIZED_OUTPUT = 'normalized_output';

	/** Expected key: dropped_records (optional). */
	public const EXPECTED_DROPPED_RECORDS = 'dropped_records';

	/** @var AI_Output_Validator */
	private AI_Output_Validator $validator;

	/** @var string Base path for loading fixture files (trailing slash optional). */
	private string $fixtures_base_path;

	public function __construct( AI_Output_Validator $validator, string $fixtures_base_path = '' ) {
		$this->validator          = $validator;
		$this->fixtures_base_path = rtrim( str_replace( '\\', '/', $fixtures_base_path ), '/' );
	}

	/**
	 * Runs regression for one golden fixture. Accepts fixture as array or path to JSON file.
	 *
	 * @param array<string, mixed>|string $fixture Fixture array (golden_fixture shape) or path to JSON file.
	 * @return Regression_Result
	 */
	public function run( $fixture ): Regression_Result {
		$data = is_string( $fixture ) ? $this->load_fixture_file( $fixture ) : $fixture;
		if ( ! is_array( $data ) ) {
			return $this->result_fixture_invalid( $data, 'Fixture must be array or path to JSON.' );
		}
		return $this->run_from_array( $data );
	}

	/**
	 * Runs regression from parsed fixture array.
	 *
	 * @param array<string, mixed> $data Golden fixture: prompt_pack_ref, schema_ref, input, expected.
	 * @return Regression_Result
	 */
	public function run_from_array( array $data ): Regression_Result {
		$pack_ref   = $data[ self::FIXTURE_PROMPT_PACK_REF ] ?? null;
		$schema_ref = isset( $data[ self::FIXTURE_SCHEMA_REF ] ) && is_string( $data[ self::FIXTURE_SCHEMA_REF ] ) ? $data[ self::FIXTURE_SCHEMA_REF ] : '';
		$input      = $data[ self::FIXTURE_INPUT ] ?? null;
		$expected   = $data[ self::FIXTURE_EXPECTED ] ?? null;

		$run_id         = 'regression-' . uniqid( '', true );
		$ran_at         = gmdate( 'Y-m-d\TH:i:s\Z' );
		$regression_run = array(
			'run_id'          => $run_id,
			'prompt_pack_ref' => is_array( $pack_ref ) ? $pack_ref : array(
				'internal_key' => '',
				'version'      => '',
			),
			'schema_ref'      => $schema_ref,
			'ran_at'          => $ran_at,
		);

		if ( ! is_array( $expected ) ) {
			return $this->result_fixture_invalid( $regression_run, 'Fixture expected must be an array.' );
		}

		$expected_state = isset( $expected[ self::EXPECTED_FINAL_STATE ] ) && is_string( $expected[ self::EXPECTED_FINAL_STATE ] )
			? $expected[ self::EXPECTED_FINAL_STATE ]
			: '';

		if ( $schema_ref === '' ) {
			return $this->result_fixture_invalid( $regression_run, 'Fixture schema_ref is required.' );
		}

		$report               = $this->validator->validate( $input, $schema_ref, false );
		$actual_state         = $report->get_final_validation_state();
		$actual_block         = $report->get_blocking_failure_stage();
		$actual_norm          = $report->get_normalized_output();
		$actual_dropped       = $report->get_dropped_records();
		$actual_dropped_array = array();
		foreach ( $actual_dropped as $d ) {
			$actual_dropped_array[] = $d->to_array();
		}

		$state_match         = $actual_state === $expected_state;
		$expected_block      = $expected['blocking_failure_stage'] ?? null;
		$block_match         = ( $expected_block === null && $actual_block === null ) || ( (string) $expected_block === (string) $actual_block );
		$expected_dropped    = $expected[ self::EXPECTED_DROPPED_RECORDS ] ?? array();
		$expected_dropped    = is_array( $expected_dropped ) ? $expected_dropped : array();
		$dropped_count_match = count( $actual_dropped_array ) === count( $expected_dropped );

		$dropped_diffs = array();
		$max_len       = max( count( $actual_dropped_array ), count( $expected_dropped ) );
		for ( $i = 0; $i < $max_len; $i++ ) {
			$exp = $expected_dropped[ $i ] ?? null;
			$act = $actual_dropped_array[ $i ] ?? null;
			if ( $exp !== $act && ( is_array( $exp ) || is_array( $act ) ) ) {
				$dropped_diffs[] = array(
					'index'    => $i,
					'expected' => $exp,
					'actual'   => $act,
				);
			}
		}

		$validator_summary = array(
			'final_validation_state_match' => $state_match,
			'blocking_stage_match'         => $block_match,
			'dropped_count_match'          => $dropped_count_match,
			'dropped_record_diffs'         => $dropped_diffs,
		);

		$expected_norm = $expected[ self::EXPECTED_NORMALIZED_OUTPUT ] ?? null;
		$norm_diff     = null;
		if ( $expected_norm !== null && is_array( $expected_norm ) ) {
			$norm_diff = $this->diff_normalized_output( $expected_norm, $actual_norm );
		} elseif ( $actual_norm !== null && $expected_state === Validation_Report::STATE_PASSED ) {
			$norm_diff = $this->diff_normalized_output( array(), $actual_norm );
		}

		$output_match = $norm_diff === null || ( $norm_diff['match'] ?? false );

		$outcome = Regression_Result::OUTCOME_PASS;
		$message = 'Regression pass: validation state and output match fixture.';
		if ( ! $state_match || ! $block_match || ! $dropped_count_match ) {
			$outcome = Regression_Result::OUTCOME_REGRESSION;
			$message = 'Validator regression: validation state or dropped records do not match fixture.';
		}
		if ( ! $output_match && $norm_diff !== null ) {
			$outcome = Regression_Result::OUTCOME_REGRESSION;
			$message = 'Normalized output regression: diff from expected.';
		}
		if ( $actual_state === Validation_Report::STATE_FAILED && $expected_state !== Validation_Report::STATE_FAILED ) {
			$outcome = Regression_Result::OUTCOME_FAIL;
			$message = 'Validation failed where fixture expected ' . $expected_state . '.';
		}

		return new Regression_Result( $outcome, $regression_run, $norm_diff, $validator_summary, $message );
	}

	/**
	 * Loads fixture from JSON file. Path may be relative to fixtures_base_path or absolute.
	 *
	 * @param string $path File path.
	 * @return array<string, mixed>|null
	 */
	public function load_fixture_file( string $path ): ?array {
		$path = str_replace( '\\', '/', $path );
		if ( $this->fixtures_base_path !== '' && $path[0] !== '/' && preg_match( '#^[A-Za-z]:#', $path ) === 0 ) {
			$path = $this->fixtures_base_path . '/' . $path;
		}
		if ( ! is_readable( $path ) ) {
			return null;
		}
		$raw = file_get_contents( $path );
		if ( $raw === false ) {
			return null;
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : null;
	}

	/**
	 * Recursive diff of normalized output: added keys, removed keys, value diffs (top-level and one level deep for arrays).
	 *
	 * @param array<string, mixed>      $expected
	 * @param array<string, mixed>|null $actual
	 * @return array{match: bool, added_keys: array, removed_keys: array, value_diffs: array}
	 */
	private function diff_normalized_output( array $expected, ?array $actual ): array {
		$added       = array();
		$removed     = array();
		$value_diffs = array();
		if ( $actual === null ) {
			$removed = array_keys( $expected );
			return array(
				'match'        => false,
				'added_keys'   => $added,
				'removed_keys' => $removed,
				'value_diffs'  => array(
					array(
						'path'     => '$',
						'expected' => $expected,
						'actual'   => null,
					),
				),
			);
		}
		$all_keys = array_unique( array_merge( array_keys( $expected ), array_keys( $actual ) ) );
		foreach ( $all_keys as $key ) {
			$exp_has = array_key_exists( $key, $expected );
			$act_has = array_key_exists( $key, $actual );
			if ( ! $exp_has && $act_has ) {
				$added[] = $key;
				continue;
			}
			if ( $exp_has && ! $act_has ) {
				$removed[] = $key;
				continue;
			}
			$ev = $expected[ $key ];
			$av = $actual[ $key ];
			if ( $ev === $av ) {
				continue;
			}
			if ( is_array( $ev ) && is_array( $av ) && $this->is_list_like( $ev ) && $this->is_list_like( $av ) ) {
				if ( json_encode( $ev ) !== json_encode( $av ) ) {
					$value_diffs[] = array(
						'path'     => $key,
						'expected' => $ev,
						'actual'   => $av,
					);
				}
			} else {
				$value_diffs[] = array(
					'path'     => $key,
					'expected' => $ev,
					'actual'   => $av,
				);
			}
		}
		$match = empty( $added ) && empty( $removed ) && empty( $value_diffs );
		return array(
			'match'        => $match,
			'added_keys'   => $added,
			'removed_keys' => $removed,
			'value_diffs'  => $value_diffs,
		);
	}

	/**
	 * @param array $a
	 * @return bool
	 */
	private function is_list_like( array $a ): bool {
		if ( empty( $a ) ) {
			return true;
		}
		return array_keys( $a ) === range( 0, count( $a ) - 1 );
	}

	/**
	 * @param array  $regression_run
	 * @param string $message
	 * @return Regression_Result
	 */
	private function result_fixture_invalid( $regression_run, string $message ): Regression_Result {
		$run = is_array( $regression_run ) ? $regression_run : array(
			'run_id'          => 'regression-' . uniqid( '', true ),
			'prompt_pack_ref' => array(),
			'schema_ref'      => '',
			'ran_at'          => gmdate( 'Y-m-d\TH:i:s\Z' ),
		);
		return new Regression_Result(
			Regression_Result::OUTCOME_FAIL,
			$run,
			null,
			array(
				'final_validation_state_match' => false,
				'blocking_stage_match'         => false,
				'dropped_count_match'          => false,
				'dropped_record_diffs'         => array(),
			),
			$message
		);
	}
}
