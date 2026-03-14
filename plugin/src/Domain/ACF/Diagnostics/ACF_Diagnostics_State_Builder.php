<?php
/**
 * Builds view-state for ACF field-architecture diagnostics screen (spec §20, §20.13–20.15, §21, §59.12; Prompt 223).
 * Aggregates health card, registration status, assignment mismatches, LPagery support, and regeneration-plan summary.
 * Read-only; suitable for diagnostics bundles when build_for_bundle() is used.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Diagnostics;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Repair\ACF_Regeneration_Plan;
use AIOPageBuilder\Domain\ACF\Repair\ACF_Regeneration_Service;
use AIOPageBuilder\Domain\Rendering\LPagery\LPagery_Compatibility_Result;

/**
 * Builds acf_diagnostics_summary, field_architecture_health_card, assignment_mismatch_group, lpagery_field_support_summary.
 *
 * Example acf_diagnostics_summary payload (build_for_bundle()):
 * {
 *   "acf_present": true,
 *   "acf_active": true,
 *   "overall_status": "healthy|drift|partial|stale|blocked",
 *   "registered_count": 12,
 *   "missing_count": 0,
 *   "version_stale_count": 0,
 *   "assignment_mismatch_count": 0,
 *   "repair_readiness": "ready|not_needed|blocked",
 *   "lpagery_status": "absent|present_unused|supported|partial|blocked"
 * }
 */
final class ACF_Diagnostics_State_Builder {

	/** Overall status: all groups registered, assignments aligned. */
	public const OVERALL_HEALTHY = 'healthy';

	/** Overall status: missing or stale groups; repair can fix. */
	public const OVERALL_DRIFT = 'drift';

	/** Overall status: partially broken (e.g. some pages without structural source). */
	public const OVERALL_PARTIAL = 'partial';

	/** Overall status: version or registry stale. */
	public const OVERALL_STALE = 'stale';

	/** Overall status: ACF missing or dependency blocked. */
	public const OVERALL_BLOCKED = 'blocked';

	/** LPagery: not detected. */
	public const LPAGERY_ABSENT = 'absent';

	/** LPagery: present but not used for field mapping. */
	public const LPAGERY_PRESENT_UNUSED = 'present_unused';

	/** LPagery: field patterns supported. */
	public const LPAGERY_SUPPORTED = 'supported';

	/** LPagery: some fields unsupported. */
	public const LPAGERY_PARTIAL = 'partial';

	/** LPagery: blocked by unsupported field patterns. */
	public const LPAGERY_BLOCKED = 'blocked';

	/** @var ACF_Diagnostics_Service */
	private ACF_Diagnostics_Service $diagnostics_service;

	/** @var ACF_Regeneration_Service */
	private ACF_Regeneration_Service $regeneration_service;

	/** @var object|null Library_LPagery_Compatibility_Service when available. */
	private $lpagery_service;

	/**
	 * @param ACF_Diagnostics_Service   $diagnostics_service
	 * @param ACF_Regeneration_Service  $regeneration_service
	 * @param object|null              $lpagery_service Optional Library_LPagery_Compatibility_Service for LPagery summary.
	 */
	public function __construct(
		ACF_Diagnostics_Service $diagnostics_service,
		ACF_Regeneration_Service $regeneration_service,
		$lpagery_service = null
	) {
		$this->diagnostics_service  = $diagnostics_service;
		$this->regeneration_service = $regeneration_service;
		$this->lpagery_service      = $lpagery_service;
	}

