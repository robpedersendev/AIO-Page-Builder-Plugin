<?php
/**
 * Builds provider-ready normalized prompt package from selected pack and input artifact (spec §27, §29.2, §59.8).
 * Resolves placeholders; produces raw-prompt capture-ready output. Rejects incomplete assembly.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\PromptPacks;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\InputArtifacts\Input_Artifact_Schema;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Assembles system_prompt and user_message from pack segments and resolved placeholders.
 */
final class Normalized_Prompt_Package_Builder {

	/**
	 * Builds normalized prompt package. Fails when required placeholders are missing.
	 * Optional industry_overlay, subtype_overlay, and goal_overlay are appended to system prompt when present.
	 *
	 * @param array<string, mixed> $selected_pack  Full prompt pack definition (from registry).
	 * @param array<string, mixed> $input_artifact Built input artifact (from Input_Artifact_Builder).
	 * @param array<string, mixed> $options        Optional: industry_overlay, subtype_overlay, goal_overlay (conversion_goal_guidance_text, etc.).
	 * @return Prompt_Package_Result Success with package or failure with validation_errors.
	 */
	public function build( array $selected_pack, array $input_artifact, array $options = array() ): Prompt_Package_Result {
		$errors = array();

		$internal_key = (string) ( $selected_pack[ Prompt_Pack_Schema::ROOT_INTERNAL_KEY ] ?? '' );
		$version      = (string) ( $selected_pack[ Prompt_Pack_Schema::ROOT_VERSION ] ?? '' );
		if ( $internal_key === '' || $version === '' ) {
			$errors[] = 'selected_pack missing internal_key or version';
			Named_Debug_Log::event( Named_Debug_Log_Event::PROMPT_PACKAGE_BUILD_FAIL, 'reason=pack_identity errors=' . (string) \wp_json_encode( $errors ) );
			return new Prompt_Package_Result( false, null, $errors, $selected_pack );
		}

		$segments = $selected_pack[ Prompt_Pack_Schema::ROOT_SEGMENTS ] ?? array();
		if ( ! is_array( $segments ) || empty( $segments[ Prompt_Pack_Schema::SEGMENT_SYSTEM_BASE ] ) ) {
			$errors[] = 'selected_pack missing required segments.system_base';
			Named_Debug_Log::event( Named_Debug_Log_Event::PROMPT_PACKAGE_BUILD_FAIL, 'reason=segments key=' . $internal_key . ' errors=' . (string) \wp_json_encode( $errors ) );
			return new Prompt_Package_Result( false, null, $errors, $selected_pack );
		}

		$placeholder_values = $this->resolve_placeholders( $selected_pack, $input_artifact, $errors );
		if ( $errors !== array() ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::PROMPT_PACKAGE_BUILD_FAIL, 'reason=placeholders key=' . $internal_key . ' errors=' . (string) \wp_json_encode( $errors ) );
			return new Prompt_Package_Result( false, null, $errors, $selected_pack );
		}

		$system_parts        = array();
		$user_parts          = array();
		$system_segment_keys = array(
			Prompt_Pack_Schema::SEGMENT_SYSTEM_BASE,
			Prompt_Pack_Schema::SEGMENT_ROLE_FRAMING,
			Prompt_Pack_Schema::SEGMENT_SAFETY_INSTRUCTIONS,
			Prompt_Pack_Schema::SEGMENT_SCHEMA_REQUIREMENTS,
			Prompt_Pack_Schema::SEGMENT_NORMALIZATION_EXPECTATIONS,
			Prompt_Pack_Schema::SEGMENT_TEMPLATE_FAMILY_GUIDANCE,
			Prompt_Pack_Schema::SEGMENT_CTA_LAW_GUIDANCE,
			Prompt_Pack_Schema::SEGMENT_HIERARCHY_ROLE_GUIDANCE,
			Prompt_Pack_Schema::SEGMENT_GREENFIELD_PLANNING_DEPTH,
			Prompt_Pack_Schema::SEGMENT_PROVIDER_NOTES,
		);
		foreach ( $system_segment_keys as $seg_key ) {
			$content = $this->get_segment_content( $segments, $seg_key );
			if ( $content !== '' ) {
				$content        = $this->substitute_placeholders( $content, $placeholder_values );
				$system_parts[] = $content;
			}
		}
		foreach ( array( Prompt_Pack_Schema::SEGMENT_PLANNING_INSTRUCTIONS, Prompt_Pack_Schema::SEGMENT_SITE_ANALYSIS_INSTRUCTIONS ) as $seg_key ) {
			$content = $this->get_segment_content( $segments, $seg_key );
			if ( $content !== '' ) {
				$content      = $this->substitute_placeholders( $content, $placeholder_values );
				$user_parts[] = $content;
			}
		}

