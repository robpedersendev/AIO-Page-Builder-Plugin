<?php
/**
 * Library-wide LPagery compatibility for section and page-template families (Prompt 179, spec §20.6, §21.5, §21.6, §21.9).
 * Bounded, server-governed; exposes lpagery_mapping_summary, lpagery_compatibility_state, unsupported_mapping_reason. Preserves canonical token identity.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\LPagery;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Blueprint_Family_Resolver;
use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Builds LPagery compatibility summaries for sections and page templates. Preview-safe; validates unsupported cases clearly.
 */
final class Library_LPagery_Compatibility_Service {

	/** @var LPagery_Token_Compatibility_Service */
	private LPagery_Token_Compatibility_Service $token_service;

	/** @var Section_Field_Blueprint_Service|null */
	private ?Section_Field_Blueprint_Service $blueprint_service;

	/** @var Blueprint_Family_Resolver|null */
	private ?Blueprint_Family_Resolver $family_resolver;

	public function __construct(
		LPagery_Token_Compatibility_Service $token_service,
		?Section_Field_Blueprint_Service $blueprint_service = null,
		?Blueprint_Family_Resolver $family_resolver = null
	) {
		$this->token_service     = $token_service;
		$this->blueprint_service = $blueprint_service;
		$this->family_resolver   = $family_resolver;
	}

	/**
	 * Returns LPagery compatibility for a section template. Uses blueprint fields to determine supported/unsupported mappings.
	 *
	 * @param string               $section_key Section internal_key.
	 * @param array<string, mixed> $definition  Optional section definition (if not provided, only token-service summary is returned).
	 * @return LPagery_Compatibility_Result
	 */
	public function get_compatibility_for_section( string $section_key, ?array $definition = null ): LPagery_Compatibility_Result {
		$section_key          = \sanitize_key( $section_key );
		$supported_mappings   = array();
		$unsupported_mappings = array();
		$reasons              = array();

		$blueprint = $this->get_effective_blueprint_for_section( $section_key, $definition ?? array() );
		$fields    = \is_array( $blueprint ) && isset( $blueprint[ Field_Blueprint_Schema::FIELDS ] ) && \is_array( $blueprint[ Field_Blueprint_Schema::FIELDS ] )
			? $blueprint[ Field_Blueprint_Schema::FIELDS ]
			: array();

		foreach ( $fields as $field ) {
			if ( ! \is_array( $field ) ) {
				continue;
			}
			$name = (string) ( $field[ Field_Blueprint_Schema::FIELD_NAME ] ?? $field[ Field_Blueprint_Schema::FIELD_KEY ] ?? '' );
			$type = (string) ( $field[ Field_Blueprint_Schema::FIELD_TYPE ] ?? 'text' );
			if ( $name === '' ) {
				continue;
			}

			if ( Field_Blueprint_Schema::is_lpagery_unsupported_type( $type ) ) {
				$unsupported_mappings[] = array(
					'field_name' => $name,
					'field_type' => $type,
				);
				$reasons[]              = array(
					'field_name' => $name,
					'reason'     => sprintf(
						/* translators: 1: field type */
						__( 'Field type "%1$s" is not in the LPagery-compatible set.', 'aio-page-builder' ),
						$type
					),
				);
				continue;
			}

			if ( Field_Blueprint_Schema::is_lpagery_supported_type( $type ) ) {
				$supported_mappings[] = array(
					'field_name'                   => $name,
					'field_type'                   => $type,
					'canonical_identity_preserved' => true,
				);
			}
		}

		$allowed_groups = $this->token_service->get_allowed_groups();
		$summary        = array(
			'supported_mappings'           => $supported_mappings,
			'unsupported_mappings'         => $unsupported_mappings,
			'allowed_groups'               => $allowed_groups,
			'canonical_identity_preserved' => true,
			'preview_safe'                 => true,
			'mapping_convention'           => $this->token_service->get_compatibility_summary()['mapping_convention'] ?? 'group.name',
		);

		$compatible = count( $supported_mappings ) > 0 && count( $reasons ) === 0;
		$state      = $this->derive_state( count( $supported_mappings ), count( $unsupported_mappings ), count( $fields ) );

		return new LPagery_Compatibility_Result( $compatible, $state, $summary, $reasons );
	}

