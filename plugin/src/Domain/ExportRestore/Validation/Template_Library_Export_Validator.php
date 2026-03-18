<?php
/**
 * Validates template-library completeness and coherence for export packages (Prompt 185, spec §52.2, §52.3, §62.11, §62.12).
 * Ensures registries serialize fully, one-pager/preview metadata are present or regenerable, and appendices can be regenerated from bundle.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Validation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Docs\Page_Template_Inventory_Appendix_Generator;
use AIOPageBuilder\Domain\Registries\Docs\Section_Inventory_Appendix_Generator;
use AIOPageBuilder\Domain\Registries\Export\Registry_Export_Fragment_Builder;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Validates registry bundle for export: completeness, one-pager inclusion, appendix regenerability.
 *
 * Example template_library_export_summary payload:
 * [
 *   'valid' => true,
 *   'section_count' => 42,
 *   'page_template_count' => 18,
 *   'composition_count' => 5,
 *   'one_pager_included_count' => 18,
 *   'one_pager_missing_keys' => [],
 *   'appendix_regenerable' => true,
 *   'appendix_section_row_count' => 42,
 *   'appendix_page_row_count' => 18,
 *   'errors' => [],
 *   'warnings' => [],
 *   'log_reference' => 'tlib-export-2025-03-13T12:00:00Z',
 * ]
 */
final class Template_Library_Export_Validator {

	/** @var Section_Inventory_Appendix_Generator|null */
	private ?Section_Inventory_Appendix_Generator $section_appendix;

	/** @var Page_Template_Inventory_Appendix_Generator|null */
	private ?Page_Template_Inventory_Appendix_Generator $page_appendix;

	public function __construct(
		?Section_Inventory_Appendix_Generator $section_appendix = null,
		?Page_Template_Inventory_Appendix_Generator $page_appendix = null
	) {
		$this->section_appendix = $section_appendix;
		$this->page_appendix    = $page_appendix;
	}

