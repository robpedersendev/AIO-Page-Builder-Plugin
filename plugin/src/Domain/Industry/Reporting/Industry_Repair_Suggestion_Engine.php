<?php
/**
 * Advisory repair suggestions for missing or invalid industry refs (Prompt 443, industry-repair-suggestion-contract).
 * Suggests deprecated replacement, inactive activation, or valid alternatives. No auto-repair; no mutation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;

/**
 * Returns at most one repair suggestion per issue. Bounded; no suggestion when ambiguity is high.
 */
final class Industry_Repair_Suggestion_Engine {

	public const SUGGESTION_TYPE_DEPRECATED_REPLACEMENT = 'deprecated_replacement';
	public const SUGGESTION_TYPE_INACTIVE_ACTIVATE       = 'inactive_activate';
	public const SUGGESTION_TYPE_VALID_ALTERNATIVE       = 'valid_alternative';
	public const SUGGESTION_TYPE_FALLBACK_BUNDLE         = 'fallback_bundle';

	public const CONFIDENCE_HIGH   = 'high';
	public const CONFIDENCE_MEDIUM = 'medium';
	public const CONFIDENCE_LOW    = 'low';

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	/** @var Industry_Starter_Bundle_Registry|null */
	private $starter_bundle_registry;

	/** @var Industry_Subtype_Registry|null */
	private $subtype_registry;

	/** @var Industry_Profile_Repository|null */
	private $profile_repo;

	/** @var object|null Optional; must have is_pack_active(string): bool. */
	private $pack_toggle;

	public function __construct(
		?Industry_Pack_Registry $pack_registry = null,
		?Industry_Starter_Bundle_Registry $starter_bundle_registry = null,
		?Industry_Subtype_Registry $subtype_registry = null,
		?Industry_Profile_Repository $profile_repo = null,
		?object $pack_toggle = null
	) {
		$this->pack_registry           = $pack_registry;
		$this->starter_bundle_registry = $starter_bundle_registry;
		$this->subtype_registry        = $subtype_registry;
		$this->profile_repo            = $profile_repo;
		$this->pack_toggle             = $pack_toggle;
	}

