<?php
/**
 * Staged validation pipeline for AI provider output (spec §28.11–28.14, ai-output-validation-contract.md).
 * Only validated normalized output may feed Build Plan generation; invalid output never enters executor pathways.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Validation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Runs capture → parse → top-level → item-level (shape, enum, required, internal-ref) → normalization.
 * Supports one bounded repair attempt (caller invokes repair and re-calls validate with is_repair_attempt=true).
 */
final class AI_Output_Validator {

	private const STAGE_RAW_CAPTURE  = 'raw_capture';
	private const STAGE_PARSE        = 'parse';
	private const STAGE_TOP_LEVEL    = 'top_level';
	private const STAGE_ITEM         = 'item';
	private const STAGE_INTERNAL_REF = 'internal_ref';

	/** @var Normalized_Output_Builder */
	private Normalized_Output_Builder $normalized_output_builder;

	public function __construct( ?Normalized_Output_Builder $normalized_output_builder = null ) {
		$this->normalized_output_builder = $normalized_output_builder ?? new Normalized_Output_Builder();
	}

	/**
	 * Validates raw provider output and returns a validation report. Does not perform provider calls.
	 *
	 * @param string|array<string, mixed> $raw_input       Raw response (JSON string or pre-parsed array).
	 * @param string                      $schema_ref      Schema reference (e.g. aio/build-plan-draft-v1).
	 * @param bool                        $is_repair_attempt True when caller is re-invoking after a repair attempt.
	 * @return Validation_Report
	 */
	public function validate( $raw_input, string $schema_ref, bool $is_repair_attempt = false ): Validation_Report {
		$record_results = array();
		$dropped        = array();

		// Stage 1: raw response capture.
		$raw_status = $this->capture_status( $raw_input );
		if ( $raw_status === Validation_Report::RAW_CAPTURE_EMPTY || $raw_status === Validation_Report::RAW_CAPTURE_ERROR ) {
			return $this->failed_report(
				$raw_status,
				Validation_Report::PARSE_FAILED,
				false,
				$schema_ref,
				$record_results,
				$dropped,
				null,
				self::STAGE_RAW_CAPTURE,
				$is_repair_attempt
			);
		}

		// Stage 2: parse attempt.
		$parsed = $this->parse( $raw_input );
		if ( $parsed === null ) {
			return $this->failed_report(
				$raw_status,
				Validation_Report::PARSE_FAILED,
				false,
				$schema_ref,
				$record_results,
				$dropped,
				null,
				self::STAGE_PARSE,
				$is_repair_attempt
			);
		}

		if ( $schema_ref === Build_Plan_Draft_Schema::SCHEMA_REF ) {
			$parsed = $this->coerce_build_plan_draft_top_level_objects( $parsed );
		}

		// Stage 3: top-level schema check.
		if ( $schema_ref !== Build_Plan_Draft_Schema::SCHEMA_REF ) {
			return $this->failed_report(
				$raw_status,
				Validation_Report::PARSE_OK,
				false,
				$schema_ref,
				$record_results,
				$dropped,
				null,
				self::STAGE_TOP_LEVEL,
				$is_repair_attempt
			);
		}

		$top_level_valid = $this->validate_top_level( $parsed, $record_results );
		if ( ! $top_level_valid ) {
			return $this->failed_report(
				$raw_status,
				Validation_Report::PARSE_OK,
				false,
				$schema_ref,
				$record_results,
				$dropped,
				null,
				self::STAGE_TOP_LEVEL,
				$is_repair_attempt
			);
		}

		// Stage 4 & 5 & 6: item-level (shape, enum, required) and internal-reference. Apply partial handling.
		$payload_for_normalization = $this->validate_item_level_and_refs( $parsed, $record_results, $dropped );
		if ( $payload_for_normalization === null ) {
			return $this->failed_report(
				$raw_status,
				Validation_Report::PARSE_OK,
				true,
				$schema_ref,
				$record_results,
				$dropped,
				null,
				self::STAGE_ITEM,
				$is_repair_attempt
			);
		}

		$normalized = $this->normalized_output_builder->build( $payload_for_normalization, $schema_ref );
		$state      = count( $dropped ) > 0 ? Validation_Report::STATE_PARTIAL : Validation_Report::STATE_PASSED;
		$repair_ok  = $is_repair_attempt && ( $state === Validation_Report::STATE_PASSED || $state === Validation_Report::STATE_PARTIAL );

		Named_Debug_Log::event(
			Named_Debug_Log_Event::OUTPUT_VALIDATION_OUTCOME,
			'schema=' . $schema_ref . ' state=' . $state . ' dropped=' . (string) count( $dropped ) . ' repair_attempt=' . ( $is_repair_attempt ? '1' : '0' )
		);
		return new Validation_Report(
			$raw_status,
			Validation_Report::PARSE_OK,
			true,
			$schema_ref,
			$record_results,
			$dropped,
			$normalized,
			$state,
			null,
			$is_repair_attempt,
			$repair_ok
		);
	}