	/**
	 * Returns LPagery compatibility for a page template. Aggregates from used sections or template-level metadata.
	 *
	 * @param string                                                         $template_key Page template internal_key.
	 * @param array<string, mixed>                                           $definition   Optional template definition (ordered_sections, etc.).
	 * @param array<int, array{section_key: string, lpagery_state?: string}> $section_compatibilities Optional precomputed section compatibilities for used sections.
	 * @return LPagery_Compatibility_Result
	 */
	public function get_compatibility_for_page_template(
		string $template_key,
		?array $definition = null,
		array $section_compatibilities = array()
	): LPagery_Compatibility_Result {
		$template_key         = \sanitize_key( $template_key );
		$supported_mappings   = array();
		$unsupported_mappings = array();
		$reasons              = array();

		$ordered = array();
		if ( \is_array( $definition ) && isset( $definition[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ) && \is_array( $definition[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ) ) {
			$ordered = $definition[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ];
		}

		$sections_with_support = 0;
		$sections_unsupported  = 0;
		foreach ( $section_compatibilities as $item ) {
			$state = (string) ( $item['lpagery_state'] ?? $item['compatibility_state'] ?? '' );
			if ( $state === LPagery_Compatibility_Result::STATE_SUPPORTED || $state === LPagery_Compatibility_Result::STATE_PARTIAL ) {
				++$sections_with_support;
			} elseif ( $state === LPagery_Compatibility_Result::STATE_UNSUPPORTED ) {
				++$sections_unsupported;
			}
		}

		if ( empty( $section_compatibilities ) && empty( $ordered ) ) {
			$summary = array(
				'supported_mappings'           => array(),
				'unsupported_mappings'         => array(),
				'allowed_groups'               => $this->token_service->get_allowed_groups(),
				'canonical_identity_preserved' => true,
				'preview_safe'                 => true,
				'mapping_convention'           => $this->token_service->get_compatibility_summary()['mapping_convention'] ?? 'group.name',
				'aggregate_from_sections'      => true,
				'used_section_count'           => 0,
			);
			return new LPagery_Compatibility_Result( false, LPagery_Compatibility_Result::STATE_UNKNOWN, $summary, array() );
		}

		$summary = array(
			'supported_mappings'            => $supported_mappings,
			'unsupported_mappings'          => $unsupported_mappings,
			'allowed_groups'                => $this->token_service->get_allowed_groups(),
			'canonical_identity_preserved'  => true,
			'preview_safe'                  => true,
			'mapping_convention'            => $this->token_service->get_compatibility_summary()['mapping_convention'] ?? 'group.name',
			'aggregate_from_sections'       => true,
			'used_section_count'            => count( $ordered ),
			'sections_with_lpagery_support' => $sections_with_support,
			'sections_unsupported'          => $sections_unsupported,
		);

		$compatible = $sections_with_support > 0;
		$state      = $sections_with_support > 0
			? ( $sections_unsupported > 0 ? LPagery_Compatibility_Result::STATE_PARTIAL : LPagery_Compatibility_Result::STATE_SUPPORTED )
			: ( count( $ordered ) > 0 ? LPagery_Compatibility_Result::STATE_UNSUPPORTED : LPagery_Compatibility_Result::STATE_UNKNOWN );

		return new LPagery_Compatibility_Result( $compatible, $state, $summary, $reasons );
	}

	/**
	 * Validates a field for LPagery mapping. Returns supported flag and reason for unsupported cases.
	 *
	 * @param string      $field_name
	 * @param string      $field_type
	 * @param string|null $token_group Optional canonical token group (validated if provided).
	 * @param string|null $token_name  Optional canonical token name (validated if provided).
	 * @return array{supported: bool, reason: string}
	 */
	public function validate_field_mapping( string $field_name, string $field_type, ?string $token_group = null, ?string $token_name = null ): array {
		$field_type = trim( $field_type );
		if ( Field_Blueprint_Schema::is_lpagery_unsupported_type( $field_type ) ) {
			return array(
				'supported' => false,
				'reason'    => sprintf(
					/* translators: 1: field type */
					__( 'Field type "%1$s" is not in the LPagery-compatible set.', 'aio-page-builder' ),
					$field_type
				),
			);
		}
		if ( ! Field_Blueprint_Schema::is_lpagery_supported_type( $field_type ) ) {
			return array(
				'supported' => false,
				'reason'    => sprintf(
					/* translators: 1: field type */
					__( 'Field type "%1$s" has conditional LPagery support; document expected token shape.', 'aio-page-builder' ),
					$field_type
				),
			);
		}
		if ( $token_group !== null && $token_name !== null && $token_group !== '' && $token_name !== '' ) {
			$result = $this->token_service->map_core_to_lpagery( $token_group, $token_name );
			if ( ! $result->is_supported() ) {
				return array(
					'supported' => false,
					'reason'    => $result->get_warning() ?? __( 'Token mapping not supported.', 'aio-page-builder' ),
				);
			}
		}
		return array(
			'supported' => true,
			'reason'    => '',
		);
	}

	/**
	 * Returns effective normalized blueprint for a section (from service + family resolver when available).
	 *
	 * @param string               $section_key
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed>|null
	 */
	private function get_effective_blueprint_for_section( string $section_key, array $definition ): ?array {
		$blueprint = null;
		if ( $this->blueprint_service !== null ) {
			$blueprint = $this->blueprint_service->get_blueprint_for_section( $section_key );
		}
		if ( $blueprint === null && isset( $definition['field_blueprint'] ) && \is_array( $definition['field_blueprint'] ) ) {
			$blueprint = array( Field_Blueprint_Schema::FIELDS => $definition['field_blueprint'][ Field_Blueprint_Schema::FIELDS ] ?? array() );
		}
		if ( $blueprint !== null && $this->family_resolver !== null && ! empty( $definition[ Section_Schema::FIELD_INTERNAL_KEY ] ) ) {
			$blueprint = $this->family_resolver->resolve( $definition, $blueprint );
		}
		return $blueprint;
	}

	private function derive_state( int $supported_count, int $unsupported_count, int $total_fields ): string {
		if ( $total_fields === 0 ) {
			return LPagery_Compatibility_Result::STATE_UNKNOWN;
		}
		if ( $supported_count > 0 && $unsupported_count === 0 ) {
			return LPagery_Compatibility_Result::STATE_SUPPORTED;
		}
		if ( $supported_count > 0 && $unsupported_count > 0 ) {
			return LPagery_Compatibility_Result::STATE_PARTIAL;
		}
		return LPagery_Compatibility_Result::STATE_UNSUPPORTED;
	}
}
