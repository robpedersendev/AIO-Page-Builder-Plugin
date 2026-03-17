<?php
/**
 * Scaffold completeness report generator (Prompt 538).
 * Evaluates scaffold packs and subtype scaffolds for presence of required artifact classes;
 * distinguishes missing scaffolding from scaffolded (draft/placeholder) vs authored (active).
 * Internal-only; advisory; no scaffold activation or auto-promotion.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;

/**
 * Produces advisory report on scaffold completeness: which artifact classes are missing, scaffolded, or authored.
 */
final class Industry_Scaffold_Completeness_Report_Service {

	public const STATE_MISSING    = 'missing';
	public const STATE_SCAFFOLDED = 'scaffolded';
	public const STATE_AUTHORED   = 'authored';
	public const STATE_NOT_EVALUATED = 'not_evaluated';

	public const ARTIFACT_PACK         = 'pack_definition';
	public const ARTIFACT_STARTER_BUNDLE = 'starter_bundle';
	public const ARTIFACT_SECTION_OVERLAY = 'section_helper_overlay';
	public const ARTIFACT_PAGE_OVERLAY   = 'page_onepager_overlay';
	public const ARTIFACT_RULES        = 'rules';
	public const ARTIFACT_DOCS         = 'docs';
	public const ARTIFACT_QA           = 'qa_evidence';

	/** @var Industry_Pack_Registry|null */
	private ?Industry_Pack_Registry $pack_registry;

	/** @var Industry_Starter_Bundle_Registry|null */
	private ?Industry_Starter_Bundle_Registry $bundle_registry;

	/** @var Industry_Subtype_Registry|null */
	private ?Industry_Subtype_Registry $subtype_registry;

	public function __construct(
		?Industry_Pack_Registry $pack_registry = null,
		?Industry_Starter_Bundle_Registry $bundle_registry = null,
		?Industry_Subtype_Registry $subtype_registry = null
	) {
		$this->pack_registry   = $pack_registry;
		$this->bundle_registry = $bundle_registry;
		$this->subtype_registry = $subtype_registry;
	}

