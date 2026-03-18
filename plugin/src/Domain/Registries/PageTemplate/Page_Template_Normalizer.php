<?php
/**
 * Normalizes and sanitizes page template input to schema shape (spec §13, page-template-registry-schema.md).
 * No persistence; produces code-level normalized page template definition for registry use.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate;

defined( 'ABSPATH' ) || exit;

/**
 * Enforces page-template-registry-schema.md shape: required keys, ordered sections, nested structures.
 * Preserves section order by position ascending.
 */
final class Page_Template_Normalizer {

	/** Max length for internal_key. */
	private const MAX_INTERNAL_KEY = 64;

	/** Max length for name. */
	private const MAX_NAME = 255;

	/** Max length for purpose_summary. */
	private const MAX_PURPOSE = 1024;

	/** Max length for endpoint_or_usage_notes. */
	private const MAX_ENDPOINT = 512;

	/** Default compatibility shape (page-template-registry-schema §6). */
	private const DEFAULT_COMPATIBILITY = array(
		'site_contexts_appropriate'         => array(),
		'site_contexts_inappropriate'       => array(),
		'required_content_assumptions'      => array(),
		'section_variant_incompatibilities' => array(),
		'hierarchy_assumptions'             => '',
		'token_or_layout_dependencies'      => '',
		'conflicts_with_purposes'           => array(),
	);

	/** Default one-pager shape (page-template-registry-schema §7). */
	private const DEFAULT_ONE_PAGER = array(
		'page_purpose_summary'         => '',
		'section_helper_order'         => 'same_as_template',
		'cross_section_strategy_notes' => '',
		'optional_section_handling'    => '',
		'global_editing_notes'         => '',
		'page_flow_explanation'        => '',
		'token_or_visual_notes'        => '',
	);

	/** Default version shape (page-template-registry-schema §8). */
	private const DEFAULT_VERSION = array(
		'version'                       => '1',
		'changelog_ref'                 => '',
		'section_version_compatibility' => '',
		'migration_notes_ref'           => '',
		'stable_key_retained'           => true,
	);

