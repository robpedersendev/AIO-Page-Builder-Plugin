<?php
/**
 * Process, steps, timeline, and FAQ section template definitions for SEC-05 library batch (spec §12, §15, §51, Prompt 150).
 * Production-grade process/timeline/FAQ sections with full metadata, field blueprints, accessibility and semantic heading discipline.
 * Does not persist; callers save via Section_Template_Repository or Process_Timeline_FAQ_Library_Batch_Seeder.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\ProcessTimelineFaqBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Returns section definitions for the process/timeline/FAQ library batch (SEC-05).
 * Each definition is schema-compliant with embedded field_blueprint, category, section_purpose_family, and accessibility metadata.
 */
final class Process_Timeline_FAQ_Library_Batch_Definitions {

	/** Batch ID per template-library-inventory-manifest §3.1 (process, timeline, FAQ scope). */
	public const BATCH_ID = 'SEC-05';

	/**
	 * Returns all process/timeline/FAQ batch section definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			self::ptf_steps_01(),
			self::ptf_steps_horizontal_01(),
			self::ptf_steps_vertical_01(),
			self::ptf_buying_process_01(),
			self::ptf_onboarding_01(),
			self::ptf_service_flow_01(),
			self::ptf_expectations_01(),
			self::ptf_timeline_01(),
			self::ptf_timeline_compact_01(),
			self::ptf_policy_explainer_01(),
			self::ptf_faq_01(),
			self::ptf_faq_accordion_01(),
			self::ptf_faq_by_category_01(),
			self::ptf_how_it_works_01(),
			self::ptf_comparison_steps_01(),
		);
	}

	/**
	 * Returns section keys in this batch (for listing and tests).
	 *
	 * @return list<string>
	 */
	public static function section_keys(): array {
		return array(
			'ptf_steps_01',
			'ptf_steps_horizontal_01',
			'ptf_steps_vertical_01',
			'ptf_buying_process_01',
			'ptf_onboarding_01',
			'ptf_service_flow_01',
			'ptf_expectations_01',
			'ptf_timeline_01',
			'ptf_timeline_compact_01',
			'ptf_policy_explainer_01',
			'ptf_faq_01',
			'ptf_faq_accordion_01',
			'ptf_faq_by_category_01',
			'ptf_how_it_works_01',
			'ptf_comparison_steps_01',
		);
	}

