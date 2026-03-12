<?php
/**
 * Design token-set apply execution (spec §35, §40.2, §41.7; Prompt 083).
 *
 * Applies approved token value changes. Alters values only; does not change
 * selector names or structural markup. Preserves previous value for revert/rollback.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Jobs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;

/**
 * Applies one design-token recommendation (token_group, token_name, proposed_value).
 */
final class Token_Set_Job_Service implements Token_Set_Job_Service_Interface {

	/** Option key for applied token values: [ group => [ name => value ] ]. */
	public const OPTION_APPLIED_TOKENS = 'aio_applied_design_tokens';

	/** Allowed token groups (spec §35, Build_Plan_Draft_Schema::DTR_ENUM_GROUP). */
	private const ALLOWED_GROUPS = array( 'color', 'typography', 'spacing', 'radius', 'shadow', 'component' );

	/**
	 * Runs the token apply flow. Validates group/name; stores value; returns previous for rollback.
	 *
	 * @param array<string, mixed> $envelope Validated action envelope (target_reference with token_group, token_name, proposed_value).
	 * @return Token_Set_Result
	 */
	public function run( array $envelope ): Token_Set_Result {
		$target = isset( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] ) && is_array( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ]
			: array();

		$token_group = isset( $target['token_group'] ) && is_string( $target['token_group'] ) ? trim( $target['token_group'] ) : '';
		$token_name  = isset( $target['token_name'] ) && is_string( $target['token_name'] ) ? trim( $target['token_name'] ) : '';
		$proposed    = $target['proposed_value'] ?? null;
		$snapshot_ref = isset( $envelope['snapshot_ref'] ) && is_string( $envelope['snapshot_ref'] ) ? trim( $envelope['snapshot_ref'] ) : '';

		if ( $token_group === '' || $token_name === '' ) {
			return Token_Set_Result::failure(
				__( 'Missing token group or name.', 'aio-page-builder' ),
				array( Execution_Action_Contract::ERROR_INVALID_ENVELOPE )
			);
		}
		if ( ! in_array( $token_group, self::ALLOWED_GROUPS, true ) ) {
			return Token_Set_Result::failure(
				__( 'Invalid token group.', 'aio-page-builder' ),
				array( 'invalid_token_group' )
			);
		}
		if ( $proposed === null ) {
			return Token_Set_Result::failure(
				__( 'Missing proposed token value.', 'aio-page-builder' ),
				array( 'invalid_target' )
			);
		}

		$store = get_option( self::OPTION_APPLIED_TOKENS, array() );
		if ( ! is_array( $store ) ) {
			$store = array();
		}
		if ( ! isset( $store[ $token_group ] ) || ! is_array( $store[ $token_group ] ) ) {
			$store[ $token_group ] = array();
		}
		$previous = $store[ $token_group ][ $token_name ] ?? null;
		$store[ $token_group ][ $token_name ] = $proposed;
		$updated = update_option( self::OPTION_APPLIED_TOKENS, $store );
		if ( ! $updated ) {
			return Token_Set_Result::failure(
				__( 'Failed to persist token value.', 'aio-page-builder' ),
				array( 'storage_failed' )
			);
		}
		return Token_Set_Result::success( $token_group, $token_name, $proposed, $previous, $snapshot_ref );
	}
}
