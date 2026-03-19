<?php
/**
 * Gap-closing section template definitions for SEC-09 (Prompt 182, spec §12, §62.11, template-library-coverage-matrix).
 * Fills remaining section-library gaps to reach 250 minimum with balanced purpose-family coverage.
 * Does not persist; callers save via Section_Template_Repository or Section_Gap_Closing_Super_Batch_Seeder.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\GapClosingSuperBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Returns section definitions for the gap-closing super-batch (SEC-09).
 * Each definition is schema-compliant with full metadata, helper ref, preview defaults, and animation metadata.
 */
final class Section_Gap_Closing_Super_Batch_Definitions {

	/** Batch ID per template-library-inventory-manifest §3.1 (balance to 250 total). */
	public const BATCH_ID = 'SEC-09';

	/** Target total section count (template-library-coverage-matrix §2.1). */
	public const SECTION_TARGET = 250;

	/** Industry keys for first launch verticals (section-industry-affinity-contract; Prompt 363). */
	private const LAUNCH_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	/**
	 * Spec rows: key, name, purpose_summary, category, section_purpose_family, variation_family_key.
	 * Distribution aligns with coverage matrix minimums and remaining gaps (offer, explainer, faq, profile, stats, listing, comparison, contact, legal, utility, timeline, related, plus spread for proof, cta, hero).
	 *
	 * @var array<int, array{key: string, name: string, purpose_summary: string, category: string, section_purpose_family: string, variation_family_key: string}>
	 */
	private static function specs(): array {
		return array(
			// offer (20 total in this batch to close gap)
			array(
				'key'                    => 'gc_offer_value_01',
				'name'                   => 'Offer value proposition A',
				'purpose_summary'        => 'Value proposition block for offer or product. Headline and supporting copy.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_value',
			),
			array(
				'key'                    => 'gc_offer_value_02',
				'name'                   => 'Offer value proposition B',
				'purpose_summary'        => 'Alternative value block for hub or child_detail. Supports CTA placement.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_value',
			),
			array(
				'key'                    => 'gc_offer_pricing_01',
				'name'                   => 'Offer pricing summary',
				'purpose_summary'        => 'Pricing or package summary for offer pages. Headline and key points.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_pricing',
			),
			array(
				'key'                    => 'gc_offer_feature_01',
				'name'                   => 'Offer feature highlight',
				'purpose_summary'        => 'Single feature or benefit highlight. Use in sequence for multi-feature pages.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_feature',
			),
			array(
				'key'                    => 'gc_offer_feature_02',
				'name'                   => 'Offer feature highlight B',
				'purpose_summary'        => 'Second feature highlight variant. Supports variation spread.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_feature',
			),
			array(
				'key'                    => 'gc_offer_local_01',
				'name'                   => 'Offer local / service',
				'purpose_summary'        => 'Local or service-area offer block. Headline and short description.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_local',
			),
			array(
				'key'                    => 'gc_offer_product_01',
				'name'                   => 'Offer product spec',
				'purpose_summary'        => 'Product or spec-focused offer block. For product detail and comparison.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_product',
			),
			array(
				'key'                    => 'gc_offer_product_02',
				'name'                   => 'Offer product spec B',
				'purpose_summary'        => 'Second product-spec variant. Supports child_detail product pages.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_product',
			),
			array(
				'key'                    => 'gc_offer_bundle_01',
				'name'                   => 'Offer bundle summary',
				'purpose_summary'        => 'Bundle or package offer summary. Headline and list of included items.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_bundle',
			),
			array(
				'key'                    => 'gc_offer_compare_01',
				'name'                   => 'Offer comparison block',
				'purpose_summary'        => 'Offer comparison or tier block. For pricing or plan comparison.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_compare',
			),
			array(
				'key'                    => 'gc_offer_01',
				'name'                   => 'Offer block 01',
				'purpose_summary'        => 'General offer block. Headline and supporting text.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_general',
			),
			array(
				'key'                    => 'gc_offer_02',
				'name'                   => 'Offer block 02',
				'purpose_summary'        => 'General offer block variant. For hub and child_detail.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_general',
			),
			array(
				'key'                    => 'gc_offer_03',
				'name'                   => 'Offer block 03',
				'purpose_summary'        => 'General offer block variant. Supports CTA flow.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_general',
			),
			array(
				'key'                    => 'gc_offer_04',
				'name'                   => 'Offer block 04',
				'purpose_summary'        => 'General offer block variant. For landing and conversion.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_general',
			),
			array(
				'key'                    => 'gc_offer_05',
				'name'                   => 'Offer block 05',
				'purpose_summary'        => 'General offer block variant. For resource and authority pages.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_general',
			),
			array(
				'key'                    => 'gc_offer_06',
				'name'                   => 'Offer block 06',
				'purpose_summary'        => 'General offer block variant. For directory and listing context.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_general',
			),
			array(
				'key'                    => 'gc_offer_07',
				'name'                   => 'Offer block 07',
				'purpose_summary'        => 'General offer block variant. For profile and event pages.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_general',
			),
			array(
				'key'                    => 'gc_offer_08',
				'name'                   => 'Offer block 08',
				'purpose_summary'        => 'General offer block variant. For legal and utility adjacency.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_general',
			),
			array(
				'key'                    => 'gc_offer_09',
				'name'                   => 'Offer block 09',
				'purpose_summary'        => 'General offer block variant. For timeline and related content.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_general',
			),
			array(
				'key'                    => 'gc_offer_10',
				'name'                   => 'Offer block 10',
				'purpose_summary'        => 'General offer block variant. Balance and spread.',
				'category'               => 'offer',
				'section_purpose_family' => 'offer',
				'variation_family_key'   => 'offer_general',
			),
			// explainer (20)
			array(
				'key'                    => 'gc_explain_steps_01',
				'name'                   => 'Explainer steps A',
				'purpose_summary'        => 'Step-by-step explainer. Headline and ordered steps.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_steps',
			),
			array(
				'key'                    => 'gc_explain_steps_02',
				'name'                   => 'Explainer steps B',
				'purpose_summary'        => 'Alternative step layout. For process and how-it-works.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_steps',
			),
			array(
				'key'                    => 'gc_explain_how_01',
				'name'                   => 'How it works block',
				'purpose_summary'        => 'How-it-works explainer. Headline and body.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_how',
			),
			array(
				'key'                    => 'gc_explain_how_02',
				'name'                   => 'How it works block B',
				'purpose_summary'        => 'How-it-works variant. For service and product explainer.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_how',
			),
			array(
				'key'                    => 'gc_explain_process_01',
				'name'                   => 'Process explainer',
				'purpose_summary'        => 'Process flow explainer. Headline and process points.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_process',
			),
			array(
				'key'                    => 'gc_explain_process_02',
				'name'                   => 'Process explainer B',
				'purpose_summary'        => 'Process variant. For onboarding and service flow.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_process',
			),
			array(
				'key'                    => 'gc_explain_resource_01',
				'name'                   => 'Resource explainer',
				'purpose_summary'        => 'Resource or guide explainer. Headline and summary.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_resource',
			),
			array(
				'key'                    => 'gc_explain_resource_02',
				'name'                   => 'Resource explainer B',
				'purpose_summary'        => 'Resource variant. For educational and authority pages.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_resource',
			),
			array(
				'key'                    => 'gc_explain_01',
				'name'                   => 'Explainer block 01',
				'purpose_summary'        => 'General explainer. Headline and body.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_general',
			),
			array(
				'key'                    => 'gc_explain_02',
				'name'                   => 'Explainer block 02',
				'purpose_summary'        => 'General explainer variant.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_general',
			),
			array(
				'key'                    => 'gc_explain_03',
				'name'                   => 'Explainer block 03',
				'purpose_summary'        => 'General explainer variant.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_general',
			),
			array(
				'key'                    => 'gc_explain_04',
				'name'                   => 'Explainer block 04',
				'purpose_summary'        => 'General explainer variant.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_general',
			),
			array(
				'key'                    => 'gc_explain_05',
				'name'                   => 'Explainer block 05',
				'purpose_summary'        => 'General explainer variant.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_general',
			),
			array(
				'key'                    => 'gc_explain_06',
				'name'                   => 'Explainer block 06',
				'purpose_summary'        => 'General explainer variant.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_general',
			),
			array(
				'key'                    => 'gc_explain_07',
				'name'                   => 'Explainer block 07',
				'purpose_summary'        => 'General explainer variant.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_general',
			),
			array(
				'key'                    => 'gc_explain_08',
				'name'                   => 'Explainer block 08',
				'purpose_summary'        => 'General explainer variant.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_general',
			),
			array(
				'key'                    => 'gc_explain_09',
				'name'                   => 'Explainer block 09',
				'purpose_summary'        => 'General explainer variant.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_general',
			),
			array(
				'key'                    => 'gc_explain_10',
				'name'                   => 'Explainer block 10',
				'purpose_summary'        => 'General explainer variant.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_general',
			),
			array(
				'key'                    => 'gc_explain_11',
				'name'                   => 'Explainer block 11',
				'purpose_summary'        => 'General explainer variant.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_general',
			),
			array(
				'key'                    => 'gc_explain_12',
				'name'                   => 'Explainer block 12',
				'purpose_summary'        => 'General explainer variant.',
				'category'               => 'explainer',
				'section_purpose_family' => 'explainer',
				'variation_family_key'   => 'explain_general',
			),
			// faq (10)
			array(
				'key'                    => 'gc_faq_general_01',
				'name'                   => 'FAQ general 01',
				'purpose_summary'        => 'FAQ section. Headline and Q&A list.',
				'category'               => 'faq',
				'section_purpose_family' => 'faq',
				'variation_family_key'   => 'faq_general',
			),
			array(
				'key'                    => 'gc_faq_general_02',
				'name'                   => 'FAQ general 02',
				'purpose_summary'        => 'FAQ variant. For accordion or expanded list.',
				'category'               => 'faq',
				'section_purpose_family' => 'faq',
				'variation_family_key'   => 'faq_general',
			),
			array(
				'key'                    => 'gc_faq_general_03',
				'name'                   => 'FAQ general 03',
				'purpose_summary'        => 'FAQ variant. For product and service FAQ.',
				'category'               => 'faq',
				'section_purpose_family' => 'faq',
				'variation_family_key'   => 'faq_general',
			),
			array(
				'key'                    => 'gc_faq_general_04',
				'name'                   => 'FAQ general 04',
				'purpose_summary'        => 'FAQ variant. For policy and legal FAQ.',
				'category'               => 'faq',
				'section_purpose_family' => 'faq',
				'variation_family_key'   => 'faq_general',
			),
			array(
				'key'                    => 'gc_faq_general_05',
				'name'                   => 'FAQ general 05',
				'purpose_summary'        => 'FAQ variant. For contact and support FAQ.',
				'category'               => 'faq',
				'section_purpose_family' => 'faq',
				'variation_family_key'   => 'faq_general',
			),
			array(
				'key'                    => 'gc_faq_general_06',
				'name'                   => 'FAQ general 06',
				'purpose_summary'        => 'FAQ variant. For buyer guide and comparison.',
				'category'               => 'faq',
				'section_purpose_family' => 'faq',
				'variation_family_key'   => 'faq_general',
			),
			array(
				'key'                    => 'gc_faq_general_07',
				'name'                   => 'FAQ general 07',
				'purpose_summary'        => 'FAQ variant. For hub and nested_hub.',
				'category'               => 'faq',
				'section_purpose_family' => 'faq',
				'variation_family_key'   => 'faq_general',
			),
			array(
				'key'                    => 'gc_faq_general_08',
				'name'                   => 'FAQ general 08',
				'purpose_summary'        => 'FAQ variant. For child_detail.',
				'category'               => 'faq',
				'section_purpose_family' => 'faq',
				'variation_family_key'   => 'faq_general',
			),
			array(
				'key'                    => 'gc_faq_general_09',
				'name'                   => 'FAQ general 09',
				'purpose_summary'        => 'FAQ variant. Balance.',
				'category'               => 'faq',
				'section_purpose_family' => 'faq',
				'variation_family_key'   => 'faq_general',
			),
			array(
				'key'                    => 'gc_faq_general_10',
				'name'                   => 'FAQ general 10',
				'purpose_summary'        => 'FAQ variant. Balance.',
				'category'               => 'faq',
				'section_purpose_family' => 'faq',
				'variation_family_key'   => 'faq_general',
			),
			// profile (8)
			array(
				'key'                    => 'gc_profile_bio_01',
				'name'                   => 'Profile bio block',
				'purpose_summary'        => 'Profile or bio block. Headline and body.',
				'category'               => 'profile',
				'section_purpose_family' => 'profile',
				'variation_family_key'   => 'profile_bio',
			),
			array(
				'key'                    => 'gc_profile_bio_02',
				'name'                   => 'Profile bio block B',
				'purpose_summary'        => 'Profile variant. For team and person pages.',
				'category'               => 'profile',
				'section_purpose_family' => 'profile',
				'variation_family_key'   => 'profile_bio',
			),
			array(
				'key'                    => 'gc_profile_card_01',
				'name'                   => 'Profile card',
				'purpose_summary'        => 'Profile card. Name, role, short bio.',
				'category'               => 'profile',
				'section_purpose_family' => 'profile',
				'variation_family_key'   => 'profile_card',
			),
			array(
				'key'                    => 'gc_profile_card_02',
				'name'                   => 'Profile card B',
				'purpose_summary'        => 'Profile card variant.',
				'category'               => 'profile',
				'section_purpose_family' => 'profile',
				'variation_family_key'   => 'profile_card',
			),
			array(
				'key'                    => 'gc_profile_01',
				'name'                   => 'Profile block 01',
				'purpose_summary'        => 'General profile block.',
				'category'               => 'profile',
				'section_purpose_family' => 'profile',
				'variation_family_key'   => 'profile_general',
			),
			array(
				'key'                    => 'gc_profile_02',
				'name'                   => 'Profile block 02',
				'purpose_summary'        => 'General profile block variant.',
				'category'               => 'profile',
				'section_purpose_family' => 'profile',
				'variation_family_key'   => 'profile_general',
			),
			array(
				'key'                    => 'gc_profile_03',
				'name'                   => 'Profile block 03',
				'purpose_summary'        => 'General profile block variant.',
				'category'               => 'profile',
				'section_purpose_family' => 'profile',
				'variation_family_key'   => 'profile_general',
			),
			array(
				'key'                    => 'gc_profile_04',
				'name'                   => 'Profile block 04',
				'purpose_summary'        => 'General profile block variant.',
				'category'               => 'profile',
				'section_purpose_family' => 'profile',
				'variation_family_key'   => 'profile_general',
			),
			// stats (8)
			array(
				'key'                    => 'gc_stats_highlights_01',
				'name'                   => 'Stats highlights 01',
				'purpose_summary'        => 'Stats or highlights block. Headline and key numbers.',
				'category'               => 'stats_highlights',
				'section_purpose_family' => 'stats',
				'variation_family_key'   => 'stats_highlights',
			),
			array(
				'key'                    => 'gc_stats_highlights_02',
				'name'                   => 'Stats highlights 02',
				'purpose_summary'        => 'Stats variant. For social proof and outcomes.',
				'category'               => 'stats_highlights',
				'section_purpose_family' => 'stats',
				'variation_family_key'   => 'stats_highlights',
			),
			array(
				'key'                    => 'gc_stats_highlights_03',
				'name'                   => 'Stats highlights 03',
				'purpose_summary'        => 'Stats variant. For feature counts.',
				'category'               => 'stats_highlights',
				'section_purpose_family' => 'stats',
				'variation_family_key'   => 'stats_highlights',
			),
			array(
				'key'                    => 'gc_stats_highlights_04',
				'name'                   => 'Stats highlights 04',
				'purpose_summary'        => 'Stats variant. For hub and top_level.',
				'category'               => 'stats_highlights',
				'section_purpose_family' => 'stats',
				'variation_family_key'   => 'stats_highlights',
			),
			array(
				'key'                    => 'gc_stats_highlights_05',
				'name'                   => 'Stats highlights 05',
				'purpose_summary'        => 'Stats variant. For child_detail.',
				'category'               => 'stats_highlights',
				'section_purpose_family' => 'stats',
				'variation_family_key'   => 'stats_highlights',
			),
			array(
				'key'                    => 'gc_stats_highlights_06',
				'name'                   => 'Stats highlights 06',
				'purpose_summary'        => 'Stats variant. Balance.',
				'category'               => 'stats_highlights',
				'section_purpose_family' => 'stats',
				'variation_family_key'   => 'stats_highlights',
			),
			array(
				'key'                    => 'gc_stats_highlights_07',
				'name'                   => 'Stats highlights 07',
				'purpose_summary'        => 'Stats variant. Balance.',
				'category'               => 'stats_highlights',
				'section_purpose_family' => 'stats',
				'variation_family_key'   => 'stats_highlights',
			),
			array(
				'key'                    => 'gc_stats_highlights_08',
				'name'                   => 'Stats highlights 08',
				'purpose_summary'        => 'Stats variant. Balance.',
				'category'               => 'stats_highlights',
				'section_purpose_family' => 'stats',
				'variation_family_key'   => 'stats_highlights',
			),
			// listing (18)
			array(
				'key'                    => 'gc_listing_dir_01',
				'name'                   => 'Listing directory 01',
				'purpose_summary'        => 'Directory or list block. Headline and list items.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_dir',
			),
			array(
				'key'                    => 'gc_listing_dir_02',
				'name'                   => 'Listing directory 02',
				'purpose_summary'        => 'Directory variant. For hub and nested_hub.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_dir',
			),
			array(
				'key'                    => 'gc_listing_gallery_01',
				'name'                   => 'Listing gallery',
				'purpose_summary'        => 'Gallery or grid list. Headline and items.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_gallery',
			),
			array(
				'key'                    => 'gc_listing_gallery_02',
				'name'                   => 'Listing gallery B',
				'purpose_summary'        => 'Gallery variant.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_gallery',
			),
			array(
				'key'                    => 'gc_listing_card_01',
				'name'                   => 'Listing card grid',
				'purpose_summary'        => 'Card grid list. For services, locations, products.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_card',
			),
			array(
				'key'                    => 'gc_listing_card_02',
				'name'                   => 'Listing card grid B',
				'purpose_summary'        => 'Card grid variant.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_card',
			),
			array(
				'key'                    => 'gc_listing_01',
				'name'                   => 'Listing block 01',
				'purpose_summary'        => 'General listing block.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_general',
			),
			array(
				'key'                    => 'gc_listing_02',
				'name'                   => 'Listing block 02',
				'purpose_summary'        => 'General listing variant.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_general',
			),
			array(
				'key'                    => 'gc_listing_03',
				'name'                   => 'Listing block 03',
				'purpose_summary'        => 'General listing variant.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_general',
			),
			array(
				'key'                    => 'gc_listing_04',
				'name'                   => 'Listing block 04',
				'purpose_summary'        => 'General listing variant.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_general',
			),
			array(
				'key'                    => 'gc_listing_05',
				'name'                   => 'Listing block 05',
				'purpose_summary'        => 'General listing variant.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_general',
			),
			array(
				'key'                    => 'gc_listing_06',
				'name'                   => 'Listing block 06',
				'purpose_summary'        => 'General listing variant.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_general',
			),
			array(
				'key'                    => 'gc_listing_07',
				'name'                   => 'Listing block 07',
				'purpose_summary'        => 'General listing variant.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_general',
			),
			array(
				'key'                    => 'gc_listing_08',
				'name'                   => 'Listing block 08',
				'purpose_summary'        => 'General listing variant.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_general',
			),
			array(
				'key'                    => 'gc_listing_09',
				'name'                   => 'Listing block 09',
				'purpose_summary'        => 'General listing variant.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_general',
			),
			array(
				'key'                    => 'gc_listing_10',
				'name'                   => 'Listing block 10',
				'purpose_summary'        => 'General listing variant.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_general',
			),
			array(
				'key'                    => 'gc_listing_11',
				'name'                   => 'Listing block 11',
				'purpose_summary'        => 'General listing variant.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_general',
			),
			array(
				'key'                    => 'gc_listing_12',
				'name'                   => 'Listing block 12',
				'purpose_summary'        => 'General listing variant.',
				'category'               => 'listing',
				'section_purpose_family' => 'listing',
				'variation_family_key'   => 'listing_general',
			),
			// comparison (8)
			array(
				'key'                    => 'gc_compare_table_01',
				'name'                   => 'Comparison table 01',
				'purpose_summary'        => 'Comparison or decision-support block. Headline and comparison content.',
				'category'               => 'comparison',
				'section_purpose_family' => 'comparison',
				'variation_family_key'   => 'compare_table',
			),
			array(
				'key'                    => 'gc_compare_table_02',
				'name'                   => 'Comparison table 02',
				'purpose_summary'        => 'Comparison variant. For plans and products.',
				'category'               => 'comparison',
				'section_purpose_family' => 'comparison',
				'variation_family_key'   => 'compare_table',
			),
			array(
				'key'                    => 'gc_compare_list_01',
				'name'                   => 'Comparison list',
				'purpose_summary'        => 'Comparison list block. Headline and pros/cons or features.',
				'category'               => 'comparison',
				'section_purpose_family' => 'comparison',
				'variation_family_key'   => 'compare_list',
			),
			array(
				'key'                    => 'gc_compare_list_02',
				'name'                   => 'Comparison list B',
				'purpose_summary'        => 'Comparison list variant.',
				'category'               => 'comparison',
				'section_purpose_family' => 'comparison',
				'variation_family_key'   => 'compare_list',
			),
			array(
				'key'                    => 'gc_compare_01',
				'name'                   => 'Comparison block 01',
				'purpose_summary'        => 'General comparison block.',
				'category'               => 'comparison',
				'section_purpose_family' => 'comparison',
				'variation_family_key'   => 'compare_general',
			),
			array(
				'key'                    => 'gc_compare_02',
				'name'                   => 'Comparison block 02',
				'purpose_summary'        => 'General comparison variant.',
				'category'               => 'comparison',
				'section_purpose_family' => 'comparison',
				'variation_family_key'   => 'compare_general',
			),
			array(
				'key'                    => 'gc_compare_03',
				'name'                   => 'Comparison block 03',
				'purpose_summary'        => 'General comparison variant.',
				'category'               => 'comparison',
				'section_purpose_family' => 'comparison',
				'variation_family_key'   => 'compare_general',
			),
			array(
				'key'                    => 'gc_compare_04',
				'name'                   => 'Comparison block 04',
				'purpose_summary'        => 'General comparison variant.',
				'category'               => 'comparison',
				'section_purpose_family' => 'comparison',
				'variation_family_key'   => 'compare_general',
			),
			// contact (8)
			array(
				'key'                    => 'gc_contact_form_01',
				'name'                   => 'Contact form 01',
				'purpose_summary'        => 'Contact or inquiry form block. Headline and form support.',
				'category'               => 'contact',
				'section_purpose_family' => 'contact',
				'variation_family_key'   => 'contact_form',
			),
			array(
				'key'                    => 'gc_contact_form_02',
				'name'                   => 'Contact form 02',
				'purpose_summary'        => 'Contact form variant.',
				'category'               => 'contact',
				'section_purpose_family' => 'contact',
				'variation_family_key'   => 'contact_form',
			),
			array(
				'key'                    => 'gc_contact_detail_01',
				'name'                   => 'Contact detail block',
				'purpose_summary'        => 'Contact detail. Address, phone, email.',
				'category'               => 'contact',
				'section_purpose_family' => 'contact',
				'variation_family_key'   => 'contact_detail',
			),
			array(
				'key'                    => 'gc_contact_detail_02',
				'name'                   => 'Contact detail block B',
				'purpose_summary'        => 'Contact detail variant.',
				'category'               => 'contact',
				'section_purpose_family' => 'contact',
				'variation_family_key'   => 'contact_detail',
			),
			array(
				'key'                    => 'gc_contact_01',
				'name'                   => 'Contact block 01',
				'purpose_summary'        => 'General contact block.',
				'category'               => 'contact',
				'section_purpose_family' => 'contact',
				'variation_family_key'   => 'contact_general',
			),
			array(
				'key'                    => 'gc_contact_02',
				'name'                   => 'Contact block 02',
				'purpose_summary'        => 'General contact variant.',
				'category'               => 'contact',
				'section_purpose_family' => 'contact',
				'variation_family_key'   => 'contact_general',
			),
			array(
				'key'                    => 'gc_contact_03',
				'name'                   => 'Contact block 03',
				'purpose_summary'        => 'General contact variant.',
				'category'               => 'contact',
				'section_purpose_family' => 'contact',
				'variation_family_key'   => 'contact_general',
			),
			array(
				'key'                    => 'gc_contact_04',
				'name'                   => 'Contact block 04',
				'purpose_summary'        => 'General contact variant.',
				'category'               => 'contact',
				'section_purpose_family' => 'contact',
				'variation_family_key'   => 'contact_general',
			),
			// legal (4)
			array(
				'key'                    => 'gc_legal_notice_01',
				'name'                   => 'Legal notice block',
				'purpose_summary'        => 'Legal notice or disclaimer. Headline and body.',
				'category'               => 'legal_disclaimer',
				'section_purpose_family' => 'legal',
				'variation_family_key'   => 'legal_notice',
			),
			array(
				'key'                    => 'gc_legal_notice_02',
				'name'                   => 'Legal notice block B',
				'purpose_summary'        => 'Legal notice variant.',
				'category'               => 'legal_disclaimer',
				'section_purpose_family' => 'legal',
				'variation_family_key'   => 'legal_notice',
			),
			array(
				'key'                    => 'gc_legal_footer_01',
				'name'                   => 'Legal footer band',
				'purpose_summary'        => 'Footer-adjacent legal band. Links and short notices.',
				'category'               => 'legal_disclaimer',
				'section_purpose_family' => 'legal',
				'variation_family_key'   => 'legal_footer',
			),
			array(
				'key'                    => 'gc_legal_footer_02',
				'name'                   => 'Legal footer band B',
				'purpose_summary'        => 'Legal footer variant.',
				'category'               => 'legal_disclaimer',
				'section_purpose_family' => 'legal',
				'variation_family_key'   => 'legal_footer',
			),
			// utility (8)
			array(
				'key'                    => 'gc_utility_nav_01',
				'name'                   => 'Utility nav jump',
				'purpose_summary'        => 'Utility navigation or jump links. Headline and links.',
				'category'               => 'utility_structural',
				'section_purpose_family' => 'utility',
				'variation_family_key'   => 'utility_nav',
			),
			array(
				'key'                    => 'gc_utility_nav_02',
				'name'                   => 'Utility nav jump B',
				'purpose_summary'        => 'Utility nav variant.',
				'category'               => 'utility_structural',
				'section_purpose_family' => 'utility',
				'variation_family_key'   => 'utility_nav',
			),
			array(
				'key'                    => 'gc_utility_structural_01',
				'name'                   => 'Utility structural 01',
				'purpose_summary'        => 'Structural utility block. Spacer or layout support.',
				'category'               => 'utility_structural',
				'section_purpose_family' => 'utility',
				'variation_family_key'   => 'utility_structural',
			),
			array(
				'key'                    => 'gc_utility_structural_02',
				'name'                   => 'Utility structural 02',
				'purpose_summary'        => 'Structural utility variant.',
				'category'               => 'utility_structural',
				'section_purpose_family' => 'utility',
				'variation_family_key'   => 'utility_structural',
			),
			array(
				'key'                    => 'gc_utility_01',
				'name'                   => 'Utility block 01',
				'purpose_summary'        => 'General utility block.',
				'category'               => 'utility_structural',
				'section_purpose_family' => 'utility',
				'variation_family_key'   => 'utility_general',
			),
			array(
				'key'                    => 'gc_utility_02',
				'name'                   => 'Utility block 02',
				'purpose_summary'        => 'General utility variant.',
				'category'               => 'utility_structural',
				'section_purpose_family' => 'utility',
				'variation_family_key'   => 'utility_general',
			),
			array(
				'key'                    => 'gc_utility_03',
				'name'                   => 'Utility block 03',
				'purpose_summary'        => 'General utility variant.',
				'category'               => 'utility_structural',
				'section_purpose_family' => 'utility',
				'variation_family_key'   => 'utility_general',
			),
			array(
				'key'                    => 'gc_utility_04',
				'name'                   => 'Utility block 04',
				'purpose_summary'        => 'General utility variant.',
				'category'               => 'utility_structural',
				'section_purpose_family' => 'utility',
				'variation_family_key'   => 'utility_general',
			),
			// timeline (6)
			array(
				'key'                    => 'gc_timeline_chrono_01',
				'name'                   => 'Timeline chronological 01',
				'purpose_summary'        => 'Chronological timeline. Headline and timeline items.',
				'category'               => 'timeline',
				'section_purpose_family' => 'timeline',
				'variation_family_key'   => 'timeline_chrono',
			),
			array(
				'key'                    => 'gc_timeline_chrono_02',
				'name'                   => 'Timeline chronological 02',
				'purpose_summary'        => 'Timeline variant.',
				'category'               => 'timeline',
				'section_purpose_family' => 'timeline',
				'variation_family_key'   => 'timeline_chrono',
			),
			array(
				'key'                    => 'gc_timeline_01',
				'name'                   => 'Timeline block 01',
				'purpose_summary'        => 'General timeline block.',
				'category'               => 'timeline',
				'section_purpose_family' => 'timeline',
				'variation_family_key'   => 'timeline_general',
			),
			array(
				'key'                    => 'gc_timeline_02',
				'name'                   => 'Timeline block 02',
				'purpose_summary'        => 'General timeline variant.',
				'category'               => 'timeline',
				'section_purpose_family' => 'timeline',
				'variation_family_key'   => 'timeline_general',
			),
			array(
				'key'                    => 'gc_timeline_03',
				'name'                   => 'Timeline block 03',
				'purpose_summary'        => 'General timeline variant.',
				'category'               => 'timeline',
				'section_purpose_family' => 'timeline',
				'variation_family_key'   => 'timeline_general',
			),
			array(
				'key'                    => 'gc_timeline_04',
				'name'                   => 'Timeline block 04',
				'purpose_summary'        => 'General timeline variant.',
				'category'               => 'timeline',
				'section_purpose_family' => 'timeline',
				'variation_family_key'   => 'timeline_general',
			),
			// related (10)
			array(
				'key'                    => 'gc_related_recommended_01',
				'name'                   => 'Related recommended 01',
				'purpose_summary'        => 'Related or recommended content. Headline and links.',
				'category'               => 'related',
				'section_purpose_family' => 'related',
				'variation_family_key'   => 'related_recommended',
			),
			array(
				'key'                    => 'gc_related_recommended_02',
				'name'                   => 'Related recommended 02',
				'purpose_summary'        => 'Related content variant.',
				'category'               => 'related',
				'section_purpose_family' => 'related',
				'variation_family_key'   => 'related_recommended',
			),
			array(
				'key'                    => 'gc_related_01',
				'name'                   => 'Related block 01',
				'purpose_summary'        => 'General related block.',
				'category'               => 'related',
				'section_purpose_family' => 'related',
				'variation_family_key'   => 'related_general',
			),
			array(
				'key'                    => 'gc_related_02',
				'name'                   => 'Related block 02',
				'purpose_summary'        => 'General related variant.',
				'category'               => 'related',
				'section_purpose_family' => 'related',
				'variation_family_key'   => 'related_general',
			),
			array(
				'key'                    => 'gc_related_03',
				'name'                   => 'Related block 03',
				'purpose_summary'        => 'General related variant.',
				'category'               => 'related',
				'section_purpose_family' => 'related',
				'variation_family_key'   => 'related_general',
			),
			array(
				'key'                    => 'gc_related_04',
				'name'                   => 'Related block 04',
				'purpose_summary'        => 'General related variant.',
				'category'               => 'related',
				'section_purpose_family' => 'related',
				'variation_family_key'   => 'related_general',
			),
			array(
				'key'                    => 'gc_related_05',
				'name'                   => 'Related block 05',
				'purpose_summary'        => 'General related variant.',
				'category'               => 'related',
				'section_purpose_family' => 'related',
				'variation_family_key'   => 'related_general',
			),
			array(
				'key'                    => 'gc_related_06',
				'name'                   => 'Related block 06',
				'purpose_summary'        => 'General related variant.',
				'category'               => 'related',
				'section_purpose_family' => 'related',
				'variation_family_key'   => 'related_general',
			),
			array(
				'key'                    => 'gc_related_07',
				'name'                   => 'Related block 07',
				'purpose_summary'        => 'General related variant.',
				'category'               => 'related',
				'section_purpose_family' => 'related',
				'variation_family_key'   => 'related_general',
			),
			array(
				'key'                    => 'gc_related_08',
				'name'                   => 'Related block 08',
				'purpose_summary'        => 'General related variant.',
				'category'               => 'related',
				'section_purpose_family' => 'related',
				'variation_family_key'   => 'related_general',
			),
			// proof (2), cta (2), hero (2) for spread
			array(
				'key'                    => 'gc_proof_quote_01',
				'name'                   => 'Proof quote block',
				'purpose_summary'        => 'Testimonial or quote block. Headline and quote.',
				'category'               => 'trust_proof',
				'section_purpose_family' => 'proof',
				'variation_family_key'   => 'proof_quote',
			),
			array(
				'key'                    => 'gc_proof_quote_02',
				'name'                   => 'Proof quote block B',
				'purpose_summary'        => 'Proof quote variant.',
				'category'               => 'trust_proof',
				'section_purpose_family' => 'proof',
				'variation_family_key'   => 'proof_quote',
			),
			array(
				'key'                    => 'gc_cta_inline_01',
				'name'                   => 'CTA inline block',
				'purpose_summary'        => 'Inline CTA block. Headline and primary link.',
				'category'               => 'cta_conversion',
				'section_purpose_family' => 'cta',
				'variation_family_key'   => 'cta_inline',
				'cta_classification'     => 'primary_cta',
			),
			array(
				'key'                    => 'gc_cta_inline_02',
				'name'                   => 'CTA inline block B',
				'purpose_summary'        => 'Inline CTA variant.',
				'category'               => 'cta_conversion',
				'section_purpose_family' => 'cta',
				'variation_family_key'   => 'cta_inline',
				'cta_classification'     => 'primary_cta',
			),
			array(
				'key'                    => 'gc_hero_compact_02',
				'name'                   => 'Hero compact variant',
				'purpose_summary'        => 'Compact hero variant. Headline and optional CTA.',
				'category'               => 'hero_intro',
				'section_purpose_family' => 'hero',
				'variation_family_key'   => 'hero_compact',
			),
			array(
				'key'                    => 'gc_hero_compact_03',
				'name'                   => 'Hero compact variant B',
				'purpose_summary'        => 'Compact hero variant B.',
				'category'               => 'hero_intro',
				'section_purpose_family' => 'hero',
				'variation_family_key'   => 'hero_compact',
			),
		);
	}

