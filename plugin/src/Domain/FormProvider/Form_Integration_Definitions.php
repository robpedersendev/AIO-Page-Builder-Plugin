<?php
/**
 * Bundled section and page template definitions for form sections (form-provider-integration-contract.md).
 * Use these to seed or import the form section and request/contact page templates that embed NDR Form Manager (or other providers).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\FormProvider;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Returns section and page template definition arrays for form embed support.
 * Does not persist; callers save via Section_Template_Repository and Page_Template_Repository.
 */
final class Form_Integration_Definitions {

	/** Section internal_key for the form-embed section template. */
	public const FORM_SECTION_KEY = 'form_section_ndr';

	/** Page template internal_key for the request/contact page with form section. */
	public const REQUEST_PAGE_TEMPLATE_KEY = 'pt_request_form';

	/**
	 * Returns the form section template definition (category form_embed, embedded field blueprint).
	 *
	 * @return array<string, mixed>
	 */
	public static function form_section_definition(): array {
		$blueprint_id = 'acf_blueprint_' . self::FORM_SECTION_KEY;
		return array(
			Section_Schema::FIELD_INTERNAL_KEY             => self::FORM_SECTION_KEY,
			Section_Schema::FIELD_NAME                     => 'Form section',
			Section_Schema::FIELD_PURPOSE_SUMMARY          => 'Embeds a single form from a registered form provider (e.g. NDR Form Manager). Use form_provider and form_id to select the form.',
			Section_Schema::FIELD_CATEGORY                 => 'form_embed',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_form_section',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF      => $blueprint_id,
			Section_Schema::FIELD_HELPER_REF               => 'helper_form_section',
			Section_Schema::FIELD_CSS_CONTRACT_REF         => 'css_form_section',
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
			'field_blueprint'                              => array(
				'blueprint_id'    => $blueprint_id,
				'section_key'     => self::FORM_SECTION_KEY,
				'section_version' => '1',
				'label'           => 'Form section fields',
				'description'     => 'Form provider and form identifier for embedding. Optional heading above the form.',
				'fields'          => array(
					array(
						'key'           => 'field_form_provider',
						'name'          => Form_Provider_Registry::FIELD_FORM_PROVIDER,
						'label'         => 'Form provider',
						'type'          => 'text',
						'required'      => true,
						'default_value' => 'ndr_forms',
						'instructions'  => 'Provider slug (e.g. ndr_forms). Must be registered in the page builder.',
					),
					array(
						'key'          => 'field_form_id',
						'name'         => Form_Provider_Registry::FIELD_FORM_ID,
						'label'        => 'Form identifier',
						'type'         => 'text',
						'required'     => true,
						'instructions' => 'Form storage key or slug (e.g. contact, or form_abc-123). Use the form list in Form Manager or the form list API.',
					),
					array(
						'key'          => 'field_headline',
						'name'         => 'headline',
						'label'        => 'Heading (optional)',
						'type'         => 'text',
						'required'     => false,
						'instructions' => 'Optional heading above the form.',
					),
				),
			),
		);
	}

	/**
	 * Returns the request/contact page template definition that includes the form section.
	 *
	 * @return array<string, mixed>
	 */
	public static function request_page_template_definition(): array {
		$ordered = array(
			array(
				Page_Template_Schema::SECTION_ITEM_KEY => self::FORM_SECTION_KEY,
				Page_Template_Schema::SECTION_ITEM_POSITION => 0,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => true,
			),
		);
		return array(
			Page_Template_Schema::FIELD_INTERNAL_KEY     => self::REQUEST_PAGE_TEMPLATE_KEY,
			Page_Template_Schema::FIELD_NAME             => 'Request / contact page',
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY  => 'Single-section page with an embedded form (e.g. contact, request). Requires a form section template and a registered form provider.',
			Page_Template_Schema::FIELD_ARCHETYPE        => 'request_page',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => $ordered,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => array( self::FORM_SECTION_KEY => array( 'required' => true ) ),
			Page_Template_Schema::FIELD_COMPATIBILITY    => array(),
			Page_Template_Schema::FIELD_ONE_PAGER        => array( 'page_purpose_summary' => 'Request or contact page with one form section.' ),
			Page_Template_Schema::FIELD_VERSION          => array( 'version' => '1' ),
			Page_Template_Schema::FIELD_STATUS           => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => array(),
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => 'Use with form section; set form_provider (e.g. ndr_forms) and form_id on the section.',
		);
	}
}
