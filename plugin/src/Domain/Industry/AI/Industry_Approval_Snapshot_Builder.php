<?php
/**
 * Builds a bounded industry context snapshot at Build Plan approval/execution-request time (industry-approval-snapshot-contract.md).
 * Used for traceability and execution safeguards; no secrets; safe when profile or registries are missing.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\LPagery\Industry_LPagery_Planning_Advisor;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Application_Service;

/**
 * Builds industry_approval_snapshot payload for plan definition.
 */
final class Industry_Approval_Snapshot_Builder {

	private const KEY_PRIMARY           = 'primary_industry_key';
	private const KEY_SECONDARY         = 'secondary_industry_keys';
	private const KEY_ACTIVE_PACK_REFS  = 'active_pack_refs';
	private const KEY_OVERRIDE_SUMMARY   = 'override_refs_summary';
	private const KEY_WEIGHTED_SUMMARY   = 'weighted_resolution_summary';
	private const KEY_STYLE_PRESET_REF   = 'style_preset_ref';
	private const KEY_LPAGERY_SUMMARY    = 'lpagery_posture_summary';
	private const KEY_CAPTURED_AT        = 'captured_at';

	/** @var Industry_Profile_Repository */
	private $profile_repository;

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	/** @var Industry_Style_Preset_Application_Service|null */
	private $preset_service;

	/** @var Industry_LPagery_Planning_Advisor|null */
	private $lpagery_advisor;

	public function __construct(
		Industry_Profile_Repository $profile_repository,
		?Industry_Pack_Registry $pack_registry = null,
		?Industry_Style_Preset_Application_Service $preset_service = null,
		?Industry_LPagery_Planning_Advisor $lpagery_advisor = null
	) {
		$this->profile_repository = $profile_repository;
		$this->pack_registry      = $pack_registry;
		$this->preset_service     = $preset_service;
		$this->lpagery_advisor    = $lpagery_advisor;
	}

	/**
	 * Builds the bounded snapshot array. Safe when profile is empty or registries are null.
	 *
	 * @return array<string, mixed> Snapshot conforming to industry-approval-snapshot-contract.
	 */
	public function build(): array {
		$captured_at = gmdate( 'c' );
		$empty = array(
			self::KEY_PRIMARY          => '',
			self::KEY_SECONDARY        => array(),
			self::KEY_ACTIVE_PACK_REFS => array(),
			self::KEY_OVERRIDE_SUMMARY => null,
			self::KEY_WEIGHTED_SUMMARY => null,
			self::KEY_STYLE_PRESET_REF => null,
			self::KEY_LPAGERY_SUMMARY  => null,
			self::KEY_CAPTURED_AT      => $captured_at,
		);

		$profile = $this->profile_repository->get_profile();
		$primary = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( (string) $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		$secondary = isset( $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] ) && is_array( $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] )
			? array_values( array_filter( array_map( 'trim', array_map( 'strval', $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] ) ) ) )
			: array();

		$empty[ self::KEY_PRIMARY ]   = $primary;
		$empty[ self::KEY_SECONDARY ]  = $secondary;
		$empty[ self::KEY_WEIGHTED_SUMMARY ] = $this->weighted_summary( $primary, $secondary );

		$active_pack_refs = array_filter( array_merge( array( $primary ), $secondary ) );
		if ( $this->pack_registry !== null && $primary !== '' ) {
			$refs = array();
			foreach ( array_merge( array( $primary ), $secondary ) as $key ) {
				if ( $key === '' ) {
					continue;
				}
				$pack = $this->pack_registry->get( $key );
				if ( $pack !== null && ( (string) ( $pack[ Industry_Pack_Schema::FIELD_STATUS ] ?? '' ) === 'active' ) ) {
					$refs[] = $key;
				}
			}
			$active_pack_refs = array_values( $refs );
		}
		$empty[ self::KEY_ACTIVE_PACK_REFS ] = array_values( $active_pack_refs );

		if ( $this->preset_service !== null ) {
			$applied = $this->preset_service->get_applied_preset();
			$empty[ self::KEY_STYLE_PRESET_REF ] = ( $applied !== null && isset( $applied['preset_key'] ) && is_string( $applied['preset_key'] ) )
				? trim( $applied['preset_key'] )
				: null;
		}

		if ( $this->lpagery_advisor !== null && $primary !== '' ) {
			$result = $this->lpagery_advisor->advise_from_profile( $profile );
			$empty[ self::KEY_LPAGERY_SUMMARY ] = $result->get_lpagery_posture() !== '' ? $result->get_lpagery_posture() : null;
		}

		return $empty;
	}

	private function weighted_summary( string $primary, array $secondary ): string {
		if ( $primary === '' ) {
			return 'none';
		}
		if ( empty( $secondary ) ) {
			return 'primary_only';
		}
		return 'primary_secondary';
	}
}