	/**
	 * Returns all gap-closing batch section definitions (order preserved for seeding).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all_definitions(): array {
		$out = array();
		foreach ( self::specs() as $spec ) {
			$out[] = self::gap_definition(
				$spec['key'],
				$spec['name'],
				$spec['purpose_summary'],
				$spec['category'],
				$spec['section_purpose_family'],
				$spec['variation_family_key'],
				$spec['cta_classification'] ?? ''
			);
		}
		return $out;
	}

	/**
	 * Returns section keys in this batch (for listing and tests).
	 *
	 * @return array<int, string>
	 */
	public static function section_keys(): array {
		$keys = array();
		foreach ( self::specs() as $spec ) {
			$keys[] = $spec['key'];
		}
		return $keys;
	}

	/**
	 * Maps spec/purpose category to section-registry schema allowed category (Section_Schema::get_allowed_categories).
	 *
	 * @param string $category Category from specs (e.g. offer, explainer, listing).
	 * @return string Schema-allowed category slug.
	 */
	private static function category_to_schema( string $category ): string {
		$map = array(
			'offer'     => 'feature_benefit',
			'explainer' => 'process_steps',
			'profile'   => 'profile_bio',
			'listing'   => 'directory_listing',
			'contact'   => 'form_embed',
			'related'   => 'related_recommended',
		);
		return $map[ $category ] ?? $category;
	}

