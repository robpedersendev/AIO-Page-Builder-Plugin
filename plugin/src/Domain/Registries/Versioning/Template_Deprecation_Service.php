<?php
/**
 * Template deprecation workflow helpers (spec §12.15, §13.13, §58.2, §61.9).
 * Validates deprecation transitions, builds deprecation blocks and decision-log/changelog payloads. Does not mutate; callers apply and persist.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Versioning;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Shared\Deprecation_Metadata;
use AIOPageBuilder\Domain\Registries\Shared\Registry_Deprecation_Service;
use AIOPageBuilder\Domain\Registries\Shared\Registry_Validation_Result;

/**
 * Deprecation workflow: validation, block building, replacement references, decision-log and changelog support payloads.
 */
final class Template_Deprecation_Service {

	/** @var Registry_Deprecation_Service */
	private Registry_Deprecation_Service $registry_deprecation;

	public function __construct( Registry_Deprecation_Service $registry_deprecation ) {
		$this->registry_deprecation = $registry_deprecation;
	}

	/**
	 * Validates section deprecation and returns validation result (reason required; replacement must be valid active section).
	 *
	 * @param int    $post_id         Section post ID.
	 * @param string $reason          Deprecation reason.
	 * @param string $replacement_key Optional replacement section internal_key.
	 * @return Registry_Validation_Result
	 */
	public function validate_section_deprecation( int $post_id, string $reason, string $replacement_key = '' ): Registry_Validation_Result {
		return $this->registry_deprecation->validate_section_deprecation( $post_id, $reason, $replacement_key );
	}

	/**
	 * Validates page template deprecation (reason required; replacement must be valid active template).
	 *
	 * @param int    $post_id         Page template post ID.
	 * @param string $reason          Deprecation reason.
	 * @param string $replacement_key Optional replacement template internal_key.
	 * @return Registry_Validation_Result
	 */
	public function validate_page_template_deprecation( int $post_id, string $reason, string $replacement_key = '' ): Registry_Validation_Result {
		return $this->registry_deprecation->validate_page_template_deprecation( $post_id, $reason, $replacement_key );
	}

	/**
	 * Returns deprecation block for section (status + deprecation metadata). Caller merges into definition and persists.
	 *
	 * @param string $reason          Deprecation reason.
	 * @param string $replacement_key Optional replacement section internal_key.
	 * @return array<string, mixed> Block to merge: status => 'deprecated', deprecation => [...], replacement_section_suggestions => [...].
	 */
	public function get_section_deprecation_block( string $reason, string $replacement_key = '' ): array {
		$block           = $this->registry_deprecation->get_section_deprecation_block( $reason, $replacement_key );
		$block['status'] = 'deprecated';
		return $block;
	}

	/**
	 * Returns deprecation block for page template. Caller merges into definition and persists.
	 *
	 * @param string $reason          Deprecation reason.
	 * @param string $replacement_key Optional replacement template internal_key.
	 * @return array<string, mixed> Block to merge: status => 'deprecated', deprecation => [...], replacement_template_refs => [...].
	 */
	public function get_page_template_deprecation_block( string $reason, string $replacement_key = '' ): array {
		$block           = $this->registry_deprecation->get_page_template_deprecation_block( $reason, $replacement_key );
		$block['status'] = 'deprecated';
		return $block;
	}