	/**
	 * Builds a process/timeline/FAQ section definition.
	 *
	 * @param string $key Internal key.
	 * @param string $name Display name.
	 * @param string $purpose_summary Purpose summary.
	 * @param string $category Section category (process_steps, timeline, faq).
	 * @param string $purpose_family section_purpose_family (process, timeline, faq).
	 * @param string $variation_family_key Variation family key.
	 * @param string $preview_desc Preview description.
	 * @param array<string, mixed> $blueprint_fields Field definitions for embedded blueprint.
	 * @param array<string, mixed> $preview_defaults Synthetic ACF defaults for preview.
	 * @param array<string, mixed> $extra Optional extra keys.
	 * @return array<string, mixed>
	 */
	private static function ptf_definition(
		string $key,
		string $name,
		string $purpose_summary,
		string $category,
		string $purpose_family,
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
			Section_Schema::FIELD_CATEGORY                => $category,
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
			'section_purpose_family'                     => $purpose_family,
			'variation_family_key'                       => $variation_family_key,
			'preview_description'                        => $preview_desc,
			'preview_image_ref'                          => '',
			'animation_tier'                             => 'subtle',
			'animation_families'                         => array( 'entrance' ),
			'preview_defaults'                           => $preview_defaults,
			'accessibility_warnings_or_enhancements'     => self::default_accessibility_guidance( $category ),
			'seo_relevance_notes'                       => 'Process and FAQ content support how-to and Q&A signals; use one primary heading per section (spec §51.6). Structured data may apply for FAQ.',
		);
		$base['field_blueprint'] = array(
			'blueprint_id'    => $bp_id,
			'section_key'     => $key,
			'section_version' => '1',
			'label'           => $name . ' fields',
			'description'     => 'Process/timeline/FAQ content fields.',
			'fields'          => $blueprint_fields,
		);
		return array_merge( $base, $extra );
	}

	/**
	 * Default accessibility guidance by category (spec §51.3, §51.6, §51.7).
	 *
	 * @param string $category process_steps, timeline, or faq.
	 * @return string
	 */
	private static function default_accessibility_guidance( string $category ): string {
		if ( $category === 'faq' ) {
			return 'Use one heading (h2) for the section. FAQ items must be in a list or definition list. If accordion pattern is used, expose expanded state (aria-expanded) and ensure static fallback preserves full content. Do not rely on color alone (spec §51.3, §51.7).';
		}
		if ( $category === 'timeline' ) {
			return 'Use one heading per section. Timeline items must be in a semantic list or ordered list. Landmark and ARIA only where needed (spec §51.6, §51.7). Optional dates/labels must be omit-safe.';
		}
		return 'Use one heading per section (spec §51.6). Step lists must be in ol/ul or role="list". Do not rely on color alone. Landmark and ARIA per §51.7. Optional step numbers must be field-driven and omit-safe.';
	}

	/** Step list: headline + repeatable (title, description). */
	public static function ptf_steps_01(): array {
		$key = 'ptf_steps_01';
		$fields = array(
			array( 'key' => 'field_ptf_st_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_ptf_st_steps',
				'name'        => 'steps',
				'label'       => 'Steps',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_ptf_st_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_ptf_st_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
				),
			),
		);
		return self::ptf_definition(
			$key,
			'Step list',
			'Numbered or bullet step list with headline and repeatable title/description. Use for how-to or process explanation.',
			'process_steps',
			'process',
			'steps_list',
			'Step list with headline and items.',
			$fields,
			array( 'headline' => 'How it works', 'steps' => array( array( 'title' => 'Step one', 'description' => 'Synthetic description.' ) ) ),
			array( 'short_label' => 'Step list', 'suggested_use_cases' => array( 'Service page', 'Product page', 'How-to' ) )
		);
	}

	/** Horizontal step flow. */
	public static function ptf_steps_horizontal_01(): array {
		$key = 'ptf_steps_horizontal_01';
		$fields = array(
			array( 'key' => 'field_ptf_sh_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_ptf_sh_steps',
				'name'        => 'steps',
				'label'       => 'Steps',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_ptf_sh_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_ptf_sh_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
				),
			),
		);
		return self::ptf_definition(
			$key,
			'Horizontal step flow',
			'Horizontal flow of steps with headline. Use for process or workflow with left-to-right reading order.',
			'process_steps',
			'process',
			'steps_horizontal',
			'Horizontal step flow.',
			$fields,
			array( 'headline' => 'Our process', 'steps' => array( array( 'title' => 'First', 'description' => '' ), array( 'title' => 'Second', 'description' => '' ) ) ),
			array( 'short_label' => 'Steps horizontal', 'suggested_use_cases' => array( 'Service flow', 'Buying process', 'Workflow' ) )
		);
	}

	/** Vertical step flow. */
	public static function ptf_steps_vertical_01(): array {
		$key = 'ptf_steps_vertical_01';
		$fields = array(
			array( 'key' => 'field_ptf_sv_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_ptf_sv_steps',
				'name'        => 'steps',
				'label'       => 'Steps',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_ptf_sv_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_ptf_sv_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
				),
			),
		);
		return self::ptf_definition(
			$key,
			'Vertical step flow',
			'Vertical flow of steps with headline. Use for onboarding or sequential process.',
			'process_steps',
			'process',
			'steps_vertical',
			'Vertical step flow.',
			$fields,
			array( 'headline' => 'Steps', 'steps' => array( array( 'title' => 'Step 1', 'description' => 'Synthetic.' ) ) ),
			array( 'short_label' => 'Steps vertical', 'suggested_use_cases' => array( 'Onboarding', 'Process', 'Guide' ) )
		);
	}

	/** Buying process roadmap. */
	public static function ptf_buying_process_01(): array {
		$key = 'ptf_buying_process_01';
		$fields = array(
			array( 'key' => 'field_ptf_bp_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_ptf_bp_steps',
				'name'        => 'steps',
				'label'       => 'Steps',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_ptf_bp_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_ptf_bp_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
				),
			),
		);
		return self::ptf_definition(
			$key,
			'Buying process roadmap',
			'Buying or decision process as step roadmap. Use for product or service purchase flow.',
			'process_steps',
			'process',
			'buying_process',
			'Buying process steps.',
			$fields,
			array( 'headline' => 'How to get started', 'steps' => array( array( 'title' => 'Choose', 'description' => 'Synthetic.' ), array( 'title' => 'Order', 'description' => '' ) ) ),
			array( 'short_label' => 'Buying process', 'suggested_use_cases' => array( 'Product page', 'Service page', 'Conversion' ) )
		);
	}

	/** Onboarding steps. */
	public static function ptf_onboarding_01(): array {
		$key = 'ptf_onboarding_01';
		$fields = array(
			array( 'key' => 'field_ptf_onb_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_ptf_onb_steps',
				'name'        => 'steps',
				'label'       => 'Steps',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_ptf_onb_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_ptf_onb_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
				),
			),
		);
		return self::ptf_definition(
			$key,
			'Onboarding steps',
			'Onboarding or setup steps with headline. Use for getting-started or activation flow.',
			'process_steps',
			'process',
			'onboarding',
			'Onboarding steps.',
			$fields,
			array( 'headline' => 'Get started', 'steps' => array( array( 'title' => 'Sign up', 'description' => 'Synthetic.' ) ) ),
			array( 'short_label' => 'Onboarding', 'suggested_use_cases' => array( 'Product', 'Service', 'Activation' ) )
		);
	}

	/** Service flow. */
	public static function ptf_service_flow_01(): array {
		$key = 'ptf_service_flow_01';
		$fields = array(
			array( 'key' => 'field_ptf_sf_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_ptf_sf_steps',
				'name'        => 'steps',
				'label'       => 'Steps',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_ptf_sf_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_ptf_sf_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
				),
			),
		);
		return self::ptf_definition(
			$key,
			'Service flow',
			'Service delivery or workflow steps. Use for service page or delivery explanation.',
			'process_steps',
			'process',
			'service_flow',
			'Service flow steps.',
			$fields,
			array( 'headline' => 'Our service flow', 'steps' => array( array( 'title' => 'Consultation', 'description' => 'Synthetic.' ) ) ),
			array( 'short_label' => 'Service flow', 'suggested_use_cases' => array( 'Service page', 'Local', 'Delivery' ) )
		);
	}

	/** Treatment/service expectations. */
	public static function ptf_expectations_01(): array {
		$key = 'ptf_expectations_01';
		$fields = array(
			array( 'key' => 'field_ptf_exp_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_ptf_exp_items',
				'name'        => 'expectations',
				'label'       => 'Expectations',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_ptf_exp_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_ptf_exp_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
				),
			),
		);
		return self::ptf_definition(
			$key,
			'Treatment / service expectations',
			'Expectation-setting list for treatment or service. Use for what to expect, duration, or outcomes.',
			'process_steps',
			'process',
			'expectations',
			'Expectations list.',
			$fields,
			array( 'headline' => 'What to expect', 'expectations' => array( array( 'title' => 'Duration', 'description' => 'Synthetic.' ) ) ),
			array( 'short_label' => 'Expectations', 'suggested_use_cases' => array( 'Service page', 'Treatment', 'Local' ) )
		);
	}

	/** Timeline: headline + repeatable (date/label, title, description). */
	public static function ptf_timeline_01(): array {
		$key = 'ptf_timeline_01';
		$fields = array(
			array( 'key' => 'field_ptf_tl_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_ptf_tl_items',
				'name'        => 'timeline_items',
				'label'       => 'Timeline items',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_ptf_tl_date', 'name' => 'date_or_label', 'label' => 'Date or label', 'type' => 'text', 'required' => false ),
					array( 'key' => 'field_ptf_tl_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_ptf_tl_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
				),
			),
		);
		return self::ptf_definition(
			$key,
			'Timeline',
			'Timeline with optional date/label per item. Use for milestones, history, or schedule.',
			'timeline',
			'timeline',
			'timeline',
			'Timeline with items.',
			$fields,
			array( 'headline' => 'Timeline', 'timeline_items' => array( array( 'date_or_label' => 'Step 1', 'title' => 'Milestone', 'description' => 'Synthetic.' ) ) ),
			array( 'short_label' => 'Timeline', 'suggested_use_cases' => array( 'Project', 'History', 'Schedule' ) )
		);
	}

	/** Timeline compact. */
	public static function ptf_timeline_compact_01(): array {
		$key = 'ptf_timeline_compact_01';
		$fields = array(
			array( 'key' => 'field_ptf_tc_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_ptf_tc_items',
				'name'        => 'timeline_items',
				'label'       => 'Timeline items',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_ptf_tc_label', 'name' => 'label', 'label' => 'Label', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_ptf_tc_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'text', 'required' => false ),
				),
			),
		);
		return self::ptf_definition(
			$key,
			'Timeline compact',
			'Compact timeline with label and short description. Use for dense timeline display.',
			'timeline',
			'timeline',
			'timeline_compact',
			'Compact timeline.',
			$fields,
			array( 'headline' => 'Key dates', 'timeline_items' => array( array( 'label' => 'Phase 1', 'description' => 'Synthetic.' ) ) ),
			array( 'short_label' => 'Timeline compact', 'suggested_use_cases' => array( 'Dense layout', 'Schedule', 'Milestones' ) )
		);
	}

	/** Policy/legal explainer. */
	public static function ptf_policy_explainer_01(): array {
		$key = 'ptf_policy_explainer_01';
		$fields = array(
			array( 'key' => 'field_ptf_pe_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_ptf_pe_body', 'name' => 'body', 'label' => 'Body copy', 'type' => 'textarea', 'required' => true ),
			array(
				'key'         => 'field_ptf_pe_steps',
				'name'        => 'steps',
				'label'       => 'Optional steps',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => false,
				'sub_fields'  => array(
					array( 'key' => 'field_ptf_pe_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_ptf_pe_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
				),
			),
		);
		return self::ptf_definition(
			$key,
			'Policy / legal explainer',
			'Policy or legal explanation with headline, body, and optional steps. Use for policy summary or legal explainer. Static content only; no legal advice in preview.',
			'process_steps',
			'process',
			'policy_explainer',
			'Policy explainer with optional steps.',
			$fields,
			array( 'headline' => 'Policy summary', 'body' => 'Synthetic body for preview.', 'steps' => array() ),
			array( 'short_label' => 'Policy explainer', 'suggested_use_cases' => array( 'Legal page', 'Policy', 'Compliance' ) )
		);
	}

	/** FAQ standard: headline + repeatable question/answer. */
	public static function ptf_faq_01(): array {
		$key = 'ptf_faq_01';
		$fields = array(
			array( 'key' => 'field_ptf_faq_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_ptf_faq_items',
				'name'        => 'faq_items',
				'label'       => 'FAQ items',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_ptf_faq_q', 'name' => 'question', 'label' => 'Question', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_ptf_faq_a', 'name' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'required' => true ),
				),
			),
		);
		return self::ptf_definition(
			$key,
			'FAQ standard',
			'Standard FAQ with headline and repeatable question/answer. Use for general or category FAQ.',
			'faq',
			'faq',
			'faq_standard',
			'FAQ with question/answer items.',
			$fields,
			array( 'headline' => 'Frequently asked questions', 'faq_items' => array( array( 'question' => 'Preview question?', 'answer' => 'Synthetic answer.' ) ) ),
			array( 'short_label' => 'FAQ', 'suggested_use_cases' => array( 'Service page', 'Product page', 'General FAQ' ) )
		);
	}

	/** FAQ accordion: same structure, accessibility note for accordion pattern. */
	public static function ptf_faq_accordion_01(): array {
		$key = 'ptf_faq_accordion_01';
		$fields = array(
			array( 'key' => 'field_ptf_fa_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_ptf_fa_items',
				'name'        => 'faq_items',
				'label'       => 'FAQ items',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_ptf_fa_q', 'name' => 'question', 'label' => 'Question', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_ptf_fa_a', 'name' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'required' => true ),
				),
			),
		);
		$extra = array(
			'short_label'             => 'FAQ accordion',
			'suggested_use_cases'      => array( 'Interactive FAQ', 'Dense page', 'Expandable' ),
			'accessibility_warnings_or_enhancements' => 'Use one heading (h2) for the section. If accordion pattern is used: expose expanded state (aria-expanded), ensure keyboard operability, and provide static fallback so content is available without JS (spec §51.3, §51.7). FAQ items in list or dl.',
		);
		return self::ptf_definition(
			$key,
			'FAQ accordion',
			'FAQ with optional accordion pattern. Ensure accessible accordion (aria-expanded, keyboard) and static fallback.',
			'faq',
			'faq',
			'faq_accordion',
			'FAQ with accordion-style display.',
			$fields,
			array( 'headline' => 'FAQ', 'faq_items' => array( array( 'question' => 'Preview question?', 'answer' => 'Synthetic answer.' ) ) ),
			$extra
		);
	}

	/** FAQ by category: headline + repeatable groups (category name, items). */
	public static function ptf_faq_by_category_01(): array {
		$key = 'ptf_faq_by_category_01';
		$fields = array(
			array( 'key' => 'field_ptf_fc_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_ptf_fc_groups',
				'name'        => 'categories',
				'label'       => 'FAQ categories',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_ptf_fc_cat_name', 'name' => 'category_name', 'label' => 'Category name', 'type' => 'text', 'required' => true ),
					array(
						'key'         => 'field_ptf_fc_items',
						'name'        => 'items',
						'label'       => 'Items',
						'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
						'required'    => true,
						'sub_fields'  => array(
							array( 'key' => 'field_ptf_fc_q', 'name' => 'question', 'label' => 'Question', 'type' => 'text', 'required' => true ),
							array( 'key' => 'field_ptf_fc_a', 'name' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'required' => true ),
						),
					),
				),
			),
		);
		return self::ptf_definition(
			$key,
			'FAQ by category',
			'FAQ grouped by category with repeatable question/answer per group. Use for multi-topic FAQ.',
			'faq',
			'faq',
			'faq_by_category',
			'FAQ grouped by category.',
			$fields,
			array( 'headline' => 'FAQ', 'categories' => array( array( 'category_name' => 'General', 'items' => array( array( 'question' => 'Q?', 'answer' => 'A.' ) ) ) ) ),
			array( 'short_label' => 'FAQ by category', 'suggested_use_cases' => array( 'Directory', 'Multi-topic', 'Hub page' ) )
		);
	}

	/** How it works explainer. */
	public static function ptf_how_it_works_01(): array {
		$key = 'ptf_how_it_works_01';
		$fields = array(
			array( 'key' => 'field_ptf_hiw_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_ptf_hiw_steps',
				'name'        => 'steps',
				'label'       => 'Steps',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_ptf_hiw_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_ptf_hiw_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
				),
			),
		);
		return self::ptf_definition(
			$key,
			'How it works',
			'How-it-works explainer with headline and steps. Use for product, service, or resource explanation.',
			'process_steps',
			'process',
			'how_it_works',
			'How it works steps.',
			$fields,
			array( 'headline' => 'How it works', 'steps' => array( array( 'title' => 'Step 1', 'description' => 'Synthetic.' ) ) ),
			array( 'short_label' => 'How it works', 'suggested_use_cases' => array( 'Product', 'Service', 'Resource' ) )
		);
	}

	/** Comparison by step. */
	public static function ptf_comparison_steps_01(): array {
		$key = 'ptf_comparison_steps_01';
		$fields = array(
			array( 'key' => 'field_ptf_cs_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_ptf_cs_steps',
				'name'        => 'steps',
				'label'       => 'Comparison steps',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_ptf_cs_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_ptf_cs_option_a', 'name' => 'option_a', 'label' => 'Option A', 'type' => 'text', 'required' => false ),
					array( 'key' => 'field_ptf_cs_option_b', 'name' => 'option_b', 'label' => 'Option B', 'type' => 'text', 'required' => false ),
				),
			),
		);
		return self::ptf_definition(
			$key,
			'Comparison by step',
			'Step-by-step comparison (e.g. Option A vs B per step). Use for plan or option comparison.',
			'process_steps',
			'process',
			'comparison_steps',
			'Comparison steps.',
			$fields,
			array( 'headline' => 'Compare', 'steps' => array( array( 'title' => 'Step', 'option_a' => 'A', 'option_b' => 'B' ) ) ),
			array( 'short_label' => 'Comparison steps', 'suggested_use_cases' => array( 'Pricing', 'Plans', 'Options' ) )
		);
	}
}
