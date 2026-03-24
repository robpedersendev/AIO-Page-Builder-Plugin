<?php
/**
 * Resolves helper documentation admin URLs truthfully (no placeholders).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Services;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Documentation\Documentation_Schema;
use AIOPageBuilder\Domain\Registries\Docs\Documentation_Registry_Lookup_Interface;
use AIOPageBuilder\Infrastructure\AdminRouting\Admin_Router;

/**
 * Resolves helper doc availability and URL for a section template key (and optional version).
 */
final class Helper_Doc_Url_Resolver {

	public const UNAVAILABLE_MESSAGE = 'Helper documentation not available for this template version.';

	private Documentation_Registry_Lookup_Interface $registry;
	private Admin_Router $router;

	public function __construct( Documentation_Registry_Lookup_Interface $registry, Admin_Router $router ) {
		$this->registry = $registry;
		$this->router   = $router;
	}

	/**
	 * Resolves helper documentation for a section template key.
	 *
	 * Contract:
	 * - input: section_key, optional version
	 * - output: valid admin URL to documentation detail, or truthful unavailable state.
	 *
	 * @param string      $section_key
	 * @param string|null $version Optional, currently used only for messaging fidelity.
	 * @param string|null $helper_ref Optional helper reference from definition (doc id or helper_* ref).
	 * @return array{available: bool, url: string, message: string, doc_id: string}
	 */
	public function resolve( string $section_key, ?string $version = null, ?string $helper_ref = null ): array {
		$section_key = \sanitize_key( $section_key );
		$helper_ref  = $helper_ref !== null ? \sanitize_text_field( $helper_ref ) : null;

		if ( $section_key === '' ) {
			return $this->unavailable();
		}

		$doc_id = '';
		$doc    = $this->registry->get_by_section_key( $section_key );
		if ( \is_array( $doc ) ) {
			$doc_id = isset( $doc['documentation_id'] ) ? (string) $doc['documentation_id'] : '';
		} elseif ( \is_string( $helper_ref ) && $helper_ref !== '' ) {
			if ( \str_starts_with( $helper_ref, 'doc-helper-' ) ) {
				$doc = $this->registry->get_by_id( $helper_ref );
				if ( \is_array( $doc ) ) {
					$doc_id = $helper_ref;
				}
			}
		}

		if ( $doc_id === '' ) {
			return $this->unavailable();
		}

		$url = $this->router->url(
			'documentation_detail',
			array(
				'doc_id'  => $doc_id,
				'section' => $section_key,
			)
		);

		if ( $url === '' ) {
			return $this->unavailable();
		}

		return array(
			'available' => true,
			'url'       => $url,
			'message'   => '',
			'doc_id'    => $doc_id,
		);
	}

	/**
	 * Resolves page-template one-pager documentation (registry lookup by page_template_key).
	 *
	 * @param string $page_template_key Page template internal_key.
	 * @return array{available: bool, url: string, message: string, doc_id: string}
	 */
	public function resolve_for_page_template( string $page_template_key ): array {
		$page_template_key = \sanitize_key( $page_template_key );
		if ( $page_template_key === '' ) {
			return $this->unavailable();
		}

		$doc = $this->registry->get_by_page_template_key( $page_template_key );
		if ( ! \is_array( $doc ) ) {
			return $this->unavailable();
		}

		$doc_id = isset( $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ) ? (string) $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] : '';
		if ( $doc_id === '' ) {
			return $this->unavailable();
		}

		$url = $this->router->url(
			'documentation_detail',
			array(
				'doc_id'  => $doc_id,
				'section' => '',
			)
		);

		if ( $url === '' ) {
			return $this->unavailable();
		}

		return array(
			'available' => true,
			'url'       => $url,
			'message'   => '',
			'doc_id'    => $doc_id,
		);
	}

	/**
	 * @return array{available: bool, url: string, message: string, doc_id: string}
	 */
	private function unavailable(): array {
		return array(
			'available' => false,
			'url'       => '',
			'message'   => self::UNAVAILABLE_MESSAGE,
			'doc_id'    => '',
		);
	}
}
