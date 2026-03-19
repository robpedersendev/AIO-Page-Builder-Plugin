<?php
/**
 * Internal stale-content report generator (Prompt 556). Scores industry assets by age and groups
 * into refresh queues. Advisory only; no auto-edit. See industry-asset-aging-scoring-contract.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

/**
 * Generates a bounded report of aging industry assets for maintainer triage.
 */
final class Industry_Asset_Aging_Report_Service {

	/** Severity: low risk; schedule for next maintenance window. */
	public const SEVERITY_BENIGN = 'benign';

	/** Severity: consider review; not urgent. */
	public const SEVERITY_ADVISORY = 'advisory';

	/** Severity: high-impact stale; flag for maintainer review. */
	public const SEVERITY_HIGH_IMPACT = 'high_impact';

	/** Item key: asset ref (path or logical id). */
	public const ITEM_ASSET_REF = 'asset_ref';

	/** Item key: asset class. */
	public const ITEM_ASSET_CLASS = 'asset_class';

	/** Item key: days since last modified. */
	public const ITEM_DAYS_OLD = 'days_old';

	/** Item key: age score 0–100 (higher = older / more stale). */
	public const ITEM_AGE_SCORE = 'age_score';

	/** Item key: severity band. */
	public const ITEM_SEVERITY = 'severity';

	/** Item key: short rationale. */
	public const ITEM_RATIONALE = 'rationale';

	/** Item key: suggested review priority 1–5 (1 = highest). */
	public const ITEM_SUGGESTED_REVIEW_PRIORITY = 'suggested_review_priority';

	/** Days threshold: beyond this age is advisory. */
	private const DAYS_ADVISORY = 90;

	/** Days threshold: beyond this age is high-impact (without criticality boost). */
	private const DAYS_HIGH_IMPACT = 180;

	/** Days threshold: very old regardless of criticality. */
	private const DAYS_VERY_OLD = 365;

	/** Max items per report to keep it bounded. */
	private const MAX_ITEMS = 500;

	/** Path patterns relative to plugin src: directory suffix => asset_class. Builtin aggregators excluded. */
	private const PATH_CLASS_MAP = array(
		'Docs/SectionHelperOverlays'                    => 'overlay_section_helper',
		'Docs/SubtypeSectionHelperOverlays'             => 'overlay_subtype_section',
		'Docs/GoalSectionHelperOverlays'                => 'overlay_goal_section',
		'Docs/SubtypeGoalOverlays'                      => 'overlay_subtype_goal_section',
		'Docs/SecondaryGoalSectionHelperOverlays'       => 'overlay_secondary_goal_section',
		'Docs/PageOnePagerOverlays'                     => 'overlay_page_onepager',
		'Docs/SubtypePageOnePagerOverlays'              => 'overlay_subtype_page',
		'Docs/GoalPageOnePagerOverlays'                 => 'overlay_goal_page',
		'Docs/SubtypeGoalOverlays'                      => 'overlay_subtype_goal_page',
		'Docs/SecondaryGoalPageOnePagerOverlays'        => 'overlay_secondary_goal_page',
		'Registry/StarterBundles'                       => 'bundle_starter',
		'Registry/StarterBundles/SubtypeGoalOverlays'   => 'overlay_subtype_goal_bundle',
		'Registry/StarterBundles/SecondaryGoalOverlays' => 'overlay_secondary_goal_bundle',
		'Registry/CTAPatterns'                          => 'rule_cta',
		'Registry/GoalCautionRules'                     => 'rule_goal_caution',
		'Registry/SecondaryGoalCautionRules'            => 'rule_secondary_goal_caution',
	);

	/** Asset classes treated as high usage-criticality (user-facing overlays, bundles). */
	private const HIGH_CRITICALITY_CLASSES = array(
		'overlay_section_helper',
		'overlay_page_onepager',
		'overlay_subtype_section',
		'overlay_subtype_page',
		'overlay_goal_section',
		'overlay_goal_page',
		'overlay_subtype_goal_section',
		'overlay_subtype_goal_page',
		'overlay_secondary_goal_section',
		'overlay_secondary_goal_page',
		'bundle_starter',
		'overlay_subtype_goal_bundle',
		'overlay_secondary_goal_bundle',
	);

