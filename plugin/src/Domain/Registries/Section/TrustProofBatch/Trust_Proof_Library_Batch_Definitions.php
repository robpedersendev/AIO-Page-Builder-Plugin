<?php
/**
 * Trust, proof, and authority section template definitions for SEC-02 library batch (spec §12, §15, §20, §51, Prompt 148).
 * Production-grade trust/proof sections with full metadata, field blueprints, preview and accessibility metadata.
 * Does not persist; callers save via Section_Template_Repository or Trust_Proof_Library_Batch_Seeder.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\TrustProofBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Returns section definitions for the trust/proof library batch (SEC-02).
 * Each definition is schema-compliant with embedded field_blueprint, taxonomy, preview and animation metadata.
 */
final class Trust_Proof_Library_Batch_Definitions {

	/** Batch ID per template-library-inventory-manifest §3.1. */
	public const BATCH_ID = 'SEC-02';

	/** Section purpose family for all in this batch. */
	public const PURPOSE_FAMILY = 'proof';

	/**
	 * Returns all trust/proof batch section definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			self::tp_testimonial_01(),
			self::tp_testimonial_02(),
			self::tp_review_01(),
			self::tp_credential_01(),
			self::tp_credential_02(),
			self::tp_guarantee_01(),
			self::tp_case_teaser_01(),
			self::tp_outcome_01(),
			self::tp_badge_01(),
			self::tp_certification_01(),
			self::tp_client_logo_01(),
			self::tp_authority_01(),
			self::tp_reassurance_01(),
			self::tp_faq_microproof_01(),
			self::tp_partner_01(),
			self::tp_rating_01(),
			self::tp_quote_01(),
			self::tp_trust_band_01(),
		);
	}

	/**
	 * Returns section keys in this batch (for listing and tests).
	 *
	 * @return list<string>
	 */
	public static function section_keys(): array {
		return array(
			'tp_testimonial_01',
			'tp_testimonial_02',
			'tp_review_01',
			'tp_credential_01',
			'tp_credential_02',
			'tp_guarantee_01',
			'tp_case_teaser_01',
			'tp_outcome_01',
			'tp_badge_01',
			'tp_certification_01',
			'tp_client_logo_01',
			'tp_authority_01',
			'tp_reassurance_01',
			'tp_faq_microproof_01',
			'tp_partner_01',
			'tp_rating_01',
			'tp_quote_01',
			'tp_trust_band_01',
		);
	}

	/**
	 * Builds a trust/proof section definition with common structure.
	 *
	 * @param string $key Internal key.
	 * @param string $name Display name.
	 * @param string $purpose_summary Purpose summary.
	 * @param string $variation_family_key Variation family (e.g. proof_testimonial, proof_credential).
	 * @param string $preview_desc Preview description.
	 * @param array<string, mixed> $blueprint_fields Field definitions for embedded blueprint.
	 * @param array<string, mixed> $preview_defaults Synthetic ACF defaults for preview.
	 * @param array<string, mixed> $extra Optional extra keys (short_label, cta_classification, suggested_use_cases, variants, etc.).
	 * @return array<string, mixed>
	 */
	private static function proof_definition(
		string $key,
		string $name,
		string $purpose_summary,
		string $variation_family_key,
		string $preview_desc,
		array $blueprint_fields,
		array $preview_defaults,
		array $extra = array()
	): array {
		$bp_id = 'acf_blueprint_' . $key;
		$base = array(
			Section_Schema::FIELD_INTERNAL_KEY            => $key,
			Section_Schema::FIELD_NAME                    => $name,
			Section_Schema::FIELD_PURPOSE_SUMMARY         => $purpose_summary,
			Section_Schema::FIELD_CATEGORY                => 'trust_proof',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_' . $key,
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF     => $bp_id,
			Section_Schema::FIELD_HELPER_REF              => 'helper_' . $key,
			Section_Schema::FIELD_CSS_CONTRACT_REF        => 'css_' . $key,
			Section_Schema::FIELD_DEFAULT_VARIANT         => 'default',
			Section_Schema::FIELD_VARIANTS                => array(
				'default' => array( 'label' => 'Default', 'description' => '', 'css_modifiers' => array() ),
			),
			Section_Schema::FIELD_COMPATIBILITY            => array(
				'may_precede'          => array(),
				'may_follow'           => array(),
				'avoid_adjacent'       => array(),
				'duplicate_purpose_of' => array(),
			),
			Section_Schema::FIELD_VERSION                 => array( 'version' => '1', 'stable_key_retained' => true ),
			Section_Schema::FIELD_STATUS                  => 'active',
			Section_Schema::FIELD_RENDER_MODE             => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION       => array( 'none' => true ),
			'section_purpose_family'                     => self::PURPOSE_FAMILY,
			'variation_family_key'                       => $variation_family_key,
			'preview_description'                        => $preview_desc,
			'preview_image_ref'                          => '',
			'animation_tier'                             => 'subtle',
			'animation_families'                         => array( 'entrance', 'hover' ),
			'preview_defaults'                           => $preview_defaults,
			'accessibility_warnings_or_enhancements'     => 'Use semantic list or grid for repeated items. Do not rely on color alone for meaning (spec §51.8). Ensure sufficient contrast for text and optional badges. Quote attribution must be programmatically associated.',
			'seo_relevance_notes'                       => 'Testimonials and credentials support entity and review signals; keep content factual and avoid misleading guarantees.',
		);
		$base['field_blueprint'] = array(
			'blueprint_id'    => $bp_id,
			'section_key'     => $key,
			'section_version' => '1',
			'label'           => $name . ' fields',
			'description'     => 'Trust/proof content fields.',
			'fields'          => $blueprint_fields,
		);
		return array_merge( $base, $extra );
	}

