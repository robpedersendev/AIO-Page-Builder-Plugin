<?php
/**
 * Runtime prompt-pack lookup and selection (spec §26, §59.8). Query by key, version, status, capability match.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\PromptPacks;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Retrieves prompt packs from repository; filters by status, pack_type, schema_target_ref, provider compatibility.
 * Exposes planning guidance content for template-family and CTA-law injection (Prompt 210, template-family-planning-prompt-pack-addendum).
 */
final class Prompt_Pack_Registry_Service {

	/** Schema version for planning guidance payload (traceability). */
	public const PLANNING_GUIDANCE_SCHEMA_VERSION = '1';

	/** @var Prompt_Pack_Registry_Repository_Interface */
	private Prompt_Pack_Registry_Repository_Interface $repository;

	public function __construct( Prompt_Pack_Registry_Repository_Interface $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Returns full pack definition by internal_key. Prefers active version.
	 *
	 * @param string $internal_key Pack internal_key (e.g. aio/build-plan-draft).
	 * @return array<string, mixed>|null selected_prompt_pack shape (full definition) or null.
	 */
	public function get_pack( string $internal_key ): ?array {
		return $this->repository->get_definition_by_key( $internal_key );
	}

	/**
	 * Returns full pack definition by internal_key and version.
	 *
	 * @param string $internal_key Pack internal_key.
	 * @param string $version      Semantic version.
	 * @return array<string, mixed>|null selected_prompt_pack shape or null.
	 */
	public function get_pack_by_version( string $internal_key, string $version ): ?array {
		return $this->repository->get_definition_by_key_and_version( $internal_key, $version );
	}

	/**
	 * Lists active packs. Optionally filter by pack_type.
	 *
	 * @param string|null $pack_type Optional: planning, repair, summary, other.
	 * @param int         $limit     Max items.
	 * @return array<int, array<string, mixed>>
	 */
	public function list_active_packs( ?string $pack_type = null, int $limit = 50 ): array {
		$defs = $this->repository->list_definitions_by_status( Prompt_Pack_Schema::STATUS_ACTIVE, $limit, 0 );
		if ( $pack_type === null ) {
			return $defs;
		}
		$out = array();
		foreach ( $defs as $def ) {
			if ( ( $def[ Prompt_Pack_Schema::ROOT_PACK_TYPE ] ?? '' ) === $pack_type ) {
				$out[] = $def;
			}
		}
		return $out;
	}

	/**
	 * Selects a prompt pack for a planning run. Uses schema_target_ref and optional provider compatibility.
	 *
	 * @param string      $schema_target_ref Plugin-owned schema ref (e.g. aio/build-plan-draft-v1).
	 * @param string|null $provider_id      Optional provider id for compatibility filter.
	 * @return array<string, mixed>|null selected_prompt_pack or null if none eligible.
	 */
	public function select_for_planning( string $schema_target_ref, ?string $provider_id = null ): ?array {
		$candidates = $this->list_active_packs( Prompt_Pack_Schema::PACK_TYPE_PLANNING, 20 );
		foreach ( $candidates as $pack ) {
			$pack_schema = $pack[ Prompt_Pack_Schema::ROOT_SCHEMA_TARGET_REF ] ?? null;
			if ( $pack_schema !== $schema_target_ref ) {
				continue;
			}
			if ( $provider_id !== null && ! $this->pack_supports_provider( $pack, $provider_id ) ) {
				continue;
			}
			$key = (string) ( $pack[ Prompt_Pack_Schema::ROOT_INTERNAL_KEY ] ?? '' );
			$ver = (string) ( $pack[ Prompt_Pack_Schema::ROOT_VERSION ] ?? '' );
			Named_Debug_Log::event( Named_Debug_Log_Event::PROMPT_PACK_SELECT_RESULT, 'found=1 schema=' . $schema_target_ref . ' key=' . $key . ' version=' . $ver . ' provider=' . ( $provider_id ?? 'null' ) );
			return $pack;
		}
		Named_Debug_Log::event( Named_Debug_Log_Event::PROMPT_PACK_SELECT_RESULT, 'found=0 schema=' . $schema_target_ref . ' provider=' . ( $provider_id ?? 'null' ) );
		return null;
	}

	/**
	 * Whether the pack declares support for the provider (provider_compatibility.supported_providers).
	 *
	 * @param array<string, mixed> $pack        Full pack definition.
	 * @param string               $provider_id Provider id.
	 * @return bool
	 */
	public function pack_supports_provider( array $pack, string $provider_id ): bool {
		$compat = $pack[ Prompt_Pack_Schema::ROOT_PROVIDER_COMPATIBILITY ] ?? null;
		if ( ! is_array( $compat ) ) {
			return true;
		}
		$supported = $compat['supported_providers'] ?? array();
		if ( ! is_array( $supported ) || empty( $supported ) ) {
			return true;
		}
		return in_array( $provider_id, $supported, true );
	}

	/**
	 * Eligibility: pack has required segments and schema_target_ref when required.
	 *
	 * @param array<string, mixed> $pack Full pack definition.
	 * @return bool
	 */
	public function is_eligible_for_planning( array $pack ): bool {
		$segments = $pack[ Prompt_Pack_Schema::ROOT_SEGMENTS ] ?? array();
		if ( ! is_array( $segments ) || empty( $segments[ Prompt_Pack_Schema::SEGMENT_SYSTEM_BASE ] ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Returns planning guidance content for template-family taxonomy, CTA-law rules, and hierarchy roles (Prompt 210).
	 * Used to populate input_artifact.planning_guidance so placeholder resolution can inject into pack segments.
	 * Authority: page-template-directory-ia-extension, cta-sequencing-and-placement-contract.
	 *
	 * @return array{template_family_guidance: string, cta_law_rules: string, hierarchy_role_guidance: string, schema_version: string}
	 */
	public function get_planning_guidance_content(): array {
		return array(
			'template_family_guidance' => $this->get_template_family_guidance_text(),
			'cta_law_rules'            => $this->get_cta_law_rules_text(),
			'hierarchy_role_guidance'  => $this->get_hierarchy_role_guidance_text(),
			'schema_version'           => self::PLANNING_GUIDANCE_SCHEMA_VERSION,
		);
	}

	/**
	 * Page-template category classes and family examples (governed taxonomy).
	 *
	 * @return string
	 */
	private function get_template_family_guidance_text(): string {
		$lines = array(
			'Page templates are grouped by template_category_class and template_family.',
			'',
			'template_category_class (hierarchy):',
			'- top_level: Home, about, contact, key landing pages.',
			'- hub: Category/listing pages (e.g. services, products, locations, offerings).',
			'- nested_hub: Sub-category or regional hub pages.',
			'- child_detail: Individual item or detail pages (product, service, location, profile).',
			'',
			'template_family examples (within a class): home, about, contact, services, products, offerings, directories, locations, product_detail, service_detail, profile_entity, location_detail.',
			'Recommendations must use template_key and template_family from the governed library; do not invent keys.',
		);
		return implode( "\n", $lines );
	}

	/**
	 * CTA sequencing and placement rules (cta-sequencing-and-placement-contract).
	 *
	 * @return string
	 */
	private function get_cta_law_rules_text(): string {
		$lines = array(
			'CTA rules (mandatory for every page template):',
			'- Minimum CTA-classified sections by template_category_class: top_level 3, hub 4, nested_hub 4, child_detail 5.',
			'- The last section of every page must be CTA-classified (bottom-of-page CTA).',
			'- No two CTA-classified sections may be adjacent; at least one non-CTA section between any two CTAs.',
			'- Non-CTA section count target: 8–14 per page (below 8 = error, above 14 = warning).',
			'When recommending templates, ensure the chosen template satisfies these rules.',
		);
		return implode( "\n", $lines );
	}

	/**
	 * Hierarchy role expectations for planning.
	 *
	 * @return string
	 */
	private function get_hierarchy_role_guidance_text(): string {
		$lines = array(
			'Hierarchy roles:',
			'- top_level: Shallow path, often in main nav; home, about, contact, primary landing.',
			'- hub: Path depth 1–2; category or listing; may be in nav (e.g. /services, /products).',
			'- nested_hub: Path depth 2; sub-category or regional hub; may or may not be in nav.',
			'- child_detail: Path depth 3+ or slug suggests single item; product/service/location/profile detail.',
			'Match suggested page class to URL structure and crawl hints when available.',
		);
		return implode( "\n", $lines );
	}

	/**
	 * Suggested fixture filename base for regression (spec §58.3, Prompt 120). Tied to prompt-pack version.
	 * Used to place or discover golden fixtures under tests/fixtures/prompt-packs/.
	 *
	 * @param string $internal_key Pack internal_key (e.g. aio/build-plan-draft).
	 * @param string $version     Pack version (e.g. 1.0.0).
	 * @return string Sanitized base name without extension (e.g. aio-build-plan-draft-1.0.0).
	 */
	public static function get_suggested_fixture_basename( string $internal_key, string $version ): string {
		$safe = preg_replace( '#[^a-zA-Z0-9/-]#', '-', $internal_key );
		$safe = trim( str_replace( '/', '-', $safe ), '-' );
		$ver  = preg_replace( '#[^0-9.]+#', '', $version );
		return $safe !== '' ? $safe . '-' . $ver : 'fixture-' . $ver;
	}
}
