<?php
/**
 * Assembles normalized input artifact from profile, crawl, registry, and goal (spec §27, §29.1, ai-input-artifact-schema).
 * Bounded and reproducible; no secrets. Validates against Input_Artifact_Schema.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\InputArtifacts;

defined( 'ABSPATH' ) || exit;

/**
 * Builds input_artifact shape from approved sources. Rejects when required keys missing or prohibited keys present.
 */
final class Input_Artifact_Builder {

	public const SCHEMA_VERSION = '1';

	/** @var array<int, string> */
	private array $last_validation_errors = array();

	/**
	 * Builds normalized input artifact. Call get_last_validation_errors() when null is returned.
	 *
	 * @param string               $artifact_id    Unique artifact id (e.g. UUID).
	 * @param array<string, mixed> $prompt_pack_ref Must contain internal_key and version.
	 * @param array<string, mixed> $options        Optional: profile, crawl, registry, goal, planning_guidance, attachment_manifest, redaction, inclusion_rationale, compatibility.
	 * @return array<string, mixed>|null Input artifact or null on validation failure.
	 */
	public function build( string $artifact_id, array $prompt_pack_ref, array $options = array() ): ?array {
		$this->last_validation_errors = array();

		$internal_key = $prompt_pack_ref[ Input_Artifact_Schema::PROMPT_PACK_REF_INTERNAL_KEY ] ?? null;
		$version      = $prompt_pack_ref[ Input_Artifact_Schema::PROMPT_PACK_REF_VERSION ] ?? null;
		if ( $internal_key === null || $version === null || $internal_key === '' || $version === '' ) {
			$this->last_validation_errors[] = 'prompt_pack_ref must contain internal_key and version';
			return null;
		}

		$redaction = $options['redaction'] ?? array();
		if ( ! is_array( $redaction ) ) {
			$redaction = array( Input_Artifact_Schema::REDACTION_APPLIED => false );
		}
		if ( ! array_key_exists( Input_Artifact_Schema::REDACTION_APPLIED, $redaction ) ) {
			$redaction[ Input_Artifact_Schema::REDACTION_APPLIED ] = false;
		}

		$artifact = array(
			Input_Artifact_Schema::ROOT_ARTIFACT_ID      => $artifact_id,
			Input_Artifact_Schema::ROOT_SCHEMA_VERSION   => self::SCHEMA_VERSION,
			Input_Artifact_Schema::ROOT_CREATED_AT       => $this->iso8601_now(),
			Input_Artifact_Schema::ROOT_PROMPT_PACK_REF => array(
				Input_Artifact_Schema::PROMPT_PACK_REF_INTERNAL_KEY => $internal_key,
				Input_Artifact_Schema::PROMPT_PACK_REF_VERSION       => $version,
			),
			Input_Artifact_Schema::ROOT_REDACTION => $redaction,
		);

		if ( isset( $options['profile'] ) && is_array( $options['profile'] ) ) {
			$artifact[ Input_Artifact_Schema::ROOT_PROFILE ] = $options['profile'];
		}
		if ( isset( $options['crawl'] ) && is_array( $options['crawl'] ) ) {
			$artifact[ Input_Artifact_Schema::ROOT_CRAWL ] = $options['crawl'];
		}
		if ( isset( $options['registry'] ) && is_array( $options['registry'] ) ) {
			$artifact[ Input_Artifact_Schema::ROOT_REGISTRY ] = $options['registry'];
		}
		if ( array_key_exists( 'goal', $options ) ) {
			$artifact[ Input_Artifact_Schema::ROOT_GOAL ] = $options['goal'];
		}
		if ( isset( $options['planning_guidance'] ) && is_array( $options['planning_guidance'] ) ) {
			$artifact[ Input_Artifact_Schema::ROOT_PLANNING_GUIDANCE ] = $options['planning_guidance'];
		}
		if ( isset( $options['attachment_manifest'] ) && is_array( $options['attachment_manifest'] ) ) {
			$artifact[ Input_Artifact_Schema::ROOT_ATTACHMENT_MANIFEST ] = $options['attachment_manifest'];
		}
		if ( isset( $options['inclusion_rationale'] ) ) {
			$artifact[ Input_Artifact_Schema::ROOT_INCLUSION_RATIONALE ] = $options['inclusion_rationale'];
		}
		if ( isset( $options['compatibility'] ) && is_array( $options['compatibility'] ) ) {
			$artifact[ Input_Artifact_Schema::ROOT_COMPATIBILITY ] = $options['compatibility'];
		}

		$prohibited = Input_Artifact_Schema::find_prohibited_keys_in_array( $artifact );
		if ( $prohibited !== array() ) {
			$this->last_validation_errors[] = 'prohibited_keys: ' . implode( ', ', $prohibited );
			return null;
		}

		foreach ( Input_Artifact_Schema::required_root_keys() as $key ) {
			if ( ! array_key_exists( $key, $artifact ) ) {
				$this->last_validation_errors[] = 'missing_required: ' . $key;
				return null;
			}
		}

		return $artifact;
	}

	/**
	 * Returns validation errors from the last build() call.
	 *
	 * @return array<int, string>
	 */
	public function get_last_validation_errors(): array {
		return $this->last_validation_errors;
	}

	private function iso8601_now(): string {
		return gmdate( 'Y-m-d\TH:i:s\Z' );
	}
}
