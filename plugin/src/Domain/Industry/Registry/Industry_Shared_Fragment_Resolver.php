<?php
/**
 * Resolves shared fragments by key and consumer scope (Prompt 475, industry-shared-fragment-contract).
 * Returns content when fragment is active and consumer_scope is allowed; otherwise null. Safe failure.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Resolver for shared fragments. Enforces allowed_consumers; deterministic; no recursion.
 */
final class Industry_Shared_Fragment_Resolver {

	/** @var Industry_Shared_Fragment_Registry */
	private $registry;

	public function __construct( Industry_Shared_Fragment_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Resolves a fragment by key for the given consumer scope. Returns content string or null.
	 * Returns null when: fragment not found, status is not active, or consumer_scope not in allowed_consumers.
	 *
	 * @param string $fragment_key   Fragment key.
	 * @param string $consumer_scope One of: section_helper_overlay, page_onepager_overlay, cta_guidance, seo_guidance, compliance_caution.
	 * @return string|null Content or null on safe failure.
	 */
	public function resolve( string $fragment_key, string $consumer_scope ): ?string {
		$key   = trim( $fragment_key );
		$scope = trim( $consumer_scope );
		if ( $key === '' ) {
			return null;
		}
		$frag = $this->registry->get( $key );
		if ( $frag === null ) {
			return null;
		}
		$status = isset( $frag[ Industry_Shared_Fragment_Registry::FIELD_STATUS ] )
			? $frag[ Industry_Shared_Fragment_Registry::FIELD_STATUS ]
			: '';
		if ( $status !== Industry_Shared_Fragment_Registry::STATUS_ACTIVE ) {
			return null;
		}
		$allowed = isset( $frag[ Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS ] ) && is_array( $frag[ Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS ] )
			? $frag[ Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS ]
			: array();
		if ( ! in_array( $scope, $allowed, true ) ) {
			return null;
		}
		$content = isset( $frag[ Industry_Shared_Fragment_Registry::FIELD_CONTENT ] )
			? $frag[ Industry_Shared_Fragment_Registry::FIELD_CONTENT ]
			: '';
		return $content !== '' ? $content : null;
	}
}
