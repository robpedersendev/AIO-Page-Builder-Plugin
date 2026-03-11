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

/**
 * Assembles system_prompt and user_message from pack segments and resolved placeholders.
 */
final class Normalized_Prompt_Package_Builder {

	/**
	 * Builds normalized prompt package. Fails when required placeholders are missing.
	 *
	 * @param array<string, mixed> $selected_pack  Full prompt pack definition (from registry).
	 * @param array<string, mixed> $input_artifact Built input artifact (from Input_Artifact_Builder).
	 * @return Prompt_Package_Result Success with package or failure with validation_errors.
	 */
	public function build( array $selected_pack, array $input_artifact ): Prompt_Package_Result {
		$errors = array();

		$internal_key = (string) ( $selected_pack[ Prompt_Pack_Schema::ROOT_INTERNAL_KEY ] ?? '' );
		$version      = (string) ( $selected_pack[ Prompt_Pack_Schema::ROOT_VERSION ] ?? '' );
		if ( $internal_key === '' || $version === '' ) {
			$errors[] = 'selected_pack missing internal_key or version';
			return new Prompt_Package_Result( false, null, $errors, $selected_pack );
		}

		$segments = $selected_pack[ Prompt_Pack_Schema::ROOT_SEGMENTS ] ?? array();
		if ( ! is_array( $segments ) || empty( $segments[ Prompt_Pack_Schema::SEGMENT_SYSTEM_BASE ] ) ) {
			$errors[] = 'selected_pack missing required segments.system_base';
			return new Prompt_Package_Result( false, null, $errors, $selected_pack );
		}

		$placeholder_values = $this->resolve_placeholders( $selected_pack, $input_artifact, $errors );
		if ( $errors !== array() ) {
			return new Prompt_Package_Result( false, null, $errors, $selected_pack );
		}

		$system_parts = array();
		$user_parts   = array();
		foreach ( array( Prompt_Pack_Schema::SEGMENT_SYSTEM_BASE, Prompt_Pack_Schema::SEGMENT_ROLE_FRAMING, Prompt_Pack_Schema::SEGMENT_SAFETY_INSTRUCTIONS, Prompt_Pack_Schema::SEGMENT_SCHEMA_REQUIREMENTS, Prompt_Pack_Schema::SEGMENT_NORMALIZATION_EXPECTATIONS, Prompt_Pack_Schema::SEGMENT_PROVIDER_NOTES ) as $seg_key ) {
			$content = $this->get_segment_content( $segments, $seg_key );
			if ( $content !== '' ) {
				$content = $this->substitute_placeholders( $content, $placeholder_values );
				$system_parts[] = $content;
			}
		}
		foreach ( array( Prompt_Pack_Schema::SEGMENT_PLANNING_INSTRUCTIONS, Prompt_Pack_Schema::SEGMENT_SITE_ANALYSIS_INSTRUCTIONS ) as $seg_key ) {
			$content = $this->get_segment_content( $segments, $seg_key );
			if ( $content !== '' ) {
				$content = $this->substitute_placeholders( $content, $placeholder_values );
				$user_parts[] = $content;
			}
		}

		$system_prompt = implode( "\n\n", $system_parts );
		$user_message  = implode( "\n\n", $user_parts );
		if ( $user_message === '' ) {
			$user_message = $this->substitute_placeholders( 'Use the provided context.', $placeholder_values );
		}

		$schema_target_ref = (string) ( $selected_pack[ Prompt_Pack_Schema::ROOT_SCHEMA_TARGET_REF ] ?? '' );
		$repair_prompt_ref = (string) ( $selected_pack[ Prompt_Pack_Schema::ROOT_REPAIR_PROMPT_REF ] ?? '' );

		$package = array(
			'prompt_pack_ref'         => array(
				Input_Artifact_Schema::PROMPT_PACK_REF_INTERNAL_KEY => $internal_key,
				Input_Artifact_Schema::PROMPT_PACK_REF_VERSION       => $version,
			),
			'schema_target_ref'       => $schema_target_ref,
			'repair_prompt_ref'       => $repair_prompt_ref,
			'system_prompt'           => $system_prompt,
			'user_message'            => $user_message,
			'input_artifact_id'       => $input_artifact[ Input_Artifact_Schema::ROOT_ARTIFACT_ID ] ?? '',
			'input_artifact'          => $input_artifact,
			'raw_prompt_capture_ready' => array(
				'system_prompt' => $system_prompt,
				'user_message'  => $user_message,
				'prompt_pack_ref' => array( 'internal_key' => $internal_key, 'version' => $version ),
				'schema_target_ref' => $schema_target_ref,
			),
		);

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

		$profile_summary = $this->summarize_section( $input_artifact[ Input_Artifact_Schema::ROOT_PROFILE ] ?? array() );
		$crawl_summary   = $this->summarize_section( $input_artifact[ Input_Artifact_Schema::ROOT_CRAWL ] ?? array() );
		$registry_summary = $this->summarize_section( $input_artifact[ Input_Artifact_Schema::ROOT_REGISTRY ] ?? array() );
		$goal_text       = $this->extract_goal( $input_artifact );

		$values = array(
			'{{profile_summary}}'   => $profile_summary,
			'{{crawl_summary}}'     => $crawl_summary,
			'{{registry_summary}}'  => $registry_summary,
			'{{goal}}'              => $goal_text,
			'{{goal_or_intent}}'    => $goal_text,
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
		$json = wp_json_encode( $section );
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
	 * @param string               $content
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