		$industry_overlay = isset( $options['industry_overlay'] ) && is_array( $options['industry_overlay'] ) ? $options['industry_overlay'] : null;
		if ( $industry_overlay !== null && isset( $industry_overlay['industry_guidance_text'] ) && is_string( $industry_overlay['industry_guidance_text'] ) && trim( $industry_overlay['industry_guidance_text'] ) !== '' ) {
			$system_parts[] = "## Industry guidance\n\n" . trim( $industry_overlay['industry_guidance_text'] );
		}
		$subtype_overlay = isset( $options['subtype_overlay'] ) && is_array( $options['subtype_overlay'] ) ? $options['subtype_overlay'] : null;
		if ( $subtype_overlay !== null && isset( $subtype_overlay['subtype_guidance_text'] ) && is_string( $subtype_overlay['subtype_guidance_text'] ) && trim( $subtype_overlay['subtype_guidance_text'] ) !== '' ) {
			$system_parts[] = "## Subtype guidance\n\n" . trim( $subtype_overlay['subtype_guidance_text'] );
		}
		$goal_overlay = isset( $options['goal_overlay'] ) && is_array( $options['goal_overlay'] ) ? $options['goal_overlay'] : null;
		if ( $goal_overlay !== null && isset( $goal_overlay['conversion_goal_guidance_text'] ) && is_string( $goal_overlay['conversion_goal_guidance_text'] ) && trim( $goal_overlay['conversion_goal_guidance_text'] ) !== '' ) {
			$system_parts[] = "## Conversion goal guidance\n\n" . trim( $goal_overlay['conversion_goal_guidance_text'] );
		}

		$system_prompt = implode( "\n\n", $system_parts );
		$user_message  = implode( "\n\n", $user_parts );
		if ( $user_message === '' ) {
			$user_message = $this->substitute_placeholders( 'Use the provided context.', $placeholder_values );
		}

		$schema_target_ref = (string) ( $selected_pack[ Prompt_Pack_Schema::ROOT_SCHEMA_TARGET_REF ] ?? '' );
		$repair_prompt_ref = (string) ( $selected_pack[ Prompt_Pack_Schema::ROOT_REPAIR_PROMPT_REF ] ?? '' );

		$package = array(
			'prompt_pack_ref'          => array(
				Input_Artifact_Schema::PROMPT_PACK_REF_INTERNAL_KEY => $internal_key,
				Input_Artifact_Schema::PROMPT_PACK_REF_VERSION       => $version,
			),
			'schema_target_ref'        => $schema_target_ref,
			'repair_prompt_ref'        => $repair_prompt_ref,
			'system_prompt'            => $system_prompt,
			'user_message'             => $user_message,
			'input_artifact_id'        => $input_artifact[ Input_Artifact_Schema::ROOT_ARTIFACT_ID ] ?? '',
			'input_artifact'           => $input_artifact,
			'raw_prompt_capture_ready' => array(
				'system_prompt'     => $system_prompt,
				'user_message'      => $user_message,
				'prompt_pack_ref'   => array(
					'internal_key' => $internal_key,
					'version'      => $version,
				),
				'schema_target_ref' => $schema_target_ref,
			),
		);

