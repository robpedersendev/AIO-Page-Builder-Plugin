<?php
/**
 * Builds reusable ACF fixture data for tests and future admin diagnostics (spec §56.2, §56.3).
 * Returns structured definitions; does not mutate storage.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Diagnostics;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Fixture scenario keys: valid, stale, deprecated, invalid.
 * Use with build_scenario() to get definitions for seeding tests or diagnostics demos.
 */
final class ACF_Fixture_Builder {

	/** Valid scenario: section with valid blueprint, template, page assignment. */
	public const SCENARIO_VALID = 'valid';

	/** Stale scenario: page has assignment no longer in structural source. */
	public const SCENARIO_STALE = 'stale';

	/** Deprecated scenario: section deprecated, page retains that group. */
	public const SCENARIO_DEPRECATED = 'deprecated';

	/** Invalid scenario: section with invalid or missing blueprint. */
	public const SCENARIO_INVALID = 'invalid';

	/**
	 * Returns fixture definitions for the given scenario. Caller applies to repositories/globals.
	 *
	 * @param string $scenario One of SCENARIO_* constants.
	 * @return array{section_definitions?: list<array>, template_definitions?: list<array>, composition_definitions?: list<array>, assignment_rows?: list<array>, description: string}
	 */
	public static function build_scenario( string $scenario ): array {
		switch ( $scenario ) {
			case self::SCENARIO_VALID:
				return self::build_valid_scenario();
			case self::SCENARIO_STALE:
				return self::build_stale_scenario();
			case self::SCENARIO_DEPRECATED:
				return self::build_deprecated_scenario();
			case self::SCENARIO_INVALID:
				return self::build_invalid_scenario();
			default:
				return array( 'description' => 'Unknown scenario.', 'section_definitions' => array(), 'template_definitions' => array(), 'assignment_rows' => array() );
		}
	}

	/**
	 * Returns all scenario keys for iteration.
	 *
	 * @return list<string>
	 */
	public static function all_scenarios(): array {
		return array(
			self::SCENARIO_VALID,
			self::SCENARIO_STALE,
			self::SCENARIO_DEPRECATED,
			self::SCENARIO_INVALID,
		);
	}

	private static function build_valid_scenario(): array {
		$section = array(
			Section_Schema::FIELD_INTERNAL_KEY => 'st_fixture_hero',
			Section_Schema::FIELD_NAME        => 'Fixture Hero',
			Section_Schema::FIELD_STATUS      => 'active',
			'field_blueprint'                 => array(
				'blueprint_id'    => 'acf_st_fixture_hero',
				'section_key'     => 'st_fixture_hero',
				'section_version' => '1',
				'label'           => 'Fixture Hero',
				'fields'          => array(
					array( 'name' => 'headline', 'type' => 'text', 'label' => 'Headline' ),
				),
			),
		);
		$template = array(
			'internal_key'   => 'pt_fixture_landing',
			'name'           => 'Fixture Landing',
			'ordered_sections' => array(
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_fixture_hero', Page_Template_Schema::SECTION_ITEM_POSITION => 0 ),
			),
		);
		return array(
			'description'          => 'Valid: section with blueprint, template, page assignment.',
			'section_definitions'  => array( $section ),
			'template_definitions' => array( $template ),
			'assignment_rows'      => array(
				array( 'map_type' => 'page_template', 'source_ref' => '1001', 'target_ref' => 'pt_fixture_landing' ),
				array( 'map_type' => 'page_field_group', 'source_ref' => '1001', 'target_ref' => 'group_aio_st_fixture_hero' ),
			),
		);
	}

	private static function build_stale_scenario(): array {
		$section = array(
			Section_Schema::FIELD_INTERNAL_KEY => 'st_fixture_old',
			Section_Schema::FIELD_NAME        => 'Fixture Old',
			Section_Schema::FIELD_STATUS      => 'active',
			'field_blueprint'                 => array(
				'blueprint_id'    => 'acf_st_fixture_old',
				'section_key'     => 'st_fixture_old',
				'section_version' => '1',
				'label'            => 'Fixture Old',
				'fields'           => array( array( 'name' => 'content', 'type' => 'textarea', 'label' => 'Content' ) ),
			),
		);
		$template = array(
			'internal_key'     => 'pt_fixture_updated',
			'name'             => 'Fixture Updated',
			'ordered_sections' => array(
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_fixture_hero' ),
			),
		);
		return array(
			'description'          => 'Stale: page has group_aio_st_fixture_old assigned but template no longer includes it.',
			'section_definitions'  => array( $section ),
			'template_definitions' => array( $template ),
			'assignment_rows'      => array(
				array( 'map_type' => 'page_template', 'source_ref' => '1002', 'target_ref' => 'pt_fixture_updated' ),
				array( 'map_type' => 'page_field_group', 'source_ref' => '1002', 'target_ref' => 'group_aio_st_fixture_old' ),
			),
		);
	}

	private static function build_deprecated_scenario(): array {
		$section = array(
			Section_Schema::FIELD_INTERNAL_KEY => 'st_fixture_deprecated',
			Section_Schema::FIELD_NAME        => 'Fixture Deprecated',
			Section_Schema::FIELD_STATUS      => 'deprecated',
			'deprecation'                     => array( 'deprecated' => true, 'reason' => 'Replaced by st_fixture_hero.' ),
			'field_blueprint'                 => array(
				'blueprint_id'    => 'acf_st_fixture_deprecated',
				'section_key'     => 'st_fixture_deprecated',
				'section_version' => '1',
				'label'            => 'Fixture Deprecated',
				'fields'           => array( array( 'name' => 'legacy', 'type' => 'text', 'label' => 'Legacy' ) ),
			),
		);
		return array(
			'description'          => 'Deprecated: section deprecated, page retains assignment for existing content.',
			'section_definitions'  => array( $section ),
			'template_definitions' => array(),
			'assignment_rows'      => array(
				array( 'map_type' => 'page_field_group', 'source_ref' => '1003', 'target_ref' => 'group_aio_st_fixture_deprecated' ),
			),
		);
	}

	private static function build_invalid_scenario(): array {
		$section_no_blueprint = array(
			Section_Schema::FIELD_INTERNAL_KEY => 'st_fixture_no_bp',
			Section_Schema::FIELD_NAME        => 'Fixture No Blueprint',
			Section_Schema::FIELD_STATUS      => 'active',
		);
		$section_invalid_blueprint = array(
			Section_Schema::FIELD_INTERNAL_KEY => 'st_fixture_invalid_bp',
			Section_Schema::FIELD_NAME        => 'Fixture Invalid Blueprint',
			Section_Schema::FIELD_STATUS      => 'active',
			'field_blueprint'                 => array(
				'section_key' => '',
				'label'       => '',
				'fields'      => array(),
			),
		);
		return array(
			'description'          => 'Invalid: sections without valid blueprint or with empty blueprint.',
			'section_definitions'  => array( $section_no_blueprint, $section_invalid_blueprint ),
			'template_definitions' => array(),
			'assignment_rows'      => array(),
		);
	}
}