	/** Testimonial cards: headline + repeater (quote, name, role, optional image). */
	public static function tp_testimonial_01(): array {
		$key = 'tp_testimonial_01';
		$fields = array(
			array( 'key' => 'field_tp_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false, 'instructions' => 'Optional section headline.' ),
			array(
				'key'           => 'field_tp_testimonials',
				'name'          => 'testimonials',
				'label'         => 'Testimonials',
				'type'          => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'      => true,
				'instructions'  => 'Add testimonial cards (quote, name, role).',
				'sub_fields'    => array(
					array( 'key' => 'field_tp_quote', 'name' => 'quote', 'label' => 'Quote', 'type' => 'textarea', 'required' => true ),
					array( 'key' => 'field_tp_name', 'name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_tp_role', 'name' => 'role', 'label' => 'Role / title', 'type' => 'text', 'required' => false ),
					array( 'key' => 'field_tp_avatar', 'name' => 'avatar', 'label' => 'Avatar image', 'type' => 'image', 'required' => false ),
				),
			),
		);
		return self::proof_definition(
			$key,
			'Testimonial cards',
			'Repeatable testimonial cards with quote, name, role, and optional avatar. Use for customer or user proof on service, product, or landing pages.',
			'proof_testimonial',
			'Grid of testimonial cards with quote and attribution.',
			$fields,
			array(
				'headline'      => 'What our customers say',
				'testimonials'   => array(
					array( 'quote' => 'Synthetic sample quote for preview.', 'name' => 'Preview User', 'role' => 'Role', 'avatar' => array() ),
				),
			),
			array( 'short_label' => 'Testimonials', 'suggested_use_cases' => array( 'Service page', 'Product page', 'Landing proof' ) )
		);
	}

	/** Single quoted proof / pull quote. */
	public static function tp_testimonial_02(): array {
		$key = 'tp_testimonial_02';
		$fields = array(
			array( 'key' => 'field_tp2_quote', 'name' => 'quote', 'label' => 'Quote', 'type' => 'textarea', 'required' => true ),
			array( 'key' => 'field_tp2_attribution', 'name' => 'attribution', 'label' => 'Attribution', 'type' => 'text', 'required' => false, 'instructions' => 'e.g. Name, Title' ),
		);
		return self::proof_definition(
			$key,
			'Single quote / quoted proof',
			'Single pull quote with optional attribution. Use for one strong testimonial or authority quote.',
			'proof_quote',
			'Single quoted proof with attribution.',
			$fields,
			array( 'quote' => 'Synthetic quote for preview.', 'attribution' => 'Preview Name, Title' ),
			array( 'short_label' => 'Single quote', 'suggested_use_cases' => array( 'Editorial', 'Authority proof', 'Highlight' ) )
		);
	}

	/** Ratings/review summary. */
	public static function tp_review_01(): array {
		$key = 'tp_review_01';
		$fields = array(
			array( 'key' => 'field_tp_rev_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_tp_rev_rating', 'name' => 'rating', 'label' => 'Rating (e.g. 4.5)', 'type' => 'number', 'required' => false ),
			array( 'key' => 'field_tp_rev_count', 'name' => 'review_count', 'label' => 'Review count', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_tp_rev_summary', 'name' => 'summary', 'label' => 'Short summary', 'type' => 'textarea', 'required' => false ),
		);
		return self::proof_definition(
			$key,
			'Review summary',
			'Ratings and review count summary with optional headline and short summary. Use for product or service review proof.',
			'proof_review',
			'Rating and review count with optional summary.',
			$fields,
			array( 'headline' => 'Customer reviews', 'rating' => 4.5, 'review_count' => '100+ reviews', 'summary' => 'Synthetic summary for preview.' ),
			array( 'short_label' => 'Review summary', 'suggested_use_cases' => array( 'Product page', 'Service page', 'Directory entry' ) )
		);
	}

	/** Credential grid: headline + repeatable (title, description, optional icon). */
	public static function tp_credential_01(): array {
		$key = 'tp_credential_01';
		$fields = array(
			array( 'key' => 'field_tp_crd_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'          => 'field_tp_crd_items',
				'name'         => 'credentials',
				'label'        => 'Credentials',
				'type'         => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'     => true,
				'sub_fields'   => array(
					array( 'key' => 'field_tp_crd_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_tp_crd_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
					array( 'key' => 'field_tp_crd_icon', 'name' => 'icon_ref', 'label' => 'Icon reference', 'type' => 'text', 'required' => false ),
				),
			),
		);
		return self::proof_definition(
			$key,
			'Credential grid',
			'Grid of credentials: title, description, optional icon. Use for certifications, accreditations, or capability proof.',
			'proof_credential',
			'Grid of credential items with title and description.',
			$fields,
			array( 'headline' => 'Our credentials', 'credentials' => array( array( 'title' => 'Certified', 'description' => 'Synthetic description.', 'icon_ref' => '' ) ) ),
			array( 'short_label' => 'Credentials', 'suggested_use_cases' => array( 'About', 'Service page', 'Trust block' ) )
		);
	}

	/** Credential strip: single-line credentials. */
	public static function tp_credential_02(): array {
		$key = 'tp_credential_02';
		$fields = array(
			array( 'key' => 'field_tp_strip_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'          => 'field_tp_strip_items',
				'name'         => 'items',
				'label'        => 'Credential items',
				'type'         => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'     => true,
				'sub_fields'   => array(
					array( 'key' => 'field_tp_strip_label', 'name' => 'label', 'label' => 'Label', 'type' => 'text', 'required' => true ),
				),
			),
		);
		return self::proof_definition(
			$key,
			'Credential strip',
			'Single-line strip of credential labels. Use for compact trust signals (badges, certifications in one row).',
			'proof_credential',
			'Horizontal strip of credential labels.',
			$fields,
			array( 'headline' => 'Trusted by', 'items' => array( array( 'label' => 'Cert A' ), array( 'label' => 'Cert B' ) ) ),
			array( 'short_label' => 'Credential strip', 'suggested_use_cases' => array( 'Footer strip', 'Compact proof', 'Header band' ) )
		);
	}

	/** Guarantee band. */
	public static function tp_guarantee_01(): array {
		$key = 'tp_guarantee_01';
		$fields = array(
			array( 'key' => 'field_tp_guar_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_tp_guar_text', 'name' => 'guarantee_text', 'label' => 'Guarantee text', 'type' => 'textarea', 'required' => true ),
			array( 'key' => 'field_tp_guar_badge', 'name' => 'badge_text', 'label' => 'Optional badge text', 'type' => 'text', 'required' => false ),
		);
		return self::proof_definition(
			$key,
			'Guarantee band',
			'Guarantee or promise block with headline and supporting text. Use for satisfaction or service guarantees. Dummy content only; no real legal guarantees in preview.',
			'proof_guarantee',
			'Guarantee band with headline and text.',
			$fields,
			array( 'headline' => 'Our guarantee', 'guarantee_text' => 'Synthetic guarantee text for preview only.', 'badge_text' => '' ),
			array( 'short_label' => 'Guarantee', 'suggested_use_cases' => array( 'Product page', 'Service page', 'Reassurance' ) )
		);
	}

	/** Case study teaser. */
	public static function tp_case_teaser_01(): array {
		$key = 'tp_case_teaser_01';
		$fields = array(
			array( 'key' => 'field_tp_case_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_tp_case_outcome', 'name' => 'outcome', 'label' => 'Outcome summary', 'type' => 'textarea', 'required' => false ),
			array( 'key' => 'field_tp_case_client', 'name' => 'client_name', 'label' => 'Client name', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_tp_case_link', 'name' => 'link', 'label' => 'Link to full case study', 'type' => 'link', 'required' => false ),
		);
		return self::proof_definition(
			$key,
			'Case study teaser',
			'Case study teaser with headline, outcome summary, client name, and optional link. Use for proof on service or offering pages.',
			'proof_case',
			'Case study teaser with outcome and link.',
			$fields,
			array( 'headline' => 'Case study preview', 'outcome' => 'Synthetic outcome.', 'client_name' => 'Preview Client', 'link' => array() ),
			array( 'short_label' => 'Case teaser', 'cta_classification' => 'navigation_cta', 'suggested_use_cases' => array( 'Service page', 'Offering page', 'Proof block' ) )
		);
	}

	/** Outcome stats (headline + stat items). */
	public static function tp_outcome_01(): array {
		$key = 'tp_outcome_01';
		$fields = array(
			array( 'key' => 'field_tp_out_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'          => 'field_tp_out_items',
				'name'         => 'stat_items',
				'label'        => 'Stat items',
				'type'         => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'     => true,
				'sub_fields'   => array(
					array( 'key' => 'field_tp_out_label', 'name' => 'label', 'label' => 'Label', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_tp_out_value', 'name' => 'value', 'label' => 'Value', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_tp_out_suffix', 'name' => 'suffix', 'label' => 'Suffix', 'type' => 'text', 'required' => false ),
				),
			),
		);
		return self::proof_definition(
			$key,
			'Outcome stats',
			'Outcome-focused stat block: headline and repeatable label/value/suffix. Use for results or proof metrics.',
			'proof_stats',
			'Outcome stats with headline and repeatable items.',
			$fields,
			array( 'headline' => 'Results', 'stat_items' => array( array( 'label' => 'Outcome', 'value' => '99', 'suffix' => '%' ) ) ),
			array( 'short_label' => 'Outcome stats', 'suggested_use_cases' => array( 'Landing page', 'Service page', 'Proof metrics' ) )
		);
	}

	/** Badge / certification (repeatable: name, image, description). */
	public static function tp_badge_01(): array {
		$key = 'tp_badge_01';
		$fields = array(
			array( 'key' => 'field_tp_bad_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'          => 'field_tp_bad_items',
				'name'         => 'badges',
				'label'        => 'Badges',
				'type'         => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'     => true,
				'sub_fields'   => array(
					array( 'key' => 'field_tp_bad_name', 'name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_tp_bad_image', 'name' => 'image', 'label' => 'Image', 'type' => 'image', 'required' => false ),
					array( 'key' => 'field_tp_bad_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'text', 'required' => false ),
				),
			),
		);
		return self::proof_definition(
			$key,
			'Badge / certification',
			'Badge or certification block: repeatable name, optional image, optional description. Use for awards or certifications.',
			'proof_badge',
			'Badges with optional images and descriptions.',
			$fields,
			array( 'headline' => 'Certifications', 'badges' => array( array( 'name' => 'Badge A', 'image' => array(), 'description' => '' ) ) ),
			array( 'short_label' => 'Badges', 'suggested_use_cases' => array( 'About', 'Service page', 'Trust band' ) )
		);
	}

	/** Certification strip/list. */
	public static function tp_certification_01(): array {
		$key = 'tp_certification_01';
		$fields = array(
			array( 'key' => 'field_tp_cert_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'          => 'field_tp_cert_items',
				'name'         => 'certifications',
				'label'        => 'Certifications',
				'type'         => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'     => true,
				'sub_fields'   => array(
					array( 'key' => 'field_tp_cert_name', 'name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_tp_cert_url', 'name' => 'url', 'label' => 'Optional URL', 'type' => 'url', 'required' => false ),
				),
			),
		);
		return self::proof_definition(
			$key,
			'Certification list',
			'List of certification names with optional URLs. Use for compliance or accreditation lists.',
			'proof_certification',
			'List of certification names.',
			$fields,
			array( 'headline' => 'Certifications', 'certifications' => array( array( 'name' => 'Cert A', 'url' => '' ) ) ),
			array( 'short_label' => 'Cert list', 'suggested_use_cases' => array( 'Legal/trust', 'Compliance', 'About' ) )
		);
	}

	/** Client/partner logo band. */
	public static function tp_client_logo_01(): array {
		$key = 'tp_client_logo_01';
		$fields = array(
			array( 'key' => 'field_tp_logo_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'          => 'field_tp_logo_items',
				'name'         => 'logos',
				'label'        => 'Logos',
				'type'         => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'     => true,
				'sub_fields'   => array(
					array( 'key' => 'field_tp_logo_image', 'name' => 'logo', 'label' => 'Logo image', 'type' => 'image', 'required' => true ),
					array( 'key' => 'field_tp_logo_name', 'name' => 'name', 'label' => 'Company name (for alt text)', 'type' => 'text', 'required' => false ),
				),
			),
		);
		return self::proof_definition(
			$key,
			'Client / partner logos',
			'Logo band: headline and repeatable logo image with optional name for accessibility. Use for client or partner proof. Synthetic logos only in preview.',
			'proof_logo',
			'Band of client or partner logos.',
			$fields,
			array( 'headline' => 'Trusted by', 'logos' => array( array( 'logo' => array(), 'name' => 'Preview partner' ) ) ),
			array( 'short_label' => 'Logo band', 'suggested_use_cases' => array( 'Homepage', 'Service page', 'Partners' ) )
		);
	}

	/** Authority highlights. */
	public static function tp_authority_01(): array {
		$key = 'tp_authority_01';
		$fields = array(
			array( 'key' => 'field_tp_auth_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'          => 'field_tp_auth_items',
				'name'         => 'highlights',
				'label'        => 'Highlights',
				'type'         => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'     => true,
				'sub_fields'   => array(
					array( 'key' => 'field_tp_auth_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_tp_auth_quote', 'name' => 'quote_or_fact', 'label' => 'Quote or fact', 'type' => 'textarea', 'required' => false ),
				),
			),
		);
		return self::proof_definition(
			$key,
			'Authority highlights',
			'Authority or expertise highlights: title and optional quote/fact per item. Use for thought leadership or authority proof.',
			'proof_authority',
			'Authority highlights with title and quote/fact.',
			$fields,
			array( 'headline' => 'Why we are trusted', 'highlights' => array( array( 'title' => 'Expertise', 'quote_or_fact' => 'Synthetic fact.' ) ) ),
			array( 'short_label' => 'Authority', 'suggested_use_cases' => array( 'About', 'Service page', 'Credibility' ) )
		);
	}

	/** Reassurance block (headline + bullet points). */
	public static function tp_reassurance_01(): array {
		$key = 'tp_reassurance_01';
		$fields = array(
			array( 'key' => 'field_tp_reas_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'          => 'field_tp_reas_items',
				'name'         => 'points',
				'label'        => 'Reassurance points',
				'type'         => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'     => true,
				'sub_fields'   => array(
					array( 'key' => 'field_tp_reas_text', 'name' => 'text', 'label' => 'Text', 'type' => 'text', 'required' => true ),
				),
			),
		);
		return self::proof_definition(
			$key,
			'Reassurance block',
			'Reassurance block with headline and repeatable short points. Use for trust or risk-reduction messaging.',
			'proof_reassurance',
			'Reassurance points list.',
			$fields,
			array( 'headline' => 'Why choose us', 'points' => array( array( 'text' => 'Synthetic point one' ), array( 'text' => 'Synthetic point two' ) ) ),
			array( 'short_label' => 'Reassurance', 'suggested_use_cases' => array( 'Product page', 'Service page', 'Checkout support' ) )
		);
	}

	/** FAQ + microproof hybrid. */
	public static function tp_faq_microproof_01(): array {
		$key = 'tp_faq_microproof_01';
		$fields = array(
			array( 'key' => 'field_tp_faq_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'          => 'field_tp_faq_items',
				'name'         => 'items',
				'label'        => 'FAQ items',
				'type'         => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'     => true,
				'sub_fields'   => array(
					array( 'key' => 'field_tp_faq_q', 'name' => 'question', 'label' => 'Question', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_tp_faq_a', 'name' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'required' => true ),
					array( 'key' => 'field_tp_faq_stat', 'name' => 'proof_stat', 'label' => 'Optional proof stat', 'type' => 'text', 'required' => false ),
				),
			),
		);
		return self::proof_definition(
			$key,
			'FAQ microproof hybrid',
			'FAQ items with optional proof stat per item. Use for trust-supporting FAQ with microproof.',
			'proof_faq',
			'FAQ with optional proof stats per item.',
			$fields,
			array( 'headline' => 'Common questions', 'items' => array( array( 'question' => 'Preview question?', 'answer' => 'Synthetic answer.', 'proof_stat' => '' ) ) ),
			array( 'short_label' => 'FAQ proof', 'suggested_use_cases' => array( 'Service page', 'Product page', 'Trust FAQ' ) )
		);
	}

	/** Partner proof (name, logo, link). */
	public static function tp_partner_01(): array {
		$key = 'tp_partner_01';
		$fields = array(
			array( 'key' => 'field_tp_part_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'          => 'field_tp_part_items',
				'name'         => 'partners',
				'label'        => 'Partners',
				'type'         => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'     => true,
				'sub_fields'   => array(
					array( 'key' => 'field_tp_part_name', 'name' => 'name', 'label' => 'Partner name', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_tp_part_logo', 'name' => 'logo', 'label' => 'Logo', 'type' => 'image', 'required' => false ),
					array( 'key' => 'field_tp_part_link', 'name' => 'link', 'label' => 'Link', 'type' => 'link', 'required' => false ),
				),
			),
		);
		return self::proof_definition(
			$key,
			'Partner proof',
			'Partner list with name, optional logo, optional link. Use for partner or channel proof.',
			'proof_partner',
			'Partners with name, logo, and optional link.',
			$fields,
			array( 'headline' => 'Our partners', 'partners' => array( array( 'name' => 'Partner A', 'logo' => array(), 'link' => array() ) ) ),
			array( 'short_label' => 'Partners', 'suggested_use_cases' => array( 'About', 'Channel page', 'Ecosystem' ) )
		);
	}

	/** Rating display (rating + label). */
	public static function tp_rating_01(): array {
		$key = 'tp_rating_01';
		$fields = array(
			array( 'key' => 'field_tp_rat_value', 'name' => 'rating_value', 'label' => 'Rating value', 'type' => 'number', 'required' => false ),
			array( 'key' => 'field_tp_rat_label', 'name' => 'rating_label', 'label' => 'Rating label', 'type' => 'text', 'required' => false ),
		);
		return self::proof_definition(
			$key,
			'Rating display',
			'Single rating value with optional label. Use for star rating or score display.',
			'proof_rating',
			'Rating value and label.',
			$fields,
			array( 'rating_value' => 4.5, 'rating_label' => 'out of 5' ),
			array( 'short_label' => 'Rating', 'suggested_use_cases' => array( 'Product', 'Service', 'Directory' ) )
		);
	}

	/** Single quote (alias / variant of tp_testimonial_02 with different emphasis). */
	public static function tp_quote_01(): array {
		$key = 'tp_quote_01';
		$fields = array(
			array( 'key' => 'field_tp_quo_quote', 'name' => 'quote', 'label' => 'Quote', 'type' => 'textarea', 'required' => true ),
			array( 'key' => 'field_tp_quo_source', 'name' => 'source', 'label' => 'Source', 'type' => 'text', 'required' => false ),
		);
		return self::proof_definition(
			$key,
			'Pull quote',
			'Single pull quote with source. Use for editorial or authority highlight.',
			'proof_quote',
			'Single quote with source.',
			$fields,
			array( 'quote' => 'Synthetic quote for preview.', 'source' => 'Preview source' ),
			array( 'short_label' => 'Pull quote', 'suggested_use_cases' => array( 'Article', 'Resource', 'Authority' ) )
		);
	}

	/** Trust band (headline + trust points). */
	public static function tp_trust_band_01(): array {
		$key = 'tp_trust_band_01';
		$fields = array(
			array( 'key' => 'field_tp_band_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'          => 'field_tp_band_points',
				'name'         => 'trust_points',
				'label'        => 'Trust points',
				'type'         => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'     => true,
				'sub_fields'   => array(
					array( 'key' => 'field_tp_band_text', 'name' => 'text', 'label' => 'Text', 'type' => 'text', 'required' => true ),
				),
			),
		);
		return self::proof_definition(
			$key,
			'Trust band',
			'Trust strip with headline and short trust points. Use for compact trust signals (secure, guaranteed, etc.).',
			'proof_trust_band',
			'Trust band with headline and points.',
			$fields,
			array( 'headline' => 'Why trust us', 'trust_points' => array( array( 'text' => 'Secure' ), array( 'text' => 'Verified' ) ) ),
			array( 'short_label' => 'Trust band', 'suggested_use_cases' => array( 'Footer', 'Checkout', 'Landing' ) )
		);
	}
}
