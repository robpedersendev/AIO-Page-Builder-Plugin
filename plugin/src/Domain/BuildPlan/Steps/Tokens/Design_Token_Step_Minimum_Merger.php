<?php
/**
 * Ensures required design token items exist on a plan step (Prompt 244 + template styling).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\Tokens;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;

/**
 * Appends missing required (group, name) rows; skips pairs not allowed by the loaded spec.
 */
final class Design_Token_Step_Minimum_Merger {

	/** @var Style_Token_Registry */
	private $registry;

	public function __construct( Style_Token_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * @param array<int, array<string, mixed>> $items   Existing design token items.
	 * @param string                           $plan_id Plan id for stable item ids.
	 * @return array<int, array<string, mixed>>
	 */
	public function merge_required_into_items( array $items, string $plan_id ): array {
		if ( ! $this->registry->is_loaded() || $plan_id === '' ) {
			return $items;
		}
		$present = $this->present_pairs( $items );
		$next    = $this->next_req_sequence( $items, $plan_id );
		foreach ( Design_Token_Required_Set::REQUIRED_PAIRS as $pair ) {
			$group = $pair[0];
			$name  = $pair[1];
			if ( ! in_array( $name, $this->registry->get_allowed_names_for_group( $group ), true ) ) {
				continue;
			}
			$key = $group . "\0" . $name;
			if ( isset( $present[ $key ] ) ) {
				continue;
			}
			$default = Design_Token_Required_Set::default_proposed_value( $group, $name );
			$item_id = $plan_id . '_dtr_req_' . (string) $next;
			++$next;
			$items[]         = Design_Token_Plan_Item_Assembler::item(
				$item_id,
				$group,
				$name,
				$default,
				__( 'Added automatically so core templates have baseline token values.', 'aio-page-builder' ),
				-1,
				'high'
			);
			$present[ $key ] = true;
		}
		return $items;
	}

	/**
	 * @param array<int, array<string, mixed>> $items
	 * @return array<string, bool>
	 */
	private function present_pairs( array $items ): array {
		$out = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' ) !== Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN ) {
				continue;
			}
			$payload = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
				? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
				: array();
			$group   = isset( $payload['token_group'] ) && is_string( $payload['token_group'] ) ? trim( $payload['token_group'] ) : '';
			$name    = isset( $payload['token_name'] ) && is_string( $payload['token_name'] ) ? trim( $payload['token_name'] ) : '';
			if ( $group === '' || $name === '' ) {
				continue;
			}
			$out[ $group . "\0" . $name ] = true;
		}
		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $items
	 */
	private function next_req_sequence( array $items, string $plan_id ): int {
		$prefix = $plan_id . '_dtr_req_';
		$max    = -1;
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$id = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' );
			if ( ! str_starts_with( $id, $prefix ) ) {
				continue;
			}
			$suffix = substr( $id, strlen( $prefix ) );
			if ( ctype_digit( $suffix ) ) {
				$max = max( $max, (int) $suffix );
			}
		}
		return $max + 1;
	}
}
