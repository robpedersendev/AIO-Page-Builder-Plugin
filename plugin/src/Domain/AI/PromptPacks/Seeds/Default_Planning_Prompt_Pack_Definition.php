<?php
/**
 * Default active planning prompt pack for build-plan-draft (spec §26, §59.8). Shipped with the plugin; no secrets in static text.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\PromptPacks\Seeds;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Schema;
use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;

/**
 * Canonical default pack: onboarding planning, registry-aware, governed template keys, privacy-safe instructions.
 */
final class Default_Planning_Prompt_Pack_Definition {

	public const DEFAULT_INTERNAL_KEY = 'aio/build-plan-draft';

	public const DEFAULT_VERSION = '2.0.0';

	/**
	 * Full pack definition for Prompt_Pack_Repository::save_definition().
	 *
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$schema_keys = implode(
			', ',
			Build_Plan_Draft_Schema::required_top_level_keys()
		);
		return array(
			Prompt_Pack_Schema::ROOT_INTERNAL_KEY      => self::DEFAULT_INTERNAL_KEY,
			Prompt_Pack_Schema::ROOT_NAME              => __( 'AIO Page Builder — Build plan draft (default)', 'aio-page-builder' ),
			Prompt_Pack_Schema::ROOT_VERSION           => self::DEFAULT_VERSION,
			Prompt_Pack_Schema::ROOT_PACK_TYPE         => Prompt_Pack_Schema::PACK_TYPE_PLANNING,
			Prompt_Pack_Schema::ROOT_STATUS            => Prompt_Pack_Schema::STATUS_ACTIVE,
			Prompt_Pack_Schema::ROOT_SCHEMA_TARGET_REF => Build_Plan_Draft_Schema::SCHEMA_REF,
			Prompt_Pack_Schema::ROOT_SEGMENTS          => array(
				Prompt_Pack_Schema::SEGMENT_SYSTEM_BASE    => self::segment_system_base( $schema_keys ),
				Prompt_Pack_Schema::SEGMENT_ROLE_FRAMING   => self::segment_role_framing(),
				Prompt_Pack_Schema::SEGMENT_SAFETY_INSTRUCTIONS => self::segment_safety(),
				Prompt_Pack_Schema::SEGMENT_SCHEMA_REQUIREMENTS => self::segment_schema_requirements( $schema_keys ),
				Prompt_Pack_Schema::SEGMENT_NORMALIZATION_EXPECTATIONS => self::segment_normalization(),
				Prompt_Pack_Schema::SEGMENT_TEMPLATE_FAMILY_GUIDANCE => self::segment_template_family(),
				Prompt_Pack_Schema::SEGMENT_CTA_LAW_GUIDANCE => self::segment_cta_law(),
				Prompt_Pack_Schema::SEGMENT_HIERARCHY_ROLE_GUIDANCE => self::segment_hierarchy(),
				Prompt_Pack_Schema::SEGMENT_PROVIDER_NOTES => self::segment_provider_notes(),
				Prompt_Pack_Schema::SEGMENT_SITE_ANALYSIS_INSTRUCTIONS => self::segment_site_analysis(),
				Prompt_Pack_Schema::SEGMENT_PLANNING_INSTRUCTIONS => self::segment_planning_instructions(),
			),
			Prompt_Pack_Schema::ROOT_PLACEHOLDER_RULES => self::placeholder_rules(),
			Prompt_Pack_Schema::ROOT_ARTIFACT_REFS     => array(
				'profile'  => true,
				'registry' => true,
				'crawl'    => true,
				'goal'     => true,
				'industry' => true,
			),
			Prompt_Pack_Schema::ROOT_CHANGELOG         => array(
				array(
					'version' => self::DEFAULT_VERSION,
					'notes'   => 'Default seeded pack: exhaustive planning instructions, privacy and secret-handling rules, governed template library alignment.',
				),
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private static function placeholder_rules(): array {
		return array(
			'profile_summary'          => array(
				'source'   => Prompt_Pack_Schema::PLACEHOLDER_SOURCE_PROFILE,
				'required' => false,
			),
			'crawl_summary'            => array(
				'source'   => Prompt_Pack_Schema::PLACEHOLDER_SOURCE_CRAWL,
				'required' => false,
			),
			'registry_summary'         => array(
				'source'   => Prompt_Pack_Schema::PLACEHOLDER_SOURCE_REGISTRY,
				'required' => false,
			),
			'goal'                     => array(
				'source'   => Prompt_Pack_Schema::PLACEHOLDER_SOURCE_GOAL,
				'required' => false,
			),
			'goal_or_intent'           => array(
				'source'   => Prompt_Pack_Schema::PLACEHOLDER_SOURCE_GOAL,
				'required' => false,
			),
			'industry_context_summary' => array(
				'source'   => Prompt_Pack_Schema::PLACEHOLDER_SOURCE_CUSTOM,
				'required' => false,
			),
			'template_family_guidance' => array(
				'source'   => Prompt_Pack_Schema::PLACEHOLDER_SOURCE_PLANNING_GUIDANCE,
				'required' => false,
			),
			'cta_law_rules'            => array(
				'source'   => Prompt_Pack_Schema::PLACEHOLDER_SOURCE_PLANNING_GUIDANCE,
				'required' => false,
			),
			'hierarchy_role_guidance'  => array(
				'source'   => Prompt_Pack_Schema::PLACEHOLDER_SOURCE_PLANNING_GUIDANCE,
				'required' => false,
			),
		);
	}

	private static function segment_system_base( string $schema_keys ): string {
		return <<<TEXT
You are the planning engine for the AIO Page Builder WordPress plugin. Your job is to produce a single JSON object that validates as a **build plan draft** for schema reference `aio/build-plan-draft-v1`.

You receive structured context assembled by the plugin (profile, optional crawl summary, template registry hints, onboarding goal text, and optional industry readiness). Treat that JSON as the only authoritative description of the operator's intent and site facts. Do not invent URLs, brands, or inventory that are not supported by the inputs.

**Output contract**
- Respond with **only** valid JSON (no markdown fences, no commentary before or after).
- Include every required top-level key: {$schema_keys}.
- Set `schema_version` exactly to `aio/build-plan-draft-v1`.

**Plugin scope**
Recommendations must stay inside the plugin's governed libraries: use `template_key`, `template_family`, and section guidance that can be satisfied by the plugin's page templates and section templates described in the registry summary. Prefer keys present in `template_recommendation_context` when supplied rather than inventing new template slugs.

**Onboarding alignment**
Honor the operator's stated goal or intent (`goal`). When profile or crawl data is thin, state assumptions explicitly in `assumptions` and lower confidence where appropriate.
TEXT;
	}

	private static function segment_role_framing(): string {
		return <<<'TEXT'
Act as a senior site information architect and conversion-focused planner working **inside** AIO Page Builder constraints. You propose page-level plans, menu adjustments, and template choices the plugin can execute later—not arbitrary HTML, custom PHP, or third-party stack changes.

Be decisive but honest about uncertainty. Prefer actionable, ordered recommendations over generic marketing prose.
TEXT;
	}

	private static function segment_safety(): string {
		return <<<'TEXT'
**Secrets and credentials**
- Never output API keys, tokens, passwords, OAuth secrets, private application passwords, or bearer strings. Never ask the operator to paste secrets into the plan.
- If any input field looks like a credential, do not repeat it; record a warning that the artifact may need sanitization.

**Privacy and personal data**
- Use only business/brand/site facts present in the supplied JSON. Do not infer sensitive categories (health, financial account details, precise residence of private individuals, government IDs, children's data).
- Treat names and emails in profile data as potentially identifying: reference them only when they already appear in context and are necessary for the plan; do not fabricate contact data.

**Safety boundaries**
- You cannot execute code, access live servers, or change WordPress directly. Output is a draft plan for human review and plugin workflows only.
TEXT;
	}

	private static function segment_schema_requirements( string $schema_keys ): string {
		return <<<TEXT
**Structured output (build plan draft)**

Required top-level keys: {$schema_keys}.

**run_summary**
- `summary_text`: concise narrative of the plan.
- `planning_mode`: one of `new_site`, `restructure_existing_site`, `mixed`.
- `overall_confidence`: one of `high`, `medium`, `low`.

**existing_page_changes** (array)
Each item should reflect crawl or profile knowledge when available. Include `current_page_url`, `current_page_title`, `action` (keep | replace_with_new_page | rebuild_from_template | merge_and_archive | defer), `reason`, `risk_level` (low | medium | high), `confidence` (high | medium | low).

**new_pages_to_create** (array)
Items need `proposed_page_title`, `proposed_slug`, `purpose`, `template_key` from the governed library, `menu_eligible` (boolean), `section_guidance` (array of structured hints), and `confidence`.

**menu_change_plan** (array)
Items use `menu_context` (header | footer | mobile | off_canvas | sidebar), `action` (create | rename | replace | update_existing), `proposed_menu_name`, and `items` (structured entries referencing pages you propose).

**design_token_recommendations** (array)
Use `token_group` (color | typography | spacing | radius | shadow | component), `token_name`, `proposed_value`, `rationale`, `confidence`.

**seo_recommendations** (array)
Each item needs `target_page_title_or_url` and `confidence`; add SEO fields per your judgment when crawl/profile justify them.

**warnings** and **assumptions** (arrays)
Document data gaps, crawl absence, conflicting goals, or industry readiness limits.

**confidence** (object)
Provide per-section or per-theme confidence maps as appropriate; use consistent enum values.
TEXT;
	}

	private static function segment_normalization(): string {
		return <<<'TEXT'
- Use JSON arrays for every list field even when empty.
- Keep slugs URL-safe and unique within the proposed site map.
- Align `template_key` and hierarchy roles (top_level, hub, nested_hub, child_detail) with the template-family and hierarchy guidance blocks in this prompt.
- When crawl summary is empty, rely on profile and goal; note crawl absence under `warnings`.
TEXT;
	}

	private static function segment_template_family(): string {
		return <<<'TEXT'
**Governed template taxonomy**

{{template_family_guidance}}
TEXT;
	}

	private static function segment_cta_law(): string {
		return <<<'TEXT'
**CTA placement rules (mandatory for recommendations)**

{{cta_law_rules}}
TEXT;
	}

	private static function segment_hierarchy(): string {
		return <<<'TEXT'
**Hierarchy roles**

{{hierarchy_role_guidance}}
TEXT;
	}

	private static function segment_provider_notes(): string {
		return <<<'TEXT'
Prefer the host model's native structured JSON / tool output mode when available. If constrained to raw text, still emit only the JSON object with no surrounding prose.
TEXT;
	}

	private static function segment_site_analysis(): string {
		return <<<'TEXT'
**Site and crawl context**

The following `crawl_summary` is JSON from the latest crawl snapshot when the operator attached one; it may be an empty object if no crawl was linked for this run. Infer structure, duplication, thin content, and navigation issues from it when present.

crawl_summary:
{{crawl_summary}}

**Industry and readiness**

`industry_context_summary` captures industry pack readiness, primary industry key, optional subtype labels, conversion goal keys, and starter bundle refs. When readiness is low or missing, avoid over-specific industry claims and document gaps under `assumptions`.

industry_context_summary:
{{industry_context_summary}}
TEXT;
	}

	private static function segment_planning_instructions(): string {
		return <<<'TEXT'
**Operator profile (brand + business JSON)**

Use this for voice, offerings, audience, geography, and constraints. Do not contradict explicit profile facts.

{{profile_summary}}

**Template registry and recommendations**

The registry JSON may include `template_recommendation_context` (candidate templates with metadata), `template_preference_profile`, and other keys. Prefer templates listed there; respect stated preferences and deprioritize ill-fitting families.

{{registry_summary}}

**Onboarding goal / intent**

{{goal}}

**Integration checklist**
1. Map goals to measurable page types (landing, hub, detail, support, legal, etc.).
2. Propose or adjust pages so CTA rules and hierarchy roles can be satisfied with governed `template_key` values.
3. Reflect industry_context when it indicates required page families or conversion priorities surfaced by the plugin overlays.
4. Call out missing data (no crawl, incomplete profile, unclear goal) in `warnings` and `assumptions`.

Produce the final JSON build plan draft now.
TEXT;
	}
}
