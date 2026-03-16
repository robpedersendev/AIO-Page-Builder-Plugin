<?php
/**
 * Internal exportable documentation summary for the industry subsystem (Prompt 458).
 * Composes diagnostics snapshot, health check, and override audit into one bounded report
 * for support handoffs and internal review. Admin/support-only; no secrets or raw payloads.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;

/**
 * Produces a bounded, exportable summary of active industry profile, packs, bundles,
 * overrides, health status, and major warnings. Safe for support and migration review.
 */
final class Industry_Documentation_Summary_Export_Service {

	/** Maximum sample issues included in summary to keep output bounded. */
	private const MAX_SAMPLE_ERRORS = 5;

	/** Maximum sample warnings included in summary. */
	private const MAX_SAMPLE_WARNINGS = 10;

	/** @var Industry_Diagnostics_Service|null */
	private $diagnostics;

	/** @var Industry_Health_Check_Service|null */
	private $health_check;

	/** @var Industry_Override_Audit_Report_Service|null */
	private $override_audit;

	/** @var Industry_Profile_Repository|null */
	private $profile_repository;

	public function __construct(
		?Industry_Diagnostics_Service $diagnostics = null,
		?Industry_Health_Check_Service $health_check = null,
		?Industry_Override_Audit_Report_Service $override_audit = null,
		?Industry_Profile_Repository $profile_repository = null
	) {
		$this->diagnostics        = $diagnostics;
		$this->health_check       = $health_check;
		$this->override_audit     = $override_audit;
		$this->profile_repository = $profile_repository;
	}

	/**
	 * Generates a bounded documentation summary. No secrets; artifact refs and counts only.
	 *
	 * @return array{
	 *   generated_at: string,
	 *   profile_state: array{primary_industry: string, secondary_industries: list<string>, profile_readiness: string, selected_starter_bundle_key: string|null, industry_subtype_key: string|null},
	 *   active_pack_refs: list<string>,
	 *   override_summary: array{total_count: int, by_type: array<string, int>},
	 *   health: array{error_count: int, warning_count: int, sample_errors: list<array{object_type: string, key: string, issue_summary: string}>, sample_warnings: list<array{object_type: string, key: string, issue_summary: string}>},
	 *   major_warnings: list<string>
	 * }
	 */
	public function generate(): array {
		$profile_state = array(
			'primary_industry'             => '',
			'secondary_industries'         => array(),
			'profile_readiness'            => 'none',
			'selected_starter_bundle_key'  => null,
			'industry_subtype_key'         => null,
		);
		$active_pack_refs = array();
		$override_summary = array( 'total_count' => 0, 'by_type' => array() );
		$health = array(
			'error_count'    => 0,
			'warning_count'  => 0,
			'sample_errors'   => array(),
			'sample_warnings' => array(),
		);
		$major_warnings = array();

		if ( $this->diagnostics !== null ) {
			$snapshot = $this->diagnostics->get_snapshot();
			$profile_state['primary_industry']   = isset( $snapshot['primary_industry'] ) && is_string( $snapshot['primary_industry'] ) ? $snapshot['primary_industry'] : '';
			$profile_state['secondary_industries'] = isset( $snapshot['secondary_industries'] ) && is_array( $snapshot['secondary_industries'] ) ? $snapshot['secondary_industries'] : array();
			$profile_state['profile_readiness'] = isset( $snapshot['profile_readiness'] ) && is_string( $snapshot['profile_readiness'] ) ? $snapshot['profile_readiness'] : 'none';
			$active_pack_refs = isset( $snapshot['active_pack_refs'] ) && is_array( $snapshot['active_pack_refs'] ) ? $snapshot['active_pack_refs'] : array();
			if ( isset( $snapshot['warnings'] ) && is_array( $snapshot['warnings'] ) ) {
				$major_warnings = array_merge( $major_warnings, $snapshot['warnings'] );
			}
		}

		if ( $this->profile_repository !== null ) {
			$profile = $this->profile_repository->get_profile();
			$bundle = isset( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
				? trim( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
				: '';
			$profile_state['selected_starter_bundle_key'] = $bundle !== '' ? $bundle : null;
			$subtype = isset( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
				? trim( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
				: '';
			$profile_state['industry_subtype_key'] = $subtype !== '' ? $subtype : null;
		}

		if ( $this->override_audit !== null ) {
			$report = $this->override_audit->build_report();
			$override_summary['total_count'] = isset( $report['total_count'] ) && is_int( $report['total_count'] ) ? $report['total_count'] : 0;
			if ( isset( $report['by_type'] ) && is_array( $report['by_type'] ) ) {
				foreach ( $report['by_type'] as $target_type => $data ) {
					$override_summary['by_type'][ $target_type ] = isset( $data['count'] ) && is_int( $data['count'] ) ? $data['count'] : 0;
				}
			}
		}

		if ( $this->health_check !== null ) {
			$result = $this->health_check->run();
			$errors   = isset( $result['errors'] ) && is_array( $result['errors'] ) ? $result['errors'] : array();
			$warnings = isset( $result['warnings'] ) && is_array( $result['warnings'] ) ? $result['warnings'] : array();
			$health['error_count']   = count( $errors );
			$health['warning_count'] = count( $warnings );
			foreach ( array_slice( $errors, 0, self::MAX_SAMPLE_ERRORS ) as $issue ) {
				$health['sample_errors'][] = array(
					'object_type'   => isset( $issue['object_type'] ) ? (string) $issue['object_type'] : '',
					'key'           => isset( $issue['key'] ) ? (string) $issue['key'] : '',
					'issue_summary' => isset( $issue['issue_summary'] ) ? (string) $issue['issue_summary'] : '',
				);
			}
			foreach ( array_slice( $warnings, 0, self::MAX_SAMPLE_WARNINGS ) as $issue ) {
				$health['sample_warnings'][] = array(
					'object_type'   => isset( $issue['object_type'] ) ? (string) $issue['object_type'] : '',
					'key'           => isset( $issue['key'] ) ? (string) $issue['key'] : '',
					'issue_summary' => isset( $issue['issue_summary'] ) ? (string) $issue['issue_summary'] : '',
				);
			}
			foreach ( $warnings as $issue ) {
				$summary = isset( $issue['issue_summary'] ) ? (string) $issue['issue_summary'] : '';
				if ( $summary !== '' && ! in_array( $summary, $major_warnings, true ) ) {
					$major_warnings[] = $summary;
				}
			}
		}

		return array(
			'generated_at'     => gmdate( 'c' ),
			'profile_state'    => $profile_state,
			'active_pack_refs'  => array_values( $active_pack_refs ),
			'override_summary' => $override_summary,
			'health'           => $health,
			'major_warnings'   => array_values( array_slice( $major_warnings, 0, 20 ) ),
		);
	}
}
