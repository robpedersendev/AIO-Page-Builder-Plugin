<?php
/**
 * Built-in shared cross-industry fragments (Prompt 476, industry-shared-fragment-schema.md).
 * Conservative seed set: CTA posture, SEO hierarchy, caution snippets, trust/contact guidance.
 * High-value reuse only; industry-specific overlays remain primary.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Shared_Fragment_Registry;

return array(
	// CTA notes
	array(
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'cta_primary_contact_above_fold',
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_CTA_NOTES,
		Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'section_helper_overlay', 'page_onepager_overlay', 'cta_guidance' ),
		Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Prefer a single primary contact or conversion CTA above the fold; avoid multiple competing CTAs.',
		Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
		Industry_Shared_Fragment_Registry::FIELD_VERSION_MARKER   => '1',
	),
	array(
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'cta_lead_capture_consent',
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_CTA_NOTES,
		Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'section_helper_overlay', 'page_onepager_overlay', 'cta_guidance' ),
		Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Lead capture and contact forms should set clear expectations (e.g. response time, use of contact info) and obtain consent where required.',
		Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
		Industry_Shared_Fragment_Registry::FIELD_VERSION_MARKER   => '1',
	),
	array(
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'cta_booking_estimate_clarity',
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_CTA_NOTES,
		Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'section_helper_overlay', 'page_onepager_overlay', 'cta_guidance' ),
		Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Booking, estimate, or quote CTAs work best when the next step (e.g. form, call, calendar) is obvious and low-friction.',
		Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
		Industry_Shared_Fragment_Registry::FIELD_VERSION_MARKER   => '1',
	),
	// SEO segments
	array(
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'seo_h1_unique_per_page',
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_SEO_SEGMENT,
		Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'section_helper_overlay', 'page_onepager_overlay', 'seo_guidance' ),
		Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Use one primary H1 per page that clearly reflects the page topic; keep hierarchy logical (H1 then H2, etc.).',
		Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
		Industry_Shared_Fragment_Registry::FIELD_VERSION_MARKER   => '1',
	),
	array(
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'seo_meta_description_length',
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_SEO_SEGMENT,
		Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'section_helper_overlay', 'page_onepager_overlay', 'seo_guidance' ),
		Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Meta descriptions should be concise (typically 150–160 characters) and include the main topic or value proposition.',
		Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
		Industry_Shared_Fragment_Registry::FIELD_VERSION_MARKER   => '1',
	),
	// Caution snippets
	array(
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'caution_testimonial_genuine',
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_CAUTION_SNIPPET,
		Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'section_helper_overlay', 'page_onepager_overlay', 'compliance_caution' ),
		Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Testimonials and reviews must be genuine; avoid misleading or fabricated endorsements. Obtain consent where required.',
		Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
		Industry_Shared_Fragment_Registry::FIELD_VERSION_MARKER   => '1',
	),
	array(
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'caution_pricing_disclosure',
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_CAUTION_SNIPPET,
		Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'section_helper_overlay', 'page_onepager_overlay', 'compliance_caution' ),
		Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Pricing or fee references should be clear and accurate; disclose conditions (e.g. estimates, exclusions) where applicable.',
		Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
		Industry_Shared_Fragment_Registry::FIELD_VERSION_MARKER   => '1',
	),
	array(
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'caution_local_accuracy',
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_CAUTION_SNIPPET,
		Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'section_helper_overlay', 'page_onepager_overlay', 'compliance_caution' ),
		Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Local and service-area claims should be accurate and not misleading; verify NAP and coverage statements.',
		Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
		Industry_Shared_Fragment_Registry::FIELD_VERSION_MARKER   => '1',
	),
	// Helper/page guidance
	array(
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'guidance_trust_proof',
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_HELPER_GUIDANCE,
		Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'section_helper_overlay' ),
		Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Use credentials, results, or social proof where they support the message without overclaiming; keep proof relevant to the section.',
		Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
		Industry_Shared_Fragment_Registry::FIELD_VERSION_MARKER   => '1',
	),
	array(
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'guidance_contact_lead_handling',
		Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_PAGE_GUIDANCE,
		Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'page_onepager_overlay' ),
		Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Contact and lead-capture pages should state what happens next (e.g. callback, email confirmation) and respect privacy expectations.',
		Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
		Industry_Shared_Fragment_Registry::FIELD_VERSION_MARKER   => '1',
	),
);