	/**
	 * Suggests a repair for a single health/conflict issue. Returns one suggestion or null when no good suggestion.
	 *
	 * @param array{object_type: string, key: string, severity: string, issue_summary: string, related_refs: list<string>} $issue
	 * @return array{broken_ref: string, suggested_ref: string, suggestion_type: string, confidence_summary: string, explanation: string}|null
	 */
	public function suggest_for_issue( array $issue ): ?array {
		$object_type = isset( $issue['object_type'] ) && \is_string( $issue['object_type'] ) ? \trim( $issue['object_type'] ) : '';
		$key         = isset( $issue['key'] ) && \is_string( $issue['key'] ) ? \trim( $issue['key'] ) : '';
		$summary     = isset( $issue['issue_summary'] ) && \is_string( $issue['issue_summary'] ) ? \trim( $issue['issue_summary'] ) : '';
		$related     = isset( $issue['related_refs'] ) && \is_array( $issue['related_refs'] ) ? array_values( array_filter( array_map( 'strval', $issue['related_refs'] ) ) ) : array();
		$broken_ref  = $related[0] ?? $key;

		// Deprecated/superseded pack or bundle with replacement_ref.
		$deprecated = $this->suggest_deprecated_replacement( $object_type, $broken_ref, $key );
		if ( $deprecated !== null ) {
			return $deprecated;
		}

		// Pack present but inactive.
		if ( $object_type === Industry_Health_Check_Service::OBJECT_TYPE_PACK && $key !== '' && $this->pack_toggle !== null && \method_exists( $this->pack_toggle, 'is_pack_active' ) ) {
			$pack = $this->pack_registry !== null ? $this->pack_registry->get( $key ) : null;
			if ( $pack !== null && ! $this->pack_toggle->is_pack_active( $key ) ) {
				return array(
					'broken_ref'         => $key,
					'suggested_ref'      => $key,
					'suggestion_type'    => self::SUGGESTION_TYPE_INACTIVE_ACTIVATE,
					'confidence_summary' => self::CONFIDENCE_HIGH,
					'explanation'        => __( 'Pack is present but inactive. Enable the pack in Industry Profile or pack management.', 'aio-page-builder' ),
				);
			}
		}

		// Pack starter_bundle_ref does not resolve: suggest first active bundle for that industry.
		if ( $object_type === Industry_Health_Check_Service::OBJECT_TYPE_PACK && $key !== '' && $this->starter_bundle_registry !== null && \strpos( $summary, 'starter_bundle_ref' ) !== false && $broken_ref !== '' ) {
			$bundles = $this->starter_bundle_registry->get_for_industry( $key, '' );
			foreach ( $bundles as $bundle ) {
				$bundle_key = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ) && \is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] )
					? \trim( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] )
					: '';
				$status = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] ) && \is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] )
					? $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ]
					: '';
				if ( $bundle_key !== '' && $status === Industry_Starter_Bundle_Registry::STATUS_ACTIVE ) {
					return array(
						'broken_ref'         => $broken_ref,
						'suggested_ref'      => $bundle_key,
						'suggestion_type'    => self::SUGGESTION_TYPE_VALID_ALTERNATIVE,
						'confidence_summary' => self::CONFIDENCE_MEDIUM,
						'explanation'        => __( 'Pack starter_bundle_ref does not resolve. An active bundle for this industry is available.', 'aio-page-builder' ),
					);
				}
			}
		}

		// Profile selected_starter_bundle_key not found: suggest first bundle for primary industry.
		if ( $object_type === Industry_Health_Check_Service::OBJECT_TYPE_PROFILE && $this->profile_repo !== null && $this->starter_bundle_registry !== null && \strpos( $summary, 'starter' ) !== false ) {
			$profile = $this->profile_repo->get_profile();
			$primary = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && \is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
				? \trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
				: '';
			if ( $primary !== '' ) {
				$bundles = $this->starter_bundle_registry->get_for_industry( $primary, '' );
				foreach ( $bundles as $bundle ) {
					$bundle_key = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ) && \is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] )
						? \trim( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] )
						: '';
					$status = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] ) && \is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] )
						? $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ]
						: '';
					if ( $bundle_key !== '' && $status === Industry_Starter_Bundle_Registry::STATUS_ACTIVE ) {
						return array(
							'broken_ref'         => $broken_ref !== '' ? $broken_ref : $key,
							'suggested_ref'      => $bundle_key,
							'suggestion_type'    => self::SUGGESTION_TYPE_FALLBACK_BUNDLE,
							'confidence_summary' => self::CONFIDENCE_MEDIUM,
							'explanation'        => __( 'Selected starter bundle not found. An active bundle for your primary industry is available.', 'aio-page-builder' ),
						);
					}
				}
			}
		}

		return null;
	}

	/**
	 * @return array{broken_ref: string, suggested_ref: string, suggestion_type: string, confidence_summary: string, explanation: string}|null
	 */
	private function suggest_deprecated_replacement( string $object_type, string $broken_ref, string $context_key ): ?array {
		if ( $broken_ref === '' ) {
			return null;
		}
		$replacement = '';
		$explanation = '';

		if ( $this->pack_registry !== null ) {
			$pack = $this->pack_registry->get( $broken_ref );
			if ( $pack !== null ) {
				$rep = isset( $pack[ Industry_Pack_Schema::FIELD_REPLACEMENT_REF ] ) && \is_string( $pack[ Industry_Pack_Schema::FIELD_REPLACEMENT_REF ] )
					? \trim( $pack[ Industry_Pack_Schema::FIELD_REPLACEMENT_REF ] )
					: '';
				if ( $rep !== '' && $this->pack_registry->get( $rep ) !== null ) {
					$replacement = $rep;
					$explanation = __( 'Pack is deprecated or superseded; use the replacement pack.', 'aio-page-builder' );
				}
			}
		}
		if ( $replacement === '' && $this->starter_bundle_registry !== null ) {
			$bundle = $this->starter_bundle_registry->get( $broken_ref );
			if ( $bundle !== null ) {
				$rep = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_REPLACEMENT_REF ] ) && \is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_REPLACEMENT_REF ] )
					? \trim( $bundle[ Industry_Starter_Bundle_Registry::FIELD_REPLACEMENT_REF ] )
					: '';
				if ( $rep !== '' && $this->starter_bundle_registry->get( $rep ) !== null ) {
					$replacement = $rep;
					$explanation = __( 'Starter bundle is deprecated or superseded; use the replacement bundle.', 'aio-page-builder' );
				}
			}
		}

		if ( $replacement === '' ) {
			return null;
		}
		return array(
			'broken_ref'         => $broken_ref,
			'suggested_ref'      => $replacement,
			'suggestion_type'    => self::SUGGESTION_TYPE_DEPRECATED_REPLACEMENT,
			'confidence_summary' => self::CONFIDENCE_HIGH,
			'explanation'        => $explanation,
		);
	}
}
