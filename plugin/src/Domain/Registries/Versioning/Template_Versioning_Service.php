<?php
/**
 * Template versioning workflow helpers (spec §12.14, §13.12, §58.2).
 * Builds version blocks and summaries for section/page template records. Does not mutate registries; callers apply and persist.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Versioning;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Version-aware update helpers: version block construction, next-version suggestion, changelog reference, display summary.
 */
final class Template_Versioning_Service {

	/**
	 * Builds a version metadata block for a section template (spec §12.14).
	 *
	 * @param string $version             Version string (e.g. "1", "2").
	 * @param bool   $stable_key_retained Whether the internal_key is unchanged.
	 * @param string $changelog_ref       Optional changelog reference (e.g. doc link or release note id).
	 * @param bool   $breaking            True if this revision is breaking for prior outputs.
	 * @return array<string, mixed> Block to set on definition[ Section_Schema::FIELD_VERSION ].
	 */
	public function build_section_version_block(
		string $version,
		bool $stable_key_retained = true,
		string $changelog_ref = '',
		bool $breaking = false
	): array {
		$block = array(
			'version'             => \sanitize_text_field( $version ),
			'stable_key_retained' => $stable_key_retained,
		);
		if ( $changelog_ref !== '' ) {
			$block['changelog_ref'] = \sanitize_text_field( $changelog_ref );
		}
		if ( $breaking ) {
			$block['breaking'] = true;
		}
		return $block;
	}

	/**
	 * Builds a version metadata block for a page template (spec §13.12).
	 *
	 * @param string $version             Version string.
	 * @param bool   $stable_key_retained Whether the internal_key is unchanged.
	 * @param string $changelog_ref       Optional changelog reference.
	 * @param bool   $breaking            True if revision is breaking (e.g. section order/meaning change).
	 * @return array<string, mixed> Block to set on definition[ Page_Template_Schema::FIELD_VERSION ].
	 */
	public function build_page_template_version_block(
		string $version,
		bool $stable_key_retained = true,
		string $changelog_ref = '',
		bool $breaking = false
	): array {
		$block = array(
			'version'             => \sanitize_text_field( $version ),
			'stable_key_retained' => $stable_key_retained,
		);
		if ( $changelog_ref !== '' ) {
			$block['changelog_ref'] = \sanitize_text_field( $changelog_ref );
		}
		if ( $breaking ) {
			$block['breaking'] = true;
		}
		return $block;
	}

	/**
	 * Suggests the next version string after a change (simple integer bump for major; caller may use minor if desired).
	 *
	 * @param string $current_version Current version string (e.g. "1", "2").
	 * @param bool   $breaking        If true, suggests integer increment; otherwise same or minor.
	 * @return string Next version string.
	 */
	public function suggest_next_version( string $current_version, bool $breaking = true ): string {
		$current_version = \sanitize_text_field( $current_version );
		if ( $current_version === '' ) {
			return '1';
		}
		if ( $breaking || ! $this->looks_numeric( $current_version ) ) {
			$n = (int) $current_version;
			return (string) ( $n >= 1 ? $n + 1 : 1 );
		}
		return $current_version;
	}

	/**
	 * Returns a short version summary for display in directory/detail (section or page definition).
	 *
	 * @param array<string, mixed> $definition Section or page template definition.
	 * @param string               $type      'section' or 'page'.
	 * @return array{version: string, stable_key_retained: bool, changelog_ref: string, breaking: bool}
	 */
	public function get_version_summary( array $definition, string $type = 'section' ): array {
		$field        = $type === 'page' ? Page_Template_Schema::FIELD_VERSION : Section_Schema::FIELD_VERSION;
		$version_data = $definition[ $field ] ?? array();
		if ( ! \is_array( $version_data ) ) {
			$version_data = array();
		}
		$version = isset( $version_data['version'] ) ? (string) $version_data['version'] : '1';
		return array(
			'version'             => $version,
			'stable_key_retained' => (bool) ( $version_data['stable_key_retained'] ?? true ),
			'changelog_ref'       => (string) ( $version_data['changelog_ref'] ?? '' ),
			'breaking'            => (bool) ( $version_data['breaking'] ?? false ),
		);
	}

	private function looks_numeric( string $s ): bool {
		return \preg_match( '#^\d+$#', $s ) === 1;
	}
}
