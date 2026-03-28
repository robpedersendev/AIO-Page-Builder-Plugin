<?php
/**
 * Validates page-template ordered_sections against the section-template registry before canonical persistence.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

final class Page_Template_Ordered_Sections_Registry_Validator {

	private Section_Template_Repository $sections;

	public function __construct( Section_Template_Repository $sections ) {
		$this->sections = $sections;
	}

	/**
	 * @param array<string, mixed> $definition Full page template definition.
	 * @return list<string> Empty when valid.
	 */
	public function validate( array $definition ): array {
		$errs  = array();
		$items = $definition[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? null;
		if ( ! is_array( $items ) || $items === array() ) {
			return array( 'ordered_sections_missing' );
		}
		$positions = array();
		foreach ( $items as $i => $row ) {
			if ( ! is_array( $row ) ) {
				$errs[] = 'ordered_sections_item_not_object';
				continue;
			}
			$key = isset( $row[ Page_Template_Schema::SECTION_ITEM_KEY ] ) ? (string) $row[ Page_Template_Schema::SECTION_ITEM_KEY ] : '';
			if ( $key === '' ) {
				$errs[] = 'missing_section_key_at_' . (string) $i;
				continue;
			}
			if ( $this->sections->get_by_key( $key ) === null ) {
				$errs[] = 'unknown_section_key:' . $key;
			}
			if ( ! isset( $row[ Page_Template_Schema::SECTION_ITEM_POSITION ] ) || ! is_numeric( $row[ Page_Template_Schema::SECTION_ITEM_POSITION ] ) ) {
				$errs[] = 'invalid_position_at_' . (string) $i;
				continue;
			}
			$pos = (int) $row[ Page_Template_Schema::SECTION_ITEM_POSITION ];
			if ( isset( $positions[ $pos ] ) ) {
				$errs[] = 'duplicate_position:' . (string) $pos;
			}
			$positions[ $pos ] = true;
		}
		return $errs;
	}
}
