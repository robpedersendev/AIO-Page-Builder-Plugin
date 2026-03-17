<?php
/**
 * Resolves active compliance/caution rules for an industry (and optional subtype) for display in docs, previews, and Build Plan (Prompt 407, 447).
 * Advisory only; no legal advice. Safe fallback when registry empty or industry unknown. Composes parent + subtype rules when subtype context provided.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Compliance_Rule_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Subtype_Compliance_Rule_Registry;

/**
 * Returns active compliance rules for a given industry (and optional subtype) as minimal display items (rule_key, severity, caution_summary).
 */
final class Industry_Compliance_Warning_Resolver {

	/** @var Industry_Compliance_Rule_Registry */
	private Industry_Compliance_Rule_Registry $registry;

	/** @var Subtype_Compliance_Rule_Registry|null When set, get_for_display can merge subtype rules for (industry, subtype). */
	private ?Subtype_Compliance_Rule_Registry $subtype_registry;

	public function __construct(
		Industry_Compliance_Rule_Registry $registry,
		?Subtype_Compliance_Rule_Registry $subtype_registry = null
	) {
		$this->registry         = $registry;
		$this->subtype_registry = $subtype_registry;
	}

	/**
	 * Returns active rules for the industry (and optional subtype) as display-safe items. Empty when industry empty or no active rules.
	 * When subtype_key is non-empty and subtype registry is set, merges parent rules with subtype rules (subtype-compliance-rule-contract).
	 *
	 * @param string $industry_key Industry pack key.
	 * @param string $subtype_key  Optional subtype key. When empty or subtype registry not set, returns parent rules only.
	 * @return list<array{rule_key: string, severity: string, caution_summary: string}>
	 */
	public function get_for_display( string $industry_key, string $subtype_key = '' ): array {
		$industry_key = trim( $industry_key );
		if ( $industry_key === '' ) {
			return array();
		}
		$out = array();
		$parent_rules = $this->registry->get_for_industry( $industry_key );
		$refined_keys = array();
		foreach ( $parent_rules as $rule ) {
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
		$subtype_key = trim( $subtype_key );
		if ( $subtype_key !== '' && $this->subtype_registry !== null ) {
			$subtype_rules = $this->subtype_registry->get_for_subtype( $industry_key, $subtype_key );
			foreach ( $subtype_rules as $rule ) {
				$status = isset( $rule[ Subtype_Compliance_Rule_Registry::FIELD_STATUS ] ) && is_string( $rule[ Subtype_Compliance_Rule_Registry::FIELD_STATUS ] )
					? trim( $rule[ Subtype_Compliance_Rule_Registry::FIELD_STATUS ] )
					: '';
				if ( $status !== Subtype_Compliance_Rule_Registry::STATUS_ACTIVE ) {
					continue;
				}
				$subtype_rule_key = (string) ( $rule[ Subtype_Compliance_Rule_Registry::FIELD_SUBTYPE_RULE_KEY ] ?? '' );
				$severity         = (string) ( $rule[ Subtype_Compliance_Rule_Registry::FIELD_SEVERITY ] ?? 'info' );
				$caution_summary  = (string) ( $rule[ Subtype_Compliance_Rule_Registry::FIELD_CAUTION_SUMMARY ] ?? '' );
				if ( $subtype_rule_key !== '' && $caution_summary !== '' ) {
					$out[] = array(
						'rule_key'        => $subtype_rule_key,
						'severity'        => $severity,
						'caution_summary' => $caution_summary,
					);
				}
			}
		}
		return $out;
	}
}
