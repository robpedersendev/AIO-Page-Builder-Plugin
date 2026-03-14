<?php
/**
 * Stable UI-state payload for composition builder section filters (Prompt 177, spec §49.6, §14).
 * Category, purpose-family, CTA, variant-family, search, pagination. Used by Composition_Builder_State_Builder.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Compositions\UI;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable filter state for large-library section selection during composition assembly.
 */
final class Composition_Filter_State {

	/** @var string */
	private string $purpose_family;

	/** @var string */
	private string $category;

	/** @var string */
	private string $cta_classification;

	/** @var string */
	private string $variation_family_key;

	/** @var string */
	private string $search;

	/** @var string */
	private string $status;

	/** @var int */
	private int $paged;

	/** @var int */
	private int $per_page;

	public function __construct(
		string $purpose_family = '',
		string $category = '',
		string $cta_classification = '',
		string $variation_family_key = '',
		string $search = '',
		string $status = '',
		int $paged = 1,
		int $per_page = 25
	) {
		$this->purpose_family      = $purpose_family;
		$this->category           = $category;
		$this->cta_classification = $cta_classification;
		$this->variation_family_key = $variation_family_key;
		$this->search             = $search;
		$this->status             = $status;
		$this->paged              = max( 1, $paged );
		$this->per_page           = max( 1, min( 100, $per_page ) );
	}

	public function get_purpose_family(): string {
		return $this->purpose_family;
	}

	public function get_category(): string {
		return $this->category;
	}

	public function get_cta_classification(): string {
		return $this->cta_classification;
	}

	public function get_variation_family_key(): string {
		return $this->variation_family_key;
	}

	public function get_search(): string {
		return $this->search;
	}

	public function get_status(): string {
		return $this->status;
	}

	public function get_paged(): int {
		return $this->paged;
	}

	public function get_per_page(): int {
		return $this->per_page;
	}

	/**
	 * Export for UI-state payload (filters object).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'purpose_family'       => $this->purpose_family,
			'category'             => $this->category,
			'cta_classification'   => $this->cta_classification,
			'variation_family_key' => $this->variation_family_key,
			'search'               => $this->search,
			'status'               => $this->status,
			'paged'                => $this->paged,
			'per_page'             => $this->per_page,
		);
	}

	/**
	 * Builds filter array for Large_Library_Query_Service::query_sections().
	 *
	 * @return array<string, mixed>
	 */
	public function to_query_filters(): array {
		$filters = array();
		if ( $this->purpose_family !== '' ) {
			$filters[\AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service::FILTER_SECTION_PURPOSE_FAMILY] = $this->purpose_family;
		}
		if ( $this->category !== '' ) {
			$filters[\AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service::FILTER_CATEGORY] = $this->category;
		}
		if ( $this->cta_classification !== '' ) {
			$filters[\AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service::FILTER_CTA_CLASSIFICATION] = $this->cta_classification;
		}
		if ( $this->variation_family_key !== '' ) {
			$filters[\AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service::FILTER_VARIATION_FAMILY_KEY] = $this->variation_family_key;
		}
		if ( $this->status !== '' ) {
			$filters[\AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service::FILTER_STATUS] = $this->status;
		}
		if ( $this->search !== '' ) {
			$filters[\AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service::FILTER_SEARCH] = $this->search;
		}
		return $filters;
	}
}
