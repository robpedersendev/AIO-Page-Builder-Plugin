<?php
/**
 * Assembles ACF field group arrays from normalized section blueprints (spec §20.2, §20.8).
 * Produces deterministic group structure for acf_add_local_field_group. Does not register.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Field_Key_Generator;

/**
 * Translates normalized section blueprints into ACF-compatible field group arrays.
 * Includes placeholder location until page-level visibility is implemented.
 */
final class ACF_Group_Builder {

	/** Post type used for placeholder location (no screens match until page assignment). */
	public const PLACEHOLDER_POST_TYPE = 'aio_built_page';

	/** @var ACF_Field_Builder */
	private ACF_Field_Builder $field_builder;

	public function __construct( ACF_Field_Builder $field_builder ) {
		$this->field_builder = $field_builder;
	}

	/**
	 * Builds ACF field group array from normalized blueprint.
	 *
	 * @param array<string, mixed> $blueprint Normalized blueprint from Section_Field_Blueprint_Service.
	 * @return array<string, mixed>|null ACF group array or null if blueprint invalid.
	 */
	public function build_group( array $blueprint ): ?array {
		$group_key = $this->resolve_group_key( $blueprint );
		$fields    = $this->resolve_fields( $blueprint );

		if ( $group_key === '' || empty( $fields ) ) {
			return null;
		}

		return array(
			'key'                   => $group_key,
			'title'                 => (string) ( $blueprint[ Field_Blueprint_Schema::LABEL ] ?? 'Untitled' ),
			'fields'                => $fields,
			'location'              => $this->placeholder_location(),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'description'           => (string) ( $blueprint['description'] ?? '' ),
			'hide_on_screen'        => '',
			'_aio_section_key'      => (string) ( $blueprint[ Field_Blueprint_Schema::SECTION_KEY ] ?? '' ),
			'_aio_section_version'  => (string) ( $blueprint[ Field_Blueprint_Schema::SECTION_VERSION ] ?? '' ),
		);
	}

	/**
	 * Resolves group key from blueprint.
	 *
	 * @param array<string, mixed> $blueprint
	 * @return string
	 */
	private function resolve_group_key( array $blueprint ): string {
		$section_key = (string) ( $blueprint[ Field_Blueprint_Schema::SECTION_KEY ] ?? '' );
		if ( $section_key === '' ) {
			return '';
		}
		return Field_Key_Generator::group_key( $section_key );
	}

	/**
	 * Resolves and builds ACF fields from blueprint.
	 *
	 * @param array<string, mixed> $blueprint
	 * @return array<int, array<string, mixed>>
	 */
	private function resolve_fields( array $blueprint ): array {
		$fields_raw = $blueprint[ Field_Blueprint_Schema::FIELDS ] ?? array();
		if ( ! is_array( $fields_raw ) ) {
			return array();
		}
		$group_key = $this->resolve_group_key( $blueprint );
		if ( $group_key === '' ) {
			return array();
		}
		return $this->field_builder->build_fields( $fields_raw, $group_key );
	}

	/**
	 * Placeholder location rules. Groups registered but not displayed until page assignment.
	 * Uses non-matching post_type so no screens show the group by default.
	 *
	 * @return array<int, array<int, array<string, string>>>
	 */
	private function placeholder_location(): array {
		return array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => self::PLACEHOLDER_POST_TYPE,
				),
			),
		);
	}

	/**
	 * Builds location rules for a specific post type (for later page-assignment use).
	 *
	 * @param string $post_type
	 * @return array<int, array<int, array<string, string>>>
	 */
	public static function location_for_post_type( string $post_type ): array {
		$post_type = \sanitize_text_field( $post_type );
		if ( $post_type === '' ) {
			$post_type = 'page';
		}
		return array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => $post_type,
				),
			),
		);
	}
}