		Named_Debug_Log::event( Named_Debug_Log_Event::PROMPT_PACKAGE_BUILD_OK, 'key=' . $internal_key . ' version=' . $version );
		return new Prompt_Package_Result( true, $package, array(), $selected_pack );
	}

	/**
	 * Resolves placeholder values from input artifact. Pushes errors for required-but-missing.
	 *
	 * @param array<string, mixed> $selected_pack
	 * @param array<string, mixed> $input_artifact
	 * @param array<int, string>   $errors
	 * @return array<string, string> Placeholder name => value.
	 */
	private function resolve_placeholders( array $selected_pack, array $input_artifact, array &$errors ): array {
		$rules = $selected_pack[ Prompt_Pack_Schema::ROOT_PLACEHOLDER_RULES ] ?? array();
		if ( ! is_array( $rules ) ) {
			$rules = array();
		}

		$profile_summary   = $this->summarize_section( $input_artifact[ Input_Artifact_Schema::ROOT_PROFILE ] ?? array() );
		$crawl_summary     = $this->summarize_section( $input_artifact[ Input_Artifact_Schema::ROOT_CRAWL ] ?? array() );
		$registry_summary  = $this->summarize_section( $input_artifact[ Input_Artifact_Schema::ROOT_REGISTRY ] ?? array() );
		$goal_text         = $this->extract_goal( $input_artifact );
		$planning_guidance = $this->extract_planning_guidance( $input_artifact );
		$industry_summary  = $this->summarize_section( $input_artifact[ Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT ] ?? array() );

		$values = array(
			'{{profile_summary}}'          => $profile_summary,
			'{{crawl_summary}}'            => $crawl_summary,
			'{{registry_summary}}'         => $registry_summary,
			'{{goal}}'                     => $goal_text,
			'{{goal_or_intent}}'           => $goal_text,
			'{{industry_context_summary}}' => $industry_summary,
			'{{template_family_guidance}}' => $planning_guidance['template_family_guidance'],
			'{{cta_law_rules}}'            => $planning_guidance['cta_law_rules'],
			'{{hierarchy_role_guidance}}'  => $planning_guidance['hierarchy_role_guidance'],
		);

		foreach ( $rules as $placeholder => $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}
			$required = ! empty( $rule['required'] );
			$source   = $rule['source'] ?? '';
			$max_len  = isset( $rule['max_length'] ) ? (int) $rule['max_length'] : 0;
			$key      = '{{' . ( is_string( $placeholder ) ? $placeholder : '' ) . '}}';
			if ( $key === '{{}}' ) {
				continue;
			}
			$val = $values[ $key ] ?? '';
			if ( $source === Prompt_Pack_Schema::PLACEHOLDER_SOURCE_PROFILE ) {
				$val = $profile_summary;
			} elseif ( $source === Prompt_Pack_Schema::PLACEHOLDER_SOURCE_CRAWL ) {
				$val = $crawl_summary;
			} elseif ( $source === Prompt_Pack_Schema::PLACEHOLDER_SOURCE_REGISTRY ) {
				$val = $registry_summary;
			} elseif ( $source === Prompt_Pack_Schema::PLACEHOLDER_SOURCE_GOAL ) {
				$val = $goal_text;
			} elseif ( $source === Prompt_Pack_Schema::PLACEHOLDER_SOURCE_PLANNING_GUIDANCE ) {
				$val = $this->resolve_planning_guidance_placeholder( $placeholder, $planning_guidance );
			}
			if ( $max_len > 0 && strlen( $val ) > $max_len ) {
				$val = substr( $val, 0, $max_len ) . '...';
			}
			$values[ $key ] = $val;
			if ( $required && trim( $val ) === '' ) {
				$errors[] = 'required_placeholder_empty: ' . $placeholder;
			}
		}

		return $values;
	}

	private function summarize_section( $section ): string {
		if ( ! is_array( $section ) ) {
			return is_string( $section ) ? $section : '';
		}
		$json = function_exists( 'wp_json_encode' ) ? wp_json_encode( $section ) : json_encode( $section );
		return $json !== false ? $json : '';
	}

	private function extract_goal( array $input_artifact ): string {
		$goal = $input_artifact[ Input_Artifact_Schema::ROOT_GOAL ] ?? null;
		if ( is_string( $goal ) ) {
			return $goal;
		}
		if ( is_array( $goal ) && isset( $goal['text'] ) ) {
			return (string) $goal['text'];
		}
		return '';
	}

	/**
	 * Extracts planning_guidance from artifact (Prompt 210). Returns empty strings when absent.
	 *
	 * @param array<string, mixed> $input_artifact
	 * @return array{template_family_guidance: string, cta_law_rules: string, hierarchy_role_guidance: string}
	 */
	private function extract_planning_guidance( array $input_artifact ): array {
		$block = $input_artifact[ Input_Artifact_Schema::ROOT_PLANNING_GUIDANCE ] ?? null;
		if ( ! is_array( $block ) ) {
			return array(
				'template_family_guidance' => '',
				'cta_law_rules'            => '',
				'hierarchy_role_guidance'  => '',
			);
		}
		return array(
			'template_family_guidance' => (string) ( $block['template_family_guidance'] ?? '' ),
			'cta_law_rules'            => (string) ( $block['cta_law_rules'] ?? '' ),
			'hierarchy_role_guidance'  => (string) ( $block['hierarchy_role_guidance'] ?? '' ),
		);
	}

	/**
	 * Resolves a single planning_guidance placeholder by name.
	 *
	 * @param string                                                                                          $placeholder Placeholder name (e.g. template_family_guidance).
	 * @param array{template_family_guidance: string, cta_law_rules: string, hierarchy_role_guidance: string} $planning_guidance
	 * @return string
	 */
	private function resolve_planning_guidance_placeholder( string $placeholder, array $planning_guidance ): string {
		if ( isset( $planning_guidance[ $placeholder ] ) && is_string( $planning_guidance[ $placeholder ] ) ) {
			return $planning_guidance[ $placeholder ];
		}
		return '';
	}

	private function get_segment_content( array $segments, string $key ): string {
		$v = $segments[ $key ] ?? null;
		if ( is_string( $v ) ) {
			return trim( $v );
		}
		if ( is_array( $v ) && isset( $v['body'] ) ) {
			return trim( (string) $v['body'] );
		}
		return '';
	}

	/**
	 * @param string                $content
	 * @param array<string, string> $values
	 * @return string
	 */
	private function substitute_placeholders( string $content, array $values ): string {
		foreach ( $values as $placeholder => $value ) {
			$content = str_replace( $placeholder, $value, $content );
		}
		return $content;
	}
}