	/**
	 * Builds full state for the ACF Architecture Diagnostics screen.
	 * Permission checks are caller's responsibility.
	 *
	 * @param string|null $filter_section_family      Optional section family key to filter by.
	 * @param string|null $filter_page_template_family Optional page template family to filter by.
	 * @param string|null $filter_severity            Optional severity: error, warning, info.
	 * @return array<string, mixed> Keys: acf_diagnostics_summary, field_architecture_health_card, assignment_mismatch_groups, lpagery_field_support_summary, regeneration_plan_summary, filters_applied.
	 */
	public function build(
		?string $filter_section_family = null,
		?string $filter_page_template_family = null,
		?string $filter_severity = null
	): array {
		$payload   = $this->diagnostics_service->get_full_payload();
		$options   = array(
			'include_page_assignments' => true,
		);
		if ( $filter_section_family !== null && $filter_section_family !== '' ) {
			$options['section_family_key'] = $filter_section_family;
		}
		if ( $filter_page_template_family !== null && $filter_page_template_family !== '' ) {
			$options['page_template_family_key'] = $filter_page_template_family;
		}
		$scope = ( $filter_section_family !== null && $filter_section_family !== '' )
			? ACF_Regeneration_Plan::SCOPE_SECTION_FAMILY
			: ( ( $filter_page_template_family !== null && $filter_page_template_family !== '' )
				? ACF_Regeneration_Plan::SCOPE_PAGE_TEMPLATE_FAMILY
				: ACF_Regeneration_Plan::SCOPE_FULL );
		$plan   = $this->regeneration_service->build_plan( true, $scope, $options );
		$plan_arr = $plan->to_array();

		$health_card = $this->build_field_architecture_health_card( $payload, $plan_arr );
		$assignment_mismatch_groups = $this->build_assignment_mismatch_groups( $payload, $plan_arr );
		$lpagery_summary = $this->build_lpagery_field_support_summary();
		$summary = $this->derive_acf_diagnostics_summary( $health_card, $lpagery_summary, $plan_arr );

		$out = array(
			'acf_diagnostics_summary'        => $summary,
			'field_architecture_health_card'  => $health_card,
			'assignment_mismatch_groups'      => $this->apply_severity_filter( $assignment_mismatch_groups, $filter_severity ),
			'lpagery_field_support_summary'   => $lpagery_summary,
			'regeneration_plan_summary'      => array(
				'dry_run'                    => $plan_arr['dry_run'] ?? true,
				'scope'                      => $plan_arr['scope'] ?? 'full',
				'missing_count'               => $plan_arr['missing_count'] ?? 0,
				'version_stale_count'        => $plan_arr['version_stale_count'] ?? 0,
				'ok_count'                   => $plan_arr['ok_count'] ?? 0,
				'candidate_count'            => $plan_arr['candidate_count'] ?? 0,
				'refused_cleanup'             => $plan_arr['refused_cleanup'] ?? array(),
			),
			'filters_applied'                => array(
				'section_family_key'       => $filter_section_family,
				'page_template_family_key' => $filter_page_template_family,
				'severity'                 => $filter_severity,
			),
		);

		return $out;
	}

	/**
	 * Builds a compact summary suitable for diagnostics bundles (e.g. Queue & Logs export).
	 * No secrets; stable shape for support.
	 *
	 * @return array<string, mixed> acf_diagnostics_summary subset.
	 */
	public function build_for_bundle(): array {
		$payload = $this->diagnostics_service->get_full_payload();
		$plan    = $this->regeneration_service->build_plan( true, ACF_Regeneration_Plan::SCOPE_FULL, array( 'include_page_assignments' => true ) );
		$plan_arr = $plan->to_array();
		$health_card = $this->build_field_architecture_health_card( $payload, $plan_arr );
		$lpagery_summary = $this->build_lpagery_field_support_summary();
		return $this->derive_acf_diagnostics_summary( $health_card, $lpagery_summary, $plan_arr );
	}

