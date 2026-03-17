<?php
/**
 * Produces read-only diff between two or more starter bundles (Prompt 450).
 * Compares page-family emphasis, template refs, section refs, CTA/LPagery/preset refs, and metadata notes.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

/**
 * Builds comparable summaries and diff rows for starter bundle comparison. Read-only; no mutation.
 */
final class Industry_Starter_Bundle_Diff_Service {

	/** Result key: list of bundle summaries (key, label, summary, industry_key, subtype_key, and extracted fields). */
	public const RESULT_BUNDLES = 'bundles';

	/** Result key: diff rows (field => array of per-bundle values plus 'changed' flag). */
	public const RESULT_DIFF_ROWS = 'diff_rows';

	/** Max bundles to compare at once. */
	private const MAX_BUNDLES = 6;

	/** @var Industry_Starter_Bundle_Registry|null */
	private ?Industry_Starter_Bundle_Registry $bundle_registry;

	public function __construct( ?Industry_Starter_Bundle_Registry $bundle_registry = null ) {
		$this->bundle_registry = $bundle_registry;
	}

	/**
	 * Compares two or more starter bundles by key. Returns bundle summaries and diff rows for UI.
	 *
	 * @param list<string> $bundle_keys Two or more bundle keys (max MAX_BUNDLES). Invalid or missing keys are skipped.
	 * @return array{bundles: list<array<string, mixed>>, diff_rows: list<array{field: string, label: string, values: list<string|list<string>>, changed: bool}>}
	 */
	public function compare( array $bundle_keys ): array {
		$bundles = array();
		$keys = array();
		foreach ( array_slice( $bundle_keys, 0, self::MAX_BUNDLES ) as $key ) {
			if ( ! is_string( $key ) || trim( $key ) === '' ) {
				continue;
			}
			$key = trim( $key );
			if ( in_array( $key, $keys, true ) ) {
				continue;
			}
			if ( $this->bundle_registry === null ) {
				continue;
			}
			$bundle = $this->bundle_registry->get( $key );
			if ( $bundle === null ) {
				continue;
			}
			$keys[] = $key;
			$bundles[] = $this->summarize_bundle( $bundle );
		}
		$diff_rows = array();
		if ( count( $bundles ) >= 2 ) {
			$diff_rows = $this->build_diff_rows( $bundles );
		}
		return array(
			self::RESULT_BUNDLES   => $bundles,
			self::RESULT_DIFF_ROWS => $diff_rows,
		);
	}

	/**
	 * @param array<string, mixed> $bundle
	 * @return array<string, mixed>
	 */
	private function summarize_bundle( array $bundle ): array {
		$page_families = $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_FAMILIES ] ?? array();
		$page_refs     = $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ] ?? array();
		$section_refs = $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS ] ?? array();
		$cta_ref       = $bundle[ Industry_Starter_Bundle_Registry::FIELD_CTA_GUIDANCE_REF ] ?? '';
		$lpagery_ref   = $bundle[ Industry_Starter_Bundle_Registry::FIELD_LPAGERY_GUIDANCE_REF ] ?? '';
		$token_ref     = $bundle[ Industry_Starter_Bundle_Registry::FIELD_TOKEN_PRESET_REF ] ?? '';
		$metadata      = $bundle[ Industry_Starter_Bundle_Registry::FIELD_METADATA ] ?? array();
		return array(
			'bundle_key'       => (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ?? '' ),
			'label'           => (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] ?? '' ),
			'summary'         => (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_SUMMARY ] ?? '' ),
			'industry_key'    => (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] ?? '' ),
			'subtype_key'     => (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY ] ?? '' ),
			'page_families'   => is_array( $page_families ) ? $page_families : array(),
			'page_template_refs' => is_array( $page_refs ) ? $page_refs : array(),
			'section_refs'   => is_array( $section_refs ) ? $section_refs : array(),
			'cta_guidance_ref' => is_string( $cta_ref ) ? trim( $cta_ref ) : '',
			'lpagery_guidance_ref' => is_string( $lpagery_ref ) ? trim( $lpagery_ref ) : '',
			'token_preset_ref' => is_string( $token_ref ) ? trim( $token_ref ) : '',
			'metadata_notes'  => is_array( $metadata ) ? $this->metadata_notes( $metadata ) : '',
		);
	}

	/**
	 * @param array<string, mixed> $metadata
	 * @return string
	 */
	private function metadata_notes( array $metadata ): string {
		$parts = array();
		if ( isset( $metadata['sort_order'] ) ) {
			$parts[] = 'sort_order:' . (string) $metadata['sort_order'];
		}
		return implode( ', ', $parts );
	}

	/**
	 * @param list<array<string, mixed>> $bundles
	 * @return list<array{field: string, label: string, values: list<string|list<string>>, changed: bool}>
	 */
	private function build_diff_rows( array $bundles ): array {
		$rows = array();
		$n = count( $bundles );

		$fields = array(
			'page_families'        => array( 'label' => __( 'Page family emphasis', 'aio-page-builder' ), 'type' => 'array' ),
			'page_template_refs'   => array( 'label' => __( 'Page template refs', 'aio-page-builder' ), 'type' => 'array' ),
			'section_refs'         => array( 'label' => __( 'Section refs', 'aio-page-builder' ), 'type' => 'array' ),
			'cta_guidance_ref'     => array( 'label' => __( 'CTA guidance ref', 'aio-page-builder' ), 'type' => 'string' ),
			'lpagery_guidance_ref' => array( 'label' => __( 'LPagery guidance ref', 'aio-page-builder' ), 'type' => 'string' ),
			'token_preset_ref'     => array( 'label' => __( 'Token preset ref', 'aio-page-builder' ), 'type' => 'string' ),
			'metadata_notes'       => array( 'label' => __( 'Preset / metadata notes', 'aio-page-builder' ), 'type' => 'string' ),
		);

		foreach ( $fields as $field => $config ) {
			$values = array();
			foreach ( $bundles as $b ) {
				$v = $b[ $field ] ?? ( $config['type'] === 'array' ? array() : '' );
				$values[] = $v;
			}
			$changed = false;
			$first = $values[0];
			for ( $i = 1; $i < $n; $i++ ) {
				if ( $config['type'] === 'array' ) {
					if ( $this->array_diff_simple( $first, $values[ $i ] ) ) {
						$changed = true;
						break;
					}
				} else {
					if ( (string) $first !== (string) $values[ $i ] ) {
						$changed = true;
						break;
					}
				}
			}
			$rows[] = array(
				'field'   => $field,
				'label'   => $config['label'],
				'values'  => $values,
				'changed' => $changed,
			);
		}
		return $rows;
	}

	/**
	 * @param array<int, mixed> $a
	 * @param array<int, mixed> $b
	 * @return bool True if different.
	 */
	private function array_diff_simple( array $a, array $b ): bool {
		if ( count( $a ) !== count( $b ) ) {
			return true;
		}
		$aa = array_values( $a );
		$bb = array_values( $b );
		foreach ( $aa as $i => $v ) {
			if ( ! array_key_exists( $i, $bb ) || (string) $v !== (string) $bb[ $i ] ) {
				return true;
			}
		}
		return false;
	}
}
