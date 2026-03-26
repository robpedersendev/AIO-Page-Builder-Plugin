<?php
/**
 * Merges starter-bundle and registry template rows; cycles template reuse to guarantee minimum breadth.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Planning;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

/**
 * Deterministic post-AI enrichment when new_pages_to_create is below {@see Planning_Breadth_Constants::MIN_NEW_PAGES_TARGET}.
 */
final class Planning_Thin_Output_Enrichment_Service {

	/** @var Industry_Starter_Bundle_Registry */
	private Industry_Starter_Bundle_Registry $bundle_registry;

	public function __construct( Industry_Starter_Bundle_Registry $bundle_registry ) {
		$this->bundle_registry = $bundle_registry;
	}

	/**
	 * @param array<string, mixed> $normalized Validated normalized build-plan draft.
	 * @param array<string, mixed> $context    Keys: crawl_empty, subtype_bundle_refs, template_recommendation_context.
	 * @return array<string, mixed>
	 */
	public function enrich( array $normalized, array $context ): array {
		$target = Planning_Breadth_Constants::MIN_NEW_PAGES_TARGET;
		$pages  = isset( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] ) && is_array( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] )
			? $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ]
			: array();
		$count  = count( $pages );
		if ( $count >= $target ) {
			return $normalized;
		}

		$crawl_empty = ! empty( $context['crawl_empty'] );
		$run_summary = isset( $normalized[ Build_Plan_Draft_Schema::KEY_RUN_SUMMARY ] ) && is_array( $normalized[ Build_Plan_Draft_Schema::KEY_RUN_SUMMARY ] )
			? $normalized[ Build_Plan_Draft_Schema::KEY_RUN_SUMMARY ]
			: array();
		$mode        = isset( $run_summary[ Build_Plan_Draft_Schema::RUN_SUMMARY_PLANNING_MODE ] ) && is_string( $run_summary[ Build_Plan_Draft_Schema::RUN_SUMMARY_PLANNING_MODE ] )
			? $run_summary[ Build_Plan_Draft_Schema::RUN_SUMMARY_PLANNING_MODE ]
			: '';
		if ( ! $crawl_empty && $mode !== 'new_site' && $mode !== 'mixed' ) {
			return $normalized;
		}

		$used_template_keys = array();
		$used_slugs         = array();
		foreach ( $pages as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$tk = isset( $row['template_key'] ) && is_string( $row['template_key'] ) ? trim( $row['template_key'] ) : '';
			if ( $tk !== '' ) {
				$used_template_keys[ $tk ] = true;
			}
			$sl = isset( $row['proposed_slug'] ) && is_string( $row['proposed_slug'] ) ? trim( $row['proposed_slug'] ) : '';
			if ( $sl !== '' ) {
				$used_slugs[ $sl ] = true;
			}
		}

		$cycle_keys  = array();
		$added       = 0;
		$new_pages   = $pages;
		$bundle_refs = isset( $context['subtype_bundle_refs'] ) && is_array( $context['subtype_bundle_refs'] ) ? $context['subtype_bundle_refs'] : array();
		foreach ( $bundle_refs as $ref ) {
			if ( $count + $added >= $target ) {
				break;
			}
			if ( ! is_string( $ref ) || trim( $ref ) === '' ) {
				continue;
			}
			$bundle = $this->bundle_registry->get( trim( $ref ) );
			if ( $bundle === null || ( isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] ) && (string) $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] !== Industry_Starter_Bundle_Registry::STATUS_ACTIVE ) ) {
				continue;
			}
			$templates = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ] ) && is_array( $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ] )
				? $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ]
				: array();
			$sections  = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS ] ) && is_array( $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS ] )
				? $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS ]
				: array();
			foreach ( $templates as $template_key ) {
				if ( $count + $added >= $target ) {
					break 2;
				}
				if ( ! is_string( $template_key ) || trim( $template_key ) === '' ) {
					continue;
				}
				$template_key = trim( $template_key );
				if ( isset( $used_template_keys[ $template_key ] ) ) {
					continue;
				}
				$row = $this->make_page_row_from_template( $template_key, $sections, __( 'Merged from industry starter bundle', 'aio-page-builder' ) );
				if ( $row === null ) {
					continue;
				}
				$slug = $row['proposed_slug'];
				if ( isset( $used_slugs[ $slug ] ) ) {
					$row['proposed_slug'] = $this->unique_slug( $slug, $used_slugs );
					$slug                 = $row['proposed_slug'];
				}
				$used_slugs[ $slug ]                 = true;
				$used_template_keys[ $template_key ] = true;
				$cycle_keys[]                        = $template_key;
				$new_pages[]                         = $row;
				++$added;
			}
		}

		$rec_ctx = isset( $context['template_recommendation_context'] ) && is_array( $context['template_recommendation_context'] )
			? $context['template_recommendation_context']
			: array();
		foreach ( $rec_ctx as $entry ) {
			if ( $count + $added >= $target ) {
				break;
			}
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$template_key = isset( $entry['template_key'] ) && is_string( $entry['template_key'] ) ? trim( $entry['template_key'] ) : '';
			if ( $template_key === '' || isset( $used_template_keys[ $template_key ] ) ) {
				continue;
			}
			$row = $this->make_page_row_from_template(
				$template_key,
				array(),
				__( 'Merged from template recommendation context', 'aio-page-builder' ),
				isset( $entry['name'] ) && is_string( $entry['name'] ) ? trim( $entry['name'] ) : ''
			);
			if ( $row === null ) {
				continue;
			}
			$slug = $row['proposed_slug'];
			if ( isset( $used_slugs[ $slug ] ) ) {
				$row['proposed_slug'] = $this->unique_slug( $slug, $used_slugs );
				$slug                 = $row['proposed_slug'];
			}
			$used_slugs[ $slug ]                 = true;
			$used_template_keys[ $template_key ] = true;
			$cycle_keys[]                        = $template_key;
			$new_pages[]                         = $row;
			++$added;
		}

		if ( $cycle_keys === array() ) {
			foreach ( $rec_ctx as $entry ) {
				if ( ! is_array( $entry ) ) {
					continue;
				}
				$tk = isset( $entry['template_key'] ) && is_string( $entry['template_key'] ) ? trim( $entry['template_key'] ) : '';
				if ( $tk !== '' ) {
					$cycle_keys[] = $tk;
				}
			}
			$cycle_keys = array_values( array_unique( $cycle_keys ) );
		}

		$this->fill_by_cycling_templates( $new_pages, $used_slugs, $cycle_keys, $target );

		if ( count( $new_pages ) <= $count ) {
			return $normalized;
		}

		$added_total = count( $new_pages ) - $count;

		$out = $normalized;
		$out[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] = $new_pages;
		$warnings                                     = isset( $out[ Build_Plan_Draft_Schema::KEY_WARNINGS ] ) && is_array( $out[ Build_Plan_Draft_Schema::KEY_WARNINGS ] )
			? $out[ Build_Plan_Draft_Schema::KEY_WARNINGS ]
			: array();
		$warnings[]                                   = array(
			'message'  => sprintf(
				/* translators: 1: number of merged pages */
				__( 'The planner added %1$d page row(s) from starter bundles, the template registry, and/or template reuse to reach the minimum breadth target.', 'aio-page-builder' ),
				$added_total
			),
			'severity' => 'low',
		);
		$out[ Build_Plan_Draft_Schema::KEY_WARNINGS ] = $warnings;

		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $new_pages
	 * @param array<string, true>              $used_slugs
	 * @param list<string>                     $cycle_keys
	 */
	private function fill_by_cycling_templates( array &$new_pages, array &$used_slugs, array $cycle_keys, int $target ): void {
		if ( $cycle_keys === array() ) {
			return;
		}
		$n_keys = count( $cycle_keys );
		$idx    = 0;
		$guard  = 0;
		while ( count( $new_pages ) < $target && $guard < 500 ) {
			++$guard;
			$tk      = $cycle_keys[ $idx % $n_keys ];
			$variant = (int) floor( $idx / $n_keys ) + 1;
			$base    = $this->humanize_template_key_to_title( $tk );
			$title   = $base . ' (' . (string) $variant . ')';
			$row     = $this->make_page_row_from_template(
				$tk,
				array(),
				__( 'Template reuse variant for breadth target (governed library).', 'aio-page-builder' ),
				$title
			);
			if ( $row === null ) {
				++$idx;
				continue;
			}
			$slug = $row['proposed_slug'];
			if ( isset( $used_slugs[ $slug ] ) ) {
				$row['proposed_slug'] = $this->unique_slug( $slug, $used_slugs );
				$slug                 = $row['proposed_slug'];
			}
			$used_slugs[ $slug ] = true;
			$new_pages[]         = $row;
			++$idx;
		}
	}

	/**
	 * @param list<string> $section_refs
	 * @return array<string, mixed>|null
	 */
	private function make_page_row_from_template( string $template_key, array $section_refs, string $purpose, string $title_override = '' ): ?array {
		$title = $title_override !== '' ? $title_override : $this->humanize_template_key_to_title( $template_key );
		if ( $title === '' ) {
			return null;
		}
		$slug = \sanitize_title( $title );
		if ( $slug === '' ) {
			$slug = 'page-' . substr( md5( $template_key . $title ), 0, 8 );
		}

		$section_guidance = array();
		$refs             = array_values(
			array_filter(
				array_map(
					static function ( $s ) {
						return is_string( $s ) ? trim( $s ) : '';
					},
					$section_refs
				)
			)
		);
		if ( $refs !== array() ) {
			foreach ( $refs as $sk ) {
				$section_guidance[] = array(
					'section_key'       => $sk,
					'intent'            => __( 'Establish this section using governed blocks and LPagery-safe tokens.', 'aio-page-builder' ),
					'content_direction' => __( 'Align copy with the site goal and industry profile; avoid placeholders that are not in the LPagery binding list.', 'aio-page-builder' ),
					'must_include'      => '',
					'must_avoid'        => '',
				);
			}
		} else {
			$section_guidance[] = array(
				'section_key'       => '',
				'intent'            => __( 'Fill all template sections using the registry field map.', 'aio-page-builder' ),
				'content_direction' => __( 'Use section-level guidance from the build plan review UI where the model omitted per-section rows.', 'aio-page-builder' ),
				'must_include'      => '',
				'must_avoid'        => '',
			);
		}

		return array(
			'proposed_page_title' => $title,
			'proposed_slug'       => $slug,
			'purpose'             => $purpose,
			'template_key'        => $template_key,
			'menu_eligible'       => true,
			'section_guidance'    => $section_guidance,
			'confidence'          => 'medium',
			'page_type'           => 'other',
		);
	}

	/**
	 * @param array<string, true> $used_slugs
	 */
	private function unique_slug( string $base, array &$used_slugs ): string {
		$n         = 2;
		$candidate = $base;
		while ( isset( $used_slugs[ $candidate ] ) ) {
			$candidate = $base . '-' . (string) $n;
			++$n;
		}
		return $candidate;
	}

	private function humanize_template_key_to_title( string $template_key ): string {
		$clean = preg_replace( '#^pt_#', '', $template_key );
		$clean = str_replace( array( '_01', '_02', '-' ), array( '', '', ' ' ), $clean ?? '' );
		$words = explode( '_', $clean ?? '' );
		$title = implode( ' ', array_map( 'ucfirst', array_map( 'strtolower', $words ) ) );
		return strlen( $title ) > 0 && strlen( $title ) <= 100 ? $title : __( 'New page', 'aio-page-builder' );
	}
}