	/**
	 * Builds field_architecture_health_card from diagnostics payload and plan.
	 *
	 * @param array<string, mixed> $payload   From get_full_payload().
	 * @param array<string, mixed> $plan_arr  From ACF_Regeneration_Plan::to_array().
	 * @return array<string, mixed>
	 */
	private function build_field_architecture_health_card( array $payload, array $plan_arr ): array {
		$registered = $payload['registered_groups'] ?? array();
		$acf_available = (bool) ( $registered['acf_available'] ?? false );
		$expected_count = (int) ( $registered['expected_count'] ?? 0 );
		$blueprints = $payload['blueprints'] ?? array();
		$valid_blueprint_count = (int) ( $blueprints['valid_count'] ?? 0 );
		$page_assignments = $payload['page_assignments'] ?? array();
		$pages_with_assignments = (int) ( $page_assignments['pages_with_assignments'] ?? 0 );
		$pages_with_source = (int) ( $page_assignments['pages_with_structural_source'] ?? 0 );
		$missing_count = (int) ( $plan_arr['missing_count'] ?? 0 );
		$version_stale_count = (int) ( $plan_arr['version_stale_count'] ?? 0 );
		$ok_count = (int) ( $plan_arr['ok_count'] ?? 0 );
		$compat = $payload['compatibility_warnings'] ?? array();
		$compat_count = (int) ( $compat['count'] ?? 0 );
		$stale = $payload['stale_items'] ?? array();
		$stale_count = (int) ( $stale['count'] ?? 0 );

		return array(
			'acf_present'                 => $acf_available,
			'acf_active'                  => $acf_available,
			'expected_group_count'        => $expected_count,
			'registered_ok_count'         => $ok_count,
			'missing_group_count'         => $missing_count,
			'version_stale_count'         => $version_stale_count,
			'blueprint_valid_count'       => $valid_blueprint_count,
			'blueprint_family_coverage'   => $valid_blueprint_count > 0 ? 'covered' : 'none',
			'pages_with_assignments'       => $pages_with_assignments,
			'pages_with_structural_source' => $pages_with_source,
			'assignment_mismatch_pages'    => max( 0, $pages_with_assignments - $pages_with_source ),
			'compatibility_warning_count'  => $compat_count,
			'stale_assignment_count'       => $stale_count,
			'summary'                     => $registered['summary'] ?? '',
		);
	}

	/**
	 * Builds assignment_mismatch_group list from payload and plan.
	 *
	 * @param array<string, mixed> $payload
	 * @param array<string, mixed> $plan_arr
	 * @return list<array<string, mixed>>
	 */
	private function build_assignment_mismatch_groups( array $payload, array $plan_arr ): array {
		$candidates = $plan_arr['page_assignment_repair_candidates'] ?? array();
		$by_page = $payload['page_assignments']['by_page'] ?? array();
		$pages_with_source = (int) ( $payload['page_assignments']['pages_with_structural_source'] ?? 0 );
		$pages_with_assignments = (int) ( $payload['page_assignments']['pages_with_assignments'] ?? 0 );
		$groups = array();
		foreach ( $candidates as $c ) {
			$page_id = (int) ( $c['page_id'] ?? 0 );
			$type = (string) ( $c['type'] ?? '' );
			$key = (string) ( $c['key'] ?? '' );
			$group_keys = $by_page[ (string) $page_id ] ?? array();
			$groups[] = array(
				'page_id'       => $page_id,
				'type'          => $type,
				'key'           => $key,
				'group_count'   => is_array( $group_keys ) ? count( $group_keys ) : 0,
				'severity'      => $page_id > 0 ? 'warning' : 'info',
			);
		}
		if ( $pages_with_assignments > $pages_with_source ) {
			$groups[] = array(
				'page_id'       => 0,
				'type'          => 'summary',
				'key'           => '',
				'mismatch_count' => $pages_with_assignments - $pages_with_source,
				'severity'      => 'warning',
			);
		}
		return $groups;
	}

	/**
	 * Builds lpagery_field_support_summary when LPagery service is available.
	 *
	 * @return array<string, mixed>
	 */
	private function build_lpagery_field_support_summary(): array {
		if ( $this->lpagery_service === null || ! method_exists( $this->lpagery_service, 'get_compatibility_for_section' ) ) {
			return array(
				'status'               => self::LPAGERY_ABSENT,
				'summary'              => __( 'LPagery compatibility service not available.', 'aio-page-builder' ),
				'sections_supported'   => 0,
				'sections_unsupported' => 0,
				'sections_partial'     => 0,
			);
		}
		$payload = $this->diagnostics_service->get_full_payload();
		$valid = $payload['blueprints']['valid'] ?? array();
		$supported = 0;
		$unsupported = 0;
		$partial = 0;
		$reasons = array();
		foreach ( $valid as $item ) {
			$section_key = (string) ( $item['section_key'] ?? '' );
			if ( $section_key === '' ) {
				continue;
			}
			$result = $this->lpagery_service->get_compatibility_for_section( $section_key );
			if ( ! $result instanceof LPagery_Compatibility_Result ) {
				continue;
			}
			$state = $result->get_compatibility_state();
			if ( $state === LPagery_Compatibility_Result::STATE_SUPPORTED ) {
				$supported++;
			} elseif ( $state === LPagery_Compatibility_Result::STATE_UNSUPPORTED ) {
				$unsupported++;
				foreach ( $result->get_unsupported_mapping_reasons() as $r ) {
					$reasons[] = array( 'section_key' => $section_key, 'reason' => $r['reason'] ?? '' );
				}
			} else {
				$partial++;
			}
		}
		$total = $supported + $unsupported + $partial;
		if ( $total === 0 ) {
			$status = self::LPAGERY_PRESENT_UNUSED;
			$summary = __( 'No sections with blueprints to evaluate.', 'aio-page-builder' );
		} elseif ( $unsupported > 0 && $supported === 0 ) {
			$status = self::LPAGERY_BLOCKED;
			$summary = sprintf( __( '%d section(s) with unsupported field patterns; %d partial.', 'aio-page-builder' ), $unsupported, $partial );
		} elseif ( $unsupported > 0 ) {
			$status = self::LPAGERY_PARTIAL;
			$summary = sprintf( __( '%d supported, %d unsupported, %d partial.', 'aio-page-builder' ), $supported, $unsupported, $partial );
		} else {
			$status = self::LPAGERY_SUPPORTED;
			$summary = sprintf( __( '%d section(s) with LPagery-compatible field patterns.', 'aio-page-builder' ), $supported );
		}
		return array(
			'status'               => $status,
			'summary'              => $summary,
			'sections_supported'   => $supported,
			'sections_unsupported' => $unsupported,
			'sections_partial'     => $partial,
			'unsupported_reasons'  => array_slice( $reasons, 0, 10 ),
		);
	}

