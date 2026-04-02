<?php
/**
 * Manual add/update and minimum-token repair for design token step items.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\Tokens;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Styling\Styles_JSON_Sanitizer;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;

/**
 * Persists mutations via {@see Build_Plan_Repository::save_plan_definition}; caller verifies capability and nonce.
 */
final class Design_Token_Plan_Item_Editor_Service {

	public const STEP_INDEX_DESIGN_TOKENS = 5;

	/** @var Build_Plan_Repository */
	private $repository;

	/** @var Design_Token_Catalog_Service */
	private $catalog;

	/** @var Styles_JSON_Sanitizer */
	private $sanitizer;

	/** @var Design_Token_Step_Minimum_Merger */
	private $minimum_merger;

	public function __construct(
		Build_Plan_Repository $repository,
		Design_Token_Catalog_Service $catalog,
		Styles_JSON_Sanitizer $sanitizer,
		Design_Token_Step_Minimum_Merger $minimum_merger
	) {
		$this->repository     = $repository;
		$this->catalog        = $catalog;
		$this->sanitizer      = $sanitizer;
		$this->minimum_merger = $minimum_merger;
	}

	/**
	 * @return array{ok: bool, code: string}
	 */
	public function add_item( int $plan_post_id, string $plan_id, string $group, string $name, string $proposed_raw, string $rationale_raw ): array {
		$group = trim( $group );
		$name  = trim( $name );
		if ( ! $this->catalog->is_allowed_pair( $group, $name ) ) {
			return array(
				'ok'   => false,
				'code' => 'invalid_pair',
			);
		}
		$san = $this->sanitize_proposed( $group, $name, $proposed_raw );
		if ( ! $san['ok'] ) {
			return array(
				'ok'   => false,
				'code' => 'invalid_value',
			);
		}
		$rationale = $this->sanitize_rationale( $rationale_raw );
		$def       = $this->repository->get_plan_definition( $plan_post_id );
		if ( $def === array() ) {
			return array(
				'ok'   => false,
				'code' => 'no_plan',
			);
		}
		$items = $this->extract_token_items( $def );
		if ( $items === null ) {
			return array(
				'ok'   => false,
				'code' => 'no_step',
			);
		}
		if ( $this->pair_exists_in_items( $items, $group, $name ) ) {
			return array(
				'ok'   => false,
				'code' => 'duplicate',
			);
		}
		$item_id = $this->next_manual_item_id( $items, $plan_id );
		$items[] = Design_Token_Plan_Item_Assembler::item( $item_id, $group, $name, $san['value'], $rationale, -1, 'medium' );
		$this->inject_token_items( $def, $items );
		return $this->repository->save_plan_definition( $plan_post_id, $def )
			? array(
				'ok'   => true,
				'code' => 'saved',
			)
			: array(
				'ok'   => false,
				'code' => 'persist_failed',
			);
	}

