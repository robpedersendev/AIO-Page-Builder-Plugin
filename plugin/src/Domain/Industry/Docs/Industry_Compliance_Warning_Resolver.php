<?php
/**
 * Resolves active compliance/caution rules for an industry for display in docs, previews, and Build Plan (Prompt 407).
 * Advisory only; no legal advice. Safe fallback when registry empty or industry unknown.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Compliance_Rule_Registry;

/**
 * Returns active compliance rules for a given industry as minimal display items (rule_key, severity, caution_summary).
 */
final class Industry_Compliance_Warning_Resolver {

	/** @var Industry_Compliance_Rule_Registry */
	private Industry_Compliance_Rule_Registry $registry;

	public function __construct( Industry_Compliance_Rule_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Returns active rules for the industry as display-safe items. Empty when industry empty or no active rules.
	 *
	 * @param string $industry_key Industry pack key.
	 * @return list<array{rule_key: string, severity: string, caution_summary: string}>
	 */
	public function get_for_display( string $industry_key ): array {
		$industry_key = trim( $industry_key );
		if ( $industry_key === '' ) {
			return array();
		}
		$rules = $this->registry->get_for_industry( $industry_key );
		$out   = array();
		foreach ( $rules as $rule ) {
			$status = isset( $rule[ Industry_Compliance_Rule_Registry::FIELD_STATUS ] ) && is_string( $rule[ Industry_Compliance_Rule_Registry::FIELD_STATUS ] )
				? trim( $rule[ Industry_Compliance_Rule_Registry::FIELD_STATUS ] )
				: '';
			if ( $status !== Industry_Compliance_Rule_Registry::STATUS_ACTIVE ) {
				continue;
			}
			$rule_key        = (string) ( $rule[ Industry_Compliance_Rule_Registry::FIELD_RULE_KEY ] ?? '' );
			$severity        = (string) ( $rule[ Industry_Compliance_Rule_Registry::FIELD_SEVERITY ] ?? 'info' );
			$caution_summary = (string) ( $rule[ Industry_Compliance_Rule_Registry::FIELD_CAUTION_SUMMARY ] ?? '' );
			if ( $rule_key !== '' && $caution_summary !== '' ) {
				$out[] = array(
					'rule_key'        => $rule_key,
					'severity'        => $severity,
					'caution_summary' => $caution_summary,
				);
			}
		}
		return $out;
	}
}
