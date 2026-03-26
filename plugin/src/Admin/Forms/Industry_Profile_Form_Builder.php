<?php
/**
 * Builds Industry Profile form field config and nonce for the Industry Profile Settings screen (industry-admin-screen-contract).
 * Does not perform save; screen or admin_post handler persists via Industry_Profile_Repository.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Forms;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;

/**
 * Form field definitions and options for Industry Profile (primary/secondary industry).
 */
final class Industry_Profile_Form_Builder {

	/** Nonce action for save. */
	public const NONCE_ACTION = 'aio_save_industry_profile';

	/** Nonce name in POST. */
	public const NONCE_NAME = 'aio_industry_profile_nonce';

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	public function __construct( ?Industry_Pack_Registry $pack_registry = null ) {
		$this->pack_registry = $pack_registry;
	}

	/**
	 * Returns options for primary industry dropdown: [ '' => label, key => name ].
	 * Uses active packs from registry; empty option for "None".
	 *
	 * @return array<string, string>
	 */
	public function get_primary_industry_options(): array {
		$options = array(
			'' => __( '— None —', 'aio-page-builder' ),
		);
		if ( $this->pack_registry === null ) {
			return $options;
		}
		$packs = $this->pack_registry->list_by_status( Industry_Pack_Schema::STATUS_ACTIVE );
		foreach ( $packs as $pack ) {
			$key  = (string) ( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] ?? '' );
			$name = (string) ( $pack[ Industry_Pack_Schema::FIELD_NAME ] ?? $key );
			if ( $key !== '' ) {
				$options[ $key ] = $name;
			}
		}
		$none_label = $options[''];
		unset( $options[''] );
		\uasort(
			$options,
			static function ( string $a, string $b ): int {
				return \strnatcasecmp( $a, $b );
			}
		);
		return array( '' => $none_label ) + $options;
	}

	/**
	 * Returns options for secondary industry multi-select (same as primary minus empty).
	 *
	 * @return array<string, string>
	 */
	public function get_secondary_industry_options(): array {
		$options = array();
		if ( $this->pack_registry === null ) {
			return $options;
		}
		$packs = $this->pack_registry->list_by_status( Industry_Pack_Schema::STATUS_ACTIVE );
		foreach ( $packs as $pack ) {
			$key  = (string) ( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] ?? '' );
			$name = (string) ( $pack[ Industry_Pack_Schema::FIELD_NAME ] ?? $key );
			if ( $key !== '' ) {
				$options[ $key ] = $name;
			}
		}
		\uasort(
			$options,
			static function ( string $a, string $b ): int {
				return \strnatcasecmp( $a, $b );
			}
		);
		return $options;
	}

	/**
	 * Returns form field config for the settings screen (name, type, label, options where applicable).
	 *
	 * @return array<string, array{name: string, type: string, label: string, options?: array<string, string>}>
	 */
	public function get_field_config(): array {
		return array(
			'primary_industry_key'    => array(
				'name'    => Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY,
				'type'    => 'select',
				'label'   => __( 'Primary industry', 'aio-page-builder' ),
				'options' => $this->get_primary_industry_options(),
			),
			'secondary_industry_keys' => array(
				'name'    => Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS . '[]',
				'type'    => 'multiselect',
				'label'   => __( 'Secondary industries', 'aio-page-builder' ),
				'options' => $this->get_secondary_industry_options(),
			),
		);
	}

	/**
	 * Returns nonce action for save.
	 */
	public function get_nonce_action(): string {
		return self::NONCE_ACTION;
	}

	/**
	 * Returns nonce field name for POST.
	 */
	public function get_nonce_name(): string {
		return self::NONCE_NAME;
	}
}