	/**
	 * @return array{ok: bool, code: string}
	 */
	public function update_item(
		int $plan_post_id,
		string $item_id,
		string $group,
		string $name,
		string $proposed_raw,
		string $rationale_raw
	): array {
		$item_id = trim( $item_id );
		$group   = trim( $group );
		$name    = trim( $name );
		if ( $item_id === '' || ! $this->catalog->is_allowed_pair( $group, $name ) ) {
			return array(
				'ok'   => false,
				'code' => 'invalid_pair',
			);
		}
		$san = $this->sanitize_proposed( $group, $name, $proposed_raw );
		if ( ! $san['ok'] ) {
			return array(
				'ok'   => false,
				'code' => 'invalid_value',
			);
		}
		$rationale = $this->sanitize_rationale( $rationale_raw );
		$def       = $this->repository->get_plan_definition( $plan_post_id );
		if ( $def === array() ) {
			return array(
				'ok'   => false,
				'code' => 'no_plan',
			);
		}
		$items = $this->extract_token_items( $def );
		if ( $items === null ) {
			return array(
				'ok'   => false,
				'code' => 'no_step',
			);
		}
		$found = false;
		foreach ( $items as $i => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' ) !== $item_id ) {
				continue;
			}
			if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' ) !== Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN ) {
				return array(
					'ok'   => false,
					'code' => 'wrong_type',
				);
			}
			if ( $this->pair_exists_in_items( $items, $group, $name, $item_id ) ) {
				return array(
					'ok'   => false,
					'code' => 'duplicate',
				);
			}
			$payload                                     = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
				? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
				: array();
			$payload['token_group']                      = $group;
			$payload['token_name']                       = $name;
			$payload['proposed_value']                   = $san['value'];
			$payload['rationale']                        = $rationale;
			$item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] = $payload;
			$item[ Build_Plan_Item_Schema::KEY_STATUS ]  = Build_Plan_Item_Statuses::PENDING;
			if ( isset( $payload['confidence'] ) && is_string( $payload['confidence'] ) ) {
				$item[ Build_Plan_Item_Schema::KEY_CONFIDENCE ] = $payload['confidence'];
			} else {
				unset( $item[ Build_Plan_Item_Schema::KEY_CONFIDENCE ] );
			}
			$items[ $i ] = $item;
			$found       = true;
			break;
		}
		if ( ! $found ) {
			return array(
				'ok'   => false,
				'code' => 'not_found',
			);
		}
		$this->inject_token_items( $def, $items );
		return $this->repository->save_plan_definition( $plan_post_id, $def )
			? array(
				'ok'   => true,
				'code' => 'saved',
			)
			: array(
				'ok'   => false,
				'code' => 'persist_failed',
			);
	}

	/**
	 * @return array{ok: bool, code: string, added: int}
	 */
	public function ensure_minimum_items( int $plan_post_id, string $plan_id ): array {
		$def = $this->repository->get_plan_definition( $plan_post_id );
		if ( $def === array() ) {
			return array(
				'ok'    => false,
				'code'  => 'no_plan',
				'added' => 0,
			);
		}
		$items = $this->extract_token_items( $def );
		if ( $items === null ) {
			return array(
				'ok'    => false,
				'code'  => 'no_step',
				'added' => 0,
			);
		}
		$before = count( $items );
		$merged = $this->minimum_merger->merge_required_into_items( $items, $plan_id );
		$added  = count( $merged ) - $before;
		$this->inject_token_items( $def, $merged );
		if ( $added === 0 ) {
			return array(
				'ok'    => true,
				'code'  => 'none_needed',
				'added' => 0,
			);
		}
		return $this->repository->save_plan_definition( $plan_post_id, $def )
			? array(
				'ok'    => true,
				'code'  => 'saved',
				'added' => $added,
			)
			: array(
				'ok'    => false,
				'code'  => 'persist_failed',
				'added' => 0,
			);
	}

	/**
	 * @param array<string, mixed> $definition
	 * @return array<int, array<string, mixed>>|null
	 */
	private function extract_token_items( array $definition ): ?array {
		if ( ! isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) || ! is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] ) ) {
			return null;
		}
		$step = $definition[ Build_Plan_Schema::KEY_STEPS ][ self::STEP_INDEX_DESIGN_TOKENS ] ?? null;
		if ( ! is_array( $step ) ) {
			return null;
		}
		$items = $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ?? array();
		return is_array( $items ) ? $items : array();
	}

	/**
	 * @param array<string, mixed>             $definition
	 * @param array<int, array<string, mixed>> $items
	 */
	private function inject_token_items( array &$definition, array $items ): void {
		if ( ! isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) || ! is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] ) ) {
			return;
		}
		if ( ! isset( $definition[ Build_Plan_Schema::KEY_STEPS ][ self::STEP_INDEX_DESIGN_TOKENS ] ) || ! is_array( $definition[ Build_Plan_Schema::KEY_STEPS ][ self::STEP_INDEX_DESIGN_TOKENS ] ) ) {
			return;
		}
		$definition[ Build_Plan_Schema::KEY_STEPS ][ self::STEP_INDEX_DESIGN_TOKENS ][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items;
	}

	/**
	 * @param array<int, array<string, mixed>> $items
	 */
	private function pair_exists_in_items( array $items, string $group, string $name, ?string $except_item_id = null ): bool {
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' ) !== Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN ) {
				continue;
			}
			$id = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' );
			if ( $except_item_id !== null && $id === $except_item_id ) {
				continue;
			}
			$payload = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
				? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
				: array();
			$g       = isset( $payload['token_group'] ) && is_string( $payload['token_group'] ) ? trim( $payload['token_group'] ) : '';
			$n       = isset( $payload['token_name'] ) && is_string( $payload['token_name'] ) ? trim( $payload['token_name'] ) : '';
			if ( $g === $group && $n === $name ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param array<int, array<string, mixed>> $items
	 */
	private function next_manual_item_id( array $items, string $plan_id ): string {
		$prefix = $plan_id . '_dtr_m_';
		$max    = 0;
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
		return $prefix . (string) ( $max + 1 );
	}

	/**
	 * @return array{ok: bool, value: string}
	 */
	private function sanitize_proposed( string $group, string $name, string $raw ): array {
		$raw = trim( $raw );
		$res = $this->sanitizer->sanitize_global_tokens(
			array(
				$group => array( $name => $raw ),
			)
		);
		if ( ! $res->is_valid() ) {
			return array(
				'ok'    => false,
				'value' => '',
			);
		}
		$data = $res->get_sanitized();
		if ( ! is_array( $data ) || ! isset( $data[ $group ][ $name ] ) || ! is_string( $data[ $group ][ $name ] ) ) {
			return array(
				'ok'    => false,
				'value' => '',
			);
		}
		return array(
			'ok'    => true,
			'value' => $data[ $group ][ $name ],
		);
	}

	private function sanitize_rationale( string $raw ): string {
		$t = trim( \wp_kses_post( $raw ) );
		if ( strlen( $t ) > 2000 ) {
			return substr( $t, 0, 2000 );
		}
		return $t;
	}
}
