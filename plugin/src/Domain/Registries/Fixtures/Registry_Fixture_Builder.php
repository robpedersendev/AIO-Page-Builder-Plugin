<?php
/**
 * Reusable fixture builders for registry-owned objects (spec §52.4, §58.2).
 * Deterministic, stable keys for tests and QA. No persistence.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Fixtures;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\Documentation\Documentation_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Snapshots\Version_Snapshot_Schema;

/**
 * Builds example definitions for section, page template, composition, documentation, snapshot.
 * Keys and structure are stable for import/export compatibility tests.
 */
final class Registry_Fixture_Builder {

	/** Fixture section key. */
	public const FIXTURE_SECTION_KEY = 'st_fixture_hero';

	/** Fixture page template key. */
	public const FIXTURE_PAGE_TEMPLATE_KEY = 'pt_fixture_landing';

	/** Fixture composition id. */
	public const FIXTURE_COMPOSITION_ID = 'comp_fixture_001';

	/** Fixture documentation id. */
	public const FIXTURE_DOCUMENTATION_ID = 'doc_fixture_helper_001';

	/** Fixture snapshot id. */
	public const FIXTURE_SNAPSHOT_ID = 'snap_fixture_registry_001';

	/**
	 * Returns a minimal valid section definition.
	 *
	 * @return array<string, mixed>
	 */
	public static function section(): array {
		return array(
			Section_Schema::FIELD_INTERNAL_KEY             => self::FIXTURE_SECTION_KEY,
			Section_Schema::FIELD_NAME                     => 'Fixture Hero',
			Section_Schema::FIELD_PURPOSE_SUMMARY          => 'Fixture hero section for export tests.',
			Section_Schema::FIELD_CATEGORY                 => 'hero_intro',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_fixture',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF      => 'acf_fixture',
			Section_Schema::FIELD_HELPER_REF               => 'helper_fixture',
			Section_Schema::FIELD_CSS_CONTRACT_REF         => 'css_fixture',
			Section_Schema::FIELD_DEFAULT_VARIANT          => 'default',
			Section_Schema::FIELD_VARIANTS                 => array( 'default' => array( 'label' => 'Default' ) ),
			Section_Schema::FIELD_COMPATIBILITY            => array(),
			Section_Schema::FIELD_VERSION                  => array(
				'version'             => '1',
				'stable_key_retained' => true,
			),
			Section_Schema::FIELD_STATUS                   => 'active',
			Section_Schema::FIELD_RENDER_MODE              => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION        => array( 'none' => true ),
		);
	}

	/**
	 * Returns a minimal valid page template definition.
	 *
	 * @return array<string, mixed>
	 */
	public static function page_template(): array {
		$ordered = array(
			array(
				Page_Template_Schema::SECTION_ITEM_KEY => self::FIXTURE_SECTION_KEY,
				Page_Template_Schema::SECTION_ITEM_POSITION => 0,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => true,
			),
		);
		return array(
			Page_Template_Schema::FIELD_INTERNAL_KEY     => self::FIXTURE_PAGE_TEMPLATE_KEY,
			Page_Template_Schema::FIELD_NAME             => 'Fixture Landing',
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY  => 'Fixture landing page for export tests.',
			Page_Template_Schema::FIELD_ARCHETYPE        => 'landing',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => $ordered,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => array( self::FIXTURE_SECTION_KEY => array( 'required' => true ) ),
			Page_Template_Schema::FIELD_COMPATIBILITY    => array(),
			Page_Template_Schema::FIELD_ONE_PAGER        => array( 'page_purpose_summary' => 'Fixture purpose.' ),
			Page_Template_Schema::FIELD_VERSION          => array( 'version' => '1' ),
			Page_Template_Schema::FIELD_STATUS           => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => array(),
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => '',
		);
	}

	/**
	 * Returns a minimal valid composition definition.
	 *
	 * @return array<string, mixed>
	 */
	public static function composition(): array {
		$ordered = array(
			array(
				Composition_Schema::SECTION_ITEM_KEY      => self::FIXTURE_SECTION_KEY,
				Composition_Schema::SECTION_ITEM_POSITION => 0,
				Composition_Schema::SECTION_ITEM_VARIANT  => 'default',
			),
		);
		return array(
			Composition_Schema::FIELD_COMPOSITION_ID       => self::FIXTURE_COMPOSITION_ID,
			Composition_Schema::FIELD_NAME                 => 'Fixture Composition',
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => $ordered,
			Composition_Schema::FIELD_STATUS               => 'active',
			Composition_Schema::FIELD_VALIDATION_STATUS    => 'valid',
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF  => self::FIXTURE_PAGE_TEMPLATE_KEY,
		);
	}

	/**
	 * Returns a minimal valid documentation definition.
	 *
	 * @return array<string, mixed>
	 */
	public static function documentation(): array {
		return array(
			Documentation_Schema::FIELD_DOCUMENTATION_ID   => self::FIXTURE_DOCUMENTATION_ID,
			Documentation_Schema::FIELD_DOCUMENTATION_TYPE => Documentation_Schema::TYPE_SECTION_HELPER,
			Documentation_Schema::FIELD_CONTENT_BODY       => 'Fixture helper content for export tests.',
			Documentation_Schema::FIELD_STATUS             => 'active',
			Documentation_Schema::FIELD_SOURCE_REFERENCE   => array(
				Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY => self::FIXTURE_SECTION_KEY,
			),
		);
	}

	/**
	 * Returns a minimal valid version snapshot definition.
	 *
	 * @return array<string, mixed>
	 */
	public static function snapshot(): array {
		return array(
			Version_Snapshot_Schema::FIELD_SNAPSHOT_ID    => self::FIXTURE_SNAPSHOT_ID,
			Version_Snapshot_Schema::FIELD_SCOPE_TYPE     => Version_Snapshot_Schema::SCOPE_REGISTRY,
			Version_Snapshot_Schema::FIELD_SCOPE_ID       => 'registry_fixture',
			Version_Snapshot_Schema::FIELD_CREATED_AT     => '2025-01-01T00:00:00Z',
			Version_Snapshot_Schema::FIELD_SCHEMA_VERSION => '1',
			Version_Snapshot_Schema::FIELD_STATUS         => Version_Snapshot_Schema::STATUS_ACTIVE,
			Version_Snapshot_Schema::FIELD_OBJECT_REFS    => array(
				'sections'       => array( self::FIXTURE_SECTION_KEY ),
				'page_templates' => array( self::FIXTURE_PAGE_TEMPLATE_KEY ),
			),
		);
	}

	/**
	 * Returns all fixture definitions as a bundle (for export/import round-trip tests).
	 *
	 * @return array{sections: list<array>, page_templates: list<array>, compositions: list<array>, documentation: list<array>, snapshots: list<array>}
	 */
	public static function full_bundle(): array {
		return array(
			'sections'       => array( self::section() ),
			'page_templates' => array( self::page_template() ),
			'compositions'   => array( self::composition() ),
			'documentation'  => array( self::documentation() ),
			'snapshots'      => array( self::snapshot() ),
		);
	}
}
