<?php
/**
 * Registry for documentation objects (section helpers, one-pagers). Resolver-friendly lookup by documentation_id or section_template_key (spec §10.7, §15, §16).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Docs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Documentation\Documentation_Schema;

/**
 * Provides lookup of documentation objects loaded from file-based section helper batches.
 * Keyed by documentation_id and by source_reference.section_template_key for one-pager and detail-screen resolution.
 */
final class Documentation_Registry {

	/** @var Documentation_Loader */
	private Documentation_Loader $loader;

	/** @var array<string, array<string, mixed>>|null Index by documentation_id. */
	private ?array $by_id = null;

	/** @var array<string, array<string, mixed>>|null Index by section_template_key. */
	private ?array $by_section_key = null;

	public function __construct( ?Documentation_Loader $loader = null ) {
		$this->loader = $loader ?? new Documentation_Loader();
	}

	/**
	 * Returns the documentation object by documentation_id, or null if not found.
	 *
	 * @param string $documentation_id e.g. doc-helper-hero_conv_01.
	 * @return array<string, mixed>|null
	 */
	public function get_by_id( string $documentation_id ): ?array {
		$this->ensure_indexed();
		return $this->by_id[ $documentation_id ] ?? null;
	}

	/**
	 * Returns the section_helper documentation for the given section template key, or null if not found.
	 * Use this to resolve helper docs from section internal_key or from helper_ref (strip "helper_" prefix to get section key).
	 *
	 * @param string $section_template_key Section internal_key e.g. hero_conv_01, cta_contact_01.
	 * @return array<string, mixed>|null
	 */
	public function get_by_section_key( string $section_template_key ): ?array {
		$this->ensure_indexed();
		return $this->by_section_key[ $section_template_key ] ?? null;
	}

	/**
	 * Returns all loaded section helper docs (for export or listing).
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_all_section_helpers(): array {
		return $this->loader->load_section_helpers();
	}

	private function ensure_indexed(): void {
		if ( $this->by_id !== null ) {
			return;
		}
		$this->by_id = array();
		$this->by_section_key = array();
		foreach ( $this->loader->load_section_helpers() as $doc ) {
			$id = (string) ( $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '' );
			if ( $id !== '' ) {
				$this->by_id[ $id ] = $doc;
			}
			$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
			if ( is_array( $ref ) ) {
				$section_key = (string) ( $ref[ Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY ] ?? '' );
				if ( $section_key !== '' ) {
					$this->by_section_key[ $section_key ] = $doc;
				}
			}
		}
	}
}
