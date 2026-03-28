<?php
/**
 * Detects when an approved template-lab draft no longer matches the live section registry (non-destructive apply gate).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * * Compares normalized draft section keys to persisted section templates; does not mutate drafts.
 */
final class Template_Lab_Approved_Snapshot_Stale_Guard {

	private Section_Template_Repository $section_templates;

	public function __construct( Section_Template_Repository $section_templates ) {
		$this->section_templates = $section_templates;
	}

	/**
	 * @param array<string, mixed> $normalized Normalized template-lab draft payload.
	 * @return string Empty when fresh; non-empty human-facing reason when apply should be blocked.
	 */
	public function registry_drift_reason( string $target_kind, array $normalized ): string {
		if ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION ) {
			return $this->reason_for_composition( $normalized );
		}
		if ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_PAGE ) {
			return $this->reason_for_page_template( $normalized );
		}
		if ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_SECTION ) {
			// * Net-new section drafts intentionally may not exist in the registry until apply; skip existence checks.
			return '';
		}
		return '';
	}

	/**
	 * @param array<string, mixed> $normalized
	 */
	private function reason_for_composition( array $normalized ): string {
		$list = $normalized[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ] ?? null;
		if ( ! is_array( $list ) ) {
			return '';
		}
		foreach ( $list as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$key = isset( $row[ Composition_Schema::SECTION_ITEM_KEY ] ) ? trim( (string) $row[ Composition_Schema::SECTION_ITEM_KEY ] ) : '';
			if ( $key === '' ) {
				continue;
			}
			if ( $this->section_templates->get_definition_by_key( $key ) === null ) {
				return 'missing_section:' . $key;
			}
		}
		return '';
	}

	/**
	 * @param array<string, mixed> $normalized
	 */
	private function reason_for_page_template( array $normalized ): string {
		$list = $normalized[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? null;
		if ( ! is_array( $list ) ) {
			return '';
		}
		foreach ( $list as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$key = isset( $row[ Page_Template_Schema::SECTION_ITEM_KEY ] ) ? trim( (string) $row[ Page_Template_Schema::SECTION_ITEM_KEY ] ) : '';
			if ( $key === '' ) {
				continue;
			}
			if ( $this->section_templates->get_definition_by_key( $key ) === null ) {
				return 'missing_section:' . $key;
			}
		}
		return '';
	}
}