	/**
	 * @param string|array<string, mixed> $raw_input
	 * @return string
	 */
	private function capture_status( $raw_input ): string {
		if ( is_string( $raw_input ) ) {
			$trimmed = trim( $raw_input );
			return $trimmed === '' ? Validation_Report::RAW_CAPTURE_EMPTY : Validation_Report::RAW_CAPTURE_OK;
		}
		if ( is_array( $raw_input ) ) {
			return Validation_Report::RAW_CAPTURE_OK;
		}
		return Validation_Report::RAW_CAPTURE_ERROR;
	}

	/**
	 * @param string|array<string, mixed> $raw_input
	 * @return array<string, mixed>|null
	 */
	private function parse( $raw_input ): ?array {
		if ( is_array( $raw_input ) ) {
			return $raw_input;
		}
		if ( ! is_string( $raw_input ) ) {
			return null;
		}
		$decoded = json_decode( trim( $raw_input ), true );
		return is_array( $decoded ) ? $decoded : null;
	}

	/**
	 * Normalizes common model mistakes: site_purpose / site_structure / confidence returned as strings instead of objects.
	 *
	 * @param array<string, mixed> $parsed
	 * @return array<string, mixed>
	 */
	private function coerce_build_plan_draft_top_level_objects( array $parsed ): array {
		$out = $parsed;

		$sp = $out[ Build_Plan_Draft_Schema::KEY_SITE_PURPOSE ] ?? null;
		if ( $sp !== null && ! is_array( $sp ) && is_scalar( $sp ) ) {
			$s = trim( (string) $sp );
			if ( $s !== '' ) {
				$out[ Build_Plan_Draft_Schema::KEY_SITE_PURPOSE ] = array( 'summary' => $s );
			}
		}

		$ss = $out[ Build_Plan_Draft_Schema::KEY_SITE_STRUCTURE ] ?? null;
		if ( $ss !== null && ! is_array( $ss ) && is_scalar( $ss ) ) {
			$s = trim( (string) $ss );
			if ( $s !== '' ) {
				$out[ Build_Plan_Draft_Schema::KEY_SITE_STRUCTURE ] = array( 'navigation_summary' => $s );
			}
		}

		$cf = $out[ Build_Plan_Draft_Schema::KEY_CONFIDENCE ] ?? null;
		if ( $cf !== null && ! is_array( $cf ) && is_string( $cf ) ) {
			$c = trim( $cf );
			if ( $c !== '' && in_array( $c, Build_Plan_Draft_Schema::ENUM_CONFIDENCE, true ) ) {
				$out[ Build_Plan_Draft_Schema::KEY_CONFIDENCE ] = array(
					'overall'       => $c,
					'planning_mode' => 'mixed',
				);
			}
		}

		return $out;
	}

