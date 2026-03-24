<?php
/**
 * Lookup contract for documentation registry reads.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Docs;

defined( 'ABSPATH' ) || exit;

interface Documentation_Registry_Lookup_Interface {

	/**
	 * @param string $documentation_id
	 * @return array<string, mixed>|null
	 */
	public function get_by_id( string $documentation_id ): ?array;

	/**
	 * @param string $section_template_key
	 * @return array<string, mixed>|null
	 */
	public function get_by_section_key( string $section_template_key ): ?array;

	/**
	 * @param string $page_template_key Page template internal_key (one-pager docs).
	 * @return array<string, mixed>|null
	 */
	public function get_by_page_template_key( string $page_template_key ): ?array;
}