	/** @var string Base path for scanning (e.g. plugin src directory). */
	private string $base_path;

	public function __construct( string $base_path = '' ) {
		$this->base_path = $base_path !== '' ? rtrim( str_replace( '\\', '/', $base_path ), '/' ) : '';
	}

	/**
	 * Generates the stale-content report. Groups by asset class and severity; highlights high-impact stale.
	 *
	 * @return array{
	 *   summary: array{total: int, benign: int, advisory: int, high_impact: int, by_class: array<string, int>},
	 *   items: list<array{asset_ref: string, asset_class: string, days_old: int, age_score: int, severity: string, rationale: string, suggested_review_priority: int}>,
	 *   by_class: array<string, list<array>>,
	 *   by_severity: array<string, list<array>>,
	 *   high_impact_stale: list<array>,
	 *   generated_at: string
	 * }
	 */
	public function generate_report(): array {
		$base  = $this->resolve_base_path();
		$raw   = $this->collect_assets( $base );
		$items = array();
		foreach ( $raw as $path => $asset_class ) {
			$full = $base . '/' . $path;
			if ( ! is_file( $full ) ) {
				continue;
			}
			$mtime = filemtime( $full );
			if ( $mtime === false ) {
				continue;
			}
			$days_old  = (int) floor( ( time() - $mtime ) / 86400 );
			$age_score = min( 100, (int) round( $days_old * 0.5 ) ); // Cap at 100
			$high_crit = in_array( $asset_class, self::HIGH_CRITICALITY_CLASSES, true );
			$severity  = $this->band_severity( $days_old, $high_crit );
			$priority  = $this->suggest_priority( $severity, $days_old, $high_crit );
			$rationale = $this->build_rationale( $days_old, $severity, $high_crit );
			$items[]   = array(
				self::ITEM_ASSET_REF                 => $path,
				self::ITEM_ASSET_CLASS               => $asset_class,
				self::ITEM_DAYS_OLD                  => $days_old,
				self::ITEM_AGE_SCORE                 => $age_score,
				self::ITEM_SEVERITY                  => $severity,
				self::ITEM_RATIONALE                 => $rationale,
				self::ITEM_SUGGESTED_REVIEW_PRIORITY => $priority,
			);
			if ( count( $items ) >= self::MAX_ITEMS ) {
				break;
			}
		}
		$by_class          = $this->group_by( $items, self::ITEM_ASSET_CLASS );
		$by_severity       = $this->group_by( $items, self::ITEM_SEVERITY );
		$high_impact_stale = isset( $by_severity[ self::SEVERITY_HIGH_IMPACT ] ) ? $by_severity[ self::SEVERITY_HIGH_IMPACT ] : array();
		$summary           = array(
			'total'       => count( $items ),
			'benign'      => count( $by_severity[ self::SEVERITY_BENIGN ] ?? array() ),
			'advisory'    => count( $by_severity[ self::SEVERITY_ADVISORY ] ?? array() ),
			'high_impact' => count( $high_impact_stale ),
			'by_class'    => array_map( 'count', $by_class ),
		);
		return array(
			'summary'           => $summary,
			'items'             => $items,
			'by_class'          => $by_class,
			'by_severity'       => $by_severity,
			'high_impact_stale' => $high_impact_stale,
			'generated_at'      => gmdate( 'Y-m-d\TH:i:s\Z' ),
		);
	}

	private function resolve_base_path(): string {
		if ( $this->base_path !== '' ) {
			return $this->base_path;
		}
		$dir = __DIR__;
		// From Reporting/ go up to Domain/Industry then use as base for relative paths.
		return dirname( $dir );
	}