	/**
	 * Validates the registry bundle for template-library export coherence.
	 *
	 * @param array{registries: array{sections?: list<array>, page_templates?: list<array>, compositions?: list<array>}} $bundle Bundle from Registry_Export_Serializer::build_registry_bundle().
	 * @param list<string>                                                                                               $included_categories Categories included in this export.
	 * @return array{valid: bool, section_count: int, page_template_count: int, composition_count: int, one_pager_included_count: int, one_pager_missing_keys: list<string>, appendix_regenerable: bool, appendix_section_row_count: int, appendix_page_row_count: int, errors: list<string>, warnings: list<string>, log_reference: string} template_library_export_summary
	 */
	public function validate( array $bundle, array $included_categories ): array {
		$log_ref                    = 'tlib-export-' . gmdate( 'Y-m-d\TH:i:s\Z' );
		$errors                     = array();
		$warnings                   = array();
		$section_count              = 0;
		$page_template_count        = 0;
		$composition_count          = 0;
		$one_pager_included_count   = 0;
		$one_pager_missing_keys     = array();
		$appendix_regenerable       = true;
		$appendix_section_row_count = 0;
		$appendix_page_row_count    = 0;

		$has_registries   = in_array( 'registries', $included_categories, true );
		$has_compositions = in_array( 'compositions', $included_categories, true );

		if ( ! $has_registries && ! $has_compositions ) {
			return $this->summary( true, 0, 0, 0, 0, array(), true, 0, 0, array(), array(), $log_ref );
		}

		$registries     = $bundle['registries'] ?? array();
		$sections       = $registries['sections'] ?? array();
		$page_templates = $registries['page_templates'] ?? array();
		$compositions   = $registries['compositions'] ?? array();

		if ( $has_registries ) {
			$section_count       = count( $sections );
			$page_template_count = count( $page_templates );
			foreach ( $sections as $frag ) {
				$payload = isset( $frag[ Registry_Export_Fragment_Builder::KEY_PAYLOAD ] ) && is_array( $frag[ Registry_Export_Fragment_Builder::KEY_PAYLOAD ] )
					? $frag[ Registry_Export_Fragment_Builder::KEY_PAYLOAD ]
					: array();
				if ( (string) ( $payload[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' ) === '' ) {
					$errors[] = 'Section fragment missing internal_key in payload.';
				}
			}
			foreach ( $page_templates as $frag ) {
				$payload = isset( $frag[ Registry_Export_Fragment_Builder::KEY_PAYLOAD ] ) && is_array( $frag[ Registry_Export_Fragment_Builder::KEY_PAYLOAD ] )
					? $frag[ Registry_Export_Fragment_Builder::KEY_PAYLOAD ]
					: array();
				$key     = (string) ( $payload[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? $frag[ Registry_Export_Fragment_Builder::KEY_OBJECT_KEY ] ?? '' );
				if ( $key === '' ) {
					$errors[] = 'Page template fragment missing internal_key in payload.';
					continue;
				}
				$one_pager = $payload[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? $payload['one_pager'] ?? null;
				if ( is_array( $one_pager ) && ( isset( $one_pager['link'] ) || isset( $one_pager['page_purpose_summary'] ) || ( isset( $one_pager['ref'] ) && $one_pager['ref'] !== '' ) ) ) {
					++$one_pager_included_count;
				} else {
					$one_pager_missing_keys[] = $key;
				}
			}
			if ( count( $one_pager_missing_keys ) > 0 ) {
				$warnings[] = 'Page templates missing one-pager metadata: ' . implode( ', ', array_slice( $one_pager_missing_keys, 0, 10 ) ) . ( count( $one_pager_missing_keys ) > 10 ? ' (and ' . ( count( $one_pager_missing_keys ) - 10 ) . ' more)' : '' );
			}
		}

		if ( $has_compositions ) {
			$composition_count = count( $compositions );
		}

		$section_defs = $this->extract_payloads( $sections );
		$page_defs    = $this->extract_payloads( $page_templates );

		if ( $this->section_appendix !== null && count( $section_defs ) > 0 ) {
			try {
				$section_result             = $this->section_appendix->build_result_from_definitions( $section_defs );
				$appendix_section_row_count = $section_result['total'] ?? 0;
				if ( $appendix_section_row_count !== count( $section_defs ) ) {
					$warnings[]           = 'Section appendix row count mismatch: expected ' . count( $section_defs ) . ', got ' . $appendix_section_row_count;
					$appendix_regenerable = false;
				}
			} catch ( \Throwable $e ) {
				$errors[]             = 'Section appendix regeneration failed: ' . $e->getMessage();
				$appendix_regenerable = false;
			}
		}

		if ( $this->page_appendix !== null && count( $page_defs ) > 0 ) {
			try {
				$page_result             = $this->page_appendix->build_result_from_definitions( $page_defs );
				$appendix_page_row_count = $page_result['total'] ?? 0;
				if ( $appendix_page_row_count !== count( $page_defs ) ) {
					$warnings[]           = 'Page template appendix row count mismatch: expected ' . count( $page_defs ) . ', got ' . $appendix_page_row_count;
					$appendix_regenerable = false;
				}
			} catch ( \Throwable $e ) {
				$errors[]             = 'Page template appendix regeneration failed: ' . $e->getMessage();
				$appendix_regenerable = false;
			}
		}

		$valid = count( $errors ) === 0;
		return $this->summary(
			$valid,
			$section_count,
			$page_template_count,
			$composition_count,
			$one_pager_included_count,
			$one_pager_missing_keys,
			$appendix_regenerable,
			$appendix_section_row_count,
			$appendix_page_row_count,
			$errors,
			$warnings,
			$log_ref
		);
	}

	/**
	 * @param list<array<string, mixed>> $fragments
	 * @return list<array<string, mixed>>
	 */
	private function extract_payloads( array $fragments ): array {
		$out = array();
		foreach ( $fragments as $frag ) {
			if ( isset( $frag[ Registry_Export_Fragment_Builder::KEY_PAYLOAD ] ) && is_array( $frag[ Registry_Export_Fragment_Builder::KEY_PAYLOAD ] ) ) {
				$out[] = $frag[ Registry_Export_Fragment_Builder::KEY_PAYLOAD ];
			}
		}
		return $out;
	}

	/**
	 * @param list<string> $one_pager_missing_keys
	 * @param list<string> $errors
	 * @param list<string> $warnings
	 * @return array<string, mixed>
	 */
	private function summary(
		bool $valid,
		int $section_count,
		int $page_template_count,
		int $composition_count,
		int $one_pager_included_count,
		array $one_pager_missing_keys,
		bool $appendix_regenerable,
		int $appendix_section_row_count,
		int $appendix_page_row_count,
		array $errors,
		array $warnings,
		string $log_reference
	): array {
		return array(
			'valid'                      => $valid,
			'section_count'              => $section_count,
			'page_template_count'        => $page_template_count,
			'composition_count'          => $composition_count,
			'one_pager_included_count'   => $one_pager_included_count,
			'one_pager_missing_keys'     => $one_pager_missing_keys,
			'appendix_regenerable'       => $appendix_regenerable,
			'appendix_section_row_count' => $appendix_section_row_count,
			'appendix_page_row_count'    => $appendix_page_row_count,
			'errors'                     => $errors,
			'warnings'                   => $warnings,
			'log_reference'              => $log_reference,
		);
	}
}