	/**
	 * Generates scaffold completeness report. Evaluates scaffold sets for required artifact classes.
	 *
	 * @param array<string, mixed> $options Optional: scaffold_industry_keys (list), scaffold_subtype_keys (list of subtype_key), include_draft_packs (bool, default true to discover draft packs), include_draft_subtypes (bool, default true).
	 * @return array{
	 *   generated_at: string,
	 *   scaffold_results: list<array{scaffold_type: string, scaffold_key: string, artifact_classes: array<string, string>, summary: string}>,
	 *   readable_summary: list<string>,
	 *   warnings: list<string>
	 * }
	 */
	public function generate_report( array $options = array() ): array {
		$warnings = array();
		$industry_keys = isset( $options['scaffold_industry_keys'] ) && is_array( $options['scaffold_industry_keys'] )
			? array_values( array_filter( array_map( function ( $k ) { return is_string( $k ) ? trim( $k ) : ''; }, $options['scaffold_industry_keys'] ) ) )
			: array();
		$subtype_keys = isset( $options['scaffold_subtype_keys'] ) && is_array( $options['scaffold_subtype_keys'] )
			? array_values( array_filter( array_map( function ( $k ) { return is_string( $k ) ? trim( $k ) : ''; }, $options['scaffold_subtype_keys'] ) ) )
			: array();
		$include_draft_packs    = $options['include_draft_packs'] ?? true;
		$include_draft_subtypes = $options['include_draft_subtypes'] ?? true;

		if ( $industry_keys === array() && $this->pack_registry !== null && $include_draft_packs ) {
			$draft_packs = $this->pack_registry->list_by_status( Industry_Pack_Schema::STATUS_DRAFT );
			foreach ( $draft_packs as $pack ) {
				$k = isset( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
					? trim( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
					: '';
				if ( $k !== '' && ! in_array( $k, $industry_keys, true ) ) {
					$industry_keys[] = $k;
				}
			}
		}
		if ( $subtype_keys === array() && $this->subtype_registry !== null && $include_draft_subtypes ) {
			$all = $this->subtype_registry->get_all();
			foreach ( $all as $sub ) {
				$status = $sub[ Industry_Subtype_Registry::FIELD_STATUS ] ?? '';
				if ( $status === Industry_Subtype_Registry::STATUS_DRAFT ) {
					$k = isset( $sub[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] ) && is_string( $sub[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] )
						? trim( $sub[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] )
						: '';
					if ( $k !== '' && ! in_array( $k, $subtype_keys, true ) ) {
						$subtype_keys[] = $k;
					}
				}
			}
		}

		$scaffold_results = array();
		foreach ( $industry_keys as $key ) {
			$scaffold_results[] = $this->evaluate_industry_scaffold( $key );
		}
		foreach ( $subtype_keys as $key ) {
			$scaffold_results[] = $this->evaluate_subtype_scaffold( $key );
		}

		$readable = array();
		foreach ( $scaffold_results as $r ) {
			$readable[] = sprintf(
				'%s "%s": %s',
				$r['scaffold_type'] === 'industry' ? 'Industry' : 'Subtype',
				$r['scaffold_key'],
				$r['summary']
			);
		}
		if ( $scaffold_results === array() && $industry_keys === array() && $subtype_keys === array() ) {
			$warnings[] = 'No scaffold sets to evaluate; pass scaffold_industry_keys or scaffold_subtype_keys, or ensure draft packs/subtypes exist in registries.';
		}

		return array(
			'generated_at'      => gmdate( 'c' ),
			'scaffold_results'  => $scaffold_results,
			'readable_summary'  => $readable,
			'warnings'          => $warnings,
		);
	}

	/**
	 * @return array{scaffold_type: string, scaffold_key: string, artifact_classes: array<string, string>, summary: string}
	 */
	private function evaluate_industry_scaffold( string $industry_key ): array {
		$classes = array(
			self::ARTIFACT_PACK           => self::STATE_MISSING,
			self::ARTIFACT_STARTER_BUNDLE => self::STATE_MISSING,
			self::ARTIFACT_SECTION_OVERLAY => self::STATE_NOT_EVALUATED,
			self::ARTIFACT_PAGE_OVERLAY   => self::STATE_NOT_EVALUATED,
			self::ARTIFACT_RULES          => self::STATE_NOT_EVALUATED,
			self::ARTIFACT_DOCS           => self::STATE_NOT_EVALUATED,
			self::ARTIFACT_QA             => self::STATE_NOT_EVALUATED,
		);

		if ( $this->pack_registry !== null ) {
			$pack = $this->pack_registry->get( $industry_key );
			if ( $pack !== null && is_array( $pack ) ) {
				$status = trim( (string) ( $pack[ Industry_Pack_Schema::FIELD_STATUS ] ?? '' ) );
				$classes[ self::ARTIFACT_PACK ] = $status === Industry_Pack_Schema::STATUS_ACTIVE ? self::STATE_AUTHORED : self::STATE_SCAFFOLDED;
			}
		}

		if ( $this->bundle_registry !== null ) {
			$bundles = $this->bundle_registry->get_for_industry( $industry_key, '' );
			if ( $bundles === array() ) {
				$classes[ self::ARTIFACT_STARTER_BUNDLE ] = self::STATE_MISSING;
			} else {
				$has_active = false;
				$has_draft  = false;
				foreach ( $bundles as $b ) {
					$st = $b[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] ?? '';
					if ( $st === Industry_Starter_Bundle_Registry::STATUS_ACTIVE ) {
						$has_active = true;
					} elseif ( $st === Industry_Starter_Bundle_Registry::STATUS_DRAFT ) {
						$has_draft = true;
					}
				}
				$classes[ self::ARTIFACT_STARTER_BUNDLE ] = $has_active ? self::STATE_AUTHORED : ( $has_draft ? self::STATE_SCAFFOLDED : self::STATE_MISSING );
			}
		}

		$summary = $this->summarize_artifact_states( $classes );
		return array(
			'scaffold_type'     => 'industry',
			'scaffold_key'      => $industry_key,
			'artifact_classes'  => $classes,
			'summary'           => $summary,
		);
	}

	/**
	 * @return array{scaffold_type: string, scaffold_key: string, artifact_classes: array<string, string>, summary: string}
	 */
	private function evaluate_subtype_scaffold( string $subtype_key ): array {
		$classes = array(
			self::ARTIFACT_PACK           => self::STATE_NOT_EVALUATED,
			self::ARTIFACT_STARTER_BUNDLE => self::STATE_MISSING,
			self::ARTIFACT_SECTION_OVERLAY => self::STATE_NOT_EVALUATED,
			self::ARTIFACT_PAGE_OVERLAY   => self::STATE_NOT_EVALUATED,
			self::ARTIFACT_RULES          => self::STATE_NOT_EVALUATED,
			self::ARTIFACT_DOCS           => self::STATE_NOT_EVALUATED,
			self::ARTIFACT_QA             => self::STATE_NOT_EVALUATED,
		);

		$parent_key = '';
		if ( $this->subtype_registry !== null ) {
			$def = $this->subtype_registry->get( $subtype_key );
			if ( $def !== null && is_array( $def ) ) {
				$parent_key = trim( (string) ( $def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ?? '' ) );
			}
		}

		if ( $parent_key !== '' && $this->bundle_registry !== null ) {
			$bundles = $this->bundle_registry->get_for_industry( $parent_key, $subtype_key );
			if ( $bundles !== array() ) {
				$has_active = false;
				$has_draft  = false;
				foreach ( $bundles as $b ) {
					$st = $b[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] ?? '';
					if ( $st === Industry_Starter_Bundle_Registry::STATUS_ACTIVE ) {
						$has_active = true;
					} elseif ( $st === Industry_Starter_Bundle_Registry::STATUS_DRAFT ) {
						$has_draft = true;
					}
				}
				$classes[ self::ARTIFACT_STARTER_BUNDLE ] = $has_active ? self::STATE_AUTHORED : ( $has_draft ? self::STATE_SCAFFOLDED : self::STATE_MISSING );
			}
		}

		$summary = $this->summarize_artifact_states( $classes );
		return array(
			'scaffold_type'     => 'subtype',
			'scaffold_key'      => $subtype_key,
			'artifact_classes'  => $classes,
			'summary'           => $summary,
		);
	}

	/**
	 * @param array<string, string> $classes
	 */
	private function summarize_artifact_states( array $classes ): string {
		$missing = 0;
		$scaffolded = 0;
		$authored = 0;
		$na = 0;
		foreach ( $classes as $state ) {
			if ( $state === self::STATE_MISSING ) {
				$missing++;
			} elseif ( $state === self::STATE_SCAFFOLDED ) {
				$scaffolded++;
			} elseif ( $state === self::STATE_AUTHORED ) {
				$authored++;
			} else {
				$na++;
			}
		}
		$parts = array();
		if ( $missing > 0 ) {
			$parts[] = "{$missing} missing";
		}
		if ( $scaffolded > 0 ) {
			$parts[] = "{$scaffolded} scaffolded";
		}
		if ( $authored > 0 ) {
			$parts[] = "{$authored} authored";
		}
		if ( $na > 0 ) {
			$parts[] = "{$na} not evaluated";
		}
		return $parts === array() ? 'No artifact classes' : implode( ', ', $parts );
	}
}