	/**
	 * Returns a short deprecation summary for display in directory/detail (section or page definition).
	 *
	 * @param array<string, mixed> $definition Section or page template definition.
	 * @param string               $type      'section' or 'page'.
	 * @return array{is_deprecated: bool, reason: string, replacement_keys: array<int, string>, deprecated_at: string}
	 */
	public function get_deprecation_summary( array $definition, string $type = 'section' ): array {
		$status = (string) ( $definition[ $type === 'page' ? Page_Template_Schema::FIELD_STATUS : Section_Schema::FIELD_STATUS ] ?? '' );
		$dep    = $definition['deprecation'] ?? array();
		if ( ! \is_array( $dep ) ) {
			$dep = array();
		}
		$is_deprecated    = $status === 'deprecated' || (bool) ( $dep['deprecated'] ?? $dep[ Deprecation_Metadata::IS_DEPRECATED ] ?? false );
		$reason           = (string) ( $dep['reason'] ?? $dep[ Deprecation_Metadata::DEPRECATED_REASON ] ?? '' );
		$deprecated_at    = (string) ( $dep[ Deprecation_Metadata::DEPRECATED_AT ] ?? $dep['deprecated_at'] ?? '' );
		$replacement_keys = array();
		if ( $type === 'page' ) {
			$refs = $definition['replacement_template_refs'] ?? array();
			if ( \is_array( $refs ) ) {
				$replacement_keys = array_values( array_filter( array_map( 'strval', $refs ) ) );
			}
			if ( empty( $replacement_keys ) && isset( $dep['replacement_template_key'] ) && (string) $dep['replacement_template_key'] !== '' ) {
				$replacement_keys = array( (string) $dep['replacement_template_key'] );
			}
		} else {
			$refs = $definition['replacement_section_suggestions'] ?? array();
			if ( \is_array( $refs ) ) {
				$replacement_keys = array_values( array_filter( array_map( 'strval', $refs ) ) );
			}
			if ( empty( $replacement_keys ) && isset( $dep['replacement_section_key'] ) && (string) $dep['replacement_section_key'] !== '' ) {
				$replacement_keys = array( (string) $dep['replacement_section_key'] );
			}
		}
		return array(
			'is_deprecated'    => $is_deprecated,
			'reason'           => $reason,
			'replacement_keys' => $replacement_keys,
			'deprecated_at'    => $deprecated_at,
		);
	}

	/**
	 * Builds a decision-log entry payload for major template-family changes (spec §61.9).
	 * Caller appends to docs/release/template-library-decision-log.md or stores elsewhere.
	 *
	 * @param string       $decision_id     Unique decision ID (e.g. "DL-001").
	 * @param string       $summary        Short summary.
	 * @param string       $rationale      Rationale text.
	 * @param string       $owner          Owner (e.g. "Technical Lead").
	 * @param string       $status         One of: proposed, approved, superseded, rejected.
	 * @param string       $effective_version Optional effective template/registry version.
	 * @param array<int, string> $impacted_section_keys Optional section keys impacted.
	 * @param array<int, string> $impacted_template_keys Optional page template keys impacted.
	 * @param string       $alternatives_considered Optional alternatives text.
	 * @return array<string, mixed> Stable payload for decision log.
	 */
	public function build_decision_log_entry(
		string $decision_id,
		string $summary,
		string $rationale,
		string $owner,
		string $status = 'approved',
		string $effective_version = '',
		array $impacted_section_keys = array(),
		array $impacted_template_keys = array(),
		string $alternatives_considered = ''
	): array {
		$date = gmdate( 'Y-m-d' );
		return array(
			'decision_id'             => \sanitize_text_field( $decision_id ),
			'date'                    => $date,
			'owner'                   => \sanitize_text_field( $owner ),
			'status'                  => \in_array( $status, array( 'proposed', 'approved', 'superseded', 'rejected' ), true ) ? $status : 'proposed',
			'summary'                 => \sanitize_text_field( $summary ),
			'rationale'               => \sanitize_textarea_field( $rationale ),
			'alternatives_considered' => \sanitize_textarea_field( $alternatives_considered ),
			'impacted_section_keys'   => array_values( array_filter( array_map( 'strval', $impacted_section_keys ) ) ),
			'impacted_template_keys'  => array_values( array_filter( array_map( 'strval', $impacted_template_keys ) ) ),
			'effective_version'       => \sanitize_text_field( $effective_version ),
		);
	}

	/**
	 * Builds a changelog snippet line for a deprecation (for insertion into docs/release/changelog.md).
	 *
	 * @param string       $template_key    Section or page template internal_key.
	 * @param string       $type            'section' or 'page'.
	 * @param string       $reason          Deprecation reason.
	 * @param array<int, string> $replacement_keys Replacement keys if any.
	 * @return string Single line or short block suitable for Deprecations section.
	 */
	public function build_changelog_snippet_for_deprecation(
		string $template_key,
		string $type,
		string $reason,
		array $replacement_keys = array()
	): string {
		$label        = $type === 'page' ? 'Page template' : 'Section template';
		$key          = \sanitize_text_field( $template_key );
		$reason_clean = \sanitize_text_field( $reason );
		$line         = "- **{$label}** `{$key}` deprecated. {$reason_clean}";
		if ( $replacement_keys !== array() ) {
			$refs  = \implode( '`, `', \array_map( 'sanitize_text_field', $replacement_keys ) );
			$line .= " Recommended replacement(s): `{$refs}`.";
		} else {
			$line .= ' No recommended replacement.';
		}
		return $line;
	}
}