	/**
	 * Derives overall acf_diagnostics_summary from health card, LPagery summary, and plan.
	 *
	 * @param array<string, mixed> $health_card
	 * @param array<string, mixed> $lpagery_summary
	 * @param array<string, mixed> $plan_arr
	 * @return array<string, mixed>
	 */
	private function derive_acf_diagnostics_summary( array $health_card, array $lpagery_summary, array $plan_arr ): array {
		$acf_present = (bool) ( $health_card['acf_present'] ?? false );
		$missing_count = (int) ( $health_card['missing_group_count'] ?? 0 );
		$version_stale_count = (int) ( $health_card['version_stale_count'] ?? 0 );
		$candidate_count = (int) ( $plan_arr['candidate_count'] ?? 0 );
		$assignment_mismatch = (int) ( $health_card['assignment_mismatch_pages'] ?? 0 );

		if ( ! $acf_present ) {
			$overall_status = self::OVERALL_BLOCKED;
			$repair_readiness = 'blocked';
		} elseif ( $missing_count > 0 || $version_stale_count > 0 ) {
			$overall_status = $version_stale_count > 0 ? self::OVERALL_STALE : self::OVERALL_DRIFT;
			$repair_readiness = 'ready';
		} elseif ( $assignment_mismatch > 0 ) {
			$overall_status = self::OVERALL_PARTIAL;
			$repair_readiness = $candidate_count > 0 ? 'ready' : 'not_needed';
		} else {
			$overall_status = self::OVERALL_HEALTHY;
			$repair_readiness = 'not_needed';
		}

		return array(
			'acf_present'              => $acf_present,
			'acf_active'               => (bool) ( $health_card['acf_active'] ?? $acf_present ),
			'overall_status'           => $overall_status,
			'registered_count'         => (int) ( $health_card['registered_ok_count'] ?? 0 ),
			'missing_count'            => $missing_count,
			'version_stale_count'      => $version_stale_count,
			'assignment_mismatch_count' => $assignment_mismatch,
			'repair_readiness'         => $repair_readiness,
			'lpagery_status'           => (string) ( $lpagery_summary['status'] ?? self::LPAGERY_ABSENT ),
		);
	}

	/**
	 * Filters assignment_mismatch_groups by severity when filter_severity is set.
	 *
	 * @param list<array<string, mixed>> $groups
	 * @param string|null                $filter_severity
	 * @return list<array<string, mixed>>
	 */
	private function apply_severity_filter( array $groups, ?string $filter_severity ): array {
		if ( $filter_severity === null || $filter_severity === '' ) {
			return $groups;
		}
		$filter_severity = strtolower( $filter_severity );
		return array_values( array_filter( $groups, function ( array $g ) use ( $filter_severity ): bool {
			$severity = strtolower( (string) ( $g['severity'] ?? '' ) );
			return $severity === $filter_severity;
		} ) );
	}
}
