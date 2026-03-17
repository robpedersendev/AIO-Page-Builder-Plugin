<?php
/**
 * Resolves industry-aware preview context for a page template detail screen (Prompt 383, industry-admin-screen-contract).
 * Read-only: recommendation fit, composed one-pager, hierarchy/LPagery posture, substitute suggestions. Safe fallback when no industry.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\ViewModels\Industry\Conversion_Goal_Preview_Influence_View_Model;
use AIOPageBuilder\Admin\ViewModels\Industry\Industry_Subtype_Preview_Influence_View_Model;
use AIOPageBuilder\Admin\ViewModels\PageTemplates\Industry_Page_Template_Preview_View_Model;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Composer;
use AIOPageBuilder\Domain\Industry\Docs\Subtype_Page_OnePager_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Subtype_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Builds industry preview view model for a single page template. Uses profile, recommendation resolver, one-pager composer, optional substitute engine.
 */
final class Industry_Page_Template_Preview_Resolver {

	/** @var Industry_Profile_Repository|null */
	private ?Industry_Profile_Repository $profile_repository;

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	/** @var Industry_Page_Template_Recommendation_Resolver */
	private Industry_Page_Template_Recommendation_Resolver $recommendation_resolver;

	/** @var Industry_Page_OnePager_Composer */
	private Industry_Page_OnePager_Composer $one_pager_composer;

	/** @var Industry_Substitute_Suggestion_Engine|null */
	private $substitute_engine;

	/**
	 * @param Industry_Profile_Repository|null              $profile_repository
	 * @param Industry_Pack_Registry|null                   $pack_registry
	 * @param Industry_Page_Template_Recommendation_Resolver $recommendation_resolver
	 * @param Industry_Page_OnePager_Composer               $one_pager_composer
	 * @param Industry_Substitute_Suggestion_Engine|null    $substitute_engine
	 */
	public function __construct(
		?Industry_Profile_Repository $profile_repository,
		?Industry_Pack_Registry $pack_registry,
		Industry_Page_Template_Recommendation_Resolver $recommendation_resolver,
		Industry_Page_OnePager_Composer $one_pager_composer,
		?Industry_Substitute_Suggestion_Engine $substitute_engine = null
	) {
		$this->profile_repository     = $profile_repository;
		$this->pack_registry          = $pack_registry;
		$this->recommendation_resolver = $recommendation_resolver;
		$this->one_pager_composer     = $one_pager_composer;
		$this->substitute_engine      = $substitute_engine;
	}

