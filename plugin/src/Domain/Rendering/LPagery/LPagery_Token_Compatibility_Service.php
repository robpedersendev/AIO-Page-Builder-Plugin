<?php
/**
 * Optional LPagery token compatibility layer (spec §7.4, §20.7, §35, §59.5).
 *
 * Bounded mapping helpers: core token (group, name) <-> LPagery-compatible key.
 * Does not change core token storage or selector contracts. Additive only; reversible where supported.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\LPagery;

defined( 'ABSPATH' ) || exit;

/**
 * Maps canonical design tokens to/from LPagery-compatible keys. Exposes diagnostics for unsupported cases.
 */
final class LPagery_Token_Compatibility_Service {

	/** Allowed token groups (aligned with Token_Set_Job_Service / spec §35). */
	private const ALLOWED_GROUPS = array( 'color', 'typography', 'spacing', 'radius', 'shadow', 'component' );

	/** LPagery key convention: group.name (reversible). */
	private const LPAGERY_KEY_SEPARATOR = '.';

	/** Max length for token name segment in LPagery key (bounded). */
	private const MAX_NAME_LENGTH = 128;

	/**
	 * Maps a core token (group, name) to an LPagery-compatible key. Canonical identity is never altered.
	 *
	 * @param string $token_group Canonical token group.
	 * @param string $token_name  Canonical token name.
	 * @return LPagery_Token_Mapping_Result
	 */
	public function map_core_to_lpagery( string $token_group, string $token_name ): LPagery_Token_Mapping_Result {
		$token_group = trim( $token_group );
		$token_name  = trim( $token_name );
		if ( $token_group === '' || $token_name === '' ) {
			return LPagery_Token_Mapping_Result::unsupported(
				$token_group,
				$token_name,
				__( 'Token group and name are required.', 'aio-page-builder' )
			);
		}
		if ( ! in_array( $token_group, self::ALLOWED_GROUPS, true ) ) {
			return LPagery_Token_Mapping_Result::unsupported(
				$token_group,
				$token_name,
				sprintf(
					/* translators: 1: token group */
					__( 'Token group "%1$s" is not in the supported LPagery compatibility set.', 'aio-page-builder' ),
					$token_group
				)
			);
		}
		$safe_name = $this->sanitize_token_name_for_lpagery( $token_name );
		if ( $safe_name === '' ) {
			return LPagery_Token_Mapping_Result::unsupported(
				$token_group,
				$token_name,
				__( 'Token name is invalid for LPagery key.', 'aio-page-builder' )
			);
		}
		$lpagery_key = $token_group . self::LPAGERY_KEY_SEPARATOR . $safe_name;
		return LPagery_Token_Mapping_Result::supported( $token_group, $token_name, $lpagery_key );
	}

	/**
	 * Maps an LPagery-compatible key back to canonical (group, name). Reversible when key follows group.name.
	 *
	 * @param string $lpagery_key LPagery key (e.g. color.primary).
	 * @return LPagery_Token_Mapping_Result
	 */
	public function map_lpagery_to_core( string $lpagery_key ): LPagery_Token_Mapping_Result {
		$lpagery_key = trim( $lpagery_key );
		if ( $lpagery_key === '' ) {
			return LPagery_Token_Mapping_Result::unsupported(
				'',
				'',
				__( 'LPagery key is required.', 'aio-page-builder' )
			);
		}
		$sep = self::LPAGERY_KEY_SEPARATOR;
		$pos = strpos( $lpagery_key, $sep );
		if ( $pos === false || $pos === 0 ) {
			return LPagery_Token_Mapping_Result::unsupported(
				'',
				$lpagery_key,
				__( 'LPagery key must use group.name format.', 'aio-page-builder' )
			);
		}
		$group = substr( $lpagery_key, 0, $pos );
		$name  = substr( $lpagery_key, $pos + strlen( $sep ) );
		if ( $name === '' ) {
			return LPagery_Token_Mapping_Result::unsupported(
				$group,
				'',
				__( 'LPagery key must include a token name after the group.', 'aio-page-builder' )
			);
		}
		if ( ! in_array( $group, self::ALLOWED_GROUPS, true ) ) {
			return LPagery_Token_Mapping_Result::unsupported(
				$group,
				$name,
				sprintf(
					/* translators: 1: token group */
					__( 'Token group "%1$s" from LPagery key is not in the supported set.', 'aio-page-builder' ),
					$group
				)
			);
		}
		return LPagery_Token_Mapping_Result::supported( $group, $name, $lpagery_key );
	}

	/**
	 * Returns a stable compatibility summary payload. No storage changes; metadata only.
	 *
	 * @return array{
	 *   allowed_groups: array<int, string>,
	 *   mapping_convention: string,
	 *   canonical_identity_preserved: bool,
	 *   sample_mappings: array<int, array{canonical_group: string, canonical_name: string, lpagery_key: string}>,
	 *   unsupported_warnings: array<int, string>
	 * }
	 */
	public function get_compatibility_summary(): array {
		$sample  = array();
		$samples = array(
			array( 'color', 'primary' ),
			array( 'typography', 'heading' ),
			array( 'spacing', 'medium' ),
			array( 'radius', 'default' ),
			array( 'shadow', 'card' ),
			array( 'component', 'button' ),
		);
		foreach ( $samples as $s ) {
			$result   = $this->map_core_to_lpagery( $s[0], $s[1] );
			$sample[] = array(
				'canonical_group' => $result->get_canonical_token_group(),
				'canonical_name'  => $result->get_canonical_token_name(),
				'lpagery_key'     => $result->get_lpagery_key(),
			);
		}
		return array(
			'allowed_groups'               => array_values( self::ALLOWED_GROUPS ),
			'mapping_convention'           => 'group' . self::LPAGERY_KEY_SEPARATOR . 'name',
			'canonical_identity_preserved' => true,
			'sample_mappings'              => $sample,
			'unsupported_warnings'         => array(),
		);
	}

	/**
	 * Returns the list of canonical token groups supported for LPagery mapping (read-only).
	 *
	 * @return array<int, string>
	 */
	public function get_allowed_groups(): array {
		return array_values( self::ALLOWED_GROUPS );
	}

	/**
	 * Validates an LPagery token key. Returns supported flag and reason for unsupported cases (Prompt 179).
	 *
	 * @param string $lpagery_key LPagery key (e.g. color.primary).
	 * @return array{supported: bool, reason: string}
	 */
	public function validate_token_key( string $lpagery_key ): array {
		$result = $this->map_lpagery_to_core( $lpagery_key );
		if ( $result->is_supported() ) {
			return array(
				'supported' => true,
				'reason'    => '',
			);
		}
		return array(
			'supported' => false,
			'reason'    => $result->get_warning() ?? __( 'LPagery key is not supported or invalid.', 'aio-page-builder' ),
		);
	}

	/**
	 * Sanitizes token name for use in LPagery key: alphanumeric, underscore, hyphen only; bounded length.
	 *
	 * @param string $name
	 * @return string
	 */
	private function sanitize_token_name_for_lpagery( string $name ): string {
		$name = preg_replace( '/[^a-zA-Z0-9_-]/', '', $name );
		return $name === null ? '' : substr( $name, 0, self::MAX_NAME_LENGTH );
	}
}
