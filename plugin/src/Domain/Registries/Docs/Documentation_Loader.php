<?php
/**
 * Loads documentation objects from file-based section helper and one-pager batches (spec §10.7, §15, §16, documentation-object-schema).
 * Section helpers: doc-helper-{section_key}.php under SectionHelpers (Hero_Batch, CTA_Batch, Proof_Batch, Legal_Policy_Batch, Process_FAQ_Batch, Feature_Benefit_Batch, Media_Listing_Profile_Batch, Gap_Closing_Batch, Contact_Form_Conversion_Batch, Pricing_Offer_Batch).
 * One-pagers: doc-onepager-{page_template_key}.php under PageTemplateOnePagers (e.g. Top_Level_Home_Batch).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Docs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Documentation\Documentation_Schema;

/**
 * Loads section_helper and page_template_one_pager documentation from dedicated batch directories.
 * Each doc file must return a single documentation array (schema-compliant).
 */
final class Documentation_Loader {

	/** Relative batch dirs under Docs (SectionHelpers subdir). */
	private const BATCH_DIRS = array(
		'SectionHelpers/Hero_Batch',
		'SectionHelpers/CTA_Batch',
		'SectionHelpers/Proof_Batch',
		'SectionHelpers/Legal_Policy_Batch',
		'SectionHelpers/Process_FAQ_Batch',
		'SectionHelpers/Feature_Benefit_Batch',
		'SectionHelpers/Media_Listing_Profile_Batch',
		'SectionHelpers/Gap_Closing_Batch',
		'SectionHelpers/Contact_Form_Conversion_Batch',
		'SectionHelpers/Pricing_Offer_Batch',
	);

	/** Relative batch dirs under Docs for page_template_one_pager (PageTemplateOnePagers subdir). */
	private const ONEPAGER_BATCH_DIRS = array(
		'PageTemplateOnePagers/Top_Level_Home_Batch',
	);

	/** @var string Base path (Docs directory). */
	private string $base_path;

	/** @var list<array<string, mixed>>|null Loaded section helper doc objects. */
	private ?array $loaded = null;

	/** @var list<array<string, mixed>>|null Loaded one-pager doc objects. */
	private ?array $loaded_one_pagers = null;

	public function __construct( string $base_path = '' ) {
		$this->base_path = $base_path !== '' ? rtrim( $base_path, '/\\' ) : __DIR__;
	}

	/**
	 * Loads all section helper docs from batch directories.
	 * Safe: skips non-array return values and missing files.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function load_section_helpers(): array {
		if ( $this->loaded !== null ) {
			return $this->loaded;
		}
		$docs = array();
		foreach ( self::BATCH_DIRS as $batch_dir ) {
			$dir = $this->base_path . '/' . $batch_dir;
			if ( ! is_dir( $dir ) ) {
				continue;
			}
			$files = glob( $dir . '/doc-helper-*.php' );
			if ( $files === false ) {
				continue;
			}
			foreach ( $files as $path ) {
				$doc = $this->include_doc_file( $path );
				if ( is_array( $doc ) && $this->is_valid_section_helper( $doc ) ) {
					$docs[] = $doc;
				}
			}
		}
		$this->loaded = $docs;
		return $docs;
	}

	/**
	 * Includes a single doc-helper PHP file and returns its return value.
	 *
	 * @param string $path Absolute path to the file.
	 * @return mixed Return value of the file (expected array).
	 */
	private function include_doc_file( string $path ) {
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			return null;
		}
		try {
			return include $path;
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	/**
	 * Checks that the array has required section_helper shape.
	 *
	 * @param array<string, mixed> $doc
	 * @return bool
	 */
	private function is_valid_section_helper( array $doc ): bool {
		$id = $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '';
		$type = $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ?? '';
		$body = $doc[ Documentation_Schema::FIELD_CONTENT_BODY ] ?? '';
		$status = $doc[ Documentation_Schema::FIELD_STATUS ] ?? '';
		if ( $id === '' || $type !== Documentation_Schema::TYPE_SECTION_HELPER || $body === '' || $status === '' ) {
			return false;
		}
		$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
		if ( ! is_array( $ref ) ) {
			return false;
		}
		$section_key = $ref[ Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY ] ?? '';
		return $section_key !== '';
	}

	/**
	 * Loads all page_template_one_pager docs from one-pager batch directories.
	 * Safe: skips non-array return values and invalid one-pagers.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function load_page_template_one_pagers(): array {
		if ( $this->loaded_one_pagers !== null ) {
			return $this->loaded_one_pagers;
		}
		$docs = array();
		foreach ( self::ONEPAGER_BATCH_DIRS as $batch_dir ) {
			$dir = $this->base_path . '/' . $batch_dir;
			if ( ! is_dir( $dir ) ) {
				continue;
			}
			$files = glob( $dir . '/doc-onepager-*.php' );
			if ( $files === false ) {
				continue;
			}
			foreach ( $files as $path ) {
				$doc = $this->include_doc_file( $path );
				if ( is_array( $doc ) && $this->is_valid_one_pager( $doc ) ) {
					$docs[] = $doc;
				}
			}
		}
		$this->loaded_one_pagers = $docs;
		return $docs;
	}

	/**
	 * Checks that the array has required page_template_one_pager shape.
	 *
	 * @param array<string, mixed> $doc
	 * @return bool
	 */
	private function is_valid_one_pager( array $doc ): bool {
		$id   = $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '';
		$type = $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ?? '';
		$body = $doc[ Documentation_Schema::FIELD_CONTENT_BODY ] ?? '';
		$status = $doc[ Documentation_Schema::FIELD_STATUS ] ?? '';
		if ( $id === '' || $type !== Documentation_Schema::TYPE_PAGE_TEMPLATE_ONE_PAGER || $body === '' || $status === '' ) {
			return false;
		}
		$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
		if ( ! is_array( $ref ) ) {
			return false;
		}
		$page_key = $ref[ Documentation_Schema::SOURCE_PAGE_TEMPLATE_KEY ] ?? '';
		return $page_key !== '';
	}
}