	/**
	 * Collects relative paths and asset class for industry definition files.
	 *
	 * @param string $base Absolute path to Domain/Industry (or plugin src).
	 * @return array<string, string> path => asset_class
	 */
	private function collect_assets( string $base ): array {
		$out           = array();
		$industry_base = $base;
		if ( substr( $industry_base, -8 ) !== 'Industry' ) {
			$industry_base = $base . '/Domain/Industry';
		}
		if ( ! is_dir( $industry_base ) ) {
			return $out;
		}
		foreach ( self::PATH_CLASS_MAP as $dir_suffix => $asset_class ) {
			$full_dir = $industry_base . '/' . $dir_suffix;
			if ( ! is_dir( $full_dir ) ) {
				continue;
			}
			$this->scan_dir( $full_dir, $dir_suffix, $asset_class, $industry_base, $out );
		}
		return $out;
	}

	private function scan_dir( string $full_dir, string $dir_suffix, string $asset_class, string $industry_base, array &$out ): void {
		$files = @scandir( $full_dir );
		if ( ! is_array( $files ) ) {
			return;
		}
		foreach ( $files as $f ) {
			if ( $f === '.' || $f === '..' ) {
				continue;
			}
			$path = $full_dir . '/' . $f;
			if ( is_dir( $path ) ) {
				$sub_suffix = $dir_suffix . '/' . $f;
				$sub_class  = self::PATH_CLASS_MAP[ $sub_suffix ] ?? $asset_class;
				$this->scan_dir( $path, $sub_suffix, $sub_class, $industry_base, $out );
				continue;
			}
			if ( substr( $f, -4 ) !== '.php' ) {
				continue;
			}
			// Skip Builtin aggregators (they don't define content; they include).
			if ( strpos( $f, 'Builtin_' ) === 0 ) {
				continue;
			}
			$rel = str_replace( '\\', '/', $path );
			$rel = preg_replace( '#^' . preg_quote( str_replace( '\\', '/', $industry_base ), '#' ) . '/#', '', $rel );
			if ( $rel !== '' && $rel !== null ) {
				$out[ $rel ] = $asset_class;
			}
		}
	}

	private function band_severity( int $days_old, bool $high_criticality ): string {
		if ( $days_old >= self::DAYS_VERY_OLD ) {
			return self::SEVERITY_HIGH_IMPACT;
		}
		if ( $days_old >= self::DAYS_HIGH_IMPACT && $high_criticality ) {
			return self::SEVERITY_HIGH_IMPACT;
		}
		if ( $days_old >= self::DAYS_ADVISORY ) {
			return self::SEVERITY_ADVISORY;
		}
		return self::SEVERITY_BENIGN;
	}

	private function suggest_priority( string $severity, int $days_old, bool $high_criticality ): int {
		if ( $severity === self::SEVERITY_HIGH_IMPACT && $high_criticality ) {
			return 1;
		}
		if ( $severity === self::SEVERITY_HIGH_IMPACT ) {
			return 2;
		}
		if ( $severity === self::SEVERITY_ADVISORY && $high_criticality ) {
			return 3;
		}
		if ( $severity === self::SEVERITY_ADVISORY ) {
			return 4;
		}
		return 5;
	}

	private function build_rationale( int $days_old, string $severity, bool $high_criticality ): string {
		$parts   = array();
		$parts[] = $days_old . ' days since last file change';
		if ( $high_criticality ) {
			$parts[] = 'high-usage asset';
		}
		if ( $severity === self::SEVERITY_HIGH_IMPACT ) {
			$parts[] = 'consider review this cycle';
		} elseif ( $severity === self::SEVERITY_ADVISORY ) {
			$parts[] = 'schedule for next maintenance window';
		}
		return implode( '; ', $parts );
	}

	/**
	 * @param list<array<string, mixed>> $items
	 * @param string                     $key
	 * @return array<string, list<array<string, mixed>>>
	 */
	private function group_by( array $items, string $key ): array {
		$out = array();
		foreach ( $items as $item ) {
			$v = isset( $item[ $key ] ) && is_string( $item[ $key ] ) ? $item[ $key ] : '';
			if ( $v === '' ) {
				continue;
			}
			if ( ! isset( $out[ $v ] ) ) {
				$out[ $v ] = array();
			}
			$out[ $v ][] = $item;
		}
		return $out;
	}
}