	/**
	 * Normalizes page template input to full schema shape.
	 *
	 * @param array<string, mixed> $input Raw or partial page template definition.
	 * @return array<string, mixed> Normalized definition; may still fail validation.
	 */
	public function normalize( array $input ): array {
		$input = $this->filter_known_keys( $input );

		$ordered      = $this->normalize_ordered_sections( $input[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array() );
		$requirements = $this->normalize_section_requirements( $input[ Page_Template_Schema::FIELD_SECTION_REQUIREMENTS ] ?? array(), $ordered );
		$compat       = $this->normalize_compatibility( $input[ Page_Template_Schema::FIELD_COMPATIBILITY ] ?? array() );
		$one_pager    = $this->normalize_one_pager( $input[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? array() );
		$version      = $this->normalize_version( $input[ Page_Template_Schema::FIELD_VERSION ] ?? array() );
		$deprecation  = $this->normalize_deprecation( $input['deprecation'] ?? array(), $input[ Page_Template_Schema::FIELD_STATUS ] ?? '' );

		$out = array(
			Page_Template_Schema::FIELD_INTERNAL_KEY     => $this->sanitize_key( (string) ( $input[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' ) ),
			Page_Template_Schema::FIELD_NAME             => $this->sanitize_string( (string) ( $input[ Page_Template_Schema::FIELD_NAME ] ?? '' ), self::MAX_NAME ),
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY  => $this->sanitize_string( (string) ( $input[ Page_Template_Schema::FIELD_PURPOSE_SUMMARY ] ?? '' ), self::MAX_PURPOSE ),
			Page_Template_Schema::FIELD_ARCHETYPE        => $this->sanitize_archetype( (string) ( $input[ Page_Template_Schema::FIELD_ARCHETYPE ] ?? '' ) ),
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => $ordered,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => $requirements,
			Page_Template_Schema::FIELD_COMPATIBILITY    => $compat,
			Page_Template_Schema::FIELD_ONE_PAGER        => $one_pager,
			Page_Template_Schema::FIELD_VERSION          => $version,
			Page_Template_Schema::FIELD_STATUS           => $this->sanitize_status( (string) ( $input[ Page_Template_Schema::FIELD_STATUS ] ?? 'draft' ) ),
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => $this->sanitize_string( (string) ( $input[ Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS ] ?? '' ), self::MAX_PURPOSE ),
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => $this->sanitize_string( (string) ( $input[ Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES ] ?? '' ), self::MAX_ENDPOINT ),
		);

		foreach ( Page_Template_Schema::get_optional_fields() as $opt ) {
			if ( $opt === 'deprecation' ) {
				continue;
			}
			if ( array_key_exists( $opt, $input ) ) {
				$out[ $opt ] = $this->normalize_optional_field( $opt, $input[ $opt ] );
			}
		}

		if ( ! empty( $deprecation ) ) {
			$out['deprecation'] = $deprecation;
		}

		return $out;
	}

	/**
	 * Normalizes ordered_sections: ensures position ascending, valid item shape.
	 *
	 * @param array<int, mixed> $raw
	 * @return list<array{section_key: string, position: int, required: bool}>
	 */
	private function normalize_ordered_sections( array $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$items = array();
		$seen  = array();
		foreach ( $raw as $i => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$key = $this->sanitize_key( (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' ) );
			if ( $key === '' ) {
				continue;
			}
			$pos     = isset( $item[ Page_Template_Schema::SECTION_ITEM_POSITION ] ) ? (int) $item[ Page_Template_Schema::SECTION_ITEM_POSITION ] : $i;
			$req     = (bool) ( $item[ Page_Template_Schema::SECTION_ITEM_REQUIRED ] ?? true );
			$items[] = array(
				Page_Template_Schema::SECTION_ITEM_KEY => $key,
				Page_Template_Schema::SECTION_ITEM_POSITION => $pos,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => $req,
			);
		}
		usort( $items, fn( $a, $b ) => $a[ Page_Template_Schema::SECTION_ITEM_POSITION ] <=> $b[ Page_Template_Schema::SECTION_ITEM_POSITION ] );
		for ( $i = 0; $i < count( $items ); $i++ ) {
			$items[ $i ][ Page_Template_Schema::SECTION_ITEM_POSITION ] = $i;
		}
		return array_values( $items );
	}

	/**
	 * Builds section_requirements from ordered sections; merges with input requirements.
	 *
	 * @param array<string, mixed>                                            $raw
	 * @param list<array{section_key: string, position: int, required: bool}> $ordered
	 * @return array<string, array{required: bool}>
	 */
	private function normalize_section_requirements( array $raw, array $ordered ): array {
		$reqs = array();
		foreach ( $ordered as $item ) {
			$key = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
			if ( $key === '' ) {
				continue;
			}
			$req = (bool) ( $item[ Page_Template_Schema::SECTION_ITEM_REQUIRED ] ?? true );
			if ( isset( $raw[ $key ] ) && is_array( $raw[ $key ] ) && array_key_exists( 'required', $raw[ $key ] ) ) {
				$req = (bool) $raw[ $key ]['required'];
			}
			$reqs[ $key ] = array( 'required' => $req );
		}
		return $reqs;
	}

	/**
	 * @param array<string, mixed> $raw
	 * @return array<string, mixed>
	 */
	private function normalize_compatibility( array $raw ): array {
		if ( ! is_array( $raw ) ) {
			return self::DEFAULT_COMPATIBILITY;
		}
		$arr = fn( $a, int $max = 128 ): array => is_array( $a )
			? array_values( array_filter( array_map( fn( $v ) => $this->sanitize_string( (string) $v, $max ), $a ) ) )
			: array();

		return array(
			'site_contexts_appropriate'         => $arr( $raw['site_contexts_appropriate'] ?? array() ),
			'site_contexts_inappropriate'       => $arr( $raw['site_contexts_inappropriate'] ?? array() ),
			'required_content_assumptions'      => $arr( $raw['required_content_assumptions'] ?? array(), 256 ),
			'section_variant_incompatibilities' => $arr( $raw['section_variant_incompatibilities'] ?? array() ),
			'hierarchy_assumptions'             => $this->sanitize_string( (string) ( $raw['hierarchy_assumptions'] ?? '' ), 512 ),
			'token_or_layout_dependencies'      => $this->sanitize_string( (string) ( $raw['token_or_layout_dependencies'] ?? '' ), 512 ),
			'conflicts_with_purposes'           => $arr( $raw['conflicts_with_purposes'] ?? array() ),
		);
	}

	/**
	 * @param array<string, mixed> $raw
	 * @return array<string, mixed>
	 */
	private function normalize_one_pager( array $raw ): array {
		if ( ! is_array( $raw ) ) {
			return self::DEFAULT_ONE_PAGER;
		}
		$order = (string) ( $raw['section_helper_order'] ?? 'same_as_template' );
		if ( $order !== 'explicit' ) {
			$order = 'same_as_template';
		}

		return array(
			'page_purpose_summary'         => $this->sanitize_string( (string) ( $raw['page_purpose_summary'] ?? '' ), 1024 ),
			'section_helper_order'         => $order,
			'cross_section_strategy_notes' => $this->sanitize_string( (string) ( $raw['cross_section_strategy_notes'] ?? '' ), 1024 ),
			'optional_section_handling'    => $this->sanitize_string( (string) ( $raw['optional_section_handling'] ?? '' ), 512 ),
			'global_editing_notes'         => $this->sanitize_string( (string) ( $raw['global_editing_notes'] ?? '' ), 1024 ),
			'page_flow_explanation'        => $this->sanitize_string( (string) ( $raw['page_flow_explanation'] ?? '' ), 1024 ),
			'token_or_visual_notes'        => $this->sanitize_string( (string) ( $raw['token_or_visual_notes'] ?? '' ), 512 ),
		);
	}

	/**
	 * @param array<string, mixed> $raw
	 * @return array<string, mixed>
	 */
	private function normalize_version( array $raw ): array {
		if ( ! is_array( $raw ) ) {
			return self::DEFAULT_VERSION;
		}
		return array(
			'version'                       => $this->sanitize_string( (string) ( $raw['version'] ?? '1' ), 32 ),
			'changelog_ref'                 => $this->sanitize_string( (string) ( $raw['changelog_ref'] ?? '' ), 255 ),
			'section_version_compatibility' => $this->sanitize_string( (string) ( $raw['section_version_compatibility'] ?? '' ), 512 ),
			'migration_notes_ref'           => $this->sanitize_string( (string) ( $raw['migration_notes_ref'] ?? '' ), 255 ),
			'stable_key_retained'           => (bool) ( $raw['stable_key_retained'] ?? true ),
		);
	}

	/**
	 * @param array<string, mixed> $raw
	 * @param string               $status
	 * @return array<string, mixed>
	 */
	private function normalize_deprecation( array $raw, string $status ): array {
		if ( ! is_array( $raw ) || $status !== 'deprecated' ) {
			return array();
		}
		return array(
			'deprecated'                       => (bool) ( $raw['deprecated'] ?? true ),
			'reason'                           => $this->sanitize_string( (string) ( $raw['reason'] ?? '' ), 512 ),
			'replacement_template_key'         => $this->sanitize_key( (string) ( $raw['replacement_template_key'] ?? '' ) ),
			'interpretability_of_old_plans'    => (bool) ( $raw['interpretability_of_old_plans'] ?? true ),
			'exclude_from_new_build_selection' => (bool) ( $raw['exclude_from_new_build_selection'] ?? true ),
		);
	}

	/**
	 * @param string $name
	 * @param mixed  $value
	 * @return mixed
	 */
	private function normalize_optional_field( string $name, mixed $value ): mixed {
		$str_limits = array(
			'template_category_class'      => 64,
			'template_family'              => 64,
			'display_description'          => 1024,
			'internal_linking_hints'       => 512,
			'default_token_affinity_notes' => 512,
			'notes_for_ai_planning'        => 1024,
			'seo_notes'                    => 1024,
			'documentation_notes'          => 1024,
			'migration_notes'              => 512,
			'hierarchy_hints'              => 0,
		);
		if ( in_array( $name, array( 'recommended_industries', 'recommended_audience_types', 'suggested_page_title_patterns', 'suggested_slug_patterns' ), true ) ) {
			$max = $name === 'suggested_page_title_patterns' ? 256 : 128;
			return is_array( $value )
				? array_map( fn( $v ) => $this->sanitize_string( (string) $v, $max ), $value )
				: array();
		}
		if ( $name === 'replacement_template_refs' ) {
			return is_array( $value )
				? array_values( array_filter( array_map( fn( $v ) => $this->sanitize_key( (string) $v ), $value ) ) )
				: array();
		}
		if ( $name === 'hierarchy_hints' && is_array( $value ) ) {
			return array(
				'likely_top_level'          => (bool) ( $value['likely_top_level'] ?? false ),
				'likely_child_page'         => (bool) ( $value['likely_child_page'] ?? false ),
				'common_parent_archetypes'  => is_array( $value['common_parent_archetypes'] ?? null )
					? array_map( fn( $v ) => $this->sanitize_string( (string) $v, 64 ), $value['common_parent_archetypes'] )
					: array(),
				'common_sibling_archetypes' => is_array( $value['common_sibling_archetypes'] ?? null )
					? array_map( fn( $v ) => $this->sanitize_string( (string) $v, 64 ), $value['common_sibling_archetypes'] )
					: array(),
				'hierarchy_role'            => $this->sanitize_string( (string) ( $value['hierarchy_role'] ?? '' ), 64 ),
			);
		}
		if ( $name === 'seo_defaults' && is_array( $value ) ) {
			return array(
				'title_pattern_suggestions'  => is_array( $value['title_pattern_suggestions'] ?? null )
					? array_map( fn( $v ) => $this->sanitize_string( (string) $v, 256 ), $value['title_pattern_suggestions'] )
					: array(),
				'meta_description_direction' => $this->sanitize_string( (string) ( $value['meta_description_direction'] ?? '' ), 512 ),
				'heading_expectations'       => $this->sanitize_string( (string) ( $value['heading_expectations'] ?? '' ), 512 ),
				'internal_link_expectations' => $this->sanitize_string( (string) ( $value['internal_link_expectations'] ?? '' ), 512 ),
				'schema_type_suggestions'    => is_array( $value['schema_type_suggestions'] ?? null )
					? array_map( fn( $v ) => $this->sanitize_string( (string) $v, 64 ), $value['schema_type_suggestions'] )
					: array(),
				'page_intent_classification' => $this->sanitize_string( (string) ( $value['page_intent_classification'] ?? '' ), 128 ),
				'keyword_targeting_notes'    => $this->sanitize_string( (string) ( $value['keyword_targeting_notes'] ?? '' ), 512 ),
			);
		}
		if ( isset( $str_limits[ $name ] ) && $str_limits[ $name ] > 0 ) {
			return $this->sanitize_string( (string) $value, $str_limits[ $name ] );
		}
		if ( $name === 'preview_metadata' ) {
			return is_array( $value ) ? $value : array();
		}
		return $value;
	}

	private function filter_known_keys( array $input ): array {
		$allowed = array_merge( Page_Template_Schema::get_required_fields(), Page_Template_Schema::get_optional_fields() );
		$out     = array();
		foreach ( $input as $k => $v ) {
			if ( in_array( $k, $allowed, true ) ) {
				$out[ $k ] = $v;
			}
		}
		return $out;
	}

	private function sanitize_key( string $key ): string {
		$key = \sanitize_text_field( strtolower( $key ) );
		$key = preg_replace( '/[^a-z0-9_]/', '', $key );
		return substr( $key, 0, self::MAX_INTERNAL_KEY );
	}

	private function sanitize_string( string $s, int $max ): string {
		$s = \wp_strip_all_tags( $s );
		$s = \sanitize_text_field( $s );
		return substr( $s, 0, $max );
	}

	private function sanitize_archetype( string $arch ): string {
		return Page_Template_Schema::is_allowed_archetype( $arch ) ? $arch : '';
	}

	private function sanitize_status( string $status ): string {
		$allowed = array( 'draft', 'active', 'inactive', 'deprecated' );
		return in_array( $status, $allowed, true ) ? $status : 'draft';
	}
}