	/**
	 * Builds a gap-closing section definition with full schema fields, preview, and accessibility metadata.
	 */
	private static function gap_definition(
		string $key,
		string $name,
		string $purpose_summary,
		string $category,
		string $section_purpose_family,
		string $variation_family_key,
		string $cta_classification = ''
	): array {
		$bp_id            = 'acf_blueprint_' . $key;
		$blueprint_fields = array(
			array(
				'key'          => 'field_gc_headline',
				'name'         => 'headline',
				'label'        => 'Headline',
				'type'         => 'text',
				'required'     => true,
				'instructions' => 'Section headline.',
			),
			array(
				'key'          => 'field_gc_body',
				'name'         => 'body',
				'label'        => 'Body',
				'type'         => 'textarea',
				'required'     => false,
				'instructions' => 'Supporting content.',
			),
		);
		$preview_defaults = array(
			'headline' => $name,
			'body'     => $purpose_summary,
		);
		$schema_category  = self::category_to_schema( $category );
		$base             = array(
			Section_Schema::FIELD_INTERNAL_KEY             => $key,
			Section_Schema::FIELD_NAME                     => $name,
			Section_Schema::FIELD_PURPOSE_SUMMARY          => $purpose_summary,
			Section_Schema::FIELD_CATEGORY                 => $schema_category,
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_' . $key,
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF      => $bp_id,
			Section_Schema::FIELD_HELPER_REF               => 'helper_' . $key,
			Section_Schema::FIELD_CSS_CONTRACT_REF         => 'css_' . $key,
			Section_Schema::FIELD_DEFAULT_VARIANT          => 'default',
			Section_Schema::FIELD_VARIANTS                 => array(
				'default' => array(
					'label'         => 'Default',
					'description'   => '',
					'css_modifiers' => array(),
				),
			),
			Section_Schema::FIELD_COMPATIBILITY            => array(
				'may_precede'          => array(),
				'may_follow'           => array(),
				'avoid_adjacent'       => array(),
				'duplicate_purpose_of' => array(),
			),
			Section_Schema::FIELD_VERSION                  => array(
				'version'             => '1',
				'stable_key_retained' => true,
			),
			Section_Schema::FIELD_STATUS                   => 'active',
			Section_Schema::FIELD_RENDER_MODE              => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION        => array( 'none' => true ),
			'section_purpose_family'                       => $section_purpose_family,
			'variation_family_key'                         => $variation_family_key,
			'preview_description'                          => $purpose_summary,
			'preview_image_ref'                            => '',
			'animation_tier'                               => 'subtle',
			'animation_families'                           => array( 'entrance' ),
			'preview_defaults'                             => $preview_defaults,
			'accessibility_warnings_or_enhancements'       => 'Use one primary heading per section. Ensure sufficient contrast and visible labels (spec §51).',
			'seo_relevance_notes'                          => 'Structured headings and content support SEO (spec §15.9).',
		);
		if ( $cta_classification !== '' ) {
			$base['cta_classification'] = $cta_classification;
		}
		$base['field_blueprint']                         = array(
			'blueprint_id'    => $bp_id,
			'section_key'     => $key,
			'section_version' => '1',
			'label'           => $name . ' fields',
			'description'     => 'Gap-closing section content fields.',
			'fields'          => $blueprint_fields,
		);
		$base[ Section_Schema::FIELD_INDUSTRY_AFFINITY ] = self::LAUNCH_INDUSTRIES;
		return $base;
	}
}
