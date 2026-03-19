<?php
/**
 * Applies an uploaded industry bundle after preview/conflict review.
 * Persists bundle registry records, payload copies, conflict records, and merge state.
 *
 * @package AIOPageBuilder
 */
declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Import;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Bundle_Service;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

final class Industry_Bundle_Apply_Service {

	public const SCOPE_SETTINGS_ONLY     = 'settings_only';
	public const SCOPE_FULL_SITE_PACKAGE = 'full_site_package';

	private const STATUS_APPLIED = 'applied';
	private const STATUS_FAILED  = 'failed';

	private Settings_Service $settings;
	private Industry_Bundle_Conflict_Scanner $scanner;

	public function __construct( Settings_Service $settings, ?Industry_Bundle_Conflict_Scanner $scanner = null ) {
		$this->settings = $settings;
		$this->scanner  = $scanner ?? new Industry_Bundle_Conflict_Scanner();
	}

	/**
	 * @param array<string, mixed> $bundle Valid bundle.
	 * @param string              $bundle_slug Slug-like label for registry record.
	 * @param string              $scope One of SCOPE_*.
	 * @param array<string, string> $decisions Map of "category|object_key" => "replace"|"skip".
	 * @param int                 $user_id Current user ID.
	 * @return array{ok: bool, bundle_id: string, error: string}
	 */
	public function apply( array $bundle, string $bundle_slug, string $scope, array $decisions, int $user_id ): array {
		$bundle_slug = sanitize_key( $bundle_slug );
		$scope       = $scope === self::SCOPE_SETTINGS_ONLY ? self::SCOPE_SETTINGS_ONLY : self::SCOPE_FULL_SITE_PACKAGE;
		$user_id     = $user_id > 0 ? $user_id : 0;

		$bundle_service = new Industry_Pack_Bundle_Service();
		$errors         = $bundle_service->validate_bundle( $bundle );
		if ( $errors !== array() ) {
			return array( 'ok' => false, 'bundle_id' => '', 'error' => 'Invalid bundle.' );
		}

		$allowed_categories = $bundle[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ] ?? array();
		if ( ! is_array( $allowed_categories ) ) {
			return array( 'ok' => false, 'bundle_id' => '', 'error' => 'Invalid included_categories.' );
		}

		$bundle_id = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : (string) ( uniqid( 'aio_pb_bundle_', true ) );
		$bundle_id = sanitize_text_field( $bundle_id );

		$source_hash = $this->bundle_source_hash( $bundle );

		$effective_local_hashes = $this->get_effective_local_hashes();
		$conflicts              = $this->scanner->scan( $bundle, $effective_local_hashes );
		if ( $conflicts !== array() && ! $this->has_explicit_decisions_for_conflicts( $conflicts, $decisions ) ) {
			return array( 'ok' => false, 'bundle_id' => '', 'error' => 'Conflicts require explicit decisions.' );
		}

		$payload_to_store = $this->build_payload_for_scope( $bundle, $scope, $decisions, $effective_local_hashes );

		// Dynamic payload/conflict option keys are not part of Settings_Service allowlist.
		update_option( $this->payload_option_key( $bundle_id ), $payload_to_store );
		update_option( $this->conflicts_option_key( $bundle_id ), array( 'conflicts' => $conflicts ) );

		$registry = $this->settings->get( Option_Names::PB_INDUSTRY_BUNDLE_REGISTRY );
		$registry = is_array( $registry ) ? $registry : array();

		$record = array(
			'bundle_id'      => $bundle_id,
			'bundle_slug'    => $bundle_slug !== '' ? $bundle_slug : 'uploaded-bundle',
			'bundle_version' => (string) ( $bundle[ Industry_Pack_Bundle_Service::MANIFEST_BUNDLE_VERSION ] ?? '' ),
			'source_hash'    => $source_hash,
			'scope'          => $scope,
			'applied_at'     => gmdate( 'c' ),
			'applied_by'     => $user_id,
			'status'         => self::STATUS_APPLIED,
		);
		$registry[] = $record;
		$this->settings->set( Option_Names::PB_INDUSTRY_BUNDLE_REGISTRY, $registry );

		$merge_state = $this->settings->get( Option_Names::PB_INDUSTRY_BUNDLE_MERGE_STATE );
		$merge_state = is_array( $merge_state ) ? $merge_state : array();
		$merge_state['apply_order'] = isset( $merge_state['apply_order'] ) && is_array( $merge_state['apply_order'] ) ? $merge_state['apply_order'] : array();
		$merge_state['apply_order'][] = $bundle_id;
		$merge_state['apply_order'] = array_values( array_unique( array_map( 'strval', $merge_state['apply_order'] ) ) );
		$merge_state['updated_at']  = gmdate( 'c' );
		$merge_state['updated_by']  = $user_id;
		$this->settings->set( Option_Names::PB_INDUSTRY_BUNDLE_MERGE_STATE, $merge_state );

		return array( 'ok' => true, 'bundle_id' => $bundle_id, 'error' => '' );
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	public function get_effective_local_hashes(): array {
		$merge_state = $this->settings->get( Option_Names::PB_INDUSTRY_BUNDLE_MERGE_STATE );
		$apply_order = isset( $merge_state['apply_order'] ) && is_array( $merge_state['apply_order'] ) ? $merge_state['apply_order'] : array();

		$hashes = array();

		// Builtins: at minimum include industry packs to prevent silent overwrite of builtin keys.
		$hashes = $this->index_builtin_pack_hashes( $hashes );

		// Applied overlays in apply order.
		foreach ( $apply_order as $bundle_id ) {
			$bundle_id = is_string( $bundle_id ) ? $bundle_id : '';
			if ( $bundle_id === '' ) {
				continue;
			}
			$payload = get_option( $this->payload_option_key( $bundle_id ), array() );
			if ( ! is_array( $payload ) || $payload === array() ) {
				continue;
			}
			$hashes = $this->index_hashes_from_bundle( $payload, $hashes );
		}

		return $hashes;
	}

	/**
	 * @param array<string, mixed> $bundle
	 * @param array<string, array<string, string>> $hashes
	 * @return array<string, array<string, string>>
	 */
	private function index_hashes_from_bundle( array $bundle, array $hashes ): array {
		$included = $bundle[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ] ?? array();
		if ( ! is_array( $included ) ) {
			return $hashes;
		}
		$scanner = new Industry_Bundle_Conflict_Scanner();
		foreach ( $included as $category ) {
			if ( ! is_string( $category ) || $category === '' ) {
				continue;
			}
			if ( ! isset( $bundle[ $category ] ) || ! is_array( $bundle[ $category ] ) ) {
				continue;
			}
			if ( ! isset( $hashes[ $category ] ) || ! is_array( $hashes[ $category ] ) ) {
				$hashes[ $category ] = array();
			}
			foreach ( $bundle[ $category ] as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$object_key = $scanner->object_key_for_category( $category, $item );
				if ( $object_key === '' ) {
					continue;
				}
				$hashes[ $category ][ $object_key ] = $scanner->content_hash( $item );
			}
		}
		return $hashes;
	}

	/**
	 * @param array<string, array<string, string>> $hashes
	 * @return array<string, array<string, string>>
	 */
	private function index_builtin_pack_hashes( array $hashes ): array {
		$scanner = new Industry_Bundle_Conflict_Scanner();
		if ( ! isset( $hashes[ Industry_Pack_Bundle_Service::PAYLOAD_PACKS ] ) || ! is_array( $hashes[ Industry_Pack_Bundle_Service::PAYLOAD_PACKS ] ) ) {
			$hashes[ Industry_Pack_Bundle_Service::PAYLOAD_PACKS ] = array();
		}
		$builtin_packs = \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry::get_builtin_pack_definitions();
		foreach ( $builtin_packs as $pack ) {
			if ( ! is_array( $pack ) ) {
				continue;
			}
			$key = $scanner->object_key_for_category( Industry_Pack_Bundle_Service::PAYLOAD_PACKS, $pack );
			if ( $key === '' ) {
				continue;
			}
			$hashes[ Industry_Pack_Bundle_Service::PAYLOAD_PACKS ][ $key ] = $scanner->content_hash( $pack );
		}
		return $hashes;
	}

	/**
	 * @param list<array{category: string, object_key: string}> $conflicts
	 * @param array<string, string> $decisions
	 */
	private function has_explicit_decisions_for_conflicts( array $conflicts, array $decisions ): bool {
		foreach ( $conflicts as $c ) {
			$k = (string) $c['category'] . '|' . (string) $c['object_key'];
			if ( ! isset( $decisions[ $k ] ) ) {
				return false;
			}
			$v = (string) $decisions[ $k ];
			if ( $v !== 'replace' && $v !== 'skip' ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param array<string, mixed> $bundle
	 * @param string $scope
	 * @param array<string, string> $decisions
	 * @param array<string, array<string, string>> $effective_local_hashes
	 * @return array<string, mixed>
	 */
	private function build_payload_for_scope( array $bundle, string $scope, array $decisions, array $effective_local_hashes ): array {
		$out = $bundle;
		$included = isset( $bundle[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ] ) && is_array( $bundle[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ] )
			? $bundle[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ]
			: array();

		if ( $scope === self::SCOPE_SETTINGS_ONLY ) {
			$out[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ] = array( Industry_Pack_Bundle_Service::PAYLOAD_SITE_PROFILE );
			foreach ( $included as $cat ) {
				if ( is_string( $cat ) && $cat !== Industry_Pack_Bundle_Service::PAYLOAD_SITE_PROFILE ) {
					unset( $out[ $cat ] );
				}
			}
			return $out;
		}

		// Full package: apply decision filters (skip items explicitly skipped, and skip no-op duplicates by hash).
		$scanner = new Industry_Bundle_Conflict_Scanner();
		foreach ( $included as $category ) {
			if ( ! is_string( $category ) || ! isset( $out[ $category ] ) || ! is_array( $out[ $category ] ) ) {
				continue;
			}
			$new_items = array();
			foreach ( $out[ $category ] as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$object_key = $scanner->object_key_for_category( $category, $item );
				if ( $object_key === '' ) {
					continue;
				}

				$incoming_hash = $scanner->content_hash( $item );

				$local_hash = isset( $effective_local_hashes[ $category ][ $object_key ] ) ? (string) $effective_local_hashes[ $category ][ $object_key ] : '';
				if ( $local_hash !== '' && hash_equals( $local_hash, $incoming_hash ) ) {
					continue;
				}

				$decision_key = $category . '|' . $object_key;
				$decision     = $decisions[ $decision_key ] ?? 'replace';
				if ( $decision === 'skip' ) {
					continue;
				}
				$new_items[] = $item;
			}
			$out[ $category ] = $new_items;
		}

		return $out;
	}

	/**
	 * @param array<string, mixed> $bundle
	 */
	private function bundle_source_hash( array $bundle ): string {
		$json = wp_json_encode( $bundle );
		$json = is_string( $json ) ? $json : '';
		return hash( 'sha256', $json );
	}

	private function payload_option_key( string $bundle_id ): string {
		return 'aio_pb_industry_bundle_payload_' . sanitize_key( $bundle_id );
	}

	private function conflicts_option_key( string $bundle_id ): string {
		return 'aio_pb_industry_bundle_conflicts_' . sanitize_key( $bundle_id );
	}
}