	/**
	 * Resolves industry-aware preview view model for the given template. Safe when no profile or invalid key.
	 *
	 * @param string               $template_key        Page template internal_key.
	 * @param array<string, mixed>  $template_definition Single template definition (internal_key, template_family, etc.).
	 * @param array<int, array<string, mixed>> $all_templates Optional. When provided with substitute_engine, substitute suggestions are filled.
	 * @return Industry_Page_Template_Preview_View_Model
	 */
	public function resolve( string $template_key, array $template_definition, array $all_templates = array() ): Industry_Page_Template_Preview_View_Model {
		$template_key = \sanitize_key( $template_key );
		if ( $this->profile_repository === null ) {
			return $this->empty_view_model();
		}
		$profile      = $this->profile_repository->get_profile();
		$primary      = isset( $profile['primary_industry_key'] ) && \is_string( $profile['primary_industry_key'] )
			? \trim( $profile['primary_industry_key'] )
			: '';

		if ( $primary === '' ) {
			return $this->empty_view_model();
		}

		$primary_pack = null;
		if ( $this->pack_registry !== null ) {
			$primary_pack = $this->pack_registry->get( $primary );
		}

		$templates_for_resolver = ! empty( $all_templates ) ? $all_templates : array( $template_definition );
		$result                 = $this->recommendation_resolver->resolve( $profile, $primary_pack, $templates_for_resolver, array() );
		$item                   = $this->get_item_by_key( $result, $template_key );

		$fit              = $item['fit_classification'] ?? Industry_Page_Template_Recommendation_Resolver::FIT_NEUTRAL;
		$hierarchy_fit    = isset( $item['hierarchy_fit'] ) && \is_string( $item['hierarchy_fit'] ) ? $item['hierarchy_fit'] : '';
		$lpagery_fit      = isset( $item['lpagery_fit'] ) && \is_string( $item['lpagery_fit'] ) ? $item['lpagery_fit'] : '';
		$warning_flags    = isset( $item['warning_flags'] ) && \is_array( $item['warning_flags'] ) ? array_values( array_filter( array_map( 'strval', $item['warning_flags'] ) ) ) : array();
		$explanation_reasons = isset( $item['explanation_reasons'] ) && \is_array( $item['explanation_reasons'] ) ? array_values( array_filter( array_map( 'strval', $item['explanation_reasons'] ) ) ) : array();

		$subtype_key = isset( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ) && \is_string( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
			? \trim( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
			: '';
		$subtype_context = array( 'primary_industry_key' => $primary, 'industry_subtype_key' => $subtype_key, 'resolved_subtype' => null, 'has_valid_subtype' => false );
		if ( $this->subtype_resolver !== null ) {
			$subtype_context = $this->subtype_resolver->resolve();
			$subtype_key = $subtype_context['industry_subtype_key'] ?? $subtype_key;
		}
		$composed_result = $this->one_pager_composer->compose( $template_key, $primary, $subtype_key );
		$composed_one_pager = $composed_result->get_composed_onepager();
		$composed_for_view  = array(
			'hierarchy_hints'   => $composed_one_pager['hierarchy_hints'] ?? '',
			'cta_strategy'      => $composed_one_pager['cta_strategy'] ?? '',
			'lpagery_seo_notes' => $composed_one_pager['lpagery_seo_notes'] ?? '',
			'compliance_cautions' => $composed_one_pager['compliance_cautions'] ?? '',
			'overlay_applied'   => $composed_result->is_overlay_applied(),
		);

		$substitute_suggestions = array();
		if ( $this->substitute_engine !== null && ! empty( $all_templates ) ) {
			$substitute_suggestions = $this->substitute_engine->suggest_template_substitutes(
				$template_key,
				$fit,
				$result,
				$all_templates,
				5
			);
		}

		$compliance_warnings = $composed_result->get_compliance_warnings();

		$subtype_influence = $this->build_subtype_influence_page( $subtype_context, $template_key );

		$goal_influence = $this->build_goal_influence_page( $profile, $template_key );

		return new Industry_Page_Template_Preview_View_Model(
			true,
			$primary,
			$fit,
			$hierarchy_fit,
			$lpagery_fit,
			$composed_for_view,
			$substitute_suggestions,
			$warning_flags,
			$explanation_reasons,
			$compliance_warnings,
			$subtype_influence,
			$goal_influence
		);
	}

	/**
	 * Builds subtype influence view model for page template (onepager refinement only).
	 *
	 * @param array{primary_industry_key: string, industry_subtype_key: string, resolved_subtype: array<string, mixed>|null, has_valid_subtype: bool} $subtype_context
	 * @param string $template_key
	 * @return array<string, mixed>
	 */
	private function build_subtype_influence_page( array $subtype_context, string $template_key ): array {
		$has_valid = ! empty( $subtype_context['has_valid_subtype'] );
		$subtype_key = isset( $subtype_context['industry_subtype_key'] ) && \is_string( $subtype_context['industry_subtype_key'] )
			? \trim( $subtype_context['industry_subtype_key'] )
			: '';
		if ( ! $has_valid || $subtype_key === '' ) {
			return Industry_Subtype_Preview_Influence_View_Model::none()->to_array();
		}
		$resolved = $subtype_context['resolved_subtype'] ?? null;
		$label = '';
		$summary = '';
		if ( \is_array( $resolved ) && $this->subtype_registry !== null ) {
			$label = isset( $resolved[ Industry_Subtype_Registry::FIELD_LABEL ] ) && \is_string( $resolved[ Industry_Subtype_Registry::FIELD_LABEL ] )
				? \trim( $resolved[ Industry_Subtype_Registry::FIELD_LABEL ] )
				: \ucfirst( \str_replace( array( '_', '-' ), ' ', $subtype_key ) );
			$summary = isset( $resolved[ Industry_Subtype_Registry::FIELD_SUMMARY ] ) && \is_string( $resolved[ Industry_Subtype_Registry::FIELD_SUMMARY ] )
				? \trim( $resolved[ Industry_Subtype_Registry::FIELD_SUMMARY ] )
				: '';
		} else {
			$label = \ucfirst( \str_replace( array( '_', '-' ), ' ', $subtype_key ) );
		}
		$caution_notes = array();
		if ( $summary !== '' ) {
			$caution_notes[] = $summary;
		}
		$onepager_refinement = false;
		if ( $this->subtype_onepager_overlay_registry !== null ) {
			$overlay = $this->subtype_onepager_overlay_registry->get( $subtype_key, $template_key );
			if ( $overlay !== null && \is_array( $overlay ) ) {
				$status = isset( $overlay[ Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS ] ) && \is_string( $overlay[ Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS ] )
					? $overlay[ Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS ]
					: '';
				$onepager_refinement = $status === Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE;
			}
		}
		$vm = new Industry_Subtype_Preview_Influence_View_Model(
			true,
			$subtype_key,
			$label,
			$summary,
			false,
			$onepager_refinement,
			$caution_notes,
			''
		);
		return $vm->to_array();
	}

	/**
	 * Builds conversion-goal influence for page template preview (Prompt 513). Fallback when no goal.
	 *
	 * @param array<string, mixed> $profile Normalized industry profile.
	 * @param string               $template_key Page template internal_key.
	 * @return array<string, mixed>
	 */
	private function build_goal_influence_page( array $profile, string $template_key ): array {
		$goal_key = isset( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] ) && \is_string( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] )
			? \trim( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] )
			: '';
		if ( $goal_key === '' ) {
			return Conversion_Goal_Preview_Influence_View_Model::none()->to_array();
		}
		$vm = new Conversion_Goal_Preview_Influence_View_Model(
			true,
			$goal_key,
			Conversion_Goal_Preview_Influence_View_Model::goal_key_to_label( $goal_key ),
			false,
			false,
			array(),
			''
		);
		return $vm->to_array();
	}

	/**
	 * @param Industry_Page_Template_Recommendation_Result $result
	 * @param string $template_key
	 * @return array{page_template_key: string, score: int, fit_classification: string, explanation_reasons: array, industry_source_refs: array, hierarchy_fit: string, lpagery_fit: string, warning_flags: array}
	 */
	private function get_item_by_key( Industry_Page_Template_Recommendation_Result $result, string $template_key ): array {
		foreach ( $result->get_items() as $item ) {
			$key = $item['page_template_key'] ?? '';
			if ( $key === $template_key ) {
				return $item;
			}
		}
		return array(
			'page_template_key'    => $template_key,
			'score'                => 0,
			'fit_classification'   => Industry_Page_Template_Recommendation_Resolver::FIT_NEUTRAL,
			'explanation_reasons'  => array(),
			'industry_source_refs' => array(),
			'hierarchy_fit'        => '',
			'lpagery_fit'          => '',
			'warning_flags'        => array(),
		);
	}

	private function empty_view_model(): Industry_Page_Template_Preview_View_Model {
		return new Industry_Page_Template_Preview_View_Model(
			false,
			'',
			Industry_Page_Template_Recommendation_Resolver::FIT_NEUTRAL,
			'',
			'',
			array(),
			array(),
			array(),
			array(),
			array(),
			Industry_Subtype_Preview_Influence_View_Model::none()->to_array(),
			Conversion_Goal_Preview_Influence_View_Model::none()->to_array()
		);
	}
}
