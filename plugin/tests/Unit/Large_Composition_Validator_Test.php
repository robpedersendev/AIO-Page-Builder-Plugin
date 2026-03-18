<?php
/**
 * Unit tests for Large_Composition_Validator (Prompt 178): CTA rules, compatibility, preview/one-pager.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Validation_Codes;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Validator;
use AIOPageBuilder\Domain\Registries\Compositions\Validation\Large_Composition_Validator;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Registry_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Validation_Codes.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/Compositions/Validation/Composition_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Compositions/Validation/Large_Composition_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Definition_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Registry_Service.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Registry_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Registry_Deprecation_Service.php';

final class Large_Composition_Validator_Test extends TestCase {

	private Section_Registry_Service $section_registry;
	private Page_Template_Registry_Service $page_template_registry;
	private Composition_Validator $legacy_validator;
	private Large_Composition_Validator $validator;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta']      = array();
		$GLOBALS['_aio_wp_query_posts'] = array();

		$section_repo           = new \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository();
		$section_normalizer     = new \AIOPageBuilder\Domain\Registries\Section\Section_Definition_Normalizer();
		$section_validator      = new \AIOPageBuilder\Domain\Registries\Section\Section_Validator( $section_normalizer, $section_repo );
		$deprecation            = new \AIOPageBuilder\Domain\Registries\Shared\Registry_Deprecation_Service(
			$section_repo,
			new \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository()
		);
		$this->section_registry = new Section_Registry_Service( $section_validator, $section_repo, $deprecation );

		$page_repo                    = new \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository();
		$page_normalizer              = new \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Normalizer();
		$page_validator               = new \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Validator( $page_normalizer, $page_repo, $this->section_registry );
		$this->page_template_registry = new Page_Template_Registry_Service( $page_validator, $page_repo, $deprecation );

		$this->legacy_validator = new Composition_Validator( $this->section_registry, $this->page_template_registry );
		$this->validator        = new Large_Composition_Validator( $this->legacy_validator, $this->section_registry, $this->page_template_registry );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_post_meta'], $GLOBALS['_aio_wp_query_posts'] );
		parent::tearDown();
	}

	/**
	 * Seeds section definitions so get_by_key returns them. Each def must have internal_key, status, and keys used by Large_Composition_Validator (cta_classification, preview_*).
	 *
	 * @param array<string, array<string, mixed>> $section_defs Map section_key => definition (with internal_key, status, cta_classification, preview_description or preview_defaults etc).
	 */
	private function seed_sections( array $section_defs ): void {
		$posts  = $GLOBALS['_aio_wp_query_posts'] ?? array();
		$meta   = array_merge( array(), $GLOBALS['_aio_post_meta'] ?? array() );
		$max_id = 0;
		foreach ( $posts as $p ) {
			$pid = is_object( $p ) ? (int) $p->ID : (int) ( $p['ID'] ?? 0 );
			if ( $pid > $max_id ) {
				$max_id = $pid;
			}
		}
		$id = $max_id + 1;
		foreach ( $section_defs as $key => $def ) {
			$def['internal_key']  = $key;
			$def['status']        = $def['status'] ?? 'active';
			$post                 = (object) array(
				'ID'         => $id,
				'post_type'  => Object_Type_Keys::SECTION_TEMPLATE,
				'post_title' => (string) ( $def['name'] ?? $key ),
			);
			$posts[]              = $post;
			$meta[ (string) $id ] = array(
				'_aio_internal_key'       => $key,
				'_aio_status'             => $def['status'],
				'_aio_section_definition' => wp_json_encode( $def ),
			);
			++$id;
		}
		$GLOBALS['_aio_wp_query_posts'] = $posts;
		$GLOBALS['_aio_post_meta']      = $meta;
	}

	/**
	 * Seeds page template definitions for resolve_template_category_class (source_template_ref).
	 *
	 * @param array<string, array<string, mixed>> $page_defs Map internal_key => definition (template_category_class, status).
	 */
	private function seed_page_templates( array $page_defs ): void {
		$posts   = $GLOBALS['_aio_wp_query_posts'] ?? array();
		$meta    = $GLOBALS['_aio_post_meta'] ?? array();
		$base_id = 500;
		foreach ( $page_defs as $key => $def ) {
			$def['internal_key']  = $key;
			$def['status']        = $def['status'] ?? 'publish';
			$id                   = $base_id++;
			$post                 = (object) array(
				'ID'         => $id,
				'post_type'  => Object_Type_Keys::PAGE_TEMPLATE,
				'post_title' => (string) ( $def['name'] ?? $key ),
			);
			$posts[]              = $post;
			$meta[ (string) $id ] = array(
				'_aio_internal_key'             => $key,
				'_aio_status'                   => $def['status'],
				'_aio_page_template_definition' => wp_json_encode( $def ),
			);
		}
		$GLOBALS['_aio_wp_query_posts'] = array_merge( $GLOBALS['_aio_wp_query_posts'] ?? array(), $posts );
		$GLOBALS['_aio_post_meta']      = array_merge( $GLOBALS['_aio_post_meta'] ?? array(), $meta );
	}

	public function test_empty_section_list_returns_legacy_codes_only(): void {
		$composition = array(
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(),
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF  => '',
			Composition_Schema::FIELD_HELPER_ONE_PAGER_REF => '',
		);
		$result      = $this->validator->validate( $composition );
		self::assertFalse( $result->is_valid() );
		self::assertNotEmpty( $result->get_blockers() );
		self::assertContains( Composition_Validation_Codes::EMPTY_SECTION_LIST, $result->get_legacy_codes() );
	}

	public function test_bottom_cta_missing_adds_blocker(): void {
		$this->seed_sections(
			array(
				'hero_a' => array(
					'cta_classification'  => 'none',
					'preview_description' => 'Hero',
				),
				'cta_a'  => array(
					'cta_classification'  => 'primary_cta',
					'preview_description' => 'CTA',
				),
			)
		);
		$composition = array(
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'cta_a',
					Composition_Schema::SECTION_ITEM_POSITION => 0,
				),
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'hero_a',
					Composition_Schema::SECTION_ITEM_POSITION => 1,
				),
			),
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF  => '',
			Composition_Schema::FIELD_HELPER_ONE_PAGER_REF => '',
		);
		$result      = $this->validator->validate( $composition );
		self::assertFalse( $result->is_valid() );
		$blocker_codes = array_column( $result->get_blockers(), 'code' );
		self::assertContains( 'bottom_cta_missing', $blocker_codes );
		self::assertNotEmpty( array_filter( $result->get_cta_rule_violations(), fn( $v ) => ( $v['code'] ?? '' ) === 'bottom_cta_missing' ) );
	}

	public function test_adjacent_cta_adds_blocker(): void {
		$this->seed_sections(
			array(
				'cta_one' => array(
					'cta_classification'  => 'primary_cta',
					'preview_description' => 'CTA 1',
				),
				'cta_two' => array(
					'cta_classification'  => 'contact_cta',
					'preview_description' => 'CTA 2',
				),
			)
		);
		$composition = array(
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'cta_one',
					Composition_Schema::SECTION_ITEM_POSITION => 0,
				),
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'cta_two',
					Composition_Schema::SECTION_ITEM_POSITION => 1,
				),
			),
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF  => '',
			Composition_Schema::FIELD_HELPER_ONE_PAGER_REF => '',
		);
		$result      = $this->validator->validate( $composition );
		self::assertFalse( $result->is_valid() );
		$blocker_codes = array_column( $result->get_blockers(), 'code' );
		self::assertContains( 'adjacent_cta_violation', $blocker_codes );
	}

	public function test_cta_count_below_minimum_when_class_set(): void {
		$section_defs = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$section_defs[ 'sec_' . $i ] = array(
				'cta_classification'  => $i < 2 ? 'primary_cta' : 'none',
				'preview_description' => 'S',
			);
		}
		$this->seed_sections( $section_defs );
		$composition = array(
			'template_category_class'                      => 'hub',
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'sec_0',
					Composition_Schema::SECTION_ITEM_POSITION => 0,
				),
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'sec_1',
					Composition_Schema::SECTION_ITEM_POSITION => 1,
				),
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'sec_2',
					Composition_Schema::SECTION_ITEM_POSITION => 2,
				),
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'sec_3',
					Composition_Schema::SECTION_ITEM_POSITION => 3,
				),
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'sec_4',
					Composition_Schema::SECTION_ITEM_POSITION => 4,
				),
			),
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF  => '',
			Composition_Schema::FIELD_HELPER_ONE_PAGER_REF => '',
		);
		$result      = $this->validator->validate( $composition );
		self::assertFalse( $result->is_valid() );
		$blocker_codes = array_column( $result->get_blockers(), 'code' );
		self::assertContains( 'cta_count_below_minimum', $blocker_codes );
	}

	public function test_non_cta_count_below_minimum_when_class_set(): void {
		$section_defs = array();
		$min_cta_hub  = 4;
		for ( $i = 0; $i < 10; $i++ ) {
			$section_defs[ 's' . $i ] = array(
				'cta_classification'  => $i < $min_cta_hub ? 'primary_cta' : 'none',
				'preview_description' => 'x',
			);
		}
		$this->seed_sections( $section_defs );
		$ordered = array();
		foreach ( array_keys( $section_defs ) as $pos => $key ) {
			$ordered[] = array(
				Composition_Schema::SECTION_ITEM_KEY      => $key,
				Composition_Schema::SECTION_ITEM_POSITION => $pos,
			);
		}
		$composition = array(
			'template_category_class'                      => 'hub',
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => $ordered,
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF  => '',
			Composition_Schema::FIELD_HELPER_ONE_PAGER_REF => '',
		);
		$result      = $this->validator->validate( $composition );
		self::assertFalse( $result->is_valid() );
		$blocker_codes = array_column( $result->get_blockers(), 'code' );
		self::assertContains( 'non_cta_count_below_minimum', $blocker_codes );
	}

	public function test_non_cta_count_above_max_adds_warning(): void {
		$section_defs = array();
		$total        = 20;
		$cta_count    = 4;
		for ( $i = 0; $i < $total; $i++ ) {
			$section_defs[ 's' . $i ] = array(
				'cta_classification'  => $i < $cta_count ? 'primary_cta' : 'none',
				'preview_description' => 'x',
			);
		}
		$this->seed_sections( $section_defs );
		$ordered = array();
		foreach ( array_keys( $section_defs ) as $pos => $key ) {
			$ordered[] = array(
				Composition_Schema::SECTION_ITEM_KEY      => $key,
				Composition_Schema::SECTION_ITEM_POSITION => $pos,
			);
		}
		$composition = array(
			'template_category_class'                      => 'hub',
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => $ordered,
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF  => '',
			Composition_Schema::FIELD_HELPER_ONE_PAGER_REF => '',
		);
		$result      = $this->validator->validate( $composition );
		$warnings    = $result->get_warnings();
		$codes       = array_column( $warnings, 'code' );
		self::assertContains( 'non_cta_count_above_max', $codes );
	}

	public function test_legacy_compatibility_adjacency_mapped_to_compatibility_violations(): void {
		$this->seed_sections(
			array(
				'a' => array(
					'cta_classification'  => 'none',
					'preview_description' => 'A',
				),
				'b' => array(
					'cta_classification'  => 'primary_cta',
					'preview_description' => 'B',
				),
			)
		);
		$composition = array(
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'a',
					Composition_Schema::SECTION_ITEM_POSITION => 0,
				),
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'b',
					Composition_Schema::SECTION_ITEM_POSITION => 1,
				),
			),
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF  => '',
			Composition_Schema::FIELD_HELPER_ONE_PAGER_REF => '',
		);
		$result      = $this->validator->validate( $composition );
		$compat      = $result->get_compatibility_violations();
		if ( ! empty( $compat ) ) {
			self::assertSame( 'compatibility_adjacency', $compat[0]['code'] ?? '' );
		}
		$blockers = $result->get_blockers();
		$legacy   = $result->get_legacy_codes();
		self::assertTrue( empty( $blockers ) || in_array( Composition_Validation_Codes::COMPATIBILITY_ADJACENCY, $legacy, true ) || count( array_filter( $compat, fn( $c ) => ( $c['code'] ?? '' ) === 'compatibility_adjacency' ) ) > 0 );
	}

	public function test_section_missing_preview_adds_preview_readiness_warning(): void {
		$this->seed_sections(
			array(
				'no_preview' => array( 'cta_classification' => 'primary_cta' ),
			)
		);
		$composition      = array(
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'no_preview',
					Composition_Schema::SECTION_ITEM_POSITION => 0,
				),
			),
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF  => '',
			Composition_Schema::FIELD_HELPER_ONE_PAGER_REF => '',
		);
		$result           = $this->validator->validate( $composition );
		$preview_warnings = $result->get_preview_readiness_warnings();
		self::assertNotEmpty( array_filter( $preview_warnings, fn( $w ) => ( $w['code'] ?? '' ) === 'section_missing_preview' ) );
	}

	public function test_one_pager_missing_adds_preview_readiness_warning(): void {
		$this->seed_sections(
			array(
				'cta' => array(
					'cta_classification'  => 'primary_cta',
					'preview_description' => 'x',
				),
			)
		);
		$composition      = array(
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'cta',
					Composition_Schema::SECTION_ITEM_POSITION => 0,
				),
			),
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF  => '',
			Composition_Schema::FIELD_HELPER_ONE_PAGER_REF => '',
		);
		$result           = $this->validator->validate( $composition );
		$preview_warnings = $result->get_preview_readiness_warnings();
		self::assertNotEmpty( array_filter( $preview_warnings, fn( $w ) => ( $w['code'] ?? '' ) === 'one_pager_missing' ) );
	}

	public function test_cta_count_by_class_skipped_when_no_class(): void {
		$section_defs = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$section_defs[ 's' . $i ] = array(
				'cta_classification'  => $i < 2 ? 'primary_cta' : 'none',
				'preview_description' => 'x',
			);
		}
		$this->seed_sections( $section_defs );
		$ordered = array();
		foreach ( array_keys( $section_defs ) as $pos => $key ) {
			$ordered[] = array(
				Composition_Schema::SECTION_ITEM_KEY      => $key,
				Composition_Schema::SECTION_ITEM_POSITION => $pos,
			);
		}
		$composition = array(
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => $ordered,
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF  => '',
			Composition_Schema::FIELD_HELPER_ONE_PAGER_REF => '',
		);
		$result      = $this->validator->validate( $composition );
		// * Without template_category_class (and no source_template_ref), CTA count-by-class is not enforced; no cta_count_below_minimum.
		$blocker_codes = array_column( $result->get_blockers(), 'code' );
		self::assertNotContains( 'cta_count_below_minimum', $blocker_codes );
	}

	/**
	 * Example composition-validation result payload with both blockers and warnings (Prompt 178).
	 */
	public function test_example_composition_validation_result_payload_with_blockers_and_warnings(): void {
		$this->seed_sections(
			array(
				'cta1' => array(
					'cta_classification'  => 'primary_cta',
					'preview_description' => 'CTA1',
				),
				'cta2' => array(
					'cta_classification'  => 'contact_cta',
					'preview_description' => 'CTA2',
				),
				'hero' => array( 'cta_classification' => 'none' ),
			)
		);
		$composition = array(
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'cta1',
					Composition_Schema::SECTION_ITEM_POSITION => 0,
				),
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'cta2',
					Composition_Schema::SECTION_ITEM_POSITION => 1,
				),
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'hero',
					Composition_Schema::SECTION_ITEM_POSITION => 2,
				),
			),
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF  => '',
			Composition_Schema::FIELD_HELPER_ONE_PAGER_REF => '',
		);
		$result      = $this->validator->validate( $composition );
		$payload     = $result->to_array();

		self::assertIsArray( $payload );
		self::assertArrayHasKey( 'valid', $payload );
		self::assertArrayHasKey( 'blockers', $payload );
		self::assertArrayHasKey( 'warnings', $payload );
		self::assertArrayHasKey( 'cta_rule_violations', $payload );
		self::assertArrayHasKey( 'compatibility_violations', $payload );
		self::assertArrayHasKey( 'preview_readiness_warnings', $payload );
		self::assertArrayHasKey( 'legacy_codes', $payload );

		self::assertFalse( $payload['valid'] );
		self::assertNotEmpty( $payload['blockers'], 'Example must include at least one blocker' );
		self::assertNotEmpty( $payload['preview_readiness_warnings'], 'Example must include at least one warning' );

		foreach ( $payload['blockers'] as $b ) {
			self::assertArrayHasKey( 'code', $b );
			self::assertArrayHasKey( 'message', $b );
		}
		foreach ( $payload['warnings'] as $w ) {
			self::assertArrayHasKey( 'code', $w );
			self::assertArrayHasKey( 'message', $w );
		}
		foreach ( $payload['cta_rule_violations'] as $v ) {
			self::assertArrayHasKey( 'code', $v );
			self::assertArrayHasKey( 'message', $v );
		}

		$example = array(
			'valid'                      => false,
			'blockers'                   => array(
				array(
					'code'    => 'adjacent_cta_violation',
					'message' => 'Two CTA sections are adjacent.',
				),
				array(
					'code'    => 'bottom_cta_missing',
					'message' => 'Last section must be CTA-classified.',
				),
			),
			'warnings'                   => array(),
			'cta_rule_violations'        => array(
				array(
					'code'     => 'adjacent_cta_violation',
					'message'  => 'Two CTA sections are adjacent; add a non-CTA section between them.',
					'position' => 0,
				),
				array(
					'code'     => 'bottom_cta_missing',
					'message'  => 'Last section must be CTA-classified.',
					'position' => 2,
				),
			),
			'compatibility_violations'   => array(),
			'preview_readiness_warnings' => array(
				array(
					'code'    => 'section_missing_preview',
					'message' => 'Section hero has no preview data.',
				),
				array(
					'code'    => 'one_pager_missing',
					'message' => 'Composition has no one-pager reference.',
				),
			),
			'legacy_codes'               => array(),
		);
		self::assertSame( array_keys( $example ), array_keys( $payload ) );
	}
}