	/**
	 * @param array<string, mixed>                                                                     $parsed
	 * @param array<int, array{section: string, index?: int, valid: bool, errors: array<int, string>}> $record_results
	 * @return bool
	 */
	private function validate_top_level( array $parsed, array &$record_results ): bool {
		$required = Build_Plan_Draft_Schema::required_top_level_keys();
		foreach ( $required as $key ) {
			if ( ! array_key_exists( $key, $parsed ) ) {
				$record_results[] = array(
					'section' => $key,
					'valid'   => false,
					'errors'  => array( 'missing_top_level_key' ),
				);
				return false;
			}
			$v = $parsed[ $key ];
			if ( in_array( $key, Build_Plan_Draft_Schema::ARRAY_SECTIONS, true ) && ! is_array( $v ) ) {
				$record_results[] = array(
					'section' => $key,
					'valid'   => false,
					'errors'  => array( 'expected_array' ),
				);
				return false;
			}
		}
		// run_summary must be object with required keys and enums.
		$run_summary = $parsed[ Build_Plan_Draft_Schema::KEY_RUN_SUMMARY ] ?? null;
		if ( ! is_array( $run_summary ) ) {
			$record_results[] = array(
				'section' => Build_Plan_Draft_Schema::KEY_RUN_SUMMARY,
				'valid'   => false,
				'errors'  => array( 'expected_object' ),
			);
			return false;
		}
		$rs_required = array( Build_Plan_Draft_Schema::RUN_SUMMARY_SUMMARY_TEXT, Build_Plan_Draft_Schema::RUN_SUMMARY_PLANNING_MODE, Build_Plan_Draft_Schema::RUN_SUMMARY_OVERALL_CONFIDENCE );
		foreach ( $rs_required as $rk ) {
			if ( ! array_key_exists( $rk, $run_summary ) ) {
				$record_results[] = array(
					'section' => Build_Plan_Draft_Schema::KEY_RUN_SUMMARY,
					'valid'   => false,
					'errors'  => array( 'missing_' . $rk ),
				);
				return false;
			}
		}
		$mode = $run_summary[ Build_Plan_Draft_Schema::RUN_SUMMARY_PLANNING_MODE ] ?? null;
		if ( ! is_string( $mode ) || ! in_array( $mode, Build_Plan_Draft_Schema::ENUM_PLANNING_MODE, true ) ) {
			$record_results[] = array(
				'section' => Build_Plan_Draft_Schema::KEY_RUN_SUMMARY,
				'valid'   => false,
				'errors'  => array( 'invalid_enum: planning_mode' ),
			);
			return false;
		}
		$conf = $run_summary[ Build_Plan_Draft_Schema::RUN_SUMMARY_OVERALL_CONFIDENCE ] ?? null;
		if ( ! is_string( $conf ) || ! in_array( $conf, Build_Plan_Draft_Schema::ENUM_CONFIDENCE, true ) ) {
			$record_results[] = array(
				'section' => Build_Plan_Draft_Schema::KEY_RUN_SUMMARY,
				'valid'   => false,
				'errors'  => array( 'invalid_enum: overall_confidence' ),
			);
			return false;
		}
		// site_purpose, site_structure, confidence must be array/object (not primitive).
		foreach ( array( Build_Plan_Draft_Schema::KEY_SITE_PURPOSE, Build_Plan_Draft_Schema::KEY_SITE_STRUCTURE, Build_Plan_Draft_Schema::KEY_CONFIDENCE ) as $obj_key ) {
			$val = $parsed[ $obj_key ] ?? null;
			if ( $val !== null && ! is_array( $val ) ) {
				$record_results[] = array(
					'section' => $obj_key,
					'valid'   => false,
					'errors'  => array( 'expected_object' ),
				);
				return false;
			}
		}
		return true;
	}

	/**
	 * Item-level validation and internal-reference checks. Drops invalid records when partial is allowed; returns payload for normalization or null if blocking.
	 *
	 * @param array<string, mixed>                                                                     $parsed
	 * @param array<int, array{section: string, index?: int, valid: bool, errors: array<int, string>}> $record_results
	 * @param array<int, Dropped_Record_Report>                                                        $dropped
	 * @return array<string, mixed>|null Payload with invalid items removed, or null if blocking failure.
	 */
	private function validate_item_level_and_refs( array $parsed, array &$record_results, array &$dropped ): ?array {
		$out      = $parsed;
		$sections = Build_Plan_Draft_Schema::ARRAY_SECTIONS;
		foreach ( $sections as $section ) {
			$list = $parsed[ $section ] ?? array();
			if ( ! is_array( $list ) ) {
				$list = array();
			}
			$valid_items = array();
			foreach ( $list as $idx => $item ) {
				if ( ! is_array( $item ) ) {
					$record_results[] = array(
						'section' => $section,
						'index'   => $idx,
						'valid'   => false,
						'errors'  => array( 'expected_object' ),
					);
					$dropped[]        = new Dropped_Record_Report( $section, $idx, 'expected_object', array( 'expected_object' ) );
					continue;
				}
				$errors = $this->validate_item( $section, $item, $idx );
				if ( $errors !== array() ) {
					$record_results[] = array(
						'section' => $section,
						'index'   => $idx,
						'valid'   => false,
						'errors'  => $errors,
					);
					$reason           = isset( $errors[0] ) ? ( strpos( (string) $errors[0], 'invalid_enum' ) !== false ? 'invalid_enum' : 'validation_failed' ) : 'validation_failed';
					$dropped[]        = new Dropped_Record_Report( $section, $idx, $reason, $errors );
					continue;
				}
				$valid_items[] = $item;
			}
			$out[ $section ] = $valid_items;
		}
		return $out;
	}

