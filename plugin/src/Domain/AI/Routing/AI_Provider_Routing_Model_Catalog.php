<?php
/**
 * Builds model lists for AI routing admin UI from driver capabilities plus {@see AI_Provider_Routing_Model_Guide}.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Routing;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Providers\AI_Provider_Interface;

/**
 * Produces JSON-safe entries for wp_localize_script (id, good_for, not_ideal_for).
 */
final class AI_Provider_Routing_Model_Catalog {

	/**
	 * @param list<string>                              $provider_ids Allowed provider slugs in stable order.
	 * @param array<string, AI_Provider_Interface|null> $drivers        Optional driver per id when registered.
	 * @return array<string, list<array{id: string, good_for: string, not_ideal_for: string}>>
	 */
	public static function build( array $provider_ids, array $drivers ): array {
		$out = array();
		foreach ( $provider_ids as $pid ) {
			$pid = \sanitize_key( (string) $pid );
			if ( $pid === '' ) {
				continue;
			}
			$driver = $drivers[ $pid ] ?? null;
			if ( ! $driver instanceof AI_Provider_Interface ) {
				$out[ $pid ] = array();
				continue;
			}
			$caps   = $driver->get_capabilities();
			$models = isset( $caps['models'] ) && is_array( $caps['models'] ) ? $caps['models'] : array();
			$guide  = self::guide_map_for_provider( $pid );
			$list   = array();
			foreach ( $models as $row ) {
				if ( ! is_array( $row ) || ! isset( $row['id'] ) || ! is_string( $row['id'] ) ) {
					continue;
				}
				$mid = trim( $row['id'] );
				if ( $mid === '' ) {
					continue;
				}
				$g      = $guide[ $mid ] ?? AI_Provider_Routing_Model_Guide::generic_unknown_model_copy();
				$list[] = array(
					'id'            => $mid,
					'good_for'      => $g['good_for'],
					'not_ideal_for' => $g['not_ideal_for'],
				);
			}
			$out[ $pid ] = $list;
		}
		return $out;
	}

	/**
	 * @return array<string, array{good_for: string, not_ideal_for: string}>
	 */
	private static function guide_map_for_provider( string $provider_id ): array {
		if ( $provider_id === 'openai' ) {
			return AI_Provider_Routing_Model_Guide::openai_by_model();
		}
		if ( $provider_id === 'anthropic' ) {
			return AI_Provider_Routing_Model_Guide::anthropic_by_model();
		}
		return array();
	}
}
