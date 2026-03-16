<?php
/**
 * Subtype selection form field for Industry Profile (Prompt 432; industry-subtype-extension-contract).
 * Provides options filtered by selected parent industry; used on industry settings and onboarding.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Forms;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;

/**
 * Builds subtype dropdown options and field config for industry profile forms.
 */
final class Industry_Subtype_Form_Field {

	/** POST/field name for industry subtype key. */
	public const FIELD_NAME = Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY;

	/** @var Industry_Subtype_Registry|null */
	private ?Industry_Subtype_Registry $subtype_registry;

	public function __construct( ?Industry_Subtype_Registry $subtype_registry = null ) {
		$this->subtype_registry = $subtype_registry;
	}

	/**
	 * Returns subtype options for the given parent industry key. Empty option plus subtype_key => label.
	 *
	 * @param string $parent_industry_key Primary industry key (e.g. realtor, plumber).
	 * @return array<string, string> Value => label.
	 */
	public function get_options_for_industry( string $parent_industry_key ): array {
		$options = array( '' => __( '— None —', 'aio-page-builder' ) );
		if ( $this->subtype_registry === null || $parent_industry_key === '' ) {
			return $options;
		}
		$subtypes = $this->subtype_registry->get_for_parent( trim( $parent_industry_key ), true );
		foreach ( $subtypes as $def ) {
			$key   = isset( $def[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] ) && is_string( $def[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] )
				? trim( $def[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] )
				: '';
			$label = isset( $def[ Industry_Subtype_Registry::FIELD_LABEL ] ) && is_string( $def[ Industry_Subtype_Registry::FIELD_LABEL ] )
				? trim( $def[ Industry_Subtype_Registry::FIELD_LABEL ] )
				: $key;
			if ( $key !== '' ) {
				$options[ $key ] = $label;
			}
		}
		return $options;
	}

	/**
	 * Returns whether the given industry has any subtypes (so UI can show the subtype field).
	 *
	 * @param string $parent_industry_key Primary industry key.
	 * @return bool
	 */
	public function industry_has_subtypes( string $parent_industry_key ): bool {
		if ( $this->subtype_registry === null || trim( $parent_industry_key ) === '' ) {
			return false;
		}
		$subtypes = $this->subtype_registry->get_for_parent( trim( $parent_industry_key ), true );
		return count( $subtypes ) > 0;
	}

	/**
	 * Returns field config for the subtype select (name, type, label, description).
	 *
	 * @return array{name: string, type: string, label: string, description: string}
	 */
	public function get_field_config(): array {
		return array(
			'name'        => self::FIELD_NAME,
			'type'        => 'select',
			'label'       => __( 'Subtype (optional)', 'aio-page-builder' ),
			'description' => __( 'Refines recommendations and guidance for this industry. Leave as "None" to use the parent industry only.', 'aio-page-builder' ),
		);
	}
}