	/**
	 * @param string               $section
	 * @param array<string, mixed> $item
	 * @param int                  $idx
	 * @return array<int, string>
	 */
	private function validate_item( string $section, array $item, int $idx ): array {
		$errors = array();
		switch ( $section ) {
			case Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES:
				foreach ( Build_Plan_Draft_Schema::EPC_REQUIRED as $req ) {
					if ( ! array_key_exists( $req, $item ) ) {
						$errors[] = 'missing_required_field:' . $req;
					}
				}
				$action = $item[ Build_Plan_Draft_Schema::EPC_ACTION ] ?? null;
				if ( $action !== null && ! in_array( $action, Build_Plan_Draft_Schema::EPC_ENUM_ACTION, true ) ) {
					$errors[] = 'invalid_enum: action';
				}
				$risk = $item[ Build_Plan_Draft_Schema::EPC_RISK_LEVEL ] ?? null;
				if ( $risk !== null && ! in_array( $risk, Build_Plan_Draft_Schema::EPC_ENUM_RISK, true ) ) {
					$errors[] = 'invalid_enum: risk_level';
				}
				$conf = $item[ Build_Plan_Draft_Schema::EPC_CONFIDENCE ] ?? null;
				if ( $conf !== null && ! in_array( $conf, Build_Plan_Draft_Schema::ENUM_CONFIDENCE, true ) ) {
					$errors[] = 'invalid_enum: confidence';
				}
				// Internal-ref: when action !== keep, target fields required (§28.4).
				if ( ( $item[ Build_Plan_Draft_Schema::EPC_ACTION ] ?? '' ) !== 'keep' ) {
					foreach ( array( 'target_page_title', 'target_slug', 'target_template_key' ) as $t ) {
						if ( ! array_key_exists( $t, $item ) || ( is_string( $item[ $t ] ) && trim( (string) $item[ $t ] ) === '' ) ) {
							$errors[] = 'internal_ref_missing:' . $t;
						}
					}
				}
				break;
			case Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE:
				foreach ( Build_Plan_Draft_Schema::NPC_REQUIRED as $req ) {
					if ( ! array_key_exists( $req, $item ) ) {
						$errors[] = 'missing_required_field:' . $req;
					}
				}
				$pt = $item[ Build_Plan_Draft_Schema::NPC_PAGE_TYPE ] ?? null;
				if ( $pt !== null && ! in_array( $pt, Build_Plan_Draft_Schema::NPC_ENUM_PAGE_TYPE, true ) ) {
					$errors[] = 'invalid_enum: page_type';
				}
				$conf = $item[ Build_Plan_Draft_Schema::NPC_CONFIDENCE ] ?? null;
				if ( $conf !== null && ! in_array( $conf, Build_Plan_Draft_Schema::ENUM_CONFIDENCE, true ) ) {
					$errors[] = 'invalid_enum: confidence';
				}
				break;
			case Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN:
				foreach ( Build_Plan_Draft_Schema::MCP_REQUIRED as $req ) {
					if ( ! array_key_exists( $req, $item ) ) {
						$errors[] = 'missing_required_field:' . $req;
					}
				}
				$ctx = $item[ Build_Plan_Draft_Schema::MCP_MENU_CONTEXT ] ?? null;
				if ( $ctx !== null && ! in_array( $ctx, Build_Plan_Draft_Schema::MCP_ENUM_CONTEXT, true ) ) {
					$errors[] = 'invalid_enum: menu_context';
				}
				$act = $item[ Build_Plan_Draft_Schema::MCP_ACTION ] ?? null;
				if ( $act !== null && ! in_array( $act, Build_Plan_Draft_Schema::MCP_ENUM_ACTION, true ) ) {
					$errors[] = 'invalid_enum: action';
				}
				break;
			case Build_Plan_Draft_Schema::KEY_DESIGN_TOKEN_RECOMMENDATIONS:
				foreach ( Build_Plan_Draft_Schema::DTR_REQUIRED as $req ) {
					if ( ! array_key_exists( $req, $item ) ) {
						$errors[] = 'missing_required_field:' . $req;
					}
				}
				$tg = $item[ Build_Plan_Draft_Schema::DTR_TOKEN_GROUP ] ?? null;
				if ( $tg !== null && ! in_array( $tg, Build_Plan_Draft_Schema::DTR_ENUM_GROUP, true ) ) {
					$errors[] = 'invalid_enum: token_group';
				}
				$conf = $item[ Build_Plan_Draft_Schema::EPC_CONFIDENCE ] ?? null;
				if ( $conf !== null && ! in_array( $conf, Build_Plan_Draft_Schema::ENUM_CONFIDENCE, true ) ) {
					$errors[] = 'invalid_enum: confidence';
				}
				break;
			case Build_Plan_Draft_Schema::KEY_SEO_RECOMMENDATIONS:
				foreach ( Build_Plan_Draft_Schema::SEO_REQUIRED as $req ) {
					if ( ! array_key_exists( $req, $item ) ) {
						$errors[] = 'missing_required_field:' . $req;
					}
				}
				$conf = $item[ Build_Plan_Draft_Schema::EPC_CONFIDENCE ] ?? null;
				if ( $conf !== null && ! in_array( $conf, Build_Plan_Draft_Schema::ENUM_CONFIDENCE, true ) ) {
					$errors[] = 'invalid_enum: confidence';
				}
				break;
			case Build_Plan_Draft_Schema::KEY_WARNINGS:
			case Build_Plan_Draft_Schema::KEY_ASSUMPTIONS:
				// Optional severity for warnings.
				$sev = $item['severity'] ?? null;
				if ( $sev !== null && $section === Build_Plan_Draft_Schema::KEY_WARNINGS && ! in_array( $sev, Build_Plan_Draft_Schema::ENUM_SEVERITY, true ) ) {
					$errors[] = 'invalid_enum: severity';
				}
				break;
		}
		return $errors;
	}

	/**
	 * @param string                                                                                   $raw_status
	 * @param string                                                                                   $parse_status
	 * @param bool                                                                                     $top_level_valid
	 * @param string                                                                                   $schema_ref
	 * @param array<int, array{section: string, index?: int, valid: bool, errors: array<int, string>}> $record_results
	 * @param array<int, Dropped_Record_Report>                                                        $dropped
	 * @param array<string, mixed>|null                                                                $normalized
	 * @param string                                                                                   $blocking_stage
	 * @param bool                                                                                     $is_repair_attempt
	 * @return Validation_Report
	 */
	private function failed_report(
		string $raw_status,
		string $parse_status,
		bool $top_level_valid,
		string $schema_ref,
		array $record_results,
		array $dropped,
		?array $normalized,
		string $blocking_stage,
		bool $is_repair_attempt
	): Validation_Report {
		Named_Debug_Log::event(
			Named_Debug_Log_Event::OUTPUT_VALIDATION_FAILED,
			'schema=' . $schema_ref . ' stage=' . $blocking_stage . ' raw=' . $raw_status . ' parse=' . $parse_status . ' repair_attempt=' . ( $is_repair_attempt ? '1' : '0' )
		);
		return new Validation_Report(
			$raw_status,
			$parse_status,
			$top_level_valid,
			$schema_ref,
			$record_results,
			$dropped,
			$normalized,
			Validation_Report::STATE_FAILED,
			$blocking_stage,
			$is_repair_attempt,
			false
		);
	}
}
